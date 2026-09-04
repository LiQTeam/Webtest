# ClickPop — سیستم طراحی و توکن‌ها (پیش‌نمای Step 2)

منبع رنگ: `brand/BRAND.md`. تمام نسبت‌های کنتراست زیر **محاسبه‌شده‌اند** (فرمول WCAG 2.x نسبی-روشنایی)، نه تخمینی.

> **اصلاح یک عدد در BRAND.md:** آنجا نوشته شده `#1668FF` روی سفید نسبت `4.2:1` دارد؛ مقدار واقعی **`4.71:1`** است. نتیجهٔ عملی تغییر نمی‌کند — این رنگ همچنان برای متن ریز مجاز نیست و باید از `--cp-blue-700` استفاده شود.

## ۱. سیاست کنتراست (صریح و قابل سنجش)

هدف «AAA در همه‌جا» با یک برند آبی/نارنجی از نظر ریاضی ممکن نیست (نارنجی `#FF7A1A` روی سفید فقط `2.61:1` است). سیاست واقعی و قابل‌تأیید:

| نوع عنصر | استاندارد | مقدار |
|---|---|---|
| متن بدنه و برچسب‌ها | **AAA** | ≥ ۷:۱ |
| متن بزرگ (≥ ۲۴px یا ۱۹px بولد) | **AAA** | ≥ ۴.۵:۱ |
| متن روی دکمهٔ اصلی | **AAA** | ≥ ۷:۱ |
| مرز اجزای تعاملی (input, checkbox) | AA غیرمتنی | ≥ ۳:۱ |
| حلقهٔ فوکوس | AA غیرمتنی | ≥ ۳:۱ (عملاً ≥ ۷:۱) |
| جداکنندهٔ تزئینی | بدون الزام | — |
| رنگ برند به‌عنوان **پرکننده** (نه متن) | AA غیرمتنی | ≥ ۳:۱ |

هرجا رنگ برند خام برای متن جواب نمی‌دهد، نسخهٔ تیره/روشن‌ترِ همان طیف جایگزین می‌شود — هویت برند حفظ می‌شود، خوانایی قربانی نمی‌شود.

## ۲. طیف رنگ (تولیدشده از رنگ برند)

| توکن | HEX | روی سفید | روی `#0B1B33` |
|---|---|---|---|
| `--cp-blue-50` | `#F0F5FF` | 1.09 | 15.76 |
| `--cp-blue-100` | `#DBE8FF` | 1.24 | 13.95 |
| `--cp-blue-200` | `#B8D1FF` | 1.54 | 11.16 |
| `--cp-blue-300` | `#85B0FF` | 2.18 | **7.92** ✅ AAA |
| `--cp-blue-400` | `#528FFF` | 3.12 | 5.52 |
| `--cp-blue-500` | `#1668FF` ← برند | **4.71** | 3.66 |
| `--cp-blue-600` | `#0050E3` | 6.45 | 2.67 |
| `--cp-blue-700` | `#0040B7` | **8.69** ✅ AAA | 1.98 |
| `--cp-blue-800` | `#00318A` | 11.63 | 1.48 |
| `--cp-blue-900` | `#002364` | 14.74 | 1.17 |

| توکن | HEX | روی سفید | روی `#0B1B33` |
|---|---|---|---|
| `--cp-orange-300` | `#FFB885` | 1.69 | 10.21 |
| `--cp-orange-400` | `#FF9A52` | 2.10 | **8.20** ✅ AAA |
| `--cp-orange-500` | `#FF7A1A` ← برند | 2.61 | 6.61 |
| `--cp-orange-600` | `#E66100` | 3.46 | 4.98 |
| `--cp-orange-700` | `#B94E00` | 5.06 | 3.41 |
| `--cp-orange-800` | `#8C3B00` | **7.66** ✅ AAA | 2.25 |

خنثی‌ها بر پایهٔ Ink (`#0A1C3D`) ساخته می‌شوند تا خاکستری‌ها ته‌رنگ برند داشته باشند، نه خاکستری مرده.

## ۳. توکن‌های معنایی

