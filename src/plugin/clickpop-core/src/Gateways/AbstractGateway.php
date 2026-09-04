<?php
declare( strict_types=1 );

namespace ClickPop\Core\Gateways;

defined( 'ABSPATH' ) || exit;

abstract class AbstractGateway {

	abstract public function slug(): string;

	abstract public function label(): string;

	/**
	 * شروع پرداخت.
	 *
	 * @param int $amount_rials مبلغ به ریال — همیشه از سرور، هرگز از ورودی کاربر.
	 * @return array{ok:bool,authority:string,redirect:string,error:string}
	 */
	abstract public function request( int $amount_rials, string $callback_url, string $description ): array;

	/**
	 * تأیید سمت سرور. مبلغ از دیتابیس می‌آید، نه از پارامترهای بازگشتی.
	 *
	 * @return array{ok:bool,ref:string,error:string}
	 */
	abstract public function verify( string $authority, int $amount_rials ): array;

	/** استخراج authority از پارامترهای بازگشت درگاه. */
	abstract public function authorityFromCallback(): string;

	protected function post( string $url, array $body ): array {
		$response = wp_remote_post(
			$url,
			[
				'timeout'     => 20,
				'redirection' => 0,
				'sslverify'   => true,
				'headers'     => [
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				],
				'body'        => wp_json_encode( $body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'ok'    => false,
				'data'  => null,
				'error' => $response->get_error_message(),
			];
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		return [
			'ok'    => is_array( $data ),
			'data'  => $data,
			'error' => is_array( $data ) ? '' : __( 'پاسخ درگاه معتبر نبود.', 'clickpop-core' ),
		];
	}
}
