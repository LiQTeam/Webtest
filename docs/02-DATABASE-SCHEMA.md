# ClickPop — طرح دیتابیس

- موتور: **InnoDB** (اجباری — به تراکنش و قفل ردیفی نیاز داریم؛ MyISAM قابل قبول نیست).
- Charset: `utf8mb4` / Collation: `utf8mb4_unicode_520_ci`.
- تمام مبالغ: `BIGINT UNSIGNED` بر حسب **ریال**. هیچ `FLOAT`/`DECIMAL` در مسیر پول نیست.
- تمام زمان‌ها: `DATETIME` در **UTC**. تبدیل به شمسی فقط در لایهٔ نمایش.
- `{P}` = `$wpdb->prefix`.

---

## ۱. تأمین‌کنندگان

```sql
CREATE TABLE {P}cp_providers (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug          VARCHAR(64)     NOT NULL,
  name          VARCHAR(191)    NOT NULL,
  driver        VARCHAR(64)     NOT NULL DEFAULT 'smm_v2',
  api_url       VARCHAR(255)    NOT NULL,
  api_key_enc   TEXT            NOT NULL,          -- AES-256-GCM، هرگز plaintext
  currency      CHAR(3)         NOT NULL DEFAULT 'IRT',
  rate_unit     SMALLINT UNSIGNED NOT NULL DEFAULT 1000, -- rate به ازای چند واحد
  status        ENUM('active','paused','disabled') NOT NULL DEFAULT 'active',
  balance_cache BIGINT          NOT NULL DEFAULT 0,  -- ریال
  latency_ms    INT UNSIGNED    NULL,
  failure_count SMALLINT UNSIGNED NOT NULL DEFAULT 0, -- برای circuit breaker
  circuit_open_until DATETIME    NULL,
  last_sync_at  DATETIME        NULL,
  created_at    DATETIME        NOT NULL,
  updated_at    DATETIME        NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB;
```

## ۲. دسته‌بندی و سرویس

```sql
CREATE TABLE {P}cp_categories (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider_id   BIGINT UNSIGNED NOT NULL,
  brand         VARCHAR(64)     NOT NULL,      -- اینستاگرام · تلگرام · …
  brand_slug    VARCHAR(64)     NOT NULL,      -- instagram · telegram · …
  name          VARCHAR(191)    NOT NULL,
  slug          VARCHAR(191)    NOT NULL,
  icon          VARCHAR(64)     NULL,
  sort_order    SMALLINT        NOT NULL DEFAULT 0,
  status        ENUM('active','hidden','archived') NOT NULL DEFAULT 'active',
  created_at    DATETIME        NOT NULL,
  updated_at    DATETIME        NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_prov_slug (provider_id, slug),
  KEY idx_brand_status (brand_slug, status, sort_order)
) ENGINE=InnoDB;

CREATE TABLE {P}cp_services (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider_id       BIGINT UNSIGNED NOT NULL,
  remote_service_id VARCHAR(64)     NOT NULL,   -- "697"
  category_id       BIGINT UNSIGNED NOT NULL,
  name              VARCHAR(255)    NOT NULL,
  slug              VARCHAR(255)    NOT NULL,
  type              VARCHAR(32)     NOT NULL DEFAULT 'default',
  cost_rate         BIGINT UNSIGNED NOT NULL,   -- ریال به ازای rate_unit (قیمت تمام‌شده)
  sale_rate         BIGINT UNSIGNED NOT NULL,   -- ریال به ازای rate_unit (ماده‌سازی‌شده)
  min_qty           INT UNSIGNED    NOT NULL,
  max_qty           INT UNSIGNED    NOT NULL,
  dripfeed          TINYINT(1)      NOT NULL DEFAULT 0,
  refill            TINYINT(1)      NOT NULL DEFAULT 0,
  cancel            TINYINT(1)      NOT NULL DEFAULT 0,
  description       TEXT            NULL,
  template_link     VARCHAR(255)    NULL,
  avg_time_minutes  INT UNSIGNED    NULL,
  status            ENUM('active','hidden','archived','review') NOT NULL DEFAULT 'active',
  raw_json          JSON            NULL,       -- پاسخ خام برای عیب‌یابی
  payload_hash      CHAR(40)        NOT NULL,   -- sha1 فیلدهای معنادار → diff ارزان
  sort_order        SMALLINT        NOT NULL DEFAULT 0,
  created_at        DATETIME        NOT NULL,
  updated_at        DATETIME        NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_provider_service (provider_id, remote_service_id),
  KEY idx_cat_status (category_id, status, sort_order),
  KEY idx_status_hash (status, payload_hash)
) ENGINE=InnoDB;
```

