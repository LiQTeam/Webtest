<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * تعریف یکجای همهٔ فیلدهای محتوا و ظاهر.
 *
 * این کلاس تنها منبع حقیقت است: فرم ادمین، پاک‌سازی ورودی و پیش‌فرض‌ها
 * همگی از همین‌جا ساخته می‌شوند. افزودن یک فیلد یعنی یک ردیف اینجا، نه سه جا.
 */
final class ContentSchema {

	/** فهرست آیکن‌های در دسترس (SVG درون‌خطی در تم). */
	public static function icons(): array {
		return [
			'bolt'      => __( 'صاعقه — سرعت', 'clickpop-core' ),
			'shield'    => __( 'سپر — امنیت', 'clickpop-core' ),
			'wallet'    => __( 'کیف پول', 'clickpop-core' ),
			'headset'   => __( 'پشتیبانی', 'clickpop-core' ),
			'chart'     => __( 'نمودار رشد', 'clickpop-core' ),
			'clock'     => __( 'ساعت', 'clickpop-core' ),
			'check'     => __( 'تیک', 'clickpop-core' ),
			'star'      => __( 'ستاره', 'clickpop-core' ),
			'refresh'   => __( 'جبران ریزش', 'clickpop-core' ),
			'lock'      => __( 'قفل', 'clickpop-core' ),
			'target'    => __( 'هدف', 'clickpop-core' ),
			'users'     => __( 'کاربران', 'clickpop-core' ),
			'sparkles'  => __( 'درخشش', 'clickpop-core' ),
			'globe'     => __( 'کره زمین', 'clickpop-core' ),
			'gift'      => __( 'هدیه', 'clickpop-core' ),
			'rocket'    => __( 'موشک', 'clickpop-core' ),
		];
	}

	/** شبکه‌های اجتماعی قابل انتخاب. */
	public static function networks(): array {
		return [
			'instagram'  => 'اینستاگرام',
			'telegram'   => 'تلگرام',
			'twitter'    => 'ایکس / توییتر',
			'youtube'    => 'یوتیوب',
			'tiktok'     => 'تیک‌تاک',
			'whatsapp'   => 'واتساپ',
			'linkedin'   => 'لینکدین',
			'aparat'     => 'آپارات',
		];
	}

	/**
	 * ساختار کامل: تب‌ها → فیلدها.
	 *
	 * انواع فیلد: text · textarea · url · email · number · color · toggle ·
	 *             select · image · code · repeater
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function tabs(): array {
		return [

			/* ─────────── ظاهر و برند ─────────── */
			'appearance' => [
				'label'  => __( 'ظاهر و برند', 'clickpop-core' ),
				'icon'   => 'admin-appearance',
				'intro'  => __( 'رنگ، گردی گوشه‌ها و حالت پیش‌فرض تم. تغییرات بلافاصله روی کل سایت اعمال می‌شود.', 'clickpop-core' ),
				'fields' => [
					'brand_primary'   => [
						'type'    => 'color',
						'label'   => __( 'رنگ اصلی برند', 'clickpop-core' ),
						'default' => '#1668FF',
						'help'    => __( 'رنگ پایه. سایه‌های تیره و روشن خودکار از همین ساخته می‌شوند.', 'clickpop-core' ),
					],
					'brand_accent'    => [
						'type'    => 'color',
						'label'   => __( 'رنگ تأکید', 'clickpop-core' ),
						'default' => '#FF7A1A',
					],
					'default_mode'    => [
						'type'    => 'select',
						'label'   => __( 'حالت پیش‌فرض تم', 'clickpop-core' ),
						'default' => 'light',
						'options' => [
							'light'  => __( 'روشن (پیشنهادی)', 'clickpop-core' ),
							'dark'   => __( 'تیره', 'clickpop-core' ),
							'system' => __( 'پیروی از سیستم بازدیدکننده', 'clickpop-core' ),
						],
					],
					'show_theme_toggle' => [
						'type'    => 'toggle',
						'label'   => __( 'نمایش دکمهٔ تغییر تم در هدر', 'clickpop-core' ),
						'default' => 1,
					],
					'radius'          => [
						'type'    => 'select',
						'label'   => __( 'گردی گوشه‌ها', 'clickpop-core' ),
						'default' => 'md',
						'options' => [
							'sm' => __( 'کم', 'clickpop-core' ),
							'md' => __( 'متوسط', 'clickpop-core' ),
							'lg' => __( 'زیاد', 'clickpop-core' ),
						],
					],
					'container_width' => [
						'type'    => 'number',
						'label'   => __( 'عرض محتوا (پیکسل)', 'clickpop-core' ),
						'default' => 1180,
						'min'     => 960,
						'max'     => 1600,
					],
					'custom_css'      => [
						'type'    => 'code',
						'label'   => __( 'CSS سفارشی', 'clickpop-core' ),
						'default' => '',
						'help'    => __( 'در انتهای استایل سایت درج می‌شود. برای تغییرات جزئی بدون ویرایش فایل تم.', 'clickpop-core' ),
					],
				],
			],

