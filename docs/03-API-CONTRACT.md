# ClickPop — قراردادهای API

## بخش الف — API تأمین‌کننده (followeran.ir / SMM Panel v2)

مرجع: `https://followeran.ir/api-docs/` (نسخهٔ آفلاین در `docs/vendor/followeran-api-v2.md`).

| مورد | مقدار |
|---|---|
| Endpoint | `https://my.followeran.ir/api/v2` |
| Method | `POST` · `application/x-www-form-urlencoded` |
| احراز هویت | فیلد `key` در بدنه |
| فرمت پاسخ | JSON |

### الف-۱ `action=services`

پاسخ: آرایه‌ای از اشیاء.

| فیلد | نوع | یادداشت پیاده‌سازی |
|---|---|---|
| `service` | string | شناسهٔ تأمین‌کننده → `cp_services.remote_service_id` |
| `name` | string | نام فارسی |
| `type` | string | `default` و … → قابلیت‌های فرم سفارش |
| `rate` | int | **قیمت به ازای ۱۰۰۰ واحد، تومان** (فرض A4 — نیاز به تأیید) |
| `min` / `max` | int | محدودهٔ مقدار — اعتبارسنجی سمت سرور |
| `service_rate` | string | معنا نامعلوم (`"3.110"`) — خام ذخیره، در قیمت‌گذاری استفاده نمی‌شود |
| `desc` | string | توضیح — با `wp_kses` پاک‌سازی |
| `template_link` | string | الگوی لینک نمونه برای placeholder فرم |
| `dripfeed` `refill` `cancel` | bool | نمایش قابلیت‌ها در UI |
| `category` | string | نام دستهٔ فارسی → `cp_categories` |
| `brand` | string | «اینستاگرام » — **دارای فاصلهٔ انتهایی؛ اجباراً `trim()`** |

نکات دفاعی:
- `brand` و `category` رشتهٔ آزاد فارسی‌اند ⇒ `trim()` + نرمال‌سازی (`ی`/`ي`، `ک`/`ك`، نیم‌فاصله) پیش از ساخت slug، وگرنه دسته‌های تکراری ساخته می‌شود.
- تولید slug با `sanitize_title()` روی متن فارسی خروجی خالی می‌دهد ⇒ از نگاشت ثابت برند + هش کوتاه استفاده می‌شود.
- پاسخ ممکن است هزاران آیتم باشد ⇒ پردازش دسته‌ای با `LIMIT` و بدون بارگذاری کل در حافظه به‌صورت entity.

### الف-۲ `action=add`

پارامترها: `key`, `action=add`, `service`, `link`, `quantity`, `is_test` (اختیاری، `0|1`).
پاسخ موفق: `{"status":"success","order":151}`.

- در محیط staging مقدار `is_test=1` اجباری می‌شود (ثابت `CLICKPOP_PROVIDER_TEST_MODE`).
- تایم‌اوت ۱۵ ثانیه. Retry **فقط** روی خطای اتصال/DNS و حداکثر یک بار. روی پاسخ HTTP معتبر با خطای تجاری، هرگز retry نمی‌شود.
- هر پاسخی که `status !== 'success'` باشد یا `order` نداشته باشد، خطای تجاری تلقی و سفارش `failed` + بازگشت کامل وجه می‌شود.

### الف-۳ `action=status`

سند تأمین‌کننده در این بخش **متناقض** است:
- نمونهٔ تکی: `key` + `order=ORDER_ID`
- نمونهٔ گروهی: `api` + `orders="34,35,38"` (نام پارامتر کلید هم `api` نوشته شده)
- جدول پارامترها: `key` + `order` با مقادیر جداشده با ویرگول

**تصمیم:** درایور هر دو حالت را پشتیبانی می‌کند و در نصب، یک probe یک‌باره اجرا می‌شود که تعیین می‌کند کدام ترکیب پاسخ معتبر می‌دهد؛ نتیجه در `cp_providers` ذخیره می‌شود. تا آن زمان مسیر تکی (`key`+`order`) مسیر امن پیش‌فرض است. — **Q5**

