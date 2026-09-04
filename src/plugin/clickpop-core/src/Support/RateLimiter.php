<?php
declare( strict_types=1 );

namespace ClickPop\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * محدودکنندهٔ نرخ با پنجرهٔ ثابت روی object cache (Redis در صورت وجود) یا transient.
 */
final class RateLimiter {

	private const GROUP = 'clickpop_rl';

	/**
	 * @param string $bucket   نام سطل، مثلاً 'order'.
	 * @param int    $limit    حداکثر تعداد در پنجره.
	 * @param int    $window   طول پنجره به ثانیه.
	 * @param string $identity شناسهٔ اضافی (کاربر/IP).
	 */
	public static function hit( string $bucket, int $limit, int $window, string $identity = '' ): bool {
		$key = self::key( $bucket, $identity, $window );

		$count = wp_cache_get( $key, self::GROUP );
		if ( false === $count ) {
			$count = (int) get_transient( self::GROUP . '_' . $key );
		}
		$count = (int) $count;

		if ( $count >= $limit ) {
			return false;
		}

		++$count;
		wp_cache_set( $key, $count, self::GROUP, $window );
		set_transient( self::GROUP . '_' . $key, $count, $window );

		return true;
	}

	private static function key( string $bucket, string $identity, int $window ): string {
		if ( '' === $identity ) {
			$identity = (string) get_current_user_id() . '|' . self::ip();
		}
		$slot = (int) floor( time() / max( 1, $window ) );

		return $bucket . '_' . md5( $identity ) . '_' . $slot;
	}

	public static function ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}

	/**
	 * IP برای ذخیره در ستون VARCHAR(45).
	 *
	 * عمداً به‌صورت متن ذخیره می‌شود، نه inet_pton: خروجی باینری حاوی بایت صفر است
	 * و در مسیر escape رشته‌ای wpdb می‌تواند بریده شود.
	 */
	public static function ipForStorage(): ?string {
		$ip = self::ip();

		return '0.0.0.0' === $ip ? null : $ip;
	}
}