			/* ─────────── نوار بالا و هدر ─────────── */
			'header' => [
				'label'  => __( 'هدر و نوار اعلان', 'clickpop-core' ),
				'icon'   => 'menu',
				'fields' => [
					'topbar_enabled'   => [
						'type'    => 'toggle',
						'label'   => __( 'نمایش نوار اعلان بالای سایت', 'clickpop-core' ),
						'default' => 0,
					],
					'topbar_text'      => [
						'type'    => 'text',
						'label'   => __( 'متن نوار اعلان', 'clickpop-core' ),
						'default' => 'تخفیف ۱۵٪ اولین سفارش با کد CLICKPOP',
					],
					'topbar_link_text' => [
						'type'    => 'text',
						'label'   => __( 'متن لینک نوار', 'clickpop-core' ),
						'default' => 'مشاهده',
					],
					'topbar_link_url'  => [
						'type'    => 'url',
						'label'   => __( 'لینک نوار', 'clickpop-core' ),
						'default' => '#services',
					],
					'header_cta_text'  => [
						'type'    => 'text',
						'label'   => __( 'متن دکمهٔ هدر (کاربر مهمان)', 'clickpop-core' ),
						'default' => 'ورود / ثبت‌نام',
					],
					'header_sticky'    => [
						'type'    => 'toggle',
						'label'   => __( 'چسبیدن هدر به بالای صفحه', 'clickpop-core' ),
						'default' => 1,
					],
				],
			],

