<?php
declare( strict_types=1 );

namespace ClickPop\Core\Frontend;

use ClickPop\Core\Gateways\GatewayManager;
use ClickPop\Core\Orders\OrderStatus;
use ClickPop\Core\Repositories\ServiceRepository;
use ClickPop\Core\Tickets\TicketService;
use ClickPop\Core\Wallet\WalletService;

defined( 'ABSPATH' ) || exit;

/**
 * رندر پوستهٔ داشبورد. asset فقط در همین صفحات بارگذاری می‌شود.
 */
final class DashboardRenderer {

	private static bool $assets_done = false;

	public static function render(): string {
		if ( ! is_user_logged_in() ) {
			return self::loginPrompt();
		}

		self::enqueue();

		$user   = wp_get_current_user();
		$wallet = new WalletService();

		$data = [
			'user'         => $user,
			'balance'      => $wallet->balance( (int) $user->ID ),
			'tabs'         => self::tabs(),
			'active_tab'   => self::activeTab(),
			'gateways'     => GatewayManager::enabled(),
			'departments'  => TicketService::departments(),
			'status_labels'=> OrderStatus::labels(),
			'pay_notice'   => self::payNotice(),
		];

		return self::template( 'dashboard/layout.php', $data );
	}

	public static function renderOrderForm(): string {
		if ( ! is_user_logged_in() ) {
			return self::loginPrompt();
		}

		self::enqueue();

		return self::template( 'dashboard/new-order.php', [ 'standalone' => true ] );
	}

	public static function renderServices(): string {
		self::enqueue();

		return self::template( 'dashboard/services.php', [ 'tree' => ( new ServiceRepository() )->tree() ] );
	}

	/** @return array<string,string> */
	public static function tabs(): array {
		return [
			'overview' => __( 'نمای کلی', 'clickpop-core' ),
			'new'      => __( 'سفارش جدید', 'clickpop-core' ),
			'orders'   => __( 'سفارش‌های من', 'clickpop-core' ),
			'wallet'   => __( 'کیف پول', 'clickpop-core' ),
			'tickets'  => __( 'پشتیبانی', 'clickpop-core' ),
		];
	}

	private static function activeTab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط انتخاب تب نمایشی.
		$tab = isset( $_GET['cp_tab'] ) ? sanitize_key( wp_unslash( $_GET['cp_tab'] ) ) : 'overview';

		return array_key_exists( $tab, self::tabs() ) ? $tab : 'overview';
	}

	/** @return array{tone:string,text:string}|null */
	private static function payNotice(): ?array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- پیام نتیجهٔ پرداخت؛ خودش هیچ عملی انجام نمی‌دهد.
		$status = isset( $_GET['cp_pay'] ) ? sanitize_key( wp_unslash( $_GET['cp_pay'] ) ) : '';

		return match ( $status ) {
			'success' => [
				'tone' => 'ok',
				'text' => __( 'پرداخت تأیید شد و موجودی کیف پول شما افزایش یافت.', 'clickpop-core' ),
			],
			'already' => [
				'tone' => 'info',
				'text' => __( 'این پرداخت قبلاً ثبت شده بود؛ مبلغ دوباره کسر یا اضافه نشد.', 'clickpop-core' ),
			],
			'failed' => [
				'tone' => 'bad',
				'text' => __( 'پرداخت تأیید نشد. اگر مبلغ از حساب شما کسر شده، ظرف ۷۲ ساعت برمی‌گردد.', 'clickpop-core' ),
			],
			'canceled' => [
				'tone' => 'warn',
				'text' => __( 'پرداخت لغو شد.', 'clickpop-core' ),
			],
			'not_found', 'gateway_unknown' => [
				'tone' => 'bad',
				'text' => __( 'تراکنش پیدا نشد.', 'clickpop-core' ),
			],
			default => null,
		};
	}

	private static function loginPrompt(): string {
		return sprintf(
			'<div class="cp-panel cp-panel--empty"><p>%s</p><p><a class="cp-btn cp-btn--primary" href="%s">%s</a></p></div>',
			esc_html__( 'برای دیدن داشبورد باید وارد حساب کاربری شوید.', 'clickpop-core' ),
			esc_url( wp_login_url( get_permalink() ?: home_url( '/' ) ) ),
			esc_html__( 'ورود به حساب', 'clickpop-core' )
		);
	}

	public static function enqueue(): void {
		if ( self::$assets_done ) {
			return;
		}
		self::$assets_done = true;

		$css = CLICKPOP_DIR . 'assets/css/dashboard.css';
		$js  = CLICKPOP_DIR . 'assets/js/dashboard.js';

		wp_enqueue_style(
			'clickpop-dashboard',
			CLICKPOP_URL . 'assets/css/dashboard.css',
			[],
			is_readable( $css ) ? (string) filemtime( $css ) : CLICKPOP_VERSION
		);

		wp_enqueue_script(
			'clickpop-dashboard',
			CLICKPOP_URL . 'assets/js/dashboard.js',
			[],
			is_readable( $js ) ? (string) filemtime( $js ) : CLICKPOP_VERSION,
			true
		);

		wp_script_add_data( 'clickpop-dashboard', 'strategy', 'defer' );

		wp_localize_script(
			'clickpop-dashboard',
			'clickpopData',
			[
				'root'     => esc_url_raw( rest_url( 'clickpop/v1/' ) ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'currency' => __( 'تومان', 'clickpop-core' ),
				'i18n'     => [
					'loading'    => __( 'در حال بارگذاری…', 'clickpop-core' ),
					'error'      => __( 'خطایی رخ داد. دوباره تلاش کنید.', 'clickpop-core' ),
					'empty'      => __( 'موردی برای نمایش نیست.', 'clickpop-core' ),
					'selectSvc'  => __( 'ابتدا سرویس را انتخاب کنید', 'clickpop-core' ),
					'quantityFmt'=> __( 'حداقل %1$s — حداکثر %2$s', 'clickpop-core' ),
					'submitting' => __( 'در حال ثبت…', 'clickpop-core' ),
				],
			]
		);
	}

	/** @param array<string,mixed> $data */
	private static function template( string $relative, array $data = [] ): string {
		// اجازهٔ بازنویسی قالب از مسیر تم: yourtheme/clickpop/…
		$override = locate_template( 'clickpop/' . $relative );
		$path     = $override ?: CLICKPOP_DIR . 'templates/' . $relative;

		if ( ! is_readable( $path ) ) {
			return '';
		}

		// عمداً از extract() استفاده نمی‌شود؛ قالب‌ها فقط به آرایهٔ ‎$cp‎ دسترسی دارند.
		$cp = $data;

		ob_start();
		include $path;

		return (string) ob_get_clean();
	}
}
