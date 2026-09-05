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
 * آیکن‌های مینیمال رابط — خطی، گوشه‌گرد، ضخامت ۱٫۷.
 *
 * همه درون‌خطی‌اند: نه فونت‌آیکن، نه درخواست شبکه، نه FOUC.
 */
function clickpop_icon( string $name, string $class = 'cp-ico' ): void {
	$paths = [
		'bolt'     => '<path d="M13.5 2.5 5 13.2h5.4L10 21.5 19 10.6h-5.4z"/>',
		'shield'   => '<path d="M12 2.8 4.8 5.6v5.5c0 4.4 3 8.5 7.2 9.9 4.2-1.4 7.2-5.5 7.2-9.9V5.6z"/><path d="M9.3 11.9 11.4 14l3.5-3.8"/>',
		'wallet'   => '<rect x="2.8" y="6" width="18.4" height="13" rx="3.2"/><path d="M2.8 10.2h18.4M16.6 14.6h2"/>',
		'headset'  => '<path d="M4.5 14v-2a7.5 7.5 0 0 1 15 0v2"/><rect x="2.8" y="13.6" width="4" height="6" rx="2"/><rect x="17.2" y="13.6" width="4" height="6" rx="2"/><path d="M19.5 19.6v.4a2.6 2.6 0 0 1-2.6 2.6H13"/>',
		'chart'    => '<path d="M3.2 17.4 9 11.6l3.6 3.6 7.2-7.4"/><path d="M14.6 7.8h5.2V13"/>',
		'clock'    => '<circle cx="12" cy="12" r="9.2"/><path d="M12 6.8v5.4l3.4 2"/>',
		'check'    => '<circle cx="12" cy="12" r="9.2"/><path d="M8.2 12.3 11 15l5-5.4"/>',
		'star'     => '<path d="m12 3.4 2.7 5.5 6.1.9-4.4 4.3 1 6-5.4-2.9-5.4 2.9 1-6L3.2 9.8l6.1-.9z"/>',
		'refresh'  => '<path d="M3.4 12a8.6 8.6 0 0 1 14.7-6.1L21 8.8"/><path d="M21 4.2v4.6h-4.6"/><path d="M20.6 12a8.6 8.6 0 0 1-14.7 6.1L3 15.2"/><path d="M3 19.8v-4.6h4.6"/>',
		'lock'     => '<rect x="4.6" y="10.4" width="14.8" height="10.2" rx="3"/><path d="M8.4 10.4V7.6a3.6 3.6 0 0 1 7.2 0v2.8"/>',
		'target'   => '<circle cx="12" cy="12" r="8.6"/><circle cx="12" cy="12" r="4.6"/><circle cx="12" cy="12" r="1" fill="currentColor" stroke="none"/>',
		'users'    => '<circle cx="9.4" cy="8.4" r="3.6"/><path d="M2.8 20a6.6 6.6 0 0 1 13.2 0"/><path d="M16.4 5.2a3.6 3.6 0 0 1 0 6.5M18 20a6.6 6.6 0 0 0-2.4-5.1"/>',
		'sparkles' => '<path d="m10 3.4 1.5 3.6 3.6 1.5-3.6 1.5L10 13.6 8.5 10 4.9 8.5 8.5 7z"/><path d="m17.4 13.6.9 2.1 2.1.9-2.1.9-.9 2.1-.9-2.1-2.1-.9 2.1-.9z"/>',
		'globe'    => '<circle cx="12" cy="12" r="9.2"/><path d="M2.8 12h18.4"/><path d="M12 2.8a14 14 0 0 1 0 18.4 14 14 0 0 1 0-18.4z"/>',
		'gift'     => '<rect x="3" y="9.4" width="18" height="11.2" rx="2.6"/><path d="M3 13.8h18M12 9.4v11.2"/><path d="M12 9.4S10.2 4.6 7.8 4.6a2.4 2.4 0 0 0 0 4.8M12 9.4s1.8-4.8 4.2-4.8a2.4 2.4 0 0 1 0 4.8"/>',
		'rocket'   => '<path d="M12 2.8c3.4 2 5.4 5.6 5.4 9.6l-2.6 2.6H9.2L6.6 12.4c0-4 2-7.6 5.4-9.6z"/><circle cx="12" cy="10" r="2"/><path d="M9.2 15 7 19.4l3.4-1.2M14.8 15 17 19.4l-3.4-1.2"/>',
		'arrow'    => '<path d="M14.6 6.4 20 12l-5.4 5.6M20 12H4.4"/>',
		'quote'    => '<path d="M9.4 6.6c-3 1.2-4.6 3.6-4.6 7v4h5.6v-5.6H7.6c0-2 .8-3.4 2.6-4.2zM19.4 6.6c-3 1.2-4.6 3.6-4.6 7v4h5.6v-5.6h-2.8c0-2 .8-3.4 2.6-4.2z"/>',
	];

	$path = $paths[ $name ] ?? $paths['check'];

	printf(
		'<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
		esc_attr( $class ),
		wp_kses( $path, clickpop_svg_allowed_tags() )
	);
}