			/* ─────────── هیرو ─────────── */
			'hero' => [
				'label'  => __( 'بنر اصلی', 'clickpop-core' ),
				'icon'   => 'align-center',
				'intro'  => __( 'اولین چیزی که بازدیدکننده می‌بیند. عنوان کوتاه و دقیق بنویسید.', 'clickpop-core' ),
				'fields' => [
					'hero_enabled'   => [ 'type' => 'toggle', 'label' => __( 'نمایش بنر اصلی', 'clickpop-core' ), 'default' => 1 ],
					'hero_order'     => [ 'type' => 'number', 'label' => __( 'ترتیب نمایش', 'clickpop-core' ), 'default' => 10, 'min' => 1, 'max' => 99 ],
					'hero_layout'    => [
						'type'    => 'select',
						'label'   => __( 'چیدمان', 'clickpop-core' ),
						'default' => 'split',
						'options' => [
							'split'  => __( 'دو ستونه با کارت سفارش', 'clickpop-core' ),
							'center' => __( 'تک‌ستونه وسط‌چین', 'clickpop-core' ),
							'image'  => __( 'دو ستونه با تصویر', 'clickpop-core' ),
						],
					],
					'hero_eyebrow'   => [ 'type' => 'text', 'label' => __( 'برچسب بالای عنوان', 'clickpop-core' ), 'default' => 'اتصال مستقیم به سرویس‌دهنده' ],
					'hero_title'     => [ 'type' => 'textarea', 'label' => __( 'عنوان اصلی', 'clickpop-core' ), 'default' => 'رشد شبکهٔ اجتماعی‌ات را به ثانیه بسپار', 'rows' => 2 ],
					'hero_highlight' => [
						'type'    => 'text',
						'label'   => __( 'بخش رنگی عنوان', 'clickpop-core' ),
						'default' => 'به ثانیه بسپار',
						'help'    => __( 'اگر این عبارت داخل عنوان باشد، با گرادیان برند رنگ می‌شود.', 'clickpop-core' ),
					],
					'hero_text'      => [ 'type' => 'textarea', 'label' => __( 'توضیح زیر عنوان', 'clickpop-core' ), 'default' => 'فالوور، لایک، ویو، ممبر و کامنت برای اینستاگرام، تلگرام، یوتیوب و تیک‌تاک. سفارش ثبت می‌شود، هزینه از کیف پول کسر می‌شود و شمارندهٔ باقی‌مانده تا لحظهٔ تکمیل جلوی چشمت است.', 'rows' => 4 ],
					'hero_cta_text'  => [ 'type' => 'text', 'label' => __( 'متن دکمهٔ اصلی', 'clickpop-core' ), 'default' => 'شروع سفارش' ],
					'hero_cta_url'   => [ 'type' => 'url', 'label' => __( 'لینک دکمهٔ اصلی', 'clickpop-core' ), 'default' => '#services' ],
					'hero_alt_text'  => [ 'type' => 'text', 'label' => __( 'متن دکمهٔ دوم', 'clickpop-core' ), 'default' => 'چطور کار می‌کند' ],
					'hero_alt_url'   => [ 'type' => 'url', 'label' => __( 'لینک دکمهٔ دوم', 'clickpop-core' ), 'default' => '#how' ],
					'hero_image'     => [ 'type' => 'image', 'label' => __( 'تصویر بنر (فقط در چیدمان تصویری)', 'clickpop-core' ), 'default' => 0 ],
					'hero_badges'    => [
						'type'    => 'repeater',
						'label'   => __( 'نشان‌های زیر دکمه‌ها', 'clickpop-core' ),
						'columns' => [
							'icon' => [ 'type' => 'icon', 'label' => __( 'آیکن', 'clickpop-core' ) ],
							'text' => [ 'type' => 'text', 'label' => __( 'متن', 'clickpop-core' ) ],
						],
						'default' => [
							[ 'icon' => 'bolt', 'text' => 'شروع آنی سفارش' ],
							[ 'icon' => 'shield', 'text' => 'بدون نیاز به رمز حساب' ],
							[ 'icon' => 'refresh', 'text' => 'بازگشت خودکار وجه' ],
						],
					],
				],
			],

			/* ─────────── ویژگی‌ها ─────────── */
			'features' => [
				'label'  => __( 'ویژگی‌ها', 'clickpop-core' ),
				'icon'   => 'grid-view',
				'fields' => [
					'features_enabled' => [ 'type' => 'toggle', 'label' => __( 'نمایش بخش ویژگی‌ها', 'clickpop-core' ), 'default' => 1 ],
					'features_order'   => [ 'type' => 'number', 'label' => __( 'ترتیب نمایش', 'clickpop-core' ), 'default' => 20, 'min' => 1, 'max' => 99 ],
					'features_title'   => [ 'type' => 'text', 'label' => __( 'عنوان بخش', 'clickpop-core' ), 'default' => 'چرا کلیک‌پاپ' ],
					'features_text'    => [ 'type' => 'textarea', 'label' => __( 'توضیح بخش', 'clickpop-core' ), 'default' => '', 'rows' => 2 ],
					'features'         => [
						'type'    => 'repeater',
						'label'   => __( 'ویژگی‌ها', 'clickpop-core' ),
						'columns' => [
							'icon'  => [ 'type' => 'icon', 'label' => __( 'آیکن', 'clickpop-core' ) ],
							'title' => [ 'type' => 'text', 'label' => __( 'عنوان', 'clickpop-core' ) ],
							'text'  => [ 'type' => 'textarea', 'label' => __( 'توضیح', 'clickpop-core' ) ],
						],
						'default' => [
							[ 'icon' => 'bolt', 'title' => 'شروع در چند دقیقه', 'text' => 'سفارش مستقیم به سرویس‌دهنده می‌رود؛ بیشتر سرویس‌ها زیر ۵ دقیقه شروع می‌شوند.' ],
							[ 'icon' => 'wallet', 'title' => 'کیف پول شفاف', 'text' => 'هر کسر و هر بازگشت وجه یک ردیف دائمی در دفتر تراکنش دارد که پاک نمی‌شود.' ],
							[ 'icon' => 'refresh', 'title' => 'بازگشت خودکار', 'text' => 'سفارش ناقص، دقیقاً به نسبت مقدار انجام‌نشده به کیف پول شما برمی‌گردد.' ],
							[ 'icon' => 'headset', 'title' => 'پشتیبانی تیکتی', 'text' => 'هر سفارش قابل پیگیری است و تیکت مستقیم به همان سفارش وصل می‌شود.' ],
						],
					],
				],
			],

