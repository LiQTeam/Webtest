<?php
declare( strict_types=1 );

namespace ClickPop\Core\Gateways;

defined( 'ABSPATH' ) || exit;

/**
 * درگاه زرین‌پال (REST v4). مبالغ به ریال ارسال می‌شوند.
 */
final class ZarinPalGateway extends AbstractGateway {

	public function slug(): string {
		return 'zarinpal';
	}

	public function label(): string {
		return __( 'زرین‌پال', 'clickpop-core' );
	}

	private function sandbox(): bool {
		return (bool) get_option( 'clickpop_zarinpal_sandbox', false );
	}

	private function base(): string {
		return $this->sandbox()
			? 'https://sandbox.zarinpal.com/pg/'
			: 'https://payment.zarinpal.com/pg/';
	}

	private function merchant(): string {
		return trim( (string) get_option( 'clickpop_zarinpal_merchant', '' ) );
	}

	public function request( int $amount_rials, string $callback_url, string $description ): array {
		if ( '' === $this->merchant() ) {
			return [
				'ok'        => false,
				'authority' => '',
				'redirect'  => '',
				'error'     => __( 'کد پذیرندهٔ زرین‌پال تنظیم نشده است.', 'clickpop-core' ),
			];
		}

		$response = $this->post(
			$this->base() . 'v4/payment/request.json',
			[
				'merchant_id'  => $this->merchant(),
				'amount'       => $amount_rials,
				'currency'     => 'IRR',
				'callback_url' => $callback_url,
				'description'  => mb_substr( $description, 0, 200 ),
			]
		);

		$code      = (int) ( $response['data']['data']['code'] ?? 0 );
		$authority = (string) ( $response['data']['data']['authority'] ?? '' );

		if ( ! $response['ok'] || 100 !== $code || '' === $authority ) {
			return [
				'ok'        => false,
				'authority' => '',
				'redirect'  => '',
				'error'     => $this->errorText( $response ),
			];
		}

		return [
			'ok'        => true,
			'authority' => $authority,
			'redirect'  => $this->base() . 'StartPay/' . rawurlencode( $authority ),
			'error'     => '',
		];
	}

	public function verify( string $authority, int $amount_rials ): array {
		$response = $this->post(
			$this->base() . 'v4/payment/verify.json',
			[
				'merchant_id' => $this->merchant(),
				'amount'      => $amount_rials,
				'authority'   => $authority,
			]
		);

		$code = (int) ( $response['data']['data']['code'] ?? 0 );

		// ۱۰۰ = تأیید موفق، ۱۰۱ = قبلاً تأیید شده (idempotent).
		if ( $response['ok'] && in_array( $code, [ 100, 101 ], true ) ) {
			return [
				'ok'    => true,
				'ref'   => (string) ( $response['data']['data']['ref_id'] ?? '' ),
				'error' => '',
			];
		}

		return [
			'ok'    => false,
			'ref'   => '',
			'error' => $this->errorText( $response ),
		];
	}

	public function authorityFromCallback(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- بازگشت از درگاه، نانس ندارد؛ اعتبار با تأیید سمت سرور بررسی می‌شود.
		$raw = isset( $_GET['Authority'] ) ? sanitize_text_field( wp_unslash( $_GET['Authority'] ) ) : '';

		return mb_substr( $raw, 0, 128 );
	}

	private function errorText( array $response ): string {
		$message = $response['data']['errors']['message'] ?? '';

		if ( is_string( $message ) && '' !== $message ) {
			return sanitize_text_field( $message );
		}

		return $response['error'] ?: __( 'پرداخت تأیید نشد.', 'clickpop-core' );
	}
}
