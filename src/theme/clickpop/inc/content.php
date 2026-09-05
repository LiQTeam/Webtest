<?php
/**
 * محتوای قابل‌ویرایش سایت.
 *
 * مقادیر از پنل «کلیک‌پاپ ← محتوا و ظاهر» می‌آیند. اگر افزونه نصب نباشد،
 * پیش‌فرض‌های همین فایل رندر می‌شوند و صفحه هرگز خالی نمی‌ماند.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

const CLICKPOP_CONTENT_OPTION = 'clickpop_site_content';

/**
 * پیش‌فرض‌ها — آینهٔ ContentSchema افزونه.
 *
 * @return array<string,mixed>
 */
function clickpop_content_defaults(): array {
	// اگر افزونه هست، همان اسکیما منبع حقیقت است تا هرگز دو تعریف واگرا نشوند.
	if ( class_exists( \ClickPop\Core\Admin\ContentSchema::class ) ) {
		return \ClickPop\Core\Admin\ContentSchema::defaults();
	}

	return [
		'brand_primary'     => '#1668FF',
		'brand_accent'      => '#FF7A1A',
		'default_mode'      => 'light',
		'show_theme_toggle' => 1,
		'radius'            => 'md',
		'container_width'   => 1180,
		'custom_css'        => '',

		'topbar_enabled'    => 0,
		'topbar_text'       => '',
		'topbar_link_text'  => '',
		'topbar_link_url'   => '',
		'header_cta_text'   => __( 'ورود / ثبت‌نام', 'clickpop' ),
		'header_sticky'     => 1,

		'hero_enabled'      => 1,
		'hero_order'        => 10,
		'hero_layout'       => 'split',
		'hero_eyebrow'      => __( 'اتصال مستقیم به سرویس‌دهنده', 'clickpop' ),
		'hero_title'        => __( 'رشد شبکهٔ اجتماعی‌ات را به ثانیه بسپار', 'clickpop' ),
		'hero_highlight'    => __( 'به ثانیه بسپار', 'clickpop' ),
		'hero_text'         => __( 'فالوور، لایک، ویو، ممبر و کامنت برای اینستاگرام، تلگرام، یوتیوب و تیک‌تاک.', 'clickpop' ),
		'hero_cta_text'     => __( 'شروع سفارش', 'clickpop' ),
		'hero_cta_url'      => '#services',
		'hero_alt_text'     => __( 'چطور کار می‌کند', 'clickpop' ),
		'hero_alt_url'      => '#how',
		'hero_image'        => 0,
		'hero_badges'       => [
			[ 'icon' => 'bolt', 'text' => __( 'شروع آنی سفارش', 'clickpop' ) ],
			[ 'icon' => 'shield', 'text' => __( 'بدون نیاز به رمز حساب', 'clickpop' ) ],
			[ 'icon' => 'refresh', 'text' => __( 'بازگشت خودکار وجه', 'clickpop' ) ],
		],

		'features_enabled'  => 1,
		'features_order'    => 20,
		'features_title'    => __( 'چرا کلیک‌پاپ', 'clickpop' ),
		'features_text'     => '',
		'features'          => [
			[ 'icon' => 'bolt', 'title' => __( 'شروع در چند دقیقه', 'clickpop' ), 'text' => __( 'سفارش مستقیم به سرویس‌دهنده می‌رود؛ بیشتر سرویس‌ها زیر ۵ دقیقه شروع می‌شوند.', 'clickpop' ) ],
			[ 'icon' => 'wallet', 'title' => __( 'کیف پول شفاف', 'clickpop' ), 'text' => __( 'هر کسر و هر بازگشت وجه یک ردیف دائمی در دفتر تراکنش دارد که پاک نمی‌شود.', 'clickpop' ) ],
			[ 'icon' => 'refresh', 'title' => __( 'بازگشت خودکار', 'clickpop' ), 'text' => __( 'سفارش ناقص، دقیقاً به نسبت مقدار انجام‌نشده به کیف پول شما برمی‌گردد.', 'clickpop' ) ],
			[ 'icon' => 'headset', 'title' => __( 'پشتیبانی تیکتی', 'clickpop' ), 'text' => __( 'هر سفارش قابل پیگیری است و تیکت مستقیم به همان سفارش وصل می‌شود.', 'clickpop' ) ],
		],

		'brands_enabled'    => 1,
		'services_enabled'  => 1,
		'services_order'    => 30,
		'services_title'    => __( 'سرویس‌ها و قیمت‌ها', 'clickpop' ),
		'services_text'     => __( 'قیمت‌ها مستقیم از سرویس‌دهنده به‌روز می‌شوند. هر سرویس بازهٔ مجاز و قابلیت‌های خودش را دارد.', 'clickpop' ),
		'services_mode'     => 'brands',
		'services_limit'    => 8,
		'services_btn'      => __( 'دیدن همهٔ سرویس‌ها', 'clickpop' ),

		'steps_enabled'     => 1,
		'steps_order'       => 40,
		'steps_title'       => __( 'سه مرحله تا تحویل', 'clickpop' ),
		'steps_text'        => '',
		'steps'             => [
			[ 'title' => __( 'کیف پول را شارژ کن', 'clickpop' ), 'text' => __( 'پرداخت از درگاه بانکی با تأیید سمت سرور. هر شارژ یک ردیف دائمی در دفتر تراکنش‌ها می‌سازد.', 'clickpop' ) ],
			[ 'title' => __( 'سرویس و لینک را بده', 'clickpop' ), 'text' => __( 'لینک با فهرست دامنه‌های مجاز همان پلتفرم بررسی می‌شود و تعداد باید داخل بازهٔ سرویس باشد.', 'clickpop' ) ],
			[ 'title' => __( 'پیشرفت را زنده ببین', 'clickpop' ), 'text' => __( 'شمارندهٔ باقی‌مانده هر پنج دقیقه به‌روز می‌شود و سفارش ناقص خودکار برگشت می‌خورد.', 'clickpop' ) ],
		],

		'stats_enabled'     => 1,
		'stats_order'       => 50,
		'stats_live'        => 0,
		'stats'             => [
			[ 'value' => '۲٬۴۰۰+', 'label' => __( 'سرویس فعال', 'clickpop' ) ],
			[ 'value' => '۹۸٫۶٪', 'label' => __( 'نرخ تکمیل سفارش', 'clickpop' ) ],
			[ 'value' => '۲۴/۷', 'label' => __( 'پشتیبانی تیکت', 'clickpop' ) ],
			[ 'value' => '۵ دقیقه', 'label' => __( 'میانگین شروع سفارش', 'clickpop' ) ],
		],

		'testimonials_enabled' => 0,
		'testimonials_order'   => 60,
		'testimonials_title'   => __( 'مشتری‌ها چه می‌گویند', 'clickpop' ),
		'testimonials'         => [],

		'faq_enabled'       => 1,
		'faq_order'         => 70,
		'faq_title'         => __( 'پرسش‌های پرتکرار', 'clickpop' ),
		'faq_text'          => '',
		'faq_schema'        => 1,
		'faq'               => [
			[ 'q' => __( 'سفارشم ناقص تحویل شد، پول باقی‌مانده چه می‌شود؟', 'clickpop' ), 'a' => __( 'سیستم مقدار باقی‌مانده را می‌خواند و دقیقاً به همان نسبت مبلغ را به کیف پول شما برمی‌گرداند. این کار خودکار است و یک ردیف بازگشت وجه ثبت می‌شود.', 'clickpop' ) ],
			[ 'q' => __( 'چرا رمز صفحه‌ام را نمی‌خواهید؟', 'clickpop' ), 'a' => __( 'چون هیچ‌وقت لازم نیست. فقط لینک عمومی صفحه یا پست کافی است. اگر جایی رمز حساب شما را خواست، آن سرویس را ترک کنید.', 'clickpop' ) ],
			[ 'q' => __( 'صفحه‌ام خصوصی است، سفارش انجام می‌شود؟', 'clickpop' ), 'a' => __( 'خیر. تا پایان سفارش صفحه باید عمومی بماند. خصوصی‌کردن صفحه سفارش را ناقص می‌کند و فقط بخش انجام‌نشده برگشت می‌خورد.', 'clickpop' ) ],
		],

		'cta_enabled'       => 1,
		'cta_order'         => 80,
		'cta_title'         => __( 'اولین سفارشت را همین امروز ثبت کن', 'clickpop' ),
		'cta_text'          => __( 'بدون قرارداد، بدون حداقل خرید. از ۱۰۰ عدد شروع کن.', 'clickpop' ),
		'cta_button'        => __( 'ساخت حساب رایگان', 'clickpop' ),
		'cta_url'           => '',

		'footer_about'      => '',
		'contact_phone'     => '',
		'contact_email'     => '',
		'contact_address'   => '',
		'contact_hours'     => '',
		'footer_note'       => '',
		'socials'           => [],
		'trust_badges'      => [],

		'seo_description'   => '',
		'og_image'          => 0,
	];
}