			/* ─────────── سرویس‌ها ─────────── */
			'services' => [
				'label'  => __( 'سرویس‌ها', 'clickpop-core' ),
				'icon'   => 'cart',
				'intro'  => __( 'کارت‌ها و قیمت‌ها خودکار از دیتابیس ساخته می‌شوند؛ فقط متن‌های اطراف را تنظیم کنید.', 'clickpop-core' ),
				'fields' => [
					'brands_enabled'   => [ 'type' => 'toggle', 'label' => __( 'نمایش نوار پلتفرم‌ها', 'clickpop-core' ), 'default' => 1 ],
					'services_enabled' => [ 'type' => 'toggle', 'label' => __( 'نمایش بخش سرویس‌ها', 'clickpop-core' ), 'default' => 1 ],
					'services_order'   => [ 'type' => 'number', 'label' => __( 'ترتیب نمایش', 'clickpop-core' ), 'default' => 30, 'min' => 1, 'max' => 99 ],
					'services_title'   => [ 'type' => 'text', 'label' => __( 'عنوان بخش', 'clickpop-core' ), 'default' => 'سرویس‌ها و قیمت‌ها' ],
					'services_text'    => [ 'type' => 'textarea', 'label' => __( 'توضیح بخش', 'clickpop-core' ), 'default' => 'قیمت‌ها مستقیم از سرویس‌دهنده به‌روز می‌شوند. هر سرویس بازهٔ مجاز و قابلیت‌های خودش را دارد.', 'rows' => 2 ],
					'services_mode'    => [
						'type'    => 'select',
						'label'   => __( 'نوع نمایش', 'clickpop-core' ),
						'default' => 'brands',
						'options' => [
							'brands'   => __( 'کارت پلتفرم‌ها با «شروع قیمت از»', 'clickpop-core' ),
							'services' => __( 'کارت تک‌تک سرویس‌ها', 'clickpop-core' ),
						],
					],
					'services_limit'   => [ 'type' => 'number', 'label' => __( 'حداکثر کارت سرویس', 'clickpop-core' ), 'default' => 8, 'min' => 3, 'max' => 48 ],
					'services_btn'     => [ 'type' => 'text', 'label' => __( 'متن دکمهٔ پایین بخش', 'clickpop-core' ), 'default' => 'دیدن همهٔ سرویس‌ها و ثبت سفارش' ],
				],
			],

			/* ─────────── مراحل ─────────── */
			'steps' => [
				'label'  => __( 'مراحل کار', 'clickpop-core' ),
				'icon'   => 'editor-ol',
				'fields' => [
					'steps_enabled' => [ 'type' => 'toggle', 'label' => __( 'نمایش بخش مراحل', 'clickpop-core' ), 'default' => 1 ],
					'steps_order'   => [ 'type' => 'number', 'label' => __( 'ترتیب نمایش', 'clickpop-core' ), 'default' => 40, 'min' => 1, 'max' => 99 ],
					'steps_title'   => [ 'type' => 'text', 'label' => __( 'عنوان بخش', 'clickpop-core' ), 'default' => 'سه مرحله تا تحویل' ],
					'steps_text'    => [ 'type' => 'textarea', 'label' => __( 'توضیح بخش', 'clickpop-core' ), 'default' => '', 'rows' => 2 ],
					'steps'         => [
						'type'    => 'repeater',
						'label'   => __( 'مراحل', 'clickpop-core' ),
						'columns' => [
							'title' => [ 'type' => 'text', 'label' => __( 'عنوان مرحله', 'clickpop-core' ) ],
							'text'  => [ 'type' => 'textarea', 'label' => __( 'توضیح', 'clickpop-core' ) ],
						],
						'default' => [
							[ 'title' => 'کیف پول را شارژ کن', 'text' => 'پرداخت از درگاه بانکی با تأیید سمت سرور. هر شارژ یک ردیف دائمی در دفتر تراکنش‌ها می‌سازد.' ],
							[ 'title' => 'سرویس و لینک را بده', 'text' => 'لینک با فهرست دامنه‌های مجاز همان پلتفرم بررسی می‌شود و تعداد باید داخل بازهٔ سرویس باشد.' ],
							[ 'title' => 'پیشرفت را زنده ببین', 'text' => 'شمارندهٔ باقی‌مانده هر پنج دقیقه به‌روز می‌شود و سفارش ناقص خودکار برگشت می‌خورد.' ],
						],
					],
				],
			],

