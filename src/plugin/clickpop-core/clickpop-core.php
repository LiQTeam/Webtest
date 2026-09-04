<?php
/**
 * Plugin Name:       ClickPop Core Engine
 * Plugin URI:        https://clickpop.ir
 * Description:       هستهٔ کلیک‌پاپ: پل API سرویس‌دهندهٔ SMM، موتور قیمت‌گذاری، کیف پول با دفتر کل، سفارش، تیکت و داشبورد کاربر.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            ClickPop
 * Text Domain:       clickpop-core
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 *
 * @package ClickPop\Core
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

const CLICKPOP_VERSION     = '1.0.0';
const CLICKPOP_DB_VERSION  = 1;
const CLICKPOP_MIN_PHP     = '8.1';
const CLICKPOP_MIN_WP      = '6.5';

define( 'CLICKPOP_FILE', __FILE__ );
define( 'CLICKPOP_DIR', plugin_dir_path( __FILE__ ) );
define( 'CLICKPOP_URL', plugin_dir_url( __FILE__ ) );

/**
 * گارد نسخه — پیش از بارگذاری هر کلاسی.
 */
function clickpop_requirements_met(): bool {
	return version_compare( PHP_VERSION, CLICKPOP_MIN_PHP, '>=' )
		&& version_compare( get_bloginfo( 'version' ), CLICKPOP_MIN_WP, '>=' );
}

if ( ! clickpop_requirements_met() ) {
	add_action(
		'admin_notices',
		static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: required PHP version, 2: required WP version */
						__( 'افزونهٔ ClickPop Core به PHP %1$s و وردپرس %2$s یا بالاتر نیاز دارد و غیرفعال مانده است.', 'clickpop-core' ),
						CLICKPOP_MIN_PHP,
						CLICKPOP_MIN_WP
					)
				)
			);
		}
	);
	return;
}

/**
 * Autoloader ساده و PSR-4 — بدون نیاز به Composer روی سرور مقصد.
 */
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'ClickPop\\Core\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}
		$relative = substr( $class_name, strlen( $prefix ) );
		$path     = CLICKPOP_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

register_activation_hook( __FILE__, [ ClickPop\Core\Database\Installer::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ ClickPop\Core\Plugin::class, 'deactivate' ] );

add_action( 'plugins_loaded', [ ClickPop\Core\Plugin::class, 'boot' ], 5 );
