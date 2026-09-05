<?php
/**
 * محتوای قابل‌ویرایش صفحهٔ اصلی.
 *
 * جای صفحه‌ساز را می‌گیرد: همهٔ متن‌ها از یک گزینهٔ آرایه‌ای می‌آیند که در
 * «کلیک‌پاپ ← محتوای سایت» ویرایش می‌شود. اگر افزونه نصب نباشد، پیش‌فرض‌های
 * همین فایل رندر می‌شوند و صفحه هرگز خالی نمی‌ماند.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

const CLICKPOP_CONTENT_OPTION = 'clickpop_site_content';

/**
 * پیش‌فرض‌های محتوا.
 *
 * @return array<string,mixed>
 */
function clickpop_content_defaults(): array {
	return [
		'hero_eyebrow'  => __( 'اتصال مستقیم به سرویس‌دهنده', 'clickpop' ),
		'hero_title'    => __( 'رشد شبکهٔ اجتماعی‌ات را به ثانیه بسپار', 'clickpop' ),
		'hero_text'     => __( 'فالوور، لایک، ویو، ممبر و کامنت برای اینستاگرام، تلگرام، یوتیوب و تیک‌تاک. سفارش ثبت می‌شود، هزینه از کیف پول کسر می‌شود و شمارندهٔ باقی‌مانده تا لحظهٔ تکمیل جلوی چشمت است.', 'clickpop' ),
		'hero_cta_text' => __( 'شروع سفارش', 'clickpop' ),
		'hero_cta_url'  => '#services',
		'hero_alt_text' => __( 'چطور کار می‌کند', 'clickpop' ),
		'hero_alt_url'  => '#how',

		'stats'         => [
			[
				'value' => '۹۸٫۶٪',
				'label' => __( 'نرخ تکمیل سفارش', 'clickpop' ),
			],
			[
				'value' => '۲۴/۷',
				'label' => __( 'پشتیبانی تیکت', 'clickpop' ),
			],
			[
				'value' => '۵ دقیقه',
				'label' => __( 'میانگین شروع سفارش', 'clickpop' ),
			],
		],

		'services_title' => __( 'سرویس‌ها و قیمت‌ها', 'clickpop' ),
		'services_text'  => __( 'قیمت‌ها مستقیم از سرویس‌دهنده به‌روز می‌شوند. هر سرویس بازهٔ مجاز و قابلیت‌های خودش را دارد.', 'clickpop' ),

		'steps_title'   => __( 'سه مرحله تا تحویل', 'clickpop' ),
		'steps'         => [
			[
				'title' => __( 'کیف پول را شارژ کن', 'clickpop' ),
				'text'  => __( 'پرداخت از درگاه بانکی با تأیید سمت سرور. هر شارژ یک ردیف دائمی در دفتر تراکنش‌ها می‌سازد که قابل حذف نیست.', 'clickpop' ),
			],
			[
				'title' => __( 'سرویس و لینک را بده', 'clickpop' ),
				'text'  => __( 'لینک با فهرست دامنه‌های مجاز همان پلتفرم بررسی می‌شود و تعداد باید داخل بازهٔ مجاز سرویس باشد.', 'clickpop' ),
			],
			[
				'title' => __( 'پیشرفت را زنده ببین', 'clickpop' ),
				'text'  => __( 'شمارندهٔ شروع و باقی‌مانده هر پنج دقیقه به‌روز می‌شود. سفارش ناقص خودکار به نسبت باقی‌مانده برگشت می‌خورد.', 'clickpop' ),
			],
		],

		'faq_title'     => __( 'پرسش‌های پرتکرار', 'clickpop' ),
		'faq'           => [
			[
				'q' => __( 'سفارشم ناقص تحویل شد، پول باقی‌مانده چه می‌شود؟', 'clickpop' ),
				'a' => __( 'وقتی سرویس‌دهنده وضعیت «ناقص» برگرداند، سیستم مقدار باقی‌مانده را می‌خواند و دقیقاً به همان نسبت مبلغ را به کیف پول شما برمی‌گرداند. این کار خودکار است و یک ردیف بازگشت وجه در تاریخچهٔ تراکنش‌ها ثبت می‌شود.', 'clickpop' ),
			],
			[
				'q' => __( 'چرا رمز صفحه‌ام را نمی‌خواهید؟', 'clickpop' ),
				'a' => __( 'چون هیچ‌وقت به آن نیازی نیست. فقط لینک عمومی صفحه یا پست کافی است. اگر جایی رمز حساب شما را خواست، آن سرویس را ترک کنید.', 'clickpop' ),
			],
			[
				'q' => __( 'صفحه‌ام خصوصی است، سفارش انجام می‌شود؟', 'clickpop' ),
				'a' => __( 'خیر. تا پایان سفارش صفحه باید عمومی بماند. خصوصی‌کردن صفحه در میانهٔ کار، سفارش را ناقص می‌کند و فقط بخش انجام‌نشده برگشت می‌خورد.', 'clickpop' ),
			],
		],

		'cta_title'     => __( 'اولین سفارشت را همین امروز ثبت کن', 'clickpop' ),
		'cta_text'      => __( 'بدون قرارداد، بدون حداقل خرید.', 'clickpop' ),
		'cta_button'    => __( 'ساخت حساب رایگان', 'clickpop' ),

		'contact_phone' => '',
		'contact_email' => '',
		'contact_address' => '',
		'social_instagram' => '',
		'social_telegram'  => '',
		'social_x'         => '',
	];
}

/**
 * یک کلید از محتوای سایت.
 *
 * @return mixed
 */
function clickpop_content( string $key, mixed $fallback = null ): mixed {
	static $cache = null;

	if ( null === $cache ) {
		$stored = get_option( CLICKPOP_CONTENT_OPTION, [] );
		$cache  = is_array( $stored ) ? array_merge( clickpop_content_defaults(), $stored ) : clickpop_content_defaults();
	}

	if ( ! array_key_exists( $key, $cache ) ) {
		return $fallback;
	}

	$value = $cache[ $key ];

	return ( '' === $value || null === $value ) ? ( $fallback ?? $value ) : $value;
}

/** خروجی امن یک متن محتوا. */
function clickpop_the_content_field( string $key ): void {
	echo esc_html( (string) clickpop_content( $key, '' ) );
}

/**
 * فهرست تکرارشونده‌ها (stats / steps / faq) با تضمین آرایه بودن.
 *
 * @return array<int,array<string,string>>
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