			/* ─────────── آمار ─────────── */
			'stats' => [
				'label'  => __( 'آمارها', 'clickpop-core' ),
				'icon'   => 'chart-bar',
				'fields' => [
					'stats_enabled' => [ 'type' => 'toggle', 'label' => __( 'نمایش بخش آمار', 'clickpop-core' ), 'default' => 1 ],
					'stats_order'   => [ 'type' => 'number', 'label' => __( 'ترتیب نمایش', 'clickpop-core' ), 'default' => 50, 'min' => 1, 'max' => 99 ],
					'stats_live'    => [
						'type'    => 'toggle',
						'label'   => __( 'استفاده از آمار واقعی سایت', 'clickpop-core' ),
						'default' => 0,
						'help'    => __( 'تعداد سرویس فعال و سفارش تکمیل‌شده را از دیتابیس می‌خواند و جای دو ردیف اول می‌گذارد.', 'clickpop-core' ),
					],
					'stats'         => [
						'type'    => 'repeater',
						'label'   => __( 'آمارها', 'clickpop-core' ),
						'columns' => [
							'value' => [ 'type' => 'text', 'label' => __( 'عدد یا متن', 'clickpop-core' ) ],
							'label' => [ 'type' => 'text', 'label' => __( 'برچسب', 'clickpop-core' ) ],
						],
						'default' => [
							[ 'value' => '۲٬۴۰۰+', 'label' => 'سرویس فعال' ],
							[ 'value' => '۹۸٫۶٪', 'label' => 'نرخ تکمیل سفارش' ],
							[ 'value' => '۲۴/۷', 'label' => 'پشتیبانی تیکت' ],
							[ 'value' => '۵ دقیقه', 'label' => 'میانگین شروع سفارش' ],
						],
					],
				],
			],

			/* ─────────── نظرات ─────────── */
			'testimonials' => [
				'label'  => __( 'نظر مشتریان', 'clickpop-core' ),
				'icon'   => 'format-quote',
				'intro'  => __( 'فقط نظرات واقعی بنویسید. نظر ساختگی هم اعتماد را می‌شکند هم ریسک سئویی دارد.', 'clickpop-core' ),
				'fields' => [
					'testimonials_enabled' => [ 'type' => 'toggle', 'label' => __( 'نمایش بخش نظرات', 'clickpop-core' ), 'default' => 0 ],
					'testimonials_order'   => [ 'type' => 'number', 'label' => __( 'ترتیب نمایش', 'clickpop-core' ), 'default' => 60, 'min' => 1, 'max' => 99 ],
					'testimonials_title'   => [ 'type' => 'text', 'label' => __( 'عنوان بخش', 'clickpop-core' ), 'default' => 'مشتری‌ها چه می‌گویند' ],
					'testimonials'         => [
						'type'    => 'repeater',
						'label'   => __( 'نظرات', 'clickpop-core' ),
						'columns' => [
							'name' => [ 'type' => 'text', 'label' => __( 'نام', 'clickpop-core' ) ],
							'role' => [ 'type' => 'text', 'label' => __( 'عنوان / کسب‌وکار', 'clickpop-core' ) ],
							'text' => [ 'type' => 'textarea', 'label' => __( 'متن نظر', 'clickpop-core' ) ],
						],
						'default' => [],
					],
				],
			],

