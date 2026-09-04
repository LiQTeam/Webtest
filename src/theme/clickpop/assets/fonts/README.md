# فونت‌ها

فایل‌های فونت عمداً در مخزن نیستند (حجم + لایسنس). این‌ها را اینجا بگذارید:

| فایل | منبع | کاربرد |
|---|---|---|
| `Vazirmatn-Variable.woff2` | https://github.com/rastikerdar/vazirmatn (OFL) | فارسی — وزن ۳۰۰ تا ۹۰۰ |
| `Inter-Variable.woff2` | https://github.com/rsms/inter (OFL) | لاتین و سیریلیک |

پس از قرار دادن `Vazirmatn-Variable.woff2`، تم به‌صورت خودکار آن را `preload` می‌کند
(`inc/perf/resource-hints.php` وجود فایل را بررسی می‌کند).

**زیرمجموعه‌سازی (subset) پیشنهادی** — حجم را تا ~۷۰٪ کم می‌کند:

```bash
pip install fonttools brotli
pyftsubset Vazirmatn[wght].ttf \
  --unicodes="U+0600-06FF,U+200C-200F,U+FB8A,U+067E,U+0686,U+06AF,U+0000-00FF,U+2000-206F" \
  --layout-features="*" --flavor=woff2 \
  --output-file=Vazirmatn-Variable.woff2
```

بدون این فایل‌ها تم بی‌صدا از فونت سیستم استفاده می‌کند و چیزی نمی‌شکند.