```css
:root{
  /* ── طیف برند (ثابت در هر دو تم) ───────────────────────── */
  --cp-blue-50:#F0F5FF;  --cp-blue-100:#DBE8FF; --cp-blue-200:#B8D1FF;
  --cp-blue-300:#85B0FF; --cp-blue-400:#528FFF; --cp-blue-500:#1668FF;
  --cp-blue-600:#0050E3; --cp-blue-700:#0040B7; --cp-blue-800:#00318A;
  --cp-blue-900:#002364;
  --cp-orange-300:#FFB885; --cp-orange-400:#FF9A52; --cp-orange-500:#FF7A1A;
  --cp-orange-600:#E66100; --cp-orange-700:#B94E00; --cp-orange-800:#8C3B00;
  --cp-ink:#0A1C3D;

  /* ── تم روشن (پیش‌فرض) ─────────────────────────────────── */
  --cp-bg:#FFFFFF;              /* پس‌زمینهٔ صفحه */
  --cp-bg-subtle:#F5F8FC;       /* نوار و بخش‌های متناوب */
  --cp-surface:#FFFFFF;         /* کارت */
  --cp-surface-raised:#FFFFFF;
  --cp-text:#0A1C3D;            /* 16.85:1  ✅AAA */
  --cp-text-secondary:#3F4E68;  /*  8.40:1  ✅AAA */
  --cp-text-muted:#5A6B87;      /*  5.40:1  ✅AA  — فقط متن بزرگ/فرعی */
  --cp-text-onbrand:#FFFFFF;    /*  8.69:1 روی --cp-brand-solid ✅AAA */
  --cp-link:#0040B7;            /*  8.69:1  ✅AAA */
  --cp-brand-solid:#0040B7;     /* پرکنندهٔ دکمهٔ اصلی */
  --cp-brand-accent:#1668FF;    /* پرکننده/آیکن/گرادیان — نه متن ریز */
  --cp-accent-solid:#B94E00;    /* نارنجی قابل استفاده روی روشن */
  --cp-border:#E2E8F2;          /* جداکنندهٔ تزئینی */
  --cp-border-interactive:#5A6B87; /* مرز input — 5.40:1 ✅ >3:1 */
  --cp-focus:#0040B7;
  --cp-success:#0F5F33;         /*  7.76:1 ✅AAA */
  --cp-warning:#7A4800;         /*  7.62:1 ✅AAA */
  --cp-danger:#A3170F;          /*  7.82:1 ✅AAA */
  --cp-info:#0040B7;            /*  8.69:1 ✅AAA */

  --cp-grad-blue:linear-gradient(135deg,#3D89FF,#0B44D6);
  --cp-grad-orange:linear-gradient(45deg,#F2470F,#FFA22A);

  /* ── سطح شیشه‌ای (بدون افت کارایی) ─────────────────────── */
  --cp-glass-bg:rgba(255,255,255,.72);
  --cp-glass-border:rgba(10,28,61,.08);
  --cp-glass-blur:14px;

  /* ── فاصله (مقیاس ۴px) ─────────────────────────────────── */
  --cp-s-1:.25rem; --cp-s-2:.5rem;  --cp-s-3:.75rem; --cp-s-4:1rem;
  --cp-s-5:1.5rem; --cp-s-6:2rem;   --cp-s-7:3rem;   --cp-s-8:4rem;
  --cp-s-9:6rem;

  /* ── تایپوگرافی (مقیاس سیال) ───────────────────────────── */
  --cp-font-fa:'Vazirmatn',system-ui,'Segoe UI',Tahoma,sans-serif;
  --cp-font-lt:'Inter','Plus Jakarta Sans',system-ui,sans-serif;
  --cp-fs-xs:clamp(.75rem,.72rem + .15vw,.8125rem);
  --cp-fs-sm:clamp(.8125rem,.79rem + .18vw,.875rem);
  --cp-fs-md:clamp(.9375rem,.90rem + .25vw,1rem);
  --cp-fs-lg:clamp(1.0625rem,1.0rem + .35vw,1.1875rem);
  --cp-fs-xl:clamp(1.25rem,1.15rem + .55vw,1.5rem);
  --cp-fs-2xl:clamp(1.5rem,1.3rem + 1vw,2rem);
  --cp-fs-3xl:clamp(1.875rem,1.5rem + 1.8vw,2.75rem);
  --cp-fs-4xl:clamp(2.25rem,1.7rem + 2.8vw,3.75rem);
  --cp-lh-tight:1.25; --cp-lh-normal:1.75; /* فارسی به line-height بازتر نیاز دارد */

  /* ── شعاع · سایه · حرکت · لایه ─────────────────────────── */
  --cp-r-sm:.5rem; --cp-r-md:.875rem; --cp-r-lg:1.25rem; --cp-r-xl:1.75rem;
  --cp-r-full:999px;
  --cp-sh-1:0 1px 2px rgba(10,28,61,.06);
  --cp-sh-2:0 4px 16px rgba(10,28,61,.08);
  --cp-sh-3:0 12px 40px rgba(10,28,61,.12);
  --cp-sh-brand:0 8px 28px rgba(22,104,255,.24);
  --cp-ease:cubic-bezier(.22,1,.36,1);
  --cp-dur-fast:120ms; --cp-dur:220ms; --cp-dur-slow:420ms;
  --cp-z-header:100; --cp-z-dropdown:200; --cp-z-modal:400; --cp-z-toast:500;

  color-scheme: light;
}

/* ── تم تیره ─────────────────────────────────────────────── */
:root[data-theme="dark"]{
  --cp-bg:#0B1B33;
  --cp-bg-subtle:#0E2039;
  --cp-surface:#12253F;
  --cp-surface-raised:#172C49;
  --cp-text:#EEF3FA;            /* 15.9:1 ✅AAA */
  --cp-text-secondary:#A9BAD4;  /*  8.75:1 ✅AAA */
  --cp-text-muted:#8FA3C0;      /*  6.70:1 ✅AA+ */
  --cp-text-onbrand:#04122B;
  --cp-link:#85B0FF;            /*  7.92:1 ✅AAA */
  --cp-brand-solid:#3D89FF;     /* پرکننده؛ متن تیره روی آن */
  --cp-brand-accent:#528FFF;
  --cp-accent-solid:#FF9A52;    /*  8.20:1 ✅AAA */
  --cp-border:#22375A;
  --cp-border-interactive:#8FA3C0; /* 6.70:1 ✅ >3:1 */
  --cp-focus:#85B0FF;
  --cp-success:#5FE39A;         /* 10.62:1 ✅AAA */
  --cp-warning:#FFC978;         /* 11.39:1 ✅AAA */
  --cp-danger:#FF9E96;          /*  8.68:1 ✅AAA */
  --cp-info:#85B0FF;
  --cp-glass-bg:rgba(18,37,63,.62);
  --cp-glass-border:rgba(255,255,255,.08);
  --cp-sh-1:0 1px 2px rgba(0,0,0,.4);
  --cp-sh-2:0 4px 16px rgba(0,0,0,.45);
  --cp-sh-3:0 12px 40px rgba(0,0,0,.55);
  color-scheme: dark;
}

/* حالت «سیستم» وقتی کاربر انتخاب صریح نکرده */
@media (prefers-color-scheme: dark){
  :root:not([data-theme="light"]){ /* همان مقادیر بلوک dark */ }
}

@media (prefers-reduced-motion: reduce){
  :root{ --cp-dur-fast:0ms; --cp-dur:0ms; --cp-dur-slow:0ms; }
}
```