			/* ─────────── پرسش‌ها ─────────── */
			'faq' => [
				'label'  => __( 'پرسش‌های پرتکرار', 'clickpop-core' ),
				'icon'   => 'editor-help',
				'fields' => [
					'faq_enabled' => [ 'type' => 'toggle', 'label' => __( 'نمایش بخش پرسش‌ها', 'clickpop-core' ), 'default' => 1 ],
					'faq_order'   => [ 'type' => 'number', 'label' => __( 'ترتیب نمایش', 'clickpop-core' ), 'default' => 70, 'min' => 1, 'max' => 99 ],
					'faq_title'   => [ 'type' => 'text', 'label' => __( 'عنوان بخش', 'clickpop-core' ), 'default' => 'پرسش‌های پرتکرار' ],
					'faq_text'    => [ 'type' => 'textarea', 'label' => __( 'توضیح بخش', 'clickpop-core' ), 'default' => '', 'rows' => 2 ],
					'faq_schema'  => [
						'type'    => 'toggle',
						'label'   => __( 'تولید داده‌ساختار FAQPage گوگل', 'clickpop-core' ),
						'default' => 1,
						'help'    => __( 'فقط وقتی روشن باشد که این پرسش‌ها واقعاً روی همین صفحه دیده می‌شوند.', 'clickpop-core' ),
					],
					'faq'         => [
						'type'    => 'repeater',
						'label'   => __( 'پرسش و پاسخ', 'clickpop-core' ),
						'columns' => [
							'q' => [ 'type' => 'text', 'label' => __( 'پرسش', 'clickpop-core' ) ],
							'a' => [ 'type' => 'textarea', 'label' => __( 'پاسخ', 'clickpop-core' ) ],
						],
						'default' => [
							[ 'q' => 'سفارشم ناقص تحویل شد، پول باقی‌مانده چه می‌شود؟', 'a' => 'سیستم مقدار باقی‌مانده را می‌خواند و دقیقاً به همان نسبت مبلغ را به کیف پول شما برمی‌گرداند. این کار خودکار است و یک ردیف بازگشت وجه در تاریخچهٔ تراکنش‌ها ثبت می‌شود.' ],
							[ 'q' => 'چرا رمز صفحه‌ام را نمی‌خواهید؟', 'a' => 'چون هیچ‌وقت به آن نیازی نیست. فقط لینک عمومی صفحه یا پست کافی است. اگر جایی رمز حساب شما را خواست، آن سرویس را ترک کنید.' ],
							[ 'q' => 'صفحه‌ام خصوصی است، سفارش انجام می‌شود؟', 'a' => 'خیر. تا پایان سفارش صفحه باید عمومی بماند. خصوصی‌کردن صفحه در میانهٔ کار، سفارش را ناقص می‌کند و فقط بخش انجام‌نشده برگشت می‌خورد.' ],
							[ 'q' => 'موجودی کیف پول قابل برداشت است؟', 'a' => 'موجودی برای خرید سرویس است و برداشت نقدی ندارد. مبالغ برگشتی هم به همان کیف پول برمی‌گردد و برای سفارش بعدی قابل استفاده است.' ],
						],
					],
				],
			],

			/* ─────────── فراخوان ─────────── */
			'cta' => [
				'label'  => __( 'فراخوان پایانی', 'clickpop-core' ),
				'icon'   => 'megaphone',
				'fields' => [
					'cta_enabled' => [ 'type' => 'toggle', 'label' => __( 'نمایش فراخوان پایانی', 'clickpop-core' ), 'default' => 1 ],
					'cta_order'   => [ 'type' => 'number', 'label' => __( 'ترتیب نمایش', 'clickpop-core' ), 'default' => 80, 'min' => 1, 'max' => 99 ],
					'cta_title'   => [ 'type' => 'text', 'label' => __( 'عنوان', 'clickpop-core' ), 'default' => 'اولین سفارشت را همین امروز ثبت کن' ],
					'cta_text'    => [ 'type' => 'text', 'label' => __( 'توضیح', 'clickpop-core' ), 'default' => 'بدون قرارداد، بدون حداقل خرید. از ۱۰۰ عدد شروع کن.' ],
					'cta_button'  => [ 'type' => 'text', 'label' => __( 'متن دکمه', 'clickpop-core' ), 'default' => 'ساخت حساب رایگان' ],
					'cta_url'     => [ 'type' => 'url', 'label' => __( 'لینک دکمه (خالی = ثبت‌نام)', 'clickpop-core' ), 'default' => '' ],
				],
			],

