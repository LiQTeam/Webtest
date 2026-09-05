# ClickPop — ساختار فایل پروژه

مخزن به‌صورت **monorepo** است: تم و افزونه کنار هم توسعه داده می‌شوند، اما هرکدام مستقل بسته‌بندی (zip) و مستقل نصب می‌شوند.

```
clickpop/
├── .github/workflows/
│   ├── ci.yml                       PHPCS · PHPStan · PHPUnit · ESLint · Stylelint
│   ├── lighthouse.yml               بودجهٔ کارایی روی PR
│   └── release.yml                  ساخت zip تم و افزونه از روی تگ
├── .editorconfig  .gitignore  .nvmrc
├── composer.json                    ابزار توسعه (WPCS، PHPStan، PHPUnit، Brain Monkey)
├── package.json                     Vite · Sass · ESLint · Stylelint · axe-core
├── phpcs.xml.dist  phpstan.neon.dist  phpunit.xml.dist  .wp-env.json
├── README.md
├── brand/                           کیت برند (SVG، فاوآیکن، BRAND.md)
├── docs/                            ← اسناد معماری (همین پوشه)
└── src/
    ├── theme/clickpop/              ← تم
    └── plugin/clickpop-core/        ← افزونه
```

---

## ۱. تم — `src/theme/clickpop/`

```
clickpop/
├── style.css                        فقط هدر تم (بدون یک خط استایل واقعی)
├── theme.json                       پالت و تایپوگرافی برای ادیتور بلوک (هم‌راستا با توکن‌ها)
├── functions.php                    فقط bootstrap: require کردن inc/*
├── screenshot.png
│
├── inc/
│   ├── setup.php                    add_theme_support · منوها · اندازهٔ تصاویر · سایدبار
│   ├── assets.php                   enqueue · preload فونت · defer/async · dequeue بلوت
│   ├── theme-mode.php               اسکریپت inline ضدفلیکر + کنترل تاگل
│   ├── i18n.php                     بارگذاری متن‌دامنه · انتخاب فونت بر اساس locale · dir
│   ├── customizer.php               بازنویسی توکن‌های برند (رنگ اصلی، شعاع، شدت شیشه)
│   ├── template-tags.php
│   ├── nav-walker.php               منوی معنایی و قابل‌دسترس (aria-current, aria-expanded)
│   ├── content.php                  متن‌های صفحهٔ اصلی از پنل «محتوای سایت»
│   ├── seo/
│   │   ├── schema.php               سازندهٔ @graph (Organization · WebSite · …)
│   │   ├── meta.php                 OG/Twitter — در حضور Rank Math/Yoast خودکار خاموش
│   │   ├── breadcrumbs.php
│   │   └── sitemap.php              افزودن cp_service_page به sitemap هستهٔ وردپرس
│   ├── perf/
│   │   ├── critical-css.php         تزریق CSS بحرانی هر قالب + بارگذاری غیرمسدود بقیه
│   │   ├── resource-hints.php       preconnect · preload · fetchpriority
│   │   └── cleanup.php              حذف emoji · oEmbed · generator · XML-RPC
│   └── security/headers.php         CSP (nonce-based) · HSTS · X-Frame · Permissions-Policy
│
│
├── template-parts/
│   ├── header/{brand,nav,theme-toggle,lang-switcher,account-chip}.php
│   ├── content/{card-service,card-post,empty-state,skeleton}.php
│   └── footer/{columns,legal,socials}.php
│
├── templates/                       قالب‌های صفحه
│   ├── page-dashboard.php           میزبان shell داشبورد (بدنه از افزونه می‌آید)
│   ├── page-services.php
│   └── page-services.php            فهرست کامل سرویس‌ها و قیمت‌ها
│
├── single-cp_service_page.php  archive-cp_service_page.php
├── front-page.php  index.php  404.php  search.php  header.php  footer.php
│
├── assets/
│   ├── src/
│   │   ├── scss/
│   │   │   ├── 00-tokens/           _color.scss _space.scss _type.scss _radius.scss
│   │   │   │                        _shadow.scss _motion.scss _z.scss _breakpoints.scss
│   │   │   ├── 01-base/             _reset.scss _root.scss _typography.scss _a11y.scss
│   │   │   ├── 02-layout/           _grid.scss _container.scss _section.scss
│   │   │   ├── 03-components/       _button.scss _card.scss _input.scss _badge.scss
│   │   │   │                        _table.scss _modal.scss _tabs.scss _accordion.scss
│   │   │   │                        _toast.scss _skeleton.scss _glass.scss
│   │   │   ├── 04-sections/         استایل بخش‌های صفحهٔ اصلی
│   │   │   ├── 05-pages/            _front.scss _dashboard.scss _auth.scss
│   │   │   └── main.scss
│   │   └── js/
│   │       ├── theme-toggle.js      ۳ حالته: light · dark · system
│   │       ├── nav.js               منوی موبایل + focus trap
│   │       ├── accordion.js         WAI-ARIA کامل
│   │       ├── counter.js           IntersectionObserver
│   │       └── main.js
│   ├── dist/                        خروجی build (در git نیست)
│   ├── fonts/                       Vazirmatn-Variable.woff2 (subset) · Inter-Variable.woff2
│   ├── brand/                       لوگو SVG · فاوآیکن‌ها
│   └── icons/                       آیکن‌های Lucide به‌صورت SVG sprite
│
└── languages/  clickpop.pot · fa_IR.po/.mo · en_US · ru_RU · zh_CN
```

