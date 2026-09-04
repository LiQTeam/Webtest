<?php
declare( strict_types=1 );

namespace ClickPop\Core\Orders;

use ClickPop\Core\Pricing\PriceCalculator;
use ClickPop\Core\Providers\ProviderManager;
use ClickPop\Core\Repositories\OrderRepository;
use ClickPop\Core\Repositories\ServiceRepository;
use ClickPop\Core\Support\RateLimiter;
use ClickPop\Core\Support\Validator;
use ClickPop\Core\Wallet\WalletService;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * ثبت سفارش به‌صورت Saga دوفازی.
 *
 * فاز ۱ — رزرو پول: اتمیک، بدون هیچ I/O شبکه‌ای.
 * فاز ۲ — فراخوان سرویس‌دهنده: خارج از تراکنش دیتابیس.
 *
 * خطای شبکه هرگز به بازگشت وجه خودکار منجر نمی‌شود؛ سفارش به pending_verify می‌رود
 * و کرون آشتی‌دهنده با action=status تعیین تکلیف می‌کند.
 */
final class OrderService {

	public function __construct(
		private readonly ServiceRepository $services = new ServiceRepository(),
		private readonly OrderRepository $orders = new OrderRepository(),
		private readonly WalletService $wallet = new WalletService(),
	) {}

	/**
	 * @return object ردیف سفارش، یا نمونهٔ WP_Error در صورت خطا (با is_wp_error بررسی شود).
	 */
	public function place( int $user_id, int $service_id, string $link, int $quantity, string $idempotency_key ): object {
		if ( ! Validator::isUuidV4( $idempotency_key ) ) {
			return new WP_Error( 'cp_bad_request', __( 'شناسهٔ درخواست معتبر نیست.', 'clickpop-core' ), [ 'status' => 400 ] );
		}

		// درخواست تکراری (دابل‌کلیک) → همان نتیجهٔ قبلی، بدون سفارش دوم.
		$existing = $this->orders->findByIdempotencyKey( $idempotency_key );
		if ( $existing ) {
			return $existing;
		}

		$service = $this->services->findActiveWithCategory( $service_id );
		if ( ! $service ) {
			return new WP_Error( 'cp_service_unavailable', __( 'این سرویس در دسترس نیست.', 'clickpop-core' ), [ 'status' => 409 ] );
		}

		if ( $quantity < (int) $service->min_qty || $quantity > (int) $service->max_qty ) {
			return new WP_Error(
				'cp_quantity_out_of_range',
				sprintf(
					/* translators: 1: min quantity, 2: max quantity */
					__( 'تعداد باید بین %1$s و %2$s باشد.', 'clickpop-core' ),
					number_format_i18n( (int) $service->min_qty ),
					number_format_i18n( (int) $service->max_qty )
				),
				[ 'status' => 422 ]
			);
		}

		$link_check = Validator::link( $link, (string) $service->brand_slug );
		if ( true !== $link_check ) {
			return new WP_Error( 'cp_invalid_link', (string) $link_check, [ 'status' => 422 ] );
		}

		$provider = ProviderManager::byId( (int) $service->provider_id );
		if ( ! $provider ) {
			return new WP_Error( 'cp_provider_unavailable', __( 'سرویس‌دهنده پیکربندی نشده است.', 'clickpop-core' ), [ 'status' => 503 ] );
		}

		if ( $provider->circuitOpen() ) {
			return new WP_Error(
				'cp_provider_unavailable',
				__( 'ارتباط با سرویس‌دهنده موقتاً قطع است. چند دقیقهٔ دیگر دوباره تلاش کنید.', 'clickpop-core' ),
				[ 'status' => 503 ]
			);
		}

		// قیمت همیشه سمت سرور محاسبه می‌شود؛ مقدار ارسالی کلاینت اصلاً خوانده نمی‌شود.
		$rate_unit = $provider->rateUnit();
		$charge    = PriceCalculator::chargeFor( (int) $service->sale_rate, $quantity, $rate_unit );
		$cost      = PriceCalculator::chargeFor( (int) $service->cost_rate, $quantity, $rate_unit );

		if ( $charge <= 0 ) {
			return new WP_Error( 'cp_price_error', __( 'قیمت این سرویس هنوز تعیین نشده است.', 'clickpop-core' ), [ 'status' => 409 ] );
		}

		$now = current_time( 'mysql', true );

		$order_id = $this->orders->insert(
			[
				'user_id'         => $user_id,
				'service_id'      => (int) $service->id,
				'provider_id'     => (int) $service->provider_id,
				'idempotency_key' => $idempotency_key,
				'link'            => $link,
				'quantity'        => $quantity,
				'sale_rate'       => (int) $service->sale_rate,
				'cost_rate'       => (int) $service->cost_rate,
				'charge'          => $charge,
				'cost'            => $cost,
				'status'          => OrderStatus::RESERVED,
				'ip'              => RateLimiter::ipForStorage(),
				'created_at'      => $now,
				'updated_at'      => $now,
			]
		);

		if ( $order_id <= 0 ) {
			// برخورد با قید یکتایی یعنی درخواست موازی همین لحظه ثبت شده است.
			$dup = $this->orders->findByIdempotencyKey( $idempotency_key );
			if ( $dup ) {
				return $dup;
			}

			return new WP_Error( 'cp_db_error', __( 'ثبت سفارش ممکن نشد.', 'clickpop-core' ), [ 'status' => 500 ] );
		}

		// ── فاز ۱: رزرو پول (اتمیک) ─────────────────────────────
		if ( ! $this->wallet->debitForOrder( $user_id, $charge, $order_id ) ) {
			$this->orders->update(
				$order_id,
				[
					'status'        => OrderStatus::FAILED,
					'error_message' => 'insufficient_balance',
				]
			);

			return new WP_Error(
				'cp_insufficient_balance',
				__( 'موجودی کیف پول کافی نیست.', 'clickpop-core' ),
				[
					'status'    => 402,
					'required'  => $charge,
					'available' => $this->wallet->balance( $user_id ),
				]
			);
		}

		// ── فاز ۲: فراخوان سرویس‌دهنده (خارج از تراکنش) ─────────
		$result = $provider->addOrder( (string) $service->remote_service_id, $link, $quantity );

		if ( $result['ok'] && ! empty( $result['data']['order'] ) ) {
			$this->orders->update(
				$order_id,
				[
					'remote_order_id' => (string) $result['data']['order'],
					'status'          => OrderStatus::PROCESSING,
					'next_sync_at'    => gmdate( 'Y-m-d H:i:s', time() + 3 * MINUTE_IN_SECONDS ),
				]
			);

			do_action( 'clickpop/order/placed', $order_id, $user_id );

			return (object) $this->orders->find( $order_id );
		}

		// خطای شبکه: وضعیت سفارش نامعلوم است → بازگشت وجه خودکار ممنوع.
		if ( 0 === $result['code'] ) {
			$this->orders->update(
				$order_id,
				[
					'status'        => OrderStatus::PENDING_VERIFY,
					'error_message' => mb_substr( $result['error'], 0, 500 ),
					'next_sync_at'  => gmdate( 'Y-m-d H:i:s', time() + 2 * MINUTE_IN_SECONDS ),
				]
			);

			return new WP_Error(
				'cp_provider_timeout',
				__( 'پاسخ سرویس‌دهنده دریافت نشد. سفارش در حال بررسی است و تا چند دقیقهٔ دیگر تعیین تکلیف می‌شود.', 'clickpop-core' ),
				[
					'status'   => 202,
					'order_id' => $order_id,
				]
			);
		}

		// خطای تجاری: سفارش قطعاً ثبت نشده → بازگشت کامل وجه.
		$this->wallet->refund( $user_id, $charge, $order_id, 'provider_rejected' );
		$this->orders->update(
			$order_id,
			[
				'status'        => OrderStatus::FAILED,
				'refunded'      => $charge,
				'error_message' => mb_substr( $result['error'], 0, 500 ),
			]
		);

		return new WP_Error(
			'cp_provider_rejected',
			$result['error'] ?: __( 'سرویس‌دهنده سفارش را نپذیرفت. مبلغ به کیف پول شما برگشت.', 'clickpop-core' ),
			[ 'status' => 409 ]
		);
	}

