<?php
declare(strict_types=1);
define('ABSPATH', '/tmp/');
const CLICKPOP_VERSION='1.0.0';
const HOUR_IN_SECONDS=3600; const MINUTE_IN_SECONDS=60; const DAY_IN_SECONDS=86400;

// حداقل شبیه‌سازی توابع وردپرس مورد نیاز کلاس‌های خالص
function __($t,$d=null){return $t;}
function _e($t,$d=null){echo $t;}
function apply_filters($h,$v,...$a){return $v;}
function number_format_i18n($n,$dec=0){return number_format((float)$n,$dec);}
function get_option($k,$d=false){return $d;}
function wp_parse_url($u,$c=-1){return parse_url($u,$c);}

$base = __DIR__ . '/../src/plugin/clickpop-core/src/';
require $base.'Support/Money.php';
require $base.'Support/Jalali.php';
require $base.'Support/Validator.php';
require $base.'Orders/OrderStatus.php';
require $base.'Pricing/PriceCalculator.php';

use ClickPop\Core\Support\{Money,Jalali,Validator};
use ClickPop\Core\Orders\OrderStatus;
use ClickPop\Core\Pricing\PriceCalculator;

$fail=0;
function t(string $name, $got, $want) { global $fail;
  $ok = $got === $want;
  if(!$ok){$fail++;}
  printf("%s %-46s got=%s want=%s\n", $ok?'PASS':'FAIL', $name, var_export($got,true), var_export($want,true));
}

/* Money */
t('fromTomans(1440)->rials', Money::fromTomans(1440)->rials(), 14400);
t('fromRials(14400)->tomans', Money::fromRials(14400)->tomans(), 1440);
t('add', Money::fromRials(100)->add(Money::fromRials(250))->rials(), 350);
t('mulDiv ceil 14400*333/1000', Money::fromRials(14400)->mulDiv(333,1000)->rials(), 4796); // 4795.2 -> ceil
t('mulDiv divisor 0 guard', Money::fromRials(100)->mulDiv(5,0)->rials(), 0);
t('format', Money::fromRials(144000)->format(false), '14,400');

/* chargeFor: قیمت هر ۱۰۰۰ = ۱۴۴٬۰۰۰ ریال، تعداد ۲۰۰۰ */
t('chargeFor 2000', PriceCalculator::chargeFor(144000,2000,1000), 288000);
t('chargeFor 1 item ceil', PriceCalculator::chargeFor(144000,1,1000), 144);
t('chargeFor rate_unit 0 guard', PriceCalculator::chargeFor(1000,10,0), 10000);

/* Jalali — 2026-09-04 باید 1405/06/13 باشد */
t('Jalali 2026-09-04', Jalali::fromGregorian(2026,9,4), [1405,6,13]);
t('Jalali 2025-03-21 (نوروز ۱۴۰۴)', Jalali::fromGregorian(2025,3,21), [1404,1,1]);
t('Jalali 2026-03-21 (نوروز ۱۴۰۵)', Jalali::fromGregorian(2026,3,21), [1405,1,1]);
t('Jalali 2024-02-29 (کبیسه میلادی)', Jalali::fromGregorian(2024,2,29), [1402,12,10]);

/* Validator — allowlist */
t('link ok instagram', Validator::link('https://instagram.com/clickpop','instagram'), true);
t('link ok www', Validator::link('https://www.instagram.com/x','instagram'), true);
t('link bypass attempt', is_string(Validator::link('https://evil.com/?x=instagram.com','instagram')), true);
t('link http rejected', is_string(Validator::link('http://instagram.com/x','instagram')), true);
t('link creds rejected', is_string(Validator::link('https://user:pass@instagram.com/x','instagram')), true);
t('link ip rejected', is_string(Validator::link('https://127.0.0.1/x','instagram')), true);
t('link wrong brand', is_string(Validator::link('https://t.me/x','instagram')), true);
t('link telegram ok', Validator::link('https://t.me/clickpop','telegram'), true);
t('link too long', is_string(Validator::link('https://instagram.com/'.str_repeat('a',600),'instagram')), true);
t('uuid v4 ok', Validator::isUuidV4('b3f1c2d4-5e6f-4a7b-8c9d-0e1f2a3b4c5d'), true);
t('uuid v1 rejected', Validator::isUuidV4('b3f1c2d4-5e6f-1a7b-8c9d-0e1f2a3b4c5d'), false);
t('normalizeFa', Validator::normalizeFa("اينستاگرام "), 'اینستاگرام');

/* OrderStatus */
t('map Completed', OrderStatus::fromProvider('Completed'), 'completed');
t('map In progress', OrderStatus::fromProvider('In progress'), 'in_progress');
t('map Cancelled', OrderStatus::fromProvider('Cancelled'), 'canceled');
t('map Partial', OrderStatus::fromProvider('Partial'), 'partial');
t('map unknown -> null', OrderStatus::fromProvider('Weird'), null);
t('isFinal completed', OrderStatus::isFinal('completed'), true);
t('isFinal processing', OrderStatus::isFinal('processing'), false);
t('tone partial', OrderStatus::tone('partial'), 'part');

echo $fail===0 ? "\nهمهٔ تست‌ها سبز.\n" : "\n{$fail} تست شکست خورد.\n";
exit($fail===0?0:1);
