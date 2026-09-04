<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

defined( 'ABSPATH' ) || exit;

final class Menu {

	public const SLUG = 'clickpop';

	public static function register(): void {
		add_action( 'admin_menu', [ self::class, 'build' ] );
		add_action( 'admin_enqueue_scripts', [ self::class, 'assets' ] );
		add_action( 'admin_post_clickpop_save_settings', [ SettingsPage::class, 'handleSave' ] );
		add_action( 'admin_post_clickpop_sync_services', [ SettingsPage::class, 'handleSync' ] );
		add_action( 'admin_post_clickpop_adjust_balance', [ WalletPage::class, 'handleAdjust' ] );
	}

	public static function build(): void {
		add_menu_page(
			__( 'کلیک‌پاپ', 'clickpop-core' ),
			__( 'کلیک‌پاپ', 'clickpop-core' ),
			'clickpop_manage_orders',
			self::SLUG,
			[ DashboardPage::class, 'render' ],
			'dashicons-chart-line',
			26
		);

		add_submenu_page(
			self::SLUG,
			__( 'نمای کلی', 'clickpop-core' ),
			__( 'نمای کلی', 'clickpop-core' ),
			'clickpop_manage_orders',
			self::SLUG,
			[ DashboardPage::class, 'render' ]
		);

		add_submenu_page(
			self::SLUG,
			__( 'سفارش‌ها', 'clickpop-core' ),
			__( 'سفارش‌ها', 'clickpop-core' ),
			'clickpop_manage_orders',
			self::SLUG . '-orders',
			[ OrdersPage::class, 'render' ]
		);

		add_submenu_page(
			self::SLUG,
			__( 'سرویس‌ها و قیمت', 'clickpop-core' ),
			__( 'سرویس‌ها و قیمت', 'clickpop-core' ),
			'clickpop_manage_pricing',
			self::SLUG . '-services',
			[ ServicesPage::class, 'render' ]
		);

		add_submenu_page(
			self::SLUG,
			__( 'کیف پول کاربران', 'clickpop-core' ),
			__( 'کیف پول کاربران', 'clickpop-core' ),
			'clickpop_adjust_balance',
			self::SLUG . '-wallet',
			[ WalletPage::class, 'render' ]
		);

		add_submenu_page(
			self::SLUG,
			__( 'تنظیمات', 'clickpop-core' ),
			__( 'تنظیمات', 'clickpop-core' ),
			'clickpop_manage_providers',
			self::SLUG . '-settings',
			[ SettingsPage::class, 'render' ]
		);
	}

	public static function assets( string $hook ): void {
		if ( ! str_contains( $hook, self::SLUG ) ) {
			return;
		}

		$css = CLICKPOP_DIR . 'assets/css/admin.css';

		wp_enqueue_style(
			'clickpop-admin',
			CLICKPOP_URL . 'assets/css/admin.css',
			[],
			is_readable( $css ) ? (string) filemtime( $css ) : CLICKPOP_VERSION
		);
	}
}
