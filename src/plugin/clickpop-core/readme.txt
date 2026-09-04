=== ClickPop Core Engine ===
Contributors: clickpop
Tags: smm, social media, wallet, api
Requires at least: 6.5
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later

هستهٔ پلتفرم کلیک‌پاپ: پل API سرویس‌دهندهٔ SMM، موتور قیمت‌گذاری، کیف پول با دفتر کل، سفارش، تیکت و داشبورد کاربر.

== Description ==

* اتصال به SMM Panel API v2 (services / add / status / balance)
* همگام‌سازی خودکار سرویس‌ها با تفاوت‌گیری hash و آرشیو به‌جای حذف
* موتور سود چندسطحی (سراسری، برند، دسته، سرویس)
* کیف پول با دفتر کل فقط-افزودنی و برداشت اتمیک
* ثبت سفارش به‌صورت Saga دوفازی با کلید idempotency
* بازگشت وجه خودکار برای سفارش ناقص و لغوشده
* درگاه زرین‌پال با تأیید سمت سرور و ضد دوباره‌شارژی
* داشبورد کاربر با شورتکد [clickpop_dashboard]

== Shortcodes ==

* [clickpop_dashboard] — داشبورد کامل کاربر
* [clickpop_order_form] — فقط فرم ثبت سفارش
* [clickpop_services] — فهرست عمومی سرویس‌ها و قیمت‌ها

== Changelog ==

= 1.0.0 =
* انتشار اولیه.
