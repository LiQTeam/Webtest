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

/**
 * خلاصهٔ برندها از افزونه. اگر افزونه نباشد، آرایهٔ خالی — صفحه نمی‌شکند.
 *
 * @return array<int,array<string,mixed>>
 */
function clickpop_brand_summary(): array {
	if ( ! class_exists( \ClickPop\Core\Api\Facade::class ) ) {
		return [];
	}

	return \ClickPop\Core\Api\Facade::brandSummary();
}

/** درخت کامل سرویس‌ها. */
function clickpop_service_tree(): array {
	if ( ! class_exists( \ClickPop\Core\Api\Facade::class ) ) {
		return [];
	}

	return \ClickPop\Core\Api\Facade::serviceTree();
}

/** آدرس داشبورد کاربر؛ رشتهٔ خالی یعنی هنوز تنظیم نشده. */
function clickpop_dashboard_url(): string {
	if ( ! class_exists( \ClickPop\Core\Api\Facade::class ) ) {
		return '';
	}

	return \ClickPop\Core\Api\Facade::dashboardUrl();
}

/**
 * آیکن SVG هر پلتفرم — درون‌خطی، بدون فونت‌آیکن و بدون درخواست شبکه.
 */
function clickpop_brand_icon( string $slug ): void {
	$icons = [
		'instagram'  => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="3.6"/><circle cx="17.2" cy="6.8" r="1.1"/>',
		'telegram'   => '<path d="M21.8 4.2 18.5 20c-.2 1.1-.9 1.4-1.8.9l-5-3.7-2.4 2.3c-.3.3-.5.5-1 .5l.4-5.1 9.3-8.4c.4-.4-.1-.6-.6-.2L5.9 13.5l-5-1.6c-1.1-.3-1.1-1 .2-1.5l19.5-7.5c.9-.3 1.7.2 1.4 1.3z"/>',
		'youtube'    => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9.5 15 12l-5 2.5z"/>',
		'tiktok'     => '<path d="M16.6 5.8a4.3 4.3 0 0 1-1-2.8h-3.1v12.4a2.6 2.6 0 1 1-1.8-2.5V9.7a5.7 5.7 0 1 0 4.9 5.6V9.6a7.3 7.3 0 0 0 4.3 1.4V7.9a4.3 4.3 0 0 1-3.3-2.1z"/>',
		'twitter'    => '<path d="M18.2 2H21l-6.5 7.5L22 22h-6l-4.7-6.2L5.9 22H3l7-8L2.3 2h6.2l4.3 5.7L18.2 2z"/>',
		'spotify'    => '<circle cx="12" cy="12" r="9.5"/><path d="M7 9.6c3.4-1 7.2-.7 10 1M7.6 12.6c2.8-.8 6-.6 8.3.9M8.2 15.5c2.3-.6 4.8-.5 6.7.7"/>',
		'soundcloud' => '<path d="M3 14v3M6 12v5M9 10v7M12 8v9M15 7v10h4a3.5 3.5 0 0 0 0-7c-.3 0-.6 0-.9.1"/>',
	];

	$path = $icons[ $slug ] ?? '<circle cx="12" cy="12" r="9"/>';

	printf(
		'<svg class="cp-bico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
		wp_kses( $path, clickpop_svg_allowed_tags() )
	);
}

/**
 * تولید FAQPage schema برای پرسش‌هایی که روی همین صفحه دیده می‌شوند.
 *
 * @param array<int,array<string,string>> $items
 */
function clickpop_faq_schema( array $items ): void {
	$questions = [];

	foreach ( $items as $item ) {
		$q = trim( (string) ( $item['q'] ?? '' ) );
		$a = trim( (string) ( $item['a'] ?? '' ) );

		if ( '' === $q || '' === $a ) {
			continue;
		}

		$questions[] = [
			'@type'          => 'Question',
			'name'           => $q,
			'acceptedAnswer' => [
				'@type' => 'Answer',
				'text'  => $a,
			],
		];
	}

	if ( ! $questions ) {
		return;
	}

	printf(
		'<script type="application/ld+json">%s</script>',
		wp_json_encode(
			[
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $questions,
			],
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		)
	);
}

/** قالب‌بندی مبلغ ریالی به تومان — بدون وابستگی به افزونه. */
function clickpop_format_rials( int $rials ): string {
	if ( class_exists( \ClickPop\Core\Support\Money::class ) ) {
		return \ClickPop\Core\Support\Money::fromRials( $rials )->format();
	}

	return number_format_i18n( intdiv( $rials, 10 ) ) . ' ' . __( 'تومان', 'clickpop' );
}

/**
 * لینک شبکه‌های اجتماعی از پنل «محتوای سایت».
 */
function clickpop_social_links(): void {
	$links = array_filter(
		[
			'instagram' => (string) clickpop_content( 'social_instagram', '' ),
			'telegram'  => (string) clickpop_content( 'social_telegram', '' ),
			'twitter'   => (string) clickpop_content( 'social_x', '' ),
		]
	);

	if ( ! $links ) {
		return;
	}

	$labels = [
		'instagram' => __( 'اینستاگرام', 'clickpop' ),
		'telegram'  => __( 'تلگرام', 'clickpop' ),
		'twitter'   => __( 'ایکس', 'clickpop' ),
	];

	echo '<ul class="cp-socials">';

	foreach ( $links as $slug => $url ) {
		printf(
			'<li><a href="%1$s" rel="noopener" target="_blank" aria-label="%2$s">',
			esc_url( $url ),
			esc_attr( $labels[ $slug ] ?? $slug )
		);
		clickpop_brand_icon( $slug );
		echo '</a></li>';
	}

	echo '</ul>';
}
