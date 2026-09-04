<?php
/**
 * توابع کمکی قالب.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * لوگو به‌صورت SVG درون‌خطی — یک درخواست شبکه کمتر روی مسیر LCP.
 * اگر مدیر لوگوی سفارشی گذاشته باشد، همان استفاده می‌شود.
 */
function clickpop_logo(): void {
	if ( has_custom_logo() ) {
		the_custom_logo();

		return;
	}

	$path = CLICKPOP_THEME_DIR . '/assets/brand/clickpop-logo-horizontal.svg';

	printf( '<a class="cp-logo" href="%s" rel="home" aria-label="%s">', esc_url( home_url( '/' ) ), esc_attr( get_bloginfo( 'name' ) ) );

	if ( is_readable( $path ) ) {
		$svg = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- فایل محلی تم، نه منبع خارجی.
		echo wp_kses( $svg, clickpop_svg_allowed_tags() );
	} else {
		echo esc_html( get_bloginfo( 'name' ) );
	}

	echo '</a>';
}

/**
 * فهرست تگ‌های مجاز SVG برای wp_kses.
 *
 * @return array<string,array<string,bool>>
 */
function clickpop_svg_allowed_tags(): array {
	$attrs = [
		'xmlns'             => true,
		'xmlns:xlink'       => true,
		'viewbox'           => true,
		'width'             => true,
		'height'            => true,
		'fill'              => true,
		'stroke'            => true,
		'stroke-width'      => true,
		'stroke-linecap'    => true,
		'stroke-linejoin'   => true,
		'd'                 => true,
		'transform'         => true,
		'cx'                => true,
		'cy'                => true,
		'r'                 => true,
		'x1'                => true,
		'y1'                => true,
		'x2'                => true,
		'y2'                => true,
		'offset'            => true,
		'stop-color'        => true,
		'id'                => true,
		'class'             => true,
		'role'              => true,
		'aria-label'        => true,
		'aria-hidden'       => true,
		'focusable'         => true,
	];

	return [
		'svg'            => $attrs,
		'g'              => $attrs,
		'path'           => $attrs,
		'circle'         => $attrs,
		'rect'           => $attrs,
		'title'          => $attrs,
		'defs'           => $attrs,
		'lineargradient' => $attrs,
		'stop'           => $attrs,
	];
}

/**
 * نشان وضعیت با آیکن — رنگ به‌تنهایی برای کاربران کوررنگ کافی نیست.
 */
function clickpop_status_badge( string $label, string $tone ): string {
	return sprintf(
		'<span class="cp-pill cp-pill--%1$s"><i aria-hidden="true"></i>%2$s</span>',
		esc_attr( $tone ),
		esc_html( $label )
	);
}

/**
 * لینک «پرش به محتوا» برای کاربران صفحه‌کلید و صفحه‌خوان.
 */
function clickpop_skip_link(): void {
	printf(
		'<a class="cp-skip" href="#cp-main">%s</a>',
		esc_html__( 'پرش به محتوای اصلی', 'clickpop' )
	);
}