**قواعد سخت تم**

- هیچ فایلی در تم `$wpdb` را import نمی‌کند.
- هیچ فایلی در تم `cp_` را به‌عنوان پیشوند جدول نمی‌شناسد.
- هر خروجی از افزونه پیش از چاپ `esc_*` می‌شود، حتی اگر افزونه ادعا کند امن است.
- هیچ CDN خارجی و هیچ صفحه‌سازی: فونت، آیکن و اسکریپت همگی self-hosted و صفحه‌ها قالب PHP‌اند.

---

## ۲. افزونه — `src/plugin/clickpop-core/`

```
clickpop-core/
├── clickpop-core.php                هدر افزونه · گاردهای نسخه PHP/WP · ثابت‌ها · bootstrap
├── uninstall.php                    حذف امن (پشت گارد گزینهٔ «حذف داده‌ها هنگام حذف افزونه»)
├── composer.json                    PSR-4: ClickPop\Core\  (autoload بهینه‌شده در انتشار)
├── readme.txt
│
├── src/
│   ├── Plugin.php                   کانتینر · ثبت سرویس‌ها · هوک‌های چرخهٔ حیات
│   │
│   ├── Contracts/
│   │   ├── ProviderInterface.php        services() add() status() balance()
│   │   ├── GatewayInterface.php         request() verify() refund()
│   │   ├── RepositoryInterface.php
│   │   └── NotifierInterface.php        sms() email() telegram()
│   │
│   ├── Support/
│   │   ├── Container.php            DI سبک (بدون وابستگی خارجی)
│   │   ├── Config.php               خواندن گزینه‌ها با پیش‌فرض و کست نوع
│   │   ├── Money.php                Value Object · ریال صحیح · بدون float
│   │   ├── Jalali.php               تبدیل میلادی↔شمسی (بدون کتابخانهٔ خارجی)
│   │   ├── Encryption.php           AES-256-GCM برای کلیدهای API
│   │   ├── Logger.php               سطح‌بندی‌شده · بدون PII · چرخش خودکار
│   │   ├── RateLimiter.php          پنجرهٔ کشویی روی Redis/transient
│   │   ├── Nonce.php                کمکی‌های CSRF
│   │   ├── Validator.php            اعتبارسنجی لینک هر پلتفرم (regex + host allowlist)
│   │   ├── Assets.php               enqueue شرطی · versioning با filemtime
│   │   └── Str.php  Arr.php
│   │
│   ├── Database/
│   │   ├── Installer.php            dbDelta · نسخهٔ اسکیما · seed اولیه
│   │   ├── Schema.php               تعریف متمرکز DDL
│   │   └── Migrations/
│   │       ├── Migration_1_0_0.php
│   │       └── MigrationRunner.php
│   │
│   ├── Entities/                    آبجکت‌های تایپ‌دار (readonly تا حد امکان)
│   │   ├── Service.php Order.php Transaction.php Ticket.php
│   │   ├── PricingRule.php Provider.php Wallet.php
│   │
│   ├── Repositories/                ← تنها لایه‌ای که SQL می‌نویسد
│   │   ├── AbstractRepository.php   prepare · pagination · شمارش کششده
│   │   ├── ServiceRepository.php  CategoryRepository.php
│   │   ├── OrderRepository.php    TransactionRepository.php
│   │   ├── WalletRepository.php   TicketRepository.php
│   │   ├── PricingRuleRepository.php  AuditRepository.php  ApiLogRepository.php
│   │
│   ├── Providers/
│   │   ├── ProviderManager.php      رجیستری · انتخاب تأمین‌کنندهٔ فعال
│   │   ├── AbstractProvider.php     HTTP · timeout · retry · circuit breaker · لاگ
│   │   ├── FollowerAnProvider.php   نگاشت اختصاصی followeran.ir
│   │   ├── SmmPanelV2Provider.php   درایور عمومی SMM API v2
│   │   └── Dto/{ServiceDto,OrderDto,StatusDto,BalanceDto}.php
│   │
│   ├── Pricing/
│   │   ├── MarginEngine.php         حل قاعده service>category>brand>global
│   │   ├── PriceCalculator.php      تابع خالص · قابل تست واحد
│   │   ├── CurrencyConverter.php    نرخ دستی/خودکار · تاریخچهٔ نرخ
│   │   └── Rounding.php
│   │
│   ├── Sync/
│   │   ├── Scheduler.php            Action Scheduler + fallback به WP-Cron
│   │   ├── ServiceSync.php          diff مبتنی بر hash · آرشیو به‌جای حذف
│   │   ├── OrderStatusSync.php      دسته‌ای · backoff تطبیقی
│   │   ├── OrphanReconciler.php     تعیین تکلیف سفارش‌های pending_verify
│   │   ├── LedgerReconciler.php     مغایرت‌گیری شبانهٔ کیف پول
│   │   └── BalanceMonitor.php       هشدار موجودی کم تأمین‌کننده
│   │
│   ├── Orders/
│   │   ├── OrderService.php         Saga ثبت سفارش
│   │   ├── OrderStateMachine.php    جدول گذارهای مجاز
│   │   ├── RefundService.php        بازگشت کامل/نسبی
│   │   └── LinkValidator.php        instagram/youtube/telegram/tiktok/…
│   │
│   ├── Wallet/
│   │   ├── WalletService.php        debit() credit() hold() release()  ← اتمیک
│   │   ├── Ledger.php               نوشتن append-only + balance_after
│   │   └── AdjustmentService.php    تعدیل دستی ادمین (اجباراً با دلیل + audit)
│   │
│   ├── Gateways/
│   │   ├── GatewayManager.php
│   │   ├── AbstractGateway.php      idempotency · قفل authority · لاگ
│   │   ├── ZarinPalGateway.php  IdPayGateway.php  NextPayGateway.php
│   │   └── PaymentController.php    شروع · callback · تأیید server-to-server
│   │
│   ├── Tickets/
│   │   ├── TicketService.php  MessageService.php
│   │   ├── DepartmentRegistry.php   فنی · مالی · فروش
│   │   ├── CannedResponses.php
│   │   └── AttachmentHandler.php    whitelist mime · نام تصادفی · سقف حجم
│   │
│   ├── Auth/
│   │   ├── OtpService.php           تولید · hash · TTL · سقف تلاش
│   │   ├── TwoFactor.php            TOTP (آماده، پشت فلگ)
│   │   ├── SessionManager.php       نشست‌های فعال · ابطال از راه دور
│   │   └── LoginThrottle.php
│   │
│   ├── Notifications/
│   │   ├── SmsNotifier.php          درایورپذیر (Kavenegar/SMS.ir/…)
│   │   ├── EmailNotifier.php        قالب‌های HTML سبک
│   │   └── Events.php               نگاشت رویداد→پیام
│   │
│   ├── Http/
│   │   ├── Rest/
│   │   │   ├── RestBootstrap.php    ثبت namespace clickpop/v1
│   │   │   ├── AbstractController.php  اعتبارسنجی · مجوز · شکل خطای یکنواخت
│   │   │   ├── ServicesController.php  OrdersController.php
│   │   │   ├── WalletController.php    TicketsController.php
│   │   │   ├── AuthController.php      ProfileController.php
│   │   │   └── Schema/ (JSON Schema هر endpoint)
│   │   └── Middleware/
│   │       ├── NonceGuard.php  CapabilityGuard.php  RateLimitGuard.php
│   │       └── JsonResponse.php
│   │
│   ├── Admin/
│   │   ├── Menu.php
│   │   ├── Pages/{Dashboard,Providers,Services,Pricing,Orders,Transactions,
│   │   │          Tickets,Settings,Logs,Health}Page.php
│   │   ├── ListTables/{Orders,Services,Transactions,Tickets}Table.php
│   │   ├── Settings/{Fields,Sections,Sanitizer}.php
│   │   ├── HealthMonitor.php        موجودی · تأخیر · نرخ خطا · وضعیت کرون
│   │   ├── BulkActions.php
│   │   └── Notices.php
│   │
│   ├── Frontend/
│   │   ├── Shortcodes.php           [clickpop_dashboard] [clickpop_order_form] …
│   │   ├── DashboardRenderer.php    رندر shell · بارگذاری شرطی asset
│   │   │   └── Rewrites.php             /dashboard/{orders,wallet,tickets,profile}
│   │
│   ├── Api/Facade.php               قرارداد پایدار برای مصرف تم
│   └── Cli/Commands.php
│
├── templates/                       قابل بازنویسی از مسیر تم: /clickpop/…
│   ├── dashboard/{layout,overview,new-order,orders,wallet,tickets,profile}.php
│   ├── partials/{order-row,status-badge,txn-row,ticket-thread,empty}.php
│   └── emails/{order-placed,order-completed,topup-success,ticket-reply}.php
│
├── assets/
│   ├── src/js/{dashboard,order-form,wallet,tickets,profile,api-client}.js
│   ├── src/scss/{dashboard,admin}.scss
│   └── dist/
│
└── languages/  clickpop-core.pot · fa_IR · en_US · ru_RU · zh_CN
```

