<?php
declare( strict_types=1 );

namespace ClickPop\Core\Gateways;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Repositories\WalletRepository;
use ClickPop\Core\Support\Money;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * شارژ کیف پول.
 *
 * قواعد ضدخطا:
 *  - مبلغ از سرور می‌آید و پیش از ریدایرکت در دیتابیس ثبت می‌شود.
 *  - قید UNIQUE(gateway, authority) دوباره‌شارژی را غیرممکن می‌کند.
 *  - تأیید همیشه server-to-server است؛ به پارامترهای GET اعتماد نمی‌شود.
 *  - گذار وضعیت با UPDATE شرطی انجام می‌شود ⇒ callback تکراری بی‌اثر است.
 */
final class PaymentController {

	public const QUERY_VAR = 'clickpop_pay';

	public static function register(): void {
		add_action( 'init', [ self::class, 'addRewrite' ] );
		add_filter( 'query_vars', [ self::class, 'queryVars' ] );
		add_action( 'template_redirect', [ self::class, 'maybeHandleCallback' ] );
	}

	public static function addRewrite(): void {
		add_rewrite_rule( '^clickpop-payment/([a-z0-9_-]+)/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
	}

	/** @param string[] $vars */
	public static function queryVars( array $vars ): array {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	public static function callbackUrl( string $gateway ): string {
		return home_url( '/clickpop-payment/' . rawurlencode( $gateway ) . '/' );
	}

	/**
	 * شروع پرداخت.
	 *
	 * @return array{redirect:string}|WP_Error
	 */
	public static function start( int $user_id, int $amount_rials, string $gateway_slug ): array|WP_Error {
		$min = (int) get_option( 'clickpop_topup_min', 100000 );   // ۱۰٬۰۰۰ تومان
		$max = (int) get_option( 'clickpop_topup_max', 500000000 ); // ۵۰٬۰۰۰٬۰۰۰ تومان

		if ( $amount_rials < $min || $amount_rials > $max ) {
			return new WP_Error(
				'cp_amount_out_of_range',
				sprintf(
					/* translators: 1: minimum amount, 2: maximum amount */
					__( 'مبلغ شارژ باید بین %1$s و %2$s باشد.', 'clickpop-core' ),
					Money::fromRials( $min )->format(),
					Money::fromRials( $max )->format()
				),
				[ 'status' => 422 ]
			);
		}

		$gateway = GatewayManager::get( $gateway_slug );

		if ( ! $gateway || ! isset( GatewayManager::enabled()[ $gateway_slug ] ) ) {
			return new WP_Error( 'cp_gateway_unavailable', __( 'این درگاه در دسترس نیست.', 'clickpop-core' ), [ 'status' => 409 ] );
		}

		$result = $gateway->request(
			$amount_rials,
			self::callbackUrl( $gateway_slug ),
			sprintf(
				/* translators: %s: site name */
				__( 'شارژ کیف پول %s', 'clickpop-core' ),
				get_bloginfo( 'name' )
			)
		);

		if ( ! $result['ok'] ) {
			return new WP_Error( 'cp_gateway_error', $result['error'], [ 'status' => 502 ] );
		}

		$repo = new WalletRepository();
		$repo->ensureRow( $user_id );

		$txn_id = $repo->ledger(
			[
				'user_id'       => $user_id,
				'type'          => 'deposit',
				'direction'     => 'credit',
				'amount'        => $amount_rials,
				'balance_after' => $repo->balance( $user_id ),
				'status'        => 'initiated',
				'ref_type'      => 'gateway',
				'gateway'       => $gateway_slug,
				'authority'     => $result['authority'],
			]
		);

		if ( $txn_id <= 0 ) {
			return new WP_Error( 'cp_db_error', __( 'ثبت تراکنش ممکن نشد.', 'clickpop-core' ), [ 'status' => 500 ] );
		}

		return [ 'redirect' => $result['redirect'] ];
	}

	public static function maybeHandleCallback(): void {
		$slug = get_query_var( self::QUERY_VAR );

		if ( ! is_string( $slug ) || '' === $slug ) {
			return;
		}

		$gateway = GatewayManager::get( $slug );

		if ( ! $gateway ) {
			wp_safe_redirect( self::dashboardUrl( 'gateway_unknown' ) );
			exit;
		}

		$authority = $gateway->authorityFromCallback();

		if ( '' === $authority ) {
			wp_safe_redirect( self::dashboardUrl( 'canceled' ) );
			exit;
		}

		global $wpdb;
		$table = Installer::table( 'transactions' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$txn = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE gateway = %s AND authority = %s",
				$slug,
				$authority
			)
		);

		if ( ! $txn ) {
			wp_safe_redirect( self::dashboardUrl( 'not_found' ) );
			exit;
		}

		if ( 'succeeded' === $txn->status ) {
			// callback تکراری — بدون شارژ دوباره.
			wp_safe_redirect( self::dashboardUrl( 'already' ) );
			exit;
		}

		// قفل گذار: فقط یک اجرا می‌تواند از initiated خارج شود.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$locked = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'pending' WHERE id = %d AND status = 'initiated'",
				(int) $txn->id
			)
		);

		if ( ! is_int( $locked ) || $locked < 1 ) {
			wp_safe_redirect( self::dashboardUrl( 'already' ) );
			exit;
		}

		// مبلغ از دیتابیس، نه از پارامترهای بازگشتی.
		$verify = $gateway->verify( $authority, (int) $txn->amount );

		if ( ! $verify['ok'] ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update(
				$table,
				[
					'status' => 'failed',
					'reason' => mb_substr( $verify['error'], 0, 255 ),
				],
				[ 'id' => (int) $txn->id ]
			);

			wp_safe_redirect( self::dashboardUrl( 'failed' ) );
			exit;
		}

		$repo = new WalletRepository();
		$repo->credit( (int) $txn->user_id, (int) $txn->amount );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			$table,
			[
				'status'        => 'succeeded',
				'gateway_ref'   => mb_substr( $verify['ref'], 0, 128 ),
				'balance_after' => $repo->balance( (int) $txn->user_id ),
			],
			[ 'id' => (int) $txn->id ]
		);

		do_action( 'clickpop/wallet/topup_succeeded', (int) $txn->user_id, (int) $txn->amount, (int) $txn->id );

		wp_safe_redirect( self::dashboardUrl( 'success' ) );
		exit;
	}

	private static function dashboardUrl( string $status ): string {
		$page = (int) get_option( 'clickpop_dashboard_page_id', 0 );
		$base = $page > 0 ? (string) get_permalink( $page ) : home_url( '/' );

		return add_query_arg(
			[
				'cp_tab' => 'wallet',
				'cp_pay' => $status,
			],
			$base
		);
	}
}
