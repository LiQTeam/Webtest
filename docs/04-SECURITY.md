# ClickPop — طرح امنیتی

## ۱. نگاشت OWASP Top 10 (2021)

| ریسک | بردار در این پروژه | کنترل |
|---|---|---|
| **A01 – Broken Access Control** | خواندن سفارش/تیکت کاربر دیگر با تغییر `id` | هر endpoint علاوه بر `current_user_can()`، **مالکیت ردیف** را در کوئری اعمال می‌کند (`WHERE id=? AND user_id=?`)، نه بعد از fetch |
| **A02 – Cryptographic Failures** | کلید API تأمین‌کننده، کد OTP، توکن نشست | کلید API با AES-256-GCM (کلید در `wp-config.php`، نه DB)؛ OTP فقط `sha256` ذخیره؛ توکن نشست فقط hash؛ HSTS + کوکی `Secure`+`HttpOnly`+`SameSite=Lax` |
| **A03 – Injection** | فیلتر سفارش، جست‌وجوی سرویس، متن تیکت | همهٔ SQL از `$wpdb->prepare()`؛ `ORDER BY`/`LIMIT` از allowlist ثابت، نه از ورودی؛ تیکت متن ساده |
| **A04 – Insecure Design** | Race condition کیف پول، دوباره‌شارژی درگاه، سفارش تکراری | برداشت تک‌عبارتی شرطی؛ `UNIQUE(gateway,authority)`؛ `UNIQUE(idempotency_key)`؛ Saga با reconciler |
| **A05 – Security Misconfiguration** | لاگ‌های پرحرف، دیباگ روشن، هدرهای غایب | `WP_DEBUG_DISPLAY=false` در پروداکشن؛ CSP/HSTS/X-Frame/Referrer-Policy/Permissions-Policy؛ `X-Powered-By` و `generator` حذف |
| **A06 – Vulnerable Components** | افزونه‌های ثالث، وابستگی npm/composer | صفر وابستگی رانتایم PHP؛ `composer audit` + `npm audit` در CI؛ Dependabot |
| **A07 – Auth Failures** | brute-force OTP/رمز، session fixation | سقف ۵ تلاش روی OTP، TTL دو دقیقه، یک‌بارمصرف؛ throttle بر اساس شماره + IP؛ `wp_set_auth_cookie` پس از regenerate؛ 2FA اختیاری |
| **A08 – Data Integrity Failures** | callback جعلی درگاه، پاسخ دستکاری‌شدهٔ تأمین‌کننده | تأیید server-to-server + تطبیق مبلغ؛ `sslverify=true` روی همهٔ تماس‌ها؛ بدون deserialize ورودی خارجی |
| **A09 – Logging & Monitoring Failures** | تعدیل موجودی بی‌رد، سفارش گم‌شده | `cp_audit_log` برای هر عمل حساس ادمین؛ مغایرت‌گیری شبانه؛ هشدار بر روند خطای تأمین‌کننده |
| **A10 – SSRF** | فیلد `link` سفارش، `template_link` تأمین‌کننده | لینک کاربر **هرگز** توسط سرور fetch نمی‌شود؛ فقط ذخیره و ارسال به تأمین‌کننده؛ allowlist هاست + رد IP literal و `localhost`/رنج‌های خصوصی |

## ۲. قواعد کدنویسی الزام‌آور

```php
// ورودی — همیشه در مرز REST/فرم
$qty  = absint( $request['quantity'] );
$link = esc_url_raw( wp_unslash( $request['link'] ) );
$note = sanitize_textarea_field( wp_unslash( $request['note'] ) );

// SQL — بدون استثنا
$wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}cp_orders WHERE id = %d AND user_id = %d",
    $order_id, get_current_user_id()
) );

// مرتب‌سازی پویا — allowlist، نه درون‌یابی
$allowed = [ 'created_at', 'charge', 'status' ];
$orderby = in_array( $request['orderby'], $allowed, true ) ? $request['orderby'] : 'created_at';

// خروجی — همیشه در محل چاپ، نه در محل ذخیره
echo esc_html( $order->service_name );
printf( '<a href="%s">%s</a>', esc_url( $link ), esc_html( $label ) );
echo wp_kses_post( $service_description );

// CSRF
if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) { … }

// مجوز
'permission_callback' => static fn() => current_user_can( 'clickpop_place_order' ),
```