			/* ─────────── فوتر ─────────── */
			'footer' => [
				'label'  => __( 'فوتر و تماس', 'clickpop-core' ),
				'icon'   => 'admin-home',
				'fields' => [
					'footer_about'    => [ 'type' => 'textarea', 'label' => __( 'معرفی کوتاه در فوتر', 'clickpop-core' ), 'default' => '', 'rows' => 3 ],
					'contact_phone'   => [ 'type' => 'text', 'label' => __( 'تلفن', 'clickpop-core' ), 'default' => '' ],
					'contact_email'   => [ 'type' => 'email', 'label' => __( 'ایمیل', 'clickpop-core' ), 'default' => '' ],
					'contact_address' => [ 'type' => 'textarea', 'label' => __( 'نشانی', 'clickpop-core' ), 'default' => '', 'rows' => 2 ],
					'contact_hours'   => [ 'type' => 'text', 'label' => __( 'ساعت پاسخگویی', 'clickpop-core' ), 'default' => '' ],
					'footer_note'     => [ 'type' => 'text', 'label' => __( 'متن کپی‌رایت (خالی = پیش‌فرض)', 'clickpop-core' ), 'default' => '' ],
					'socials'         => [
						'type'    => 'repeater',
						'label'   => __( 'شبکه‌های اجتماعی', 'clickpop-core' ),
						'columns' => [
							'network' => [ 'type' => 'network', 'label' => __( 'شبکه', 'clickpop-core' ) ],
							'url'     => [ 'type' => 'url', 'label' => __( 'آدرس', 'clickpop-core' ) ],
						],
						'default' => [],
					],
					'trust_badges'    => [
						'type'    => 'repeater',
						'label'   => __( 'نمادهای اعتماد (اینماد و…)', 'clickpop-core' ),
						'columns' => [
							'image' => [ 'type' => 'image', 'label' => __( 'تصویر', 'clickpop-core' ) ],
							'url'   => [ 'type' => 'url', 'label' => __( 'لینک', 'clickpop-core' ) ],
						],
						'default' => [],
					],
				],
			],

			/* ─────────── سئو ─────────── */
			'seo' => [
				'label'  => __( 'سئو و اشتراک‌گذاری', 'clickpop-core' ),
				'icon'   => 'search',
				'intro'  => __( 'اگر افزونهٔ سئو (رنک‌مث/یواست) فعال باشد، تم این مقادیر را نادیده می‌گیرد و تداخل ایجاد نمی‌کند.', 'clickpop-core' ),
				'fields' => [
					'seo_description' => [ 'type' => 'textarea', 'label' => __( 'توضیح متای صفحهٔ اصلی', 'clickpop-core' ), 'default' => '', 'rows' => 3 ],
					'og_image'        => [ 'type' => 'image', 'label' => __( 'تصویر اشتراک‌گذاری (۱۲۰۰×۶۳۰)', 'clickpop-core' ), 'default' => 0 ],
				],
			],
		];
	}

	/**
	 * نگاشت مسطح کلید → تعریف فیلد.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function fields(): array {
		static $flat = null;

		if ( null !== $flat ) {
			return $flat;
		}

		$flat = [];

		foreach ( self::tabs() as $tab ) {
			foreach ( $tab['fields'] as $key => $field ) {
				$flat[ $key ] = $field;
			}
		}

		return $flat;
	}

	/**
	 * پیش‌فرض همهٔ کلیدها.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$out = [];

		foreach ( self::fields() as $key => $field ) {
			$out[ $key ] = $field['default'] ?? '';
		}

		return $out;
	}
}
