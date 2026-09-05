#!/usr/bin/env bash
# اجرای همهٔ بررسی‌های محلی. خروجی غیرصفر یعنی چیزی شکسته است.
set -u
cd "$(dirname "$0")/.."
fail=0

echo "── لینت PHP ──"
while read -r f; do
  out=$(php -l "$f" 2>&1)
  case "$out" in "No syntax errors detected"*) ;; *) echo "$out"; fail=1;; esac
done < <(find src tests -name '*.php')
echo "  $(find src -name '*.php' | wc -l) فایل بررسی شد"

echo "── لینت JS ──"
for j in $(find src -name '*.js'); do node --check "$j" || fail=1; done
echo "  $(find src -name '*.js' | wc -l) فایل بررسی شد"

echo "── منطق پول، تاریخ، اعتبارسنجی ──"
php tests/smoke.php | tail -1 || fail=1

echo "── بارگذاری کلاس‌ها ──"
php tests/classload.php || fail=1

echo "── هم‌خوانی اسکیمای محتوا ──"
php tests/content-drift.php || fail=1

[ "$fail" -eq 0 ] && echo "همه‌چیز سبز." || echo "خطا پیدا شد."
exit "$fail"
