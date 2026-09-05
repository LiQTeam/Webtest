<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Orders\OrderService;
use ClickPop\Core\Orders\OrderStatus;
use ClickPop\Core\Providers\ProviderManager;
use ClickPop\Core\Repositories\OrderRepository;
use ClickPop\Core\Support\Audit;
use ClickPop\Core\Support\Money;

defined( 'ABSPATH' ) || exit;

/**
 * اعمال دستی روی سفارش.
 *
 * هر عمل در گزارش ممیزی ثبت می‌شود و بازگشت وجه از همان مسیر امنِ
 * دفتر کل عبور می‌کند — نه UPDATE مستقیم روی موجودی.
 */
final class OrderActions {

	private const CAP   = 'clickpop_manage_orders';
	public const  NONCE = 'clickpop_order_action';

	public static function register(): void {
		add_action( 'admin_post_clickpop_order_action', [ self::class, 'handle' ] );
	}

	public static function handle(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::NONCE );

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$do       = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';

		$repo  = new OrderRepository();
		$order = $order_id > 0 ? $repo->find( $order_id ) : null;

		if ( ! $order ) {
			self::back( __( 'سفارش پیدا نشد.', 'clickpop-core' ) );
		}

		$message = match ( $do ) {
			'resync'      => self::resync( $order, $repo ),
			'set_status'  => self::setStatus( $order, $repo ),
			'refund_full' => self::refundFull( $order ),
			default       => __( 'عملیات ناشناخته.', 'clickpop-core' ),
		};

		self::back( $message, $order_id );
	}

	/** بررسی فوری وضعیت از سرویس‌دهنده، بدون انتظار برای کرون. */
	private static function resync( object $order, OrderRepository $repo ): string {
		if ( empty( $order->remote_order_id ) ) {
			return __( 'این سفارش شناسهٔ سرویس‌دهنده ندارد؛ چیزی برای بررسی نیست.', 'clickpop-core' );
		}

		$provider = ProviderManager::byId( (int) $order->provider_id );

		if ( ! $provider ) {
			return __( 'سرویس‌دهنده در دسترس نیست.', 'clickpop-core' );
		}

		$response = $provider->status( [ (string) $order->remote_order_id ] );

		if ( ! $response['ok'] || ! is_array( $response['data'] ) ) {
			return __( 'پاسخ سرویس‌دهنده دریافت نشد.', 'clickpop-core' );
		}

		$row = $response['data'][ (string) $order->remote_order_id ] ?? null;

		if ( ! is_array( $row ) ) {
			return __( 'سرویس‌دهنده برای این سفارش وضعیتی برنگرداند.', 'clickpop-core' );
		}

		// همان مسیر کرون، تا رفتار دستی و خودکار یکسان بماند.
		$repo->update(
			(int) $order->id,
			[
				'next_sync_at' => current_time( 'mysql', true ),
			]
		);

		\ClickPop\Core\Sync\OrderStatusSync::run();

		Audit::log( 'order.resync', 'order', (int) $order->id, null, [ 'provider_status' => $row['status'] ?? '' ] );

		return sprintf(
			/* translators: %s: provider status string */
			__( 'وضعیت از سرویس‌دهنده خوانده شد: %s', 'clickpop-core' ),
			(string) ( $row['status'] ?? '—' )
		);
	}

	/**
	 * تغییر دستی وضعیت.
	 *
	 * وضعیت‌های مالی (لغو/بازگشت/ناقص) عمداً اینجا پول جابه‌جا نمی‌کنند؛
	 * برای بازگشت وجه دکمهٔ جداگانه هست تا هیچ پرداختی اتفاقی رخ ندهد.
	 */
	private static function setStatus( object $order, OrderRepository $repo ): string {
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';

		if ( ! array_key_exists( $status, OrderStatus::labels() ) ) {
			return __( 'وضعیت نامعتبر است.', 'clickpop-core' );
		}

		$before = (string) $order->status;

		$fields = [
			'status'       => $status,
			'next_sync_at' => OrderStatus::isFinal( $status ) ? null : current_time( 'mysql', true ),
		];

		if ( OrderStatus::COMPLETED === $status ) {
			$fields['completed_at'] = current_time( 'mysql', true );
			$fields['remains']      = 0;
		}

		$repo->update( (int) $order->id, $fields );

		Audit::log(
			'order.force_status',
			'order',
			(int) $order->id,
			[ 'status' => $before ],
			[ 'status' => $status ],
			__( 'تغییر دستی وضعیت توسط مدیر', 'clickpop-core' )
		);

		return sprintf(
			/* translators: 1: old status, 2: new status */
			__( 'وضعیت از «%1$s» به «%2$s» تغییر کرد. توجه: این کار به‌تنهایی پولی برنمی‌گرداند.', 'clickpop-core' ),
			OrderStatus::label( $before ),
			OrderStatus::label( $status )
		);
	}

	/** بازگشت کامل باقی‌ماندهٔ مبلغ به کیف پول کاربر. */
	private static function refundFull( object $order ): string {
		$remaining = (int) $order->charge - (int) $order->refunded;

		if ( $remaining <= 0 ) {
			return __( 'مبلغ این سفارش قبلاً به‌طور کامل برگشت خورده است.', 'clickpop-core' );
		}

		( new OrderService() )->refundFull( $order, 'manual_admin_refund' );

		Audit::log(
			'order.refund_manual',
			'order',
			(int) $order->id,
			[ 'refunded' => (int) $order->refunded ],
			[ 'refunded' => (int) $order->charge ],
			__( 'بازگشت وجه دستی توسط مدیر', 'clickpop-core' )
		);

		return sprintf(
			/* translators: %s: refunded amount */
			__( 'مبلغ %s به کیف پول کاربر برگشت خورد.', 'clickpop-core' ),
			Money::fromRials( $remaining )->format()
		);
	}

	private static function back( string $message, int $order_id = 0 ): never {
		wp_safe_redirect(
			add_query_arg(
				array_filter(
					[
						'page'   => Menu::SLUG . '-orders',
						'order'  => $order_id ?: null,
						'cp_msg' => rawurlencode( $message ),
					]
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