---

## ۳. قرارداد نام‌گذاری

| مورد | قاعده | نمونه |
|---|---|---|
| Namespace | `ClickPop\Core\<Module>` | `ClickPop\Core\Orders\OrderService` |
| پیشوند جدول | `{$wpdb->prefix}cp_` | `wp_cp_orders` |
| پیشوند گزینه | `clickpop_` | `clickpop_provider_settings` |
| هوک | `clickpop/<module>/<event>` | `clickpop/order/placed` |
| REST | `clickpop/v1/<resource>` | `clickpop/v1/orders` |
| کلاس CSS | BEM با پیشوند `cp-` | `cp-card__title--muted` |
| توکن CSS | `--cp-<group>-<name>` | `--cp-color-brand-600` |
| متن‌دامنه | `clickpop` (تم) · `clickpop-core` (افزونه) | `__( 'سفارش', 'clickpop-core' )` |
| قابلیت | `clickpop_<verb>_<object>` | `clickpop_manage_orders` |

## ۴. نقش‌ها و قابلیت‌ها

| نقش | قابلیت‌ها |
|---|---|
| `clickpop_customer` | `clickpop_place_order`, `clickpop_view_own_orders`, `clickpop_topup_wallet`, `clickpop_open_ticket` |
| `clickpop_agent` | بالا + `clickpop_manage_tickets`, `clickpop_view_orders` |
| `clickpop_manager` | بالا + `clickpop_manage_orders`, `clickpop_manage_pricing`, `clickpop_adjust_balance` |
| `administrator` | همه + `clickpop_manage_providers`, `clickpop_view_audit_log` |

`clickpop_adjust_balance` جداگانه است تا اپراتور پشتیبانی نتواند بدون مجوز صریح موجودی دستکاری کند. هر استفاده از آن اجباراً در `cp_audit_log` با دلیل متنی ثبت می‌شود.