> `status='review'` وقتی ست می‌شود که قیمت تأمین‌کننده بیش از آستانه (پیش‌فرض ۲۰٪) جهش کند؛ سرویس تا تأیید ادمین از فروش خارج می‌شود تا زیر قیمت تمام‌شده فروخته نشود.

## ۳. قواعد قیمت‌گذاری

```sql
CREATE TABLE {P}cp_pricing_rules (
  id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  scope        ENUM('global','brand','category','service') NOT NULL,
  scope_ref    VARCHAR(191)    NULL,   -- brand_slug | category_id | service_id
  margin_type  ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  margin_value BIGINT          NOT NULL,  -- percent: صدم‌درصد (1500=15.00%) | fixed: ریال
  min_profit   BIGINT UNSIGNED NOT NULL DEFAULT 0,  -- ریال به ازای rate_unit
  round_step   INT UNSIGNED    NOT NULL DEFAULT 1000, -- ریال
  round_mode   ENUM('ceil','floor','nearest') NOT NULL DEFAULT 'ceil',
  priority     SMALLINT        NOT NULL DEFAULT 0,
  active       TINYINT(1)      NOT NULL DEFAULT 1,
  created_at   DATETIME        NOT NULL,
  updated_at   DATETIME        NOT NULL,
  PRIMARY KEY (id),
  KEY idx_scope (scope, scope_ref, active, priority)
) ENGINE=InnoDB;
```

## ۴. کیف پول و دفتر کل

```sql
CREATE TABLE {P}cp_wallets (
  user_id     BIGINT UNSIGNED NOT NULL,
  balance     BIGINT          NOT NULL DEFAULT 0,  -- ریال · می‌تواند منفی نشود (CHECK اپلیکیشنی)
  held        BIGINT UNSIGNED NOT NULL DEFAULT 0,  -- رزروشده برای سفارش در حال پردازش
  currency    CHAR(3)         NOT NULL DEFAULT 'IRR',
  version     BIGINT UNSIGNED NOT NULL DEFAULT 0,  -- optimistic lock
  updated_at  DATETIME        NOT NULL,
  PRIMARY KEY (user_id)
) ENGINE=InnoDB;

-- دفتر کل: فقط INSERT. هیچ UPDATE/DELETE مجاز نیست.
CREATE TABLE {P}cp_transactions (
  id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id        BIGINT UNSIGNED NOT NULL,
  type           ENUM('deposit','order','refund','adjust','bonus','fee') NOT NULL,
  direction      ENUM('credit','debit') NOT NULL,
  amount         BIGINT UNSIGNED NOT NULL,        -- همیشه مثبت؛ جهت در direction
  balance_after  BIGINT          NOT NULL,        -- برای مغایرت‌گیری
  status         ENUM('initiated','pending','succeeded','failed','canceled') NOT NULL,
  ref_type       VARCHAR(32)     NULL,            -- order | gateway | manual
  ref_id         BIGINT UNSIGNED NULL,
  gateway        VARCHAR(32)     NULL,
  authority      VARCHAR(128)    NULL,            -- شناسهٔ درگاه
  gateway_ref    VARCHAR(128)    NULL,            -- کد پیگیری بانک
  reason         VARCHAR(255)    NULL,            -- اجباری برای type='adjust'
  meta_json      JSON            NULL,
  ip             VARBINARY(16)   NULL,
  created_by     BIGINT UNSIGNED NULL,            -- ادمین در تعدیل دستی
  created_at     DATETIME        NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_authority (gateway, authority),   -- ضد دوباره‌شارژی
  KEY idx_user_time (user_id, created_at),
  KEY idx_type_status (type, status, created_at),
  KEY idx_ref (ref_type, ref_id)
) ENGINE=InnoDB;
```

> `UNIQUE (gateway, authority)` تنها خط دفاعی قطعی در برابر **دوبار شارژ شدن کیف پول با یک تراکنش بانکی** است. حتی اگر callback ده بار صدا زده شود، درج دوم شکست می‌خورد.

## ۵. سفارش‌ها

