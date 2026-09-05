<?php
declare(strict_types=1);
define('ABSPATH','/tmp/');
function __($t,$d=null){return $t;}
function apply_filters($h,$v,...$a){return $v;}
function get_option($k,$d=false){return $d;}
function class_exists_stub(){return false;}

// اسکیمای افزونه
require __DIR__ . '/../src/plugin/clickpop-core/src/Admin/ContentSchema.php';
$schema = ClickPop\Core\Admin\ContentSchema::defaults();

// پیش‌فرض تم — با شبیه‌سازی «افزونه نصب نیست»
$src = file_get_contents(__DIR__ . '/../src/theme/clickpop/inc/content.php');
$src = preg_replace('/if \( class_exists.*?\n\t}\n/s', '', $src, 1);
$src = str_replace('<?php', '', $src);
$src = str_replace("declare( strict_types=1 );", '', $src);
$src = str_replace("defined( 'ABSPATH' ) || exit;", '', $src);
$src = str_replace("const CLICKPOP_CONTENT_OPTION = 'clickpop_site_content';", '', $src);
eval($src);
$theme = clickpop_content_defaults();

$missing_in_theme  = array_diff(array_keys($schema), array_keys($theme));
$missing_in_schema = array_diff(array_keys($theme), array_keys($schema));

printf("اسکیما: %d کلید | تم: %d کلید\n", count($schema), count($theme));
printf("در تم نیست: %s\n", $missing_in_theme ? implode(', ', $missing_in_theme) : 'هیچ ✓');
printf("در اسکیما نیست: %s\n", $missing_in_schema ? implode(', ', $missing_in_schema) : 'هیچ ✓');
exit(($missing_in_theme || $missing_in_schema) ? 1 : 0);