	/** بازگشت نسبی سفارش ناقص، بر اساس مقدار انجام‌نشده. */
	public function refundPartial( object $order, int $remains ): void {
		$quantity = max( 1, (int) $order->quantity );
		$remains  = max( 0, min( $remains, $quantity ) );

		if ( 0 === $remains ) {
			return;
		}

		$amount = (int) ceil( ( (int) $order->charge * $remains ) / $quantity );
		$amount = min( $amount, (int) $order->charge - (int) $order->refunded );

		if ( $amount <= 0 ) {
			return;
		}

		// قفل خوش‌بینانه: اگر کرون دیگری زودتر برگشت زده باشد، این اجرا کاری نمی‌کند.
		if ( ! $this->orders->addRefundGuarded( (int) $order->id, (int) $order->refunded, $amount ) ) {
			return;
		}

		$this->wallet->refund( (int) $order->user_id, $amount, (int) $order->id, 'partial_remains_' . $remains );
	}

	public function refundFull( object $order, string $reason ): void {
		$amount = (int) $order->charge - (int) $order->refunded;

		if ( $amount <= 0 ) {
			return;
		}

		if ( ! $this->orders->addRefundGuarded( (int) $order->id, (int) $order->refunded, $amount ) ) {
			return;
		}

		$this->wallet->refund( (int) $order->user_id, $amount, (int) $order->id, $reason );
	}
}