```sql
CREATE TABLE {P}cp_orders (
  id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id           BIGINT UNSIGNED NOT NULL,
  service_id        BIGINT UNSIGNED NOT NULL,
  provider_id       BIGINT UNSIGNED NOT NULL,
  remote_order_id   VARCHAR(64)     NULL,
  idempotency_key   CHAR(36)        NOT NULL,
  link              VARCHAR(500)    NOT NULL,
  quantity          INT UNSIGNED    NOT NULL,
  sale_rate         BIGINT UNSIGNED NOT NULL,   -- عکس‌برداری از قیمت لحظهٔ ثبت
  cost_rate         BIGINT UNSIGNED NOT NULL,
  charge            BIGINT UNSIGNED NOT NULL,   -- مبلغ کسرشده از کاربر (ریال)
  cost              BIGINT UNSIGNED NOT NULL,   -- هزینهٔ تأمین‌کننده (ریال)
  profit            BIGINT          NOT NULL,   -- charge - cost
  refunded          BIGINT UNSIGNED NOT NULL DEFAULT 0,
  status            ENUM('reserved','pending_verify','processing','in_progress',
                         'completed','partial','canceled','refunded','failed') NOT NULL,
  provider_status   VARCHAR(64)     NULL,       -- رشتهٔ خام تأمین‌کننده
  start_count       INT UNSIGNED    NULL,
  remains           INT UNSIGNED    NULL,
  error_code        VARCHAR(64)     NULL,
  error_message     VARCHAR(500)    NULL,
  sync_attempts     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  next_sync_at      DATETIME        NULL,
  ip                VARBINARY(16)   NULL,
  created_at        DATETIME        NOT NULL,
  updated_at        DATETIME        NOT NULL,
  completed_at      DATETIME        NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_idempotency (idempotency_key),
  UNIQUE KEY uq_remote (provider_id, remote_order_id),
  KEY idx_user_status (user_id, status, created_at),
  KEY idx_sync (status, next_sync_at),
  KEY idx_service (service_id, created_at)
) ENGINE=InnoDB;
```

> `idx_sync (status, next_sync_at)` کوئری کرون همگام‌سازی را روی میلیون‌ها ردیف در حد چند میلی‌ثانیه نگه می‌دارد.

## ۶. تیکتینگ

```sql
CREATE TABLE {P}cp_tickets (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       BIGINT UNSIGNED NOT NULL,
  department    VARCHAR(32)     NOT NULL,     -- technical | billing | sales
  subject       VARCHAR(255)    NOT NULL,
  order_id      BIGINT UNSIGNED NULL,
  status        ENUM('open','answered','pending_user','closed') NOT NULL DEFAULT 'open',
  priority      ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
  assigned_to   BIGINT UNSIGNED NULL,
  last_reply_at DATETIME        NOT NULL,
  last_reply_by ENUM('user','staff') NOT NULL DEFAULT 'user',
  created_at    DATETIME        NOT NULL,
  updated_at    DATETIME        NOT NULL,
  PRIMARY KEY (id),
  KEY idx_user_status (user_id, status, last_reply_at),
  KEY idx_queue (status, department, priority, last_reply_at)
) ENGINE=InnoDB;

CREATE TABLE {P}cp_ticket_messages (
  id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  ticket_id     BIGINT UNSIGNED NOT NULL,
  author_id     BIGINT UNSIGNED NOT NULL,
  is_staff      TINYINT(1)      NOT NULL DEFAULT 0,
  is_internal   TINYINT(1)      NOT NULL DEFAULT 0,  -- یادداشت داخلی؛ هرگز به کاربر نشان داده نمی‌شود
  body          TEXT            NOT NULL,            -- متن ساده؛ در خروجی esc_html + nl2br
  attachments   JSON            NULL,
  created_at    DATETIME        NOT NULL,
  PRIMARY KEY (id),
  KEY idx_ticket_time (ticket_id, created_at)
) ENGINE=InnoDB;
```

> `body` **متن ساده** ذخیره می‌شود، نه HTML. علت: بردار XSI/XSS ذخیره‌شده در پنل ادمین را کاملاً حذف می‌کند. اگر قالب‌بندی لازم شد، Markdown محدود سمت سرور رندر می‌شود.

## ۷. احراز هویت و نشست

