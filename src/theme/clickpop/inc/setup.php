<?php
/**
 * پیکربندی پایهٔ تم.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

add_action(
	'after_setup_theme',
	static function (): void {
		load_theme_textdomain( 'clickpop', CLICKPOP_THEME_DIR . '/languages' );

		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'custom-logo', [
			'height'      => 61,
			'width'       => 220,
			'flex-height' => true,
			'flex-width'  => true,
		] );
		add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );

		register_nav_menus(
			[
				'primary' => __( 'منوی اصلی', 'clickpop' ),
				'footer'  => __( 'منوی فوتر', 'clickpop' ),
				'legal'   => __( 'منوی قوانین', 'clickpop' ),
			]
		);

		// عرض محتوا برای ادیتور بلوک و قالب‌های تم.
		if ( ! isset( $GLOBALS['content_width'] ) ) {
			$GLOBALS['content_width'] = 1180;
		}
	}
);

add_action(
	'widgets_init',
	static function (): void {
		register_sidebar(
			[
				'name'          => __( 'ستون فوتر', 'clickpop' ),
				'id'            => 'footer-1',
				'before_widget' => '<div class="cp-fwidget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h4 class="cp-fwidget__t">',
				'after_title'   => '</h4>',
			]
		);
	}
);

/**
 * جهت سند بر اساس locale تعیین می‌شود، نه هاردکد.
 * با تعویض زبان در Polylang/WPML، dir خودکار درست می‌شود.
 */
add_filter(
	'language_attributes',
	static function ( string $output ): string {
		if ( is_rtl() && ! str_contains( $output, 'dir=' ) ) {
			$output .= ' dir="rtl"';
		}

		return $output;
	}
);