پاسخ:
```json
{ "order":8598432, "status":"Completed", "charge":"250",
  "start_count":null, "remains":0, "created_at":"1404-08-23 00:27:49",
  "service":637, "service_name":"بازدید تلگرام (5 پست آخر)" }
```
- `created_at` **تاریخ شمسی** است ⇒ هرگز به `strtotime()` داده نمی‌شود. تنها به‌عنوان رشتهٔ نمایشی یا پس از تبدیل صریح با `Support\Jalali` استفاده می‌شود. زمان‌بندی داخلی همیشه از `cp_orders.created_at` (UTC) می‌آید.
- `charge` رشته است ⇒ کست صریح.
- `start_count` می‌تواند `null` باشد.

### الف-۴ نگاشت وضعیت

| وضعیت تأمین‌کننده | وضعیت داخلی | اقدام مالی |
|---|---|---|
| `Pending`, `Awaiting` | `processing` | — |
| `In progress`, `Processing` | `in_progress` | — |
| `Completed` | `completed` | — |
| `Partial` | `partial` | بازگشت نسبی: `ceil(sale_rate × remains / rate_unit)` |
| `Canceled`, `Cancelled` | `canceled` | بازگشت کامل |
| `Refunded` | `refunded` | بازگشت کامل (اگر قبلاً انجام نشده) |
| ناشناخته | بدون تغییر | لاگ هشدار + alert ادمین |

قاعدهٔ سخت: هر بازگشت وجه ابتدا `cp_orders.refunded` را با `UPDATE … WHERE refunded = <مقدار قبلی>` قفل می‌کند؛ در غیر این صورت دو اجرای همزمان کرون می‌تواند دو بار بازگشت وجه بزند.

### الف-۵ `action=balance`

`{"status":"success","balance":1526952,"currency":"IRT"}` → هر ۵ دقیقه کش، نمایش در مانیتور سلامت، هشدار زیر آستانه.

### الف-۶ تاب‌آوری

- **Circuit breaker:** ۵ خطای پیاپی ⇒ مدار به مدت ۵ دقیقه باز؛ در این بازه ثبت سفارش جدید با پیام روشن رد می‌شود (به‌جای کسر پول و شکست).
- **Backoff نمایی** روی کارهای کرون، نه روی درخواست کاربر.
- تمام تماس‌ها از `wp_remote_post()` با `sslverify => true`، `user-agent` مشخص و `timeout` صریح.

---

## بخش ب — REST داخلی (`/wp-json/clickpop/v1`)

### قواعد عمومی

- احراز هویت: کوکی وردپرس + هدر `X-WP-Nonce` (نانس `wp_rest`).
- هر route اجباراً `permission_callback` دارد. `__return_true` **ممنوع** است مگر برای endpointهای صراحتاً عمومی.
- هر route یک `args` schema با `validate_callback` + `sanitize_callback` دارد.
- شکل خطای یکنواخت:
  ```json
  { "code":"cp_insufficient_balance", "message":"موجودی کافی نیست.",
    "data":{ "status":402, "required":150000, "available":90000 } }
  ```
- تمام پاسخ‌های حاوی دادهٔ کاربر: `Cache-Control: private, no-store`.
- محدودیت نرخ (پنجرهٔ کشویی، بر اساس user_id + IP):

| مسیر | سقف |
|---|---|
| `POST /orders` | ۱۰ / دقیقه |
| `POST /auth/otp` | ۳ / ۵ دقیقه · ۱۰ / ساعت / IP |
| `POST /wallet/topup` | ۵ / دقیقه |
| `POST /tickets/*` | ۲۰ / ساعت |
| `GET  /services*` | ۱۲۰ / دقیقه |

### فهرست endpointها

| متد | مسیر | مجوز | شرح |
|---|---|---|---|
| GET | `/services/tree` | عمومی | برند → دسته → سرویس (کش ۵ دقیقه، ETag) |
| GET | `/services/{id}` | عمومی | جزئیات + قیمت فروش |
| POST | `/services/quote` | ورود لازم | محاسبهٔ قیمت سمت سرور برای `{service_id, quantity}` |
| GET | `/orders` | `clickpop_view_own_orders` | صفحه‌بندی، فیلتر وضعیت/بازه |
| GET | `/orders/{id}` | مالکیت | |
| POST | `/orders` | `clickpop_place_order` | `{service_id, link, quantity, idempotency_key}` |
| GET | `/wallet` | ورود لازم | موجودی + رزرو |
| GET | `/wallet/transactions` | ورود لازم | دفتر کل کاربر |
| POST | `/wallet/topup` | `clickpop_topup_wallet` | `{amount, gateway}` → `{redirect_url}` |
| GET | `/wallet/callback/{gateway}` | عمومی | بازگشت درگاه؛ تأیید server-to-server |
| GET/POST | `/tickets` | ورود لازم | فهرست / ایجاد |
| GET/POST | `/tickets/{id}/messages` | مالکیت | خواندن / پاسخ |
| GET | `/profile` · POST `/profile` | ورود لازم | |
| GET | `/profile/sessions` · DELETE `/profile/sessions/{id}` | ورود لازم | مدیریت نشست فعال |
| POST | `/auth/otp` · `/auth/verify` | عمومی + throttle | |

