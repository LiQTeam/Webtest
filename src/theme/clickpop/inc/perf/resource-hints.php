<?php
/**
 * راهنمای منابع: فقط فونت اصلی preload می‌شود.
 *
 * preload زیاد ضد خودش عمل می‌کند و پهنای باند مسیر بحرانی را می‌خورد.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	static function (): void {
		$font = CLICKPOP_THEME_DIR . '/assets/fonts/Vazirmatn-Variable.woff2';

		if ( ! is_readable( $font ) ) {
			return;
		}

		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( CLICKPOP_THEME_URI . '/assets/fonts/Vazirmatn-Variable.woff2' )
		);
	},
	2
);

/**
 * حذف resource hint های بی‌مصرف پیش‌فرض وردپرس (s.w.org).
 */
add_filter(
	'wp_resource_hints',
	static function ( array $urls, string $relation ): array {
		if ( 'dns-prefetch' !== $relation ) {
			return $urls;
		}

		return array_values(
			array_filter(
				$urls,
				static fn( $url ): bool => ! ( is_string( $url ) && str_contains( $url, 's.w.org' ) )
			)
		);
	},
	10,
	2
);
