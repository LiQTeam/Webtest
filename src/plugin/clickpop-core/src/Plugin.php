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
			add_action( 'admin_notices', [ self::class, 'storageEngineNotice' ] );
			add_action( 'admin_post_clickpop_convert_innodb', [ self::class, 'handleConvertEngine' ] );
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

	/**
	 * هشدار موتور ذخیره‌سازی.
	 *
	 * روی میزبان‌هایی که default_storage_engine=MyISAM است، جدول‌ها بدون تراکنش
	 * و بدون قفل ردیفی ساخته می‌شوند. در آن حالت دو سفارش همزمان می‌توانند
	 * موجودی را منفی کنند و مغایرت مالی بی‌صدا بماند — پس این هشدار قابل رد شدن نیست.
	 */
	public static function storageEngineNotice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$bad = get_transient( 'clickpop_non_innodb' );

		if ( false === $bad ) {
			$bad = Installer::nonInnodbTables();
			set_transient( 'clickpop_non_innodb', $bad, HOUR_IN_SECONDS );
		}

		if ( ! is_array( $bad ) || ! $bad ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%s</strong> %s</p><p><code>%s</code></p><p>%s</p></div>',
			esc_html__( 'کلیک‌پاپ — خطر داده‌ی مالی:', 'clickpop-core' ),
			esc_html(
				sprintf(
					/* translators: %d: number of tables */
					__( '%d جدول کلیک‌پاپ روی موتور InnoDB نیست. کیف پول بدون تراکنش و قفل ردیفی کار می‌کند و دو سفارش همزمان می‌تواند موجودی را منفی کند. تا رفع این مورد، فروش را شروع نکنید.', 'clickpop-core' ),
					count( $bad )
				)
			),
			esc_html( implode( ' · ', $bad ) ),
			wp_kses_post(
				sprintf(
					'<a class="button button-primary" href="%s">%s</a>',
					esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=clickpop_convert_innodb' ), 'clickpop_convert_innodb' ) ),
					esc_html__( 'تبدیل جدول‌های کلیک‌پاپ به InnoDB', 'clickpop-core' )
				)
			)
		);
	}

	/**
	 * تبدیل یک‌کلیکی جدول‌های افزونه به InnoDB.
	 *
	 * فقط جدول‌های با پیشوند cp_ را لمس می‌کند؛ جدول‌های هستهٔ وردپرس دست‌نخورده می‌مانند
	 * (تبدیل آن‌ها تصمیم مدیر است و باید با پشتیبان‌گیری انجام شود).
	 */
	public static function handleConvertEngine(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( 'clickpop_convert_innodb' );

		$result = Installer::convertToInnodb();

		delete_transient( 'clickpop_non_innodb' );

		$message = $result['failed']
			? sprintf(
				/* translators: 1: converted count, 2: failed table names */
				__( '%1$d جدول تبدیل شد. تبدیل این‌ها ناموفق بود: %2$s', 'clickpop-core' ),
				count( $result['converted'] ),
				implode( '، ', $result['failed'] )
			)
			: sprintf(
				/* translators: %d: converted count */
				__( '%d جدول با موفقیت به InnoDB تبدیل شد.', 'clickpop-core' ),
				count( $result['converted'] )
			);

		wp_safe_redirect(
			add_query_arg(
				[
					'page'   => 'clickpop',
					'cp_msg' => rawurlencode( $message ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
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
