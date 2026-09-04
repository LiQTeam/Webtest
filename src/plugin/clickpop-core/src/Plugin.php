<?php
declare( strict_types=1 );

namespace ClickPop\Core;

use ClickPop\Core\Admin\Menu;
use ClickPop\Core\Database\Installer;
use ClickPop\Core\Frontend\Shortcodes;
use ClickPop\Core\Gateways\PaymentController;
use ClickPop\Core\Http\Rest\RestBootstrap;
use ClickPop\Core\Support\Encryption;
use ClickPop\Core\Sync\Scheduler;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function boot(): void {
		load_plugin_textdomain( 'clickpop-core', false, dirname( plugin_basename( CLICKPOP_FILE ) ) . '/languages' );

		Installer::maybeMigrate();
		Scheduler::register();
		RestBootstrap::register();
		PaymentController::register();
		Shortcodes::register();

		if ( is_admin() ) {
			Menu::register();
			add_action( 'admin_notices', [ self::class, 'encryptionNotice' ] );
		}

		add_action( 'init', [ self::class, 'registerServicePagePostType' ] );
	}

	public static function deactivate(): void {
		Scheduler::unschedule();
		flush_rewrite_rules();
	}

	/**
	 * CPT محتوایی صفحهٔ سرویس — جدا از جدول تراکنشی cp_services.
	 * همگام‌سازی خودکار هرگز این محتوا را بازنویسی نمی‌کند.
	 */
	public static function registerServicePagePostType(): void {
		register_post_type(
			'cp_service_page',
			[
				'labels'       => [
					'name'          => __( 'صفحات سرویس', 'clickpop-core' ),
					'singular_name' => __( 'صفحهٔ سرویس', 'clickpop-core' ),
					'add_new_item'  => __( 'افزودن صفحهٔ سرویس', 'clickpop-core' ),
				],
				'public'       => true,
				'has_archive'  => true,
				'menu_icon'    => 'dashicons-megaphone',
				'rewrite'      => [ 'slug' => 'services' ],
				'supports'     => [ 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes' ],
				'show_in_rest' => true,
			]
		);

		register_taxonomy(
			'cp_brand',
			'cp_service_page',
			[
				'labels'       => [
					'name'          => __( 'پلتفرم‌ها', 'clickpop-core' ),
					'singular_name' => __( 'پلتفرم', 'clickpop-core' ),
				],
				'public'       => true,
				'hierarchical' => true,
				'rewrite'      => [ 'slug' => 'platform' ],
				'show_in_rest' => true,
			]
		);
	}

	public static function encryptionNotice(): void {
		if ( Encryption::hasDedicatedKey() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p><strong>%s</strong> %s</p><p><code>%s</code></p></div>',
			esc_html__( 'کلیک‌پاپ:', 'clickpop-core' ),
			esc_html__( 'کلید رمزنگاری اختصاصی تعریف نشده است. کلید API سرویس‌دهنده با salt وردپرس رمز می‌شود و با تغییر salt غیرقابل بازیابی خواهد بود. این خط را به wp-config.php اضافه کنید:', 'clickpop-core' ),
			"define( 'CLICKPOP_ENCRYPTION_KEY', '" . esc_html( substr( wp_generate_password( 44, true, true ), 0, 44 ) ) . "' );"
		);
	}
}