/**
 * عنوان با بخش رنگی.
 *
 * عبارت انتخاب‌شده در پنل، داخل عنوان با گرادیان برند رنگ می‌شود.
 */
function clickpop_highlighted_title( string $title, string $highlight, string $tag = 'h1', string $class = 'cp-hero__t' ): void {
	$allowed = [ 'h1', 'h2', 'h3' ];
	$tag     = in_array( $tag, $allowed, true ) ? $tag : 'h1';

	$safe      = esc_html( $title );
	$highlight = trim( $highlight );

	if ( '' !== $highlight && str_contains( $title, $highlight ) ) {
		$safe = str_replace(
			esc_html( $highlight ),
			'<span class="cp-grad">' . esc_html( $highlight ) . '</span>',
			$safe
		);
	}

	printf(
		'<%1$s class="%2$s">%3$s</%1$s>',
		esc_html( $tag ),
		esc_attr( $class ),
		wp_kses( $safe, [ 'span' => [ 'class' => true ], 'br' => [] ] )
	);
}

/** آمار زندهٔ سایت برای بخش آمارها. */
function clickpop_live_stats(): array {
	if ( ! class_exists( \ClickPop\Core\Api\Facade::class ) ) {
		return [];
	}

	return \ClickPop\Core\Api\Facade::siteStats();
}

/**
 * شبکه‌های اجتماعی از پنل محتوا (ساختار تکرارشونده).
 */
function clickpop_social_links(): void {
	$rows = clickpop_content_list( 'socials' );

	if ( ! $rows ) {
		return;
	}

	$labels = [
		'instagram' => __( 'اینستاگرام', 'clickpop' ),
		'telegram'  => __( 'تلگرام', 'clickpop' ),
		'twitter'   => __( 'ایکس', 'clickpop' ),
		'youtube'   => __( 'یوتیوب', 'clickpop' ),
		'tiktok'    => __( 'تیک‌تاک', 'clickpop' ),
		'whatsapp'  => __( 'واتساپ', 'clickpop' ),
		'linkedin'  => __( 'لینکدین', 'clickpop' ),
		'aparat'    => __( 'آپارات', 'clickpop' ),
	];

	echo '<ul class="cp-socials">';

	foreach ( $rows as $row ) {
		$url = (string) ( $row['url'] ?? '' );

		if ( '' === $url ) {
			continue;
		}

		$network = (string) ( $row['network'] ?? 'instagram' );

		printf(
			'<li><a href="%1$s" rel="noopener" target="_blank" aria-label="%2$s">',
			esc_url( $url ),
			esc_attr( $labels[ $network ] ?? $network )
		);
		clickpop_brand_icon( $network );
		echo '</a></li>';
	}

	echo '</ul>';
}

/** نمادهای اعتماد در فوتر. */
function clickpop_trust_badges(): void {
	$rows = clickpop_content_list( 'trust_badges' );

	if ( ! $rows ) {
		return;
	}

	echo '<div class="cp-trust">';

	foreach ( $rows as $row ) {
		$image = (int) ( $row['image'] ?? 0 );

		if ( $image <= 0 ) {
			continue;
		}

		$img = wp_get_attachment_image(
			$image,
			'medium',
			false,
			[
				'loading' => 'lazy',
				'class'   => 'cp-trust__img',
			]
		);

		$url = (string) ( $row['url'] ?? '' );

		if ( '' !== $url ) {
			printf(
				'<a class="cp-trust__item" href="%s" rel="noopener" target="_blank">%s</a>',
				esc_url( $url ),
				wp_kses_post( $img )
			);
		} else {
			printf( '<span class="cp-trust__item">%s</span>', wp_kses_post( $img ) );
		}
	}

	echo '</div>';
}
