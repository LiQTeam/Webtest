<?php
declare( strict_types=1 );

namespace ClickPop\Core\Providers;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Support\Encryption;

defined( 'ABSPATH' ) || exit;

/**
 * پایهٔ درایور سرویس‌دهنده: HTTP، تایم‌اوت، تلاش مجدد کنترل‌شده، مدارشکن و لاگ.
 */
abstract class AbstractProvider {

	protected const TIMEOUT          = 15;
	protected const FAILURE_LIMIT    = 5;
	protected const CIRCUIT_COOLDOWN = 300;

	public function __construct( protected readonly object $row ) {}

	public function id(): int {
		return (int) $this->row->id;
	}

	public function rateUnit(): int {
		return max( 1, (int) $this->row->rate_unit );
	}

	/** ضریب تبدیل واحد سرویس‌دهنده به ریال. */
	public function currencyMultiplier(): int {
		return 'IRT' === strtoupper( (string) $this->row->currency ) ? 10 : 1;
	}

	protected function apiKey(): string {
		return Encryption::decrypt( (string) $this->row->api_key_enc );
	}

	public function circuitOpen(): bool {
		$until = $this->row->circuit_open_until;

		return is_string( $until ) && '' !== $until && strtotime( $until . ' UTC' ) > time();
	}

	/**
	 * فراخوان خام API.
	 *
	 * @param array<string,scalar> $body
	 * @return array{ok:bool,data:mixed,error:string,code:int}
	 */
	protected function call( string $action, array $body = [], bool $retry_on_network = true ): array {
		if ( $this->circuitOpen() ) {
			return [
				'ok'    => false,
				'data'  => null,
				'code'  => 0,
				'error' => __( 'ارتباط با سرویس‌دهنده موقتاً قطع است. چند دقیقهٔ دیگر تلاش کنید.', 'clickpop-core' ),
			];
		}

		$payload = array_merge(
			[
				'key'    => $this->apiKey(),
				'action' => $action,
			],
			$body
		);

		$started  = microtime( true );
		$response = wp_remote_post(
			(string) $this->row->api_url,
			[
				'timeout'     => self::TIMEOUT,
				'redirection' => 0,
				'sslverify'   => true,
				'user-agent'  => 'ClickPop/' . CLICKPOP_VERSION . '; ' . home_url( '/' ),
				'body'        => $payload,
			]
		);
		$latency = (int) round( ( microtime( true ) - $started ) * 1000 );

		if ( is_wp_error( $response ) ) {
			// خطای شبکه: یک بار و فقط یک بار تلاش دوباره.
			if ( $retry_on_network ) {
				return $this->call( $action, $body, false );
			}

			$this->registerFailure();
			$this->log( $action, null, $latency, false, $response->get_error_message() );

			return [
				'ok'    => false,
				'data'  => null,
				'code'  => 0,
				'error' => __( 'ارتباط با سرویس‌دهنده برقرار نشد.', 'clickpop-core' ),
			];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 || null === $data ) {
			$this->registerFailure();
			$this->log( $action, $code, $latency, false, mb_substr( $raw, 0, 400 ) );

			return [
				'ok'    => false,
				'data'  => null,
				'code'  => $code,
				'error' => __( 'پاسخ سرویس‌دهنده معتبر نبود.', 'clickpop-core' ),
			];
		}

		$this->resetFailures( $latency );
		$this->log( $action, $code, $latency, true, null );

		return [
			'ok'    => true,
			'data'  => $data,
			'code'  => $code,
			'error' => '',
		];
	}

	private function registerFailure(): void {
		global $wpdb;

		$table = Installer::table( 'providers' );
		$count = (int) $this->row->failure_count + 1;

		$data = [
			'failure_count' => $count,
			'updated_at'    => current_time( 'mysql', true ),
		];

		if ( $count >= self::FAILURE_LIMIT ) {
			$data['circuit_open_until'] = gmdate( 'Y-m-d H:i:s', time() + self::CIRCUIT_COOLDOWN );
			$data['failure_count']      = 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( $table, $data, [ 'id' => $this->id() ] );
	}

	private function resetFailures( int $latency ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			Installer::table( 'providers' ),
			[
				'failure_count'      => 0,
				'circuit_open_until' => null,
				'latency_ms'         => $latency,
				'updated_at'         => current_time( 'mysql', true ),
			],
			[ 'id' => $this->id() ]
		);
	}

	/** لاگ بدون کلید API و بدون لینک کاربر. */
	private function log( string $action, ?int $code, int $latency, bool $ok, ?string $error ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			Installer::table( 'api_log' ),
			[
				'provider_id' => $this->id(),
				'action'      => $action,
				'http_code'   => $code,
				'latency_ms'  => $latency,
				'ok'          => $ok ? 1 : 0,
				'error'       => $error,
				'created_at'  => current_time( 'mysql', true ),
			]
		);
	}

	/** @return array{ok:bool,data:mixed,error:string,code:int} */
	abstract public function services(): array;

	/** @return array{ok:bool,data:mixed,error:string,code:int} */
	abstract public function addOrder( string $remote_service_id, string $link, int $quantity ): array;

	/**
	 * @param string[] $remote_ids
	 * @return array{ok:bool,data:mixed,error:string,code:int}
	 */
	abstract public function status( array $remote_ids ): array;

	/** @return array{ok:bool,data:mixed,error:string,code:int} */
	abstract public function balance(): array;
}
