# ClickPop — کارایی و سئو

## ۱. بودجهٔ کارایی (اجباری در CI)

| متریک | هدف | سقف شکست build |
|---|---|---|
| LCP (موبایل، 4G شبیه‌سازی‌شده) | < ۲.۰s | ۲.۵s |
| INP | < ۱۵۰ms | ۲۰۰ms |
| CLS | < ۰.۰۵ | ۰.۱ |
| TTFB | < ۲۰۰ms (کش‌شده) | ۶۰۰ms |
| CSS صفحهٔ فرود (gz) | ≤ ۳۵KB | ۵۰KB |
| JS صفحهٔ فرود (gz) | ≤ ۲۰KB | ۳۵KB |
| JS داشبورد (gz) | ≤ ۴۵KB | ۶۰KB |
| تعداد درخواست صفحهٔ فرود | ≤ ۲۰ | ۳۰ |
| Lighthouse Performance | ≥ ۹۵ | ۹۰ |
| Lighthouse Accessibility | ۱۰۰ | ۹۵ |

## ۲. راهبردهای کلیدی

**فونت** — Vazirmatn Variable و Inter Variable، self-hosted، `woff2`، **subset‌شده** (فارسی/عربی + لاتین پایه). فقط فایل وزن اصلی `preload` می‌شود. `font-display: swap` + `size-adjust` برای حذف پرش چیدمان. بدون Google Fonts (هم کارایی، هم حریم خصوصی).

**CSS** — CSS بحرانی هر قالب در build استخراج و inline می‌شود؛ باقی با `media="print" onload="this.media='all'"` بارگذاری می‌شود. بدون فریم‌ورک؛ Grid/Flex بومی. Logical properties ⇒ یک فایل برای RTL و LTR.

**JS** — بدون jQuery در فرانت (dequeue می‌شود مگر افزونه‌ای واقعاً لازم داشته باشد). ماژول‌های ESM بومی، `type="module"` + `defer`. کد داشبورد فقط در صفحات داشبورد enqueue می‌شود. بدون فریم‌ورک SPA — رندر سمت سرور + جزیره‌های تعاملی.

**تصویر** — AVIF با fallback به WebP، `<picture>` + `srcset`، همیشه `width`/`height` صریح، `loading="lazy"` روی همه به‌جز LCP، `fetchpriority="high"` روی تصویر Hero.

**بدون صفحه‌ساز** — صفحه‌ها قالب PHP‌اند. این تنها راه رسیدن به بودجهٔ بالا بود: صفحه‌سازها به‌ازای هر صفحه چند ده کیلوبایت CSS/JS اضافه و DOM چندلایه تولید می‌کنند که مستقیم روی LCP و INP می‌نشیند.

**دیتابیس** — ایندکس‌های ترکیبی سند اسکیما؛ بدون `SELECT *` در مسیر داغ؛ صفحه‌بندی keyset (`WHERE id < ?`) به‌جای `OFFSET` بزرگ در تاریخچهٔ سفارش؛ شمارش کل به‌صورت کش‌شده و تقریبی در صفحات عمیق.

**سرور** — Nginx FastCGI cache یا افزونهٔ کش با bypass روی کوکی `cp_logged_in`؛ Brotli؛ `Cache-Control: public, max-age=31536000, immutable` روی asset نسخه‌دار؛ OPcache با `validate_timestamps=0` در پروداکشن؛ Redis برای object cache.

**پاک‌سازی وردپرس** — حذف emoji script، oEmbed discovery، `wp-embed.js`، REST link header، `generator`، XML-RPC، pingback، و `wp_resource_hints` غیرلازم.

## ۳. سئو

### JSON-LD — یک گراف واحد

به‌جای چند بلوک پراکنده، یک `@graph` در `wp_head` با نودهای به‌هم‌ارجاع‌دهنده:

| نود | کجا | نکته |
|---|---|---|
| `Organization` | همه‌جا | `name`, `alternateName` (کلیک پاپ), `url`, `logo` (SVG + PNG), `sameAs`, `contactPoint` |
| `WebSite` | همه‌جا | + `potentialAction: SearchAction`, `inLanguage` |
| `BreadcrumbList` | همه‌جا جز خانه | هم‌راستا با ناوبری بصری |
| `Service` | صفحهٔ سرویس | `serviceType`, `provider`, `areaServed`, `offers: Offer` با `price` و `priceCurrency: IRR` |
| `Product` + `Offer` | صفحهٔ سرویس (اختیاری) | `availability`, `priceValidUntil` |
| `FAQPage` | صفحاتی که ویجت FAQ دارند | **فقط** پرسش‌هایی که واقعاً روی صفحه دیده می‌شوند |
| `AggregateRating` | ❌ | **تولید نمی‌شود مگر نظرات واقعی و قابل تأیید وجود داشته باشد** — Rich Result جعلی ریسک جریمهٔ دستی گوگل دارد |

قیمت در Schema از همان `sale_rate` می‌آید که کاربر می‌بیند؛ مغایرت قیمت schema و صفحه، خطای Search Console است.

### ساختار HTML

`<header>` → `<nav aria-label="اصلی">` → `<main id="main">` → `<article>`/`<section>` با `<h1>` یکتا و سلسله‌مراتب پیوستهٔ h2/h3 → `<footer>`. لینک «پرش به محتوا». `aria-current="page"`. لندمارک‌های نام‌گذاری‌شده.

### چندزبانه

- `<html lang="fa-IR" dir="rtl">` — `dir` از locale مشتق می‌شود، نه هاردکد.
- `hreflang` متقابل برای fa/en/ru/zh + `x-default`.
- ساختار URL: `clickpop.ir/` (fa) · `clickpop.ir/en/` · `/ru/` · `/zh/`.
- اعداد: نمایش فارسی با CSS/فرمترِ نمایشی؛ مقادیر ماشینی (`<time datetime>`، schema، مقادیر فرم) همیشه لاتین.

### نقشهٔ سایت و ایندکس

- افزودن `cp_service_page` و taxonomy برند به sitemap هستهٔ وردپرس (اگر Rank Math فعال باشد، به آن واگذار می‌شود).
- `noindex` روی: `/dashboard/*`، صفحات callback پرداخت، نتایج جست‌وجوی داخلی، صفحه‌بندی‌های عمیق (`page/3+` آرشیو نازک).
- `robots.txt` با `Disallow: /wp-json/clickpop/` (مسیرهای خصوصی) و اجازهٔ کامل به asset.
- Canonical روی همهٔ صفحات؛ حذف پارامترهای ردیابی از canonical.

## ۴. دسترس‌پذیری (WCAG 2.2)

- کنتراست: متن عادی ≥ 7:1 (AAA) در هر دو تم؛ متن بزرگ ≥ 4.5:1. **`#1668FF` روی سفید فقط 4.2:1 است** ⇒ برای متن ریز از `--cp-brand-700` (`#0B44D6`) استفاده می‌شود، نه رنگ برند خام.
- تمرکز کیبورد قابل‌رؤیت: `:focus-visible` با outline دو رنگ (روشن/تیره) و `outline-offset`.
- هدف لمسی ≥ ۴۴×۴۴px.
- `prefers-reduced-motion: reduce` ⇒ خاموشی کامل انیمیشن شمارنده و ترنزیشن‌ها.
- فرم‌ها: `<label>` واقعی (نه placeholder به‌جای برچسب)، `aria-describedby` برای راهنما و خطا، `aria-live="polite"` برای پیام نتیجه.
- جدول سفارش‌ها: `<caption>`، `<th scope>`، و نمای کارتی در موبایل به‌جای اسکرول افقی.
- تست خودکار axe-core در CI + تست دستی با NVDA و صفحه‌کلید تنها.