> **نکتهٔ تیره‌مود که معمولاً از قلم می‌افتد:** کنتراست `--cp-surface` نسبت به `--cp-bg` فقط `1.12:1` است. یعنی در تم تیره، «ارتفاع» کارت‌ها را **نمی‌توان با اختلاف رنگ پس‌زمینه نشان داد** — مرز (`--cp-border`) و سایه اجباری‌اند، وگرنه لبهٔ کارت‌ها روی صفحه گم می‌شود.

## ۴. اسکریپت ضدفلیکر (قبل از هر CSS در `<head>`)

```html
<script>
(function(){try{
  var t=localStorage.getItem('cp-theme');
  if(t!=='light'&&t!=='dark'){t=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';}
  document.documentElement.setAttribute('data-theme',t);
}catch(e){}})();
</script>
```
۲۲۰ بایت، مسدودکننده و عمدی — قبل از اولین paint اجرا می‌شود، پس هیچ فریمی با تم اشتباه رندر نمی‌شود. تاگل UI سه‌حالته است: روشن / تیره / سیستم (حالت سیستم کلید را از `localStorage` حذف می‌کند).

## ۵. تایپوگرافی و RTL

- فارسی: **Vazirmatn Variable** (وزن ۳۰۰–۸۰۰)، subset فارسی/عربی + لاتین پایه، `woff2`.
- لاتین/سیریلیک: **Inter Variable**. چینی: `Noto Sans SC` فقط در locale `zh` بارگذاری می‌شود.
- `line-height` پایه در فارسی `1.75` (کشیدگی حروف و اعراب فضای عمودی بیشتری می‌خواهد).
- `letter-spacing: 0` در فارسی — فاصله‌گذاری حروف، اتصال حروف فارسی را می‌شکند.
- `font-feature-settings: "ss01"` برای شکل عددی فارسی، با کلاس `.cp-num-latin` برای مقادیر ماشینی.
- تمام فاصله‌گذاری با logical properties ⇒ بدون `rtl.css`.
- اعداد و مبالغ در `<bdi>` قرار می‌گیرند تا الگوریتم دوجهته آن‌ها را در جملهٔ فارسی نشکند.

## ۶. کتابخانهٔ اجزا (فاز F4)

`Button` (primary/secondary/ghost/danger × sm/md/lg × loading/disabled) · `Input` `Select` `Textarea` (با حالت خطا و متن راهنما) · `Card` · `StatusBadge` (۹ وضعیت سفارش با رنگ + **آیکن**، چون رنگ به‌تنهایی برای کوررنگی کافی نیست) · `Table` (نمای کارتی در موبایل) · `Modal` (focus trap + `inert`) · `Tabs` (WAI-ARIA) · `Accordion` · `Toast` (`aria-live`) · `Skeleton` · `EmptyState` · `Stepper` · `Pagination` · `Avatar` · `Tooltip` (فعال با کیبورد).

## ۷. آیکن‌ها

مجموعهٔ Lucide، فقط آیکن‌های استفاده‌شده، در یک **SVG sprite** با `<symbol>` (یک درخواست، قابل کش). `stroke-width:1.75`، `currentColor`، `aria-hidden="true"` مگر آیکن تنها معنای دکمه باشد (که آنگاه `<title>` می‌گیرد). هیچ فونت-آیکنی (FontAwesome/eicons) بارگذاری نمی‌شود.
