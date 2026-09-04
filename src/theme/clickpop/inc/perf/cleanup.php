<?php
/**
 * پاک‌سازی خروجی وردپرس.
 *
 * هر مورد اینجا یک درخواست شبکه یا چند کیلوبایت HTML/JS حذف می‌کند.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

// اسکریپت ایموجی: ~۱۵KB جاوااسکریپت که هیچ مرورگر مدرنی به آن نیاز ندارد.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

add_filter( 'emoji_svg_url', '__return_false' );

// نشت اطلاعات نسخه.
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

// oEmbed discovery — روی سایت خدماتی بی‌مصرف است.
remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
remove_action( 'wp_head', 'wp_oembed_add_host_js' );

add_action(
	'wp_footer',
	static function (): void {
		wp_dequeue_script( 'wp-embed' );
	},
	100
);

// XML-RPC و pingback: سطح حملهٔ بی‌فایده روی این پروژه.
add_filter( 'xmlrpc_enabled', '__return_false' );

add_filter(
	'wp_headers',
	static function ( array $headers ): array {
		unset( $headers['X-Pingback'] );

		return $headers;
	}
);

/**
 * شمارش کاربران را از REST عمومی می‌بندد (جلوگیری از user enumeration).
 */
add_filter(
	'rest_endpoints',
	static function ( array $endpoints ): array {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}

		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );

		return $endpoints;
	}
);

/**
 * هدرهای امنیتی. CSP عمداً اینجا نیست:
 * باید ابتدا در حالت Report-Only روی وب‌سرور مستقر شود (مستندات docs/04-SECURITY.md).
 */
add_action(
	'send_headers',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );

		if ( is_ssl() ) {
			header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
		}
	}
);