/** یک کلید از محتوای سایت. */
function clickpop_content( string $key, mixed $fallback = null ): mixed {
	static $cache = null;

	if ( null === $cache ) {
		$stored = get_option( CLICKPOP_CONTENT_OPTION, [] );
		$cache  = is_array( $stored )
			? array_merge( clickpop_content_defaults(), $stored )
			: clickpop_content_defaults();
	}

	if ( ! array_key_exists( $key, $cache ) ) {
		return $fallback;
	}

	$value = $cache[ $key ];

	if ( is_array( $value ) ) {
		return $value;
	}

	return ( '' === $value || null === $value ) ? ( $fallback ?? $value ) : $value;
}

function clickpop_on( string $key ): bool {
	return (bool) (int) clickpop_content( $key, 0 );
}

function clickpop_the_content_field( string $key ): void {
	echo esc_html( (string) clickpop_content( $key, '' ) );
}

/**
 * فهرست تکرارشونده با تضمین آرایه بودن.
 *
 * @return array<int,array<string,mixed>>
 */
function clickpop_content_list( string $key ): array {
	$value = clickpop_content( $key, [] );

	if ( ! is_array( $value ) ) {
		return [];
	}

	return array_values(
		array_filter(
			$value,
			static fn( $row ): bool => is_array( $row ) && array_filter( $row )
		)
	);
}

/**
 * بخش‌های صفحهٔ اصلی به ترتیبی که مدیر تعیین کرده.
 *
 * @return string[] نام فایل قالب هر بخش.
 */
function clickpop_home_sections(): array {
	$sections = [
		'hero'         => (int) clickpop_content( 'hero_order', 10 ),
		'features'     => (int) clickpop_content( 'features_order', 20 ),
		'services'     => (int) clickpop_content( 'services_order', 30 ),
		'steps'        => (int) clickpop_content( 'steps_order', 40 ),
		'stats'        => (int) clickpop_content( 'stats_order', 50 ),
		'testimonials' => (int) clickpop_content( 'testimonials_order', 60 ),
		'faq'          => (int) clickpop_content( 'faq_order', 70 ),
		'cta'          => (int) clickpop_content( 'cta_order', 80 ),
	];

	$sections = array_filter(
		$sections,
		static fn( int $order, string $key ): bool => clickpop_on( $key . '_enabled' ),
		ARRAY_FILTER_USE_BOTH
	);

	asort( $sections );

	return array_keys( $sections );
}
