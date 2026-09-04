<?php
declare( strict_types=1 );

namespace ClickPop\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * اعتبارسنجی ورودی‌های حساس.
 */
final class Validator {

	/**
	 * فهرست دامنه‌های مجاز هر برند.
	 *
	 * تطبیق دقیق روی هاست انجام می‌شود؛ جست‌وجوی زیررشته‌ای با
	 * https://evil.com/?x=instagram.com دور زده می‌شود.
	 *
	 * @return array<string,string[]>
	 */
	public static function hostMap(): array {
		return (array) apply_filters(
			'clickpop/validator/host_map',
			[
				'instagram' => [ 'instagram.com', 'www.instagram.com' ],
				'youtube'   => [ 'youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be' ],
				'telegram'  => [ 't.me', 'telegram.me', 'www.t.me' ],
				'tiktok'    => [ 'tiktok.com', 'www.tiktok.com', 'vm.tiktok.com' ],
				'twitter'   => [ 'twitter.com', 'www.twitter.com', 'x.com', 'www.x.com' ],
				'spotify'   => [ 'open.spotify.com' ],
				'soundcloud'=> [ 'soundcloud.com', 'www.soundcloud.com', 'm.soundcloud.com' ],
			]
		);
	}

	/**
	 * اعتبارسنجی لینک هدف سفارش.
	 *
	 * @return true|string true در صورت اعتبار، وگرنه پیام خطا.
	 */
	public static function link( string $link, string $brand_slug ): bool|string {
		$link = trim( $link );

		if ( '' === $link || strlen( $link ) > 500 ) {
			return __( 'لینک وارد نشده یا بیش از حد طولانی است.', 'clickpop-core' );
		}

		$parts = wp_parse_url( $link );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
			return __( 'ساختار لینک معتبر نیست.', 'clickpop-core' );
		}

		if ( ! isset( $parts['scheme'] ) || 'https' !== strtolower( $parts['scheme'] ) ) {
			return __( 'لینک باید با https شروع شود.', 'clickpop-core' );
		}

		// اعتبارنامه داخل URL نشانهٔ لینک دستکاری‌شده است.
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return __( 'لینک نباید حاوی نام کاربری یا رمز باشد.', 'clickpop-core' );
		}

		$host = strtolower( $parts['host'] );

		// آدرس IP یا میزبان محلی — جلوگیری از SSRF در سرویس‌های پایین‌دستی.
		if ( filter_var( $host, FILTER_VALIDATE_IP ) || in_array( $host, [ 'localhost', '127.0.0.1', '::1' ], true ) ) {
			return __( 'لینک باید به یکی از شبکه‌های اجتماعی پشتیبانی‌شده اشاره کند.', 'clickpop-core' );
		}

		$map = self::hostMap();
		if ( ! isset( $map[ $brand_slug ] ) ) {
			// برند ناشناخته: فقط قواعد عمومی بالا اعمال می‌شود.
			return true;
		}

		if ( ! in_array( $host, $map[ $brand_slug ], true ) ) {
			return sprintf(
				/* translators: %s: comma separated allowed domains */
				__( 'دامنهٔ لینک با سرویس انتخابی هم‌خوان نیست. دامنه‌های مجاز: %s', 'clickpop-core' ),
				implode( '، ', $map[ $brand_slug ] )
			);
		}

		return true;
	}

	public static function isUuidV4( string $value ): bool {
		return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value );
	}

	/** نرمال‌سازی متن فارسی: یکسان‌سازی ی/ك و حذف فاصله‌های اضافی. */
	public static function normalizeFa( string $text ): string {
		$text = str_replace( [ "\u{064A}", "\u{0643}", "\u{200C}" ], [ "\u{06CC}", "\u{06A9}", ' ' ], $text );
		return trim( preg_replace( '/\s+/u', ' ', $text ) ?? $text );
	}
}
