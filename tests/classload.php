<?php
declare(strict_types=1);
define('ABSPATH','/tmp/');
const CLICKPOP_VERSION='1.0.0'; const CLICKPOP_DB_VERSION=1;
define('CLICKPOP_FILE','/tmp/x.php'); define('CLICKPOP_DIR','/tmp/'); define('CLICKPOP_URL','http://x/');
const HOUR_IN_SECONDS=3600; const MINUTE_IN_SECONDS=60; const DAY_IN_SECONDS=86400;
foreach ([ '__'=>1,'_e'=>1 ] as $f=>$v) {}
function __($t,$d=null){return $t;} function _e($t,$d=null){echo $t;}
function apply_filters($h,$v,...$a){return $v;} function add_action(...$a){} function add_filter(...$a){}
function esc_html($t){return $t;} function esc_attr($t){return $t;} function esc_url($u){return $u;}
function esc_html__($t,$d=null){return $t;} function esc_attr__($t,$d=null){return $t;}
function get_option($k,$d=false){return $d;} function update_option(...$a){return true;}
function wp_parse_url($u,$c=-1){return parse_url($u,$c);}
function number_format_i18n($n,$d=0){return number_format((float)$n,$d);}
function current_time($t,$g=0){return gmdate('Y-m-d H:i:s');}
function get_current_user_id(){return 1;}
function home_url($p='/'){return 'https://x'.$p;}
function admin_url($p=''){return 'https://x/wp-admin/'.$p;}

spl_autoload_register(function(string $c):void{
  $prefix='ClickPop\\Core\\';
  if (!str_starts_with($c,$prefix)) return;
  $p=__DIR__ . '/../src/plugin/clickpop-core/src/'.str_replace('\\','/',substr($c,strlen($prefix))).'.php';
  if (is_readable($p)) require_once $p;
});

$classes=[];
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../src/plugin/clickpop-core/src'));
foreach($it as $f){
  if($f->getExtension()!=='php') continue;
  $rel=substr($f->getPathname(), strlen(__DIR__ . '/../src/plugin/clickpop-core/src/'), -4);
  $classes[]='ClickPop\\Core\\'.str_replace('/','\\',$rel);
}
sort($classes);
$fail=0;
foreach($classes as $c){
  if(!class_exists($c)){ echo "MISSING: $c\n"; $fail++; }
}
echo count($classes)." کلاس بررسی شد، $fail مشکل.\n";

// بررسی سازگاری اسکیمای محتوا با پیش‌فرض‌های تم
$schema = ClickPop\Core\Admin\ContentSchema::defaults();
echo "کلیدهای اسکیما: ".count($schema)."\n";
$dup = [];
foreach (ClickPop\Core\Admin\ContentSchema::tabs() as $tabKey=>$tab) {
  foreach ($tab['fields'] as $k=>$f) { $dup[$k] = ($dup[$k] ?? 0) + 1; }
}
$repeated = array_filter($dup, fn($n)=>$n>1);
echo "کلید تکراری بین تب‌ها: ".count($repeated).(count($repeated)?' -> '.implode(',',array_keys($repeated)):'')."\n";
exit($fail>0||count($repeated)>0?1:0);
