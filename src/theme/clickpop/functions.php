<?php
/**
 * راه‌انداز تم ClickPop.
 *
 * این فایل عمداً فقط bootstrap است: هیچ منطقی اینجا نوشته نمی‌شود.
 * تم هیچ منطق تجاری، هیچ SQL و هیچ تماس API ندارد — آن‌ها کار افزونهٔ ClickPop Core است.
 *
 * تم مستقل است و به هیچ صفحه‌سازی وابسته نیست: صفحه‌ها با قالب PHP رندر می‌شوند و
 * متن‌هایشان از پنل «کلیک‌پاپ ← محتوای سایت» می‌آید.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

define( 'CLICKPOP_THEME_VERSION', '1.0.0' );
define( 'CLICKPOP_THEME_DIR', get_template_directory() );
define( 'CLICKPOP_THEME_URI', get_template_directory_uri() );

$clickpop_modules = [
	'inc/setup.php',
	'inc/assets.php',
	'inc/theme-mode.php',
	'inc/content.php',
	'inc/template-tags.php',
	'inc/nav-walker.php',
	'inc/seo/schema.php',
	'inc/seo/meta.php',
	'inc/perf/cleanup.php',
	'inc/perf/resource-hints.php',
];

foreach ( $clickpop_modules as $clickpop_module ) {
	$clickpop_path = CLICKPOP_THEME_DIR . '/' . $clickpop_module;

	if ( is_readable( $clickpop_path ) ) {
		require_once $clickpop_path;
	}
}

unset( $clickpop_modules, $clickpop_module, $clickpop_path );