**ممنوعیت‌های سخت (شکست CI):**
- `$_GET` / `$_POST` / `$_REQUEST` مستقیم بیرون از لایهٔ کنترلر
- درون‌یابی متغیر داخل رشتهٔ SQL
- `permission_callback => '__return_true'` روی هر endpointی که داده‌ی کاربر برمی‌گرداند
- `extract()`، `eval()`، `unserialize()` روی دادهٔ خارجی
- `error_log()` حاوی کلید API، شماره تلفن کامل، یا لینک کامل کاربر
- `md5()`/`sha1()` برای رمز یا توکن
- ذخیرهٔ HTML خام از ورودی کاربر

## ۳. رمزنگاری

```php
// wp-config.php  (خارج از مخزن)
define( 'CLICKPOP_ENCRYPTION_KEY', '<32 بایت base64>' );
```
- الگوریتم: `openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag)`؛ خروجی `base64(iv|tag|ciphertext)`.
- در نبود ثابت، fallback به `wp_salt('secure_auth')` **با نمایش هشدار دائمی در ادمین** (چون تغییر salt کلیدها را غیرقابل بازیابی می‌کند).
- کلید API در فرم ادمین فقط به‌صورت ماسک (`••••••1234`) نمایش داده می‌شود؛ فیلد خالی یعنی «تغییر نده».

## ۴. هدرهای امنیتی

```
Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{RANDOM}';
  style-src 'self'; img-src 'self' data:; font-src 'self';
  connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self' https://*.zarinpal.com
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=(), interest-cohort=()
X-Frame-Options: DENY
```
> با حذف صفحه‌ساز، `style-src 'unsafe-inline'` دیگر لازم نیست و می‌تواند از CSP حذف شود. CSP ابتدا در حالت `Report-Only` مستقر می‌شود.

## ۵. آپلود پیوست تیکت

- Allowlist صریح: `jpg, jpeg, png, webp, pdf` — بدون `svg` (بردار XSS)، بدون `zip`.
- اعتبارسنجی با `wp_check_filetype_and_ext()` **و** `finfo` (نه پسوند).
- سقف ۵MB، حداکثر ۳ فایل در هر پیام.
- نام فایل بازنویسی می‌شود به `{uuid}.{ext}`.
- ذخیره در `wp-content/uploads/clickpop-private/{yyyy}/{mm}/` با `.htaccess`/قاعدهٔ Nginx که دسترسی مستقیم را می‌بندد؛ سرو از طریق endpoint احراز هویت‌شده با بررسی مالکیت.
- هدر `Content-Disposition: attachment` + `X-Content-Type-Options: nosniff` هنگام سرو.

## ۶. حریم خصوصی و انطباق

- شمارهٔ موبایل در لاگ‌ها ماسک می‌شود (`0912***4567`).
- IP به‌صورت `VARCHAR(45)` ذخیره و پس از ۹۰ روز ناشناس‌سازی می‌شود.
- ابزار خروجی/حذف داده مطابق قلاب‌های حریم خصوصی وردپرس (`wp_privacy_personal_data_exporters` / `_erasers`) — با استثنای دادهٔ مالی که الزام نگهداری دارد.
- افزونه هیچ داده‌ای به سرویس ثالث غیر از تأمین‌کنندهٔ SMM و درگاه پرداخت ارسال نمی‌کند. بدون Google Fonts، بدون CDN.

## ۷. برنامهٔ تست امنیتی (فاز F8)

1. تست دستی IDOR روی هر endpoint دارای `{id}` با دو حساب کاربری
2. تست همزمانی: ۵۰ درخواست موازی `POST /orders` با موجودی کافی برای یک سفارش ⇒ باید دقیقاً یک سفارش ثبت شود
3. بازپخش (replay) callback درگاه ⇒ باید فقط یک بار شارژ کند
4. Fuzzing فیلد `link` با ۲۰۰ payload (SSRF، XSS، path traversal، null byte)
5. brute-force روی OTP ⇒ باید در تلاش ششم قفل شود
6. اسکن با WPScan + `composer audit` + `npm audit`
7. مرور خودکار PHPCS با `WordPress.Security.*` در سطح `error`