### قرارداد `POST /orders`

درخواست:
```json
{ "service_id": 412, "link": "https://instagram.com/username",
  "quantity": 1000, "idempotency_key": "b3f1…-uuid-v4" }
```

اعتبارسنجی سمت سرور (به همین ترتیب، هر شکست ⇒ توقف):
1. نانس + قابلیت + محدودیت نرخ
2. `idempotency_key` معتبر UUIDv4 و تکراری نباشد
3. سرویس وجود دارد و `status='active'`
4. `min_qty <= quantity <= max_qty`
5. لینک: طول ≤ ۵۰۰، اسکیمای `https` (یا `http` مجاز؟ → فقط `https`)، هاست در allowlist همان برند، بدون credential در URL، بدون IP literal
6. **قیمت مجدداً از دیتابیس محاسبه می‌شود** — هر مبلغی در بدنهٔ درخواست نادیده گرفته می‌شود
7. برداشت اتمیک از کیف پول
8. فراخوان تأمین‌کننده (خارج از تراکنش DB)

پاسخ موفق `201`:
```json
{ "id": 8123, "status": "processing", "charge": 150000,
  "charge_display": "۱۵٬۰۰۰ تومان", "balance_after": 640000 }
```

خطاهای شناخته‌شده: `cp_invalid_nonce` (403) · `cp_rate_limited` (429) · `cp_service_unavailable` (409) · `cp_quantity_out_of_range` (422) · `cp_invalid_link` (422) · `cp_insufficient_balance` (402) · `cp_provider_unavailable` (503) · `cp_duplicate_request` (200 با نتیجهٔ سفارش قبلی).

### allowlist اعتبارسنجی لینک

| برند | هاست‌های مجاز |
|---|---|
| instagram | `instagram.com`, `www.instagram.com` |
| youtube | `youtube.com`, `www.youtube.com`, `youtu.be`, `m.youtube.com` |
| telegram | `t.me`, `telegram.me` |
| tiktok | `tiktok.com`, `www.tiktok.com`, `vm.tiktok.com` |
| twitter/x | `twitter.com`, `x.com` |
| spotify | `open.spotify.com` |
| soundcloud | `soundcloud.com` |

بررسی هاست با `wp_parse_url()` + تطبیق **دقیق** روی allowlist (نه `strpos`). `strpos($link,'instagram.com')` با `https://evil.com/?x=instagram.com` دور زده می‌شود.

---

## بخش ج — درگاه پرداخت

| مرحله | قاعده |
|---|---|
| شروع | مبلغ **از سرور**؛ ردیف `cp_transactions(status='initiated')` پیش از ریدایرکت ساخته می‌شود |
| authority | بلافاصله ذخیره؛ `UNIQUE(gateway, authority)` |
| بازگشت | پارامترهای GET فقط برای *یافتن* تراکنش‌اند، نه برای تأیید |
| تأیید | تماس server-to-server با مبلغ ذخیره‌شده در DB؛ اگر مبلغ برگشتی ≠ مبلغ DB ⇒ رد + هشدار امنیتی |
| idempotency | `UPDATE cp_transactions SET status='succeeded' WHERE id=? AND status='initiated'` ⇒ `affected_rows=0` یعنی قبلاً پردازش شده |
| اعتبار مبلغ | حداقل/حداکثر شارژ قابل تنظیم؛ رد مقادیر غیرصحیح یا منفی |
| لاگ | کد پیگیری بانک در `gateway_ref`؛ نمایش در تاریخچهٔ کاربر |
