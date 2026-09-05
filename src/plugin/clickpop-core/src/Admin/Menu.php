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
		add_action( 'admin_post_clickpop_ticket_reply', [ TicketsPage::class, 'handleReply' ] );
		add_action( 'admin_post_clickpop_ticket_update', [ TicketsPage::class, 'handleUpdate' ] );

		OrderActions::register();
		ContentPage::register();
		CustomersPage::register();

		add_action( 'admin_post_clickpop_install_pages', [ self::class, 'handleInstallPages' ] );
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
			__( 'مشتریان', 'clickpop-core' ),
			__( 'مشتریان', 'clickpop-core' ),
			'clickpop_manage_orders',
			self::SLUG . '-customers',
			[ CustomersPage::class, 'render' ]
		);

		add_submenu_page(
			self::SLUG,
			__( 'تیکت‌ها', 'clickpop-core' ),
			self::ticketsLabel(),
			'clickpop_manage_tickets',
			self::SLUG . '-tickets',
			[ TicketsPage::class, 'render' ]
		);

		add_submenu_page(
			self::SLUG,
			__( 'محتوا و ظاهر سایت', 'clickpop-core' ),
			__( 'محتوا و ظاهر', 'clickpop-core' ),
			'clickpop_manage_pricing',
			self::SLUG . '-content',
			[ ContentPage::class, 'render' ]
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

	/** ساخت خودکار صفحه‌های لازم از روی چک‌لیست. */
	public static function handleInstallPages(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( 'clickpop_install_pages' );

		$pages = \ClickPop\Core\Setup\PageInstaller::ensure();
		\ClickPop\Core\Setup\PageInstaller::ensureMenu( $pages );
		flush_rewrite_rules();

		wp_safe_redirect(
			add_query_arg(
				[
					'page'   => self::SLUG,
					'cp_msg' => rawurlencode(
						sprintf(
							/* translators: %d: page count */
							__( '%d صفحه بررسی و در صورت نبود ساخته شد.', 'clickpop-core' ),
							count( $pages )
						)
					),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/** برچسب منوی تیکت با شمارندهٔ تیکت‌های باز. */
	private static function ticketsLabel(): string {
		global $wpdb;

		$table = \ClickPop\Core\Database\Installer::table( 'tickets' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$open = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status IN ('open','pending_user')" );

		if ( $open < 1 ) {
			return __( 'تیکت‌ها', 'clickpop-core' );
		}

		return sprintf(
			'%s <span class="awaiting-mod"><span class="pending-count">%s</span></span>',
			__( 'تیکت‌ها', 'clickpop-core' ),
			esc_html( number_format_i18n( $open ) )
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

		// انتخابگر رسانه فقط در صفحهٔ محتوا لازم است.
		if ( str_contains( $hook, self::SLUG . '-content' ) ) {
			wp_enqueue_media();
		}

		$js = CLICKPOP_DIR . 'assets/js/admin.js';

		wp_enqueue_script(
			'clickpop-admin',
			CLICKPOP_URL . 'assets/js/admin.js',
			[],
			is_readable( $js ) ? (string) filemtime( $js ) : CLICKPOP_VERSION,
			true
		);
	}
}