```sql
CREATE TABLE {P}cp_otp (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  identifier  VARCHAR(191)    NOT NULL,   -- شمارهٔ نرمال‌شده 98XXXXXXXXXX
  code_hash   CHAR(64)        NOT NULL,   -- sha256(code + salt) — کد خام ذخیره نمی‌شود
  purpose     ENUM('login','register','reset','verify') NOT NULL,
  attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ip          VARBINARY(16)   NULL,
  expires_at  DATETIME        NOT NULL,
  consumed_at DATETIME        NULL,
  created_at  DATETIME        NOT NULL,
  PRIMARY KEY (id),
  KEY idx_identifier (identifier, purpose, expires_at)
) ENGINE=InnoDB;

CREATE TABLE {P}cp_sessions (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NOT NULL,
  token_hash  CHAR(64)        NOT NULL,
  ip          VARBINARY(16)   NULL,
  user_agent  VARCHAR(255)    NULL,
  device_label VARCHAR(64)    NULL,
  last_seen   DATETIME        NOT NULL,
  created_at  DATETIME        NOT NULL,
  revoked_at  DATETIME        NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_token (token_hash),
  KEY idx_user (user_id, revoked_at)
) ENGINE=InnoDB;
```

## ۸. لاگ و ممیزی

```sql
CREATE TABLE {P}cp_audit_log (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  actor_id    BIGINT UNSIGNED NULL,
  action      VARCHAR(64)     NOT NULL,   -- wallet.adjust · order.force_status · …
  object_type VARCHAR(32)     NOT NULL,
  object_id   BIGINT UNSIGNED NULL,
  before_json JSON            NULL,
  after_json  JSON            NULL,
  reason      VARCHAR(255)    NULL,
  ip          VARBINARY(16)   NULL,
  created_at  DATETIME        NOT NULL,
  PRIMARY KEY (id),
  KEY idx_object (object_type, object_id, created_at),
  KEY idx_actor (actor_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE {P}cp_api_log (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  provider_id BIGINT UNSIGNED NOT NULL,
  action      VARCHAR(32)     NOT NULL,
  http_code   SMALLINT UNSIGNED NULL,
  latency_ms  INT UNSIGNED    NULL,
  ok          TINYINT(1)      NOT NULL DEFAULT 0,
  error       VARCHAR(500)    NULL,
  request_digest VARCHAR(255) NULL,   -- ★ بدون کلید API، بدون لینک کامل کاربر
  created_at  DATETIME        NOT NULL,
  PRIMARY KEY (id),
  KEY idx_provider_time (provider_id, created_at)
) ENGINE=InnoDB;
```

> `cp_api_log` هرگز `key` را ذخیره نمی‌کند و توسط کرون روزانه، ردیف‌های قدیمی‌تر از ۳۰ روز را حذف می‌کند.

---

## ۹. صفحهٔ سرویس (محتوای سئویی)

CPT `cp_service_page` با متای `_cp_service_id`. Taxonomy: `cp_brand` (اینستاگرام، تلگرام، …).
این لایه **فقط محتوا** است: عنوان سئویی، توضیح بلند، FAQ، نظرات، تصاویر. قیمت و دکمهٔ سفارش از `cp_services` می‌آید. جدا نگه داشتن این دو یعنی sync خودکار هرگز محتوای دستی‌نویس شما را پاک نمی‌کند.

---

## ۱۰. تخمین اندازه و نگهداشت

| جدول | رشد سالانه (تخمین ۵۰۰ سفارش/روز) | سیاست |
|---|---|---|
| `cp_orders` | ~۱۸۰k ردیف / ~۹۰MB | نگهداری دائم؛ پارتیشن بر اساس سال در >۲M ردیف |
| `cp_transactions` | ~۳۵۰k ردیف / ~۱۲۰MB | نگهداری دائم (الزام مالی) |
| `cp_api_log` | ~۲M ردیف | هرس ۳۰ روزه |
| `cp_audit_log` | ~۵۰k ردیف | نگهداری ۲ سال |
| `cp_otp` | — | هرس ۲۴ ساعته |

## ۱۱. مغایرت‌گیری

کرون شبانه:
```sql
SELECT user_id,
       SUM(IF(direction='credit', amount, -amount)) AS ledger_balance
FROM {P}cp_transactions WHERE status='succeeded' GROUP BY user_id;
```
مقایسه با `cp_wallets.balance`. هر اختلاف → ثبت در `cp_audit_log` + ایمیل هشدار به مدیر. **موجودی به‌صورت خودکار اصلاح نمی‌شود** — اصلاح خودکار می‌تواند یک باگ را به یک ضرر مالی خاموش تبدیل کند.
