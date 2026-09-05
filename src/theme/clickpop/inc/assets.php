<?php
/**
 * بارگذاری asset با بودجهٔ سخت.
 *
 * قواعد:
 *  - بدون CDN؛ فونت و اسکریپت همگی self-hosted.
 *  - CSS داشبورد فقط در صفحات داشبورد.
 *  - نسخهٔ فایل از filemtime تا کش مرورگر هرگز کهنه نماند.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * نسخهٔ فایل بر اساس زمان تغییر — برای کش «immutable».
 */
function clickpop_asset_version( string $relative ): string {
	$path = CLICKPOP_THEME_DIR . '/' . ltrim( $relative, '/' );

	return is_readable( $path ) ? (string) filemtime( $path ) : CLICKPOP_THEME_VERSION;
}

function clickpop_asset_uri( string $relative ): string {
	return CLICKPOP_THEME_URI . '/' . ltrim( $relative, '/' );
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'clickpop-main',
			clickpop_asset_uri( 'assets/css/main.css' ),
			[],
			clickpop_asset_version( 'assets/css/main.css' )
		);

		wp_enqueue_script(
			'clickpop-main',
			clickpop_asset_uri( 'assets/js/main.js' ),
			[],
			clickpop_asset_version( 'assets/js/main.js' ),
			true
		);

		wp_script_add_data( 'clickpop-main', 'strategy', 'defer' );

		// این تم اصلاً jQuery نمی‌خواهد؛ فقط لایهٔ سازگاری قدیمی حذف می‌شود
		// تا افزونه‌های ثالثی که به خود jQuery وابسته‌اند نشکنند.
		if ( ! is_admin() && ! is_customize_preview() ) {
			wp_dequeue_script( 'jquery-migrate' );
		}
	},
	20
);

/**
 * حذف استایل‌های بلوکی که در تم کلاسیک استفاده نمی‌شوند.
 * ~۷KB gzip روی هر صفحه صرفه‌جویی می‌کند.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		if ( ! has_blocks() ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'global-styles' );
			wp_dequeue_style( 'classic-theme-styles' );
		}
	},
	100
);

/**
 * صفحهٔ داشبورد نباید در کش عمومی بیفتد.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( ! is_user_logged_in() ) {
			return;
		}

		global $post;

		if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'clickpop_dashboard' ) ) {
			nocache_headers();
		}
	}
);
