<?php
declare( strict_types=1 );

namespace ClickPop\Core\Sync;

use ClickPop\Core\Orders\OrderService;
use ClickPop\Core\Orders\OrderStatus;
use ClickPop\Core\Providers\ProviderManager;
use ClickPop\Core\Repositories\OrderRepository;

defined( 'ABSPATH' ) || exit;

/**
 * همگام‌سازی دسته‌ای وضعیت سفارش‌ها + آشتی‌دادن سفارش‌های یتیم.
 */
final class OrderStatusSync {

	private const BATCH = 100;

	public static function run(): void {
		$repo   = new OrderRepository();
		$orders = $repo->dueForSync( self::BATCH );

		if ( ! $orders ) {
			return;
		}

		// گروه‌بندی بر اساس سرویس‌دهنده تا هر گروه یک فراخوان بگیرد.
		$by_provider = [];
		foreach ( $orders as $order ) {
			$by_provider[ (int) $order->provider_id ][] = $order;
		}

		$service = new OrderService();

		foreach ( $by_provider as $provider_id => $group ) {
			$provider = ProviderManager::byId( $provider_id );
			if ( ! $provider || $provider->circuitOpen() ) {
				continue;
			}

			$ids      = array_map( static fn( object $o ): string => (string) $o->remote_order_id, $group );
			$response = $provider->status( $ids );

			if ( ! $response['ok'] || ! is_array( $response['data'] ) ) {
				self::pushBack( $repo, $group );
				continue;
			}

			foreach ( $group as $order ) {
				$row = $response['data'][ (string) $order->remote_order_id ] ?? null;

				if ( ! is_array( $row ) ) {
					self::pushBack( $repo, [ $order ] );
					continue;
				}

				self::applyStatus( $repo, $service, $order, $row );
			}
		}
	}

	/** @param array<string,mixed> $row */
	private static function applyStatus( OrderRepository $repo, OrderService $service, object $order, array $row ): void {
		$raw      = (string) ( $row['status'] ?? '' );
		$mapped   = OrderStatus::fromProvider( $raw );
		$remains  = isset( $row['remains'] ) ? (int) $row['remains'] : null;
		$start    = isset( $row['start_count'] ) ? (int) $row['start_count'] : null;
		$attempts = (int) $order->sync_attempts + 1;

		$update = [
			'provider_status' => mb_substr( $raw, 0, 64 ),
			'start_count'     => $start,
			'remains'         => $remains,
			'sync_attempts'   => $attempts,
			'next_sync_at'    => self::nextSync( $order, $attempts ),
		];

		if ( null === $mapped ) {
			// وضعیت ناشناخته: چیزی تغییر نمی‌کند، فقط ثبت می‌شود.
			$repo->update( (int) $order->id, $update );
			do_action( 'clickpop/sync/unknown_status', (int) $order->id, $raw );

			return;
		}

		$update['status'] = $mapped;

		if ( OrderStatus::COMPLETED === $mapped ) {
			$update['completed_at'] = current_time( 'mysql', true );
			$update['remains']      = 0;
			$update['next_sync_at'] = null;
		}

		if ( OrderStatus::isFinal( $mapped ) ) {
			$update['next_sync_at'] = null;
		}

		$repo->update( (int) $order->id, $update );

		// بازگشت وجه پس از ثبت وضعیت انجام می‌شود تا ردیف تازه خوانده شود.
		$fresh = $repo->find( (int) $order->id );
		if ( ! $fresh ) {
			return;
		}

		if ( OrderStatus::PARTIAL === $mapped && null !== $remains ) {
			$service->refundPartial( $fresh, $remains );
		}

		if ( in_array( $mapped, [ OrderStatus::CANCELED, OrderStatus::REFUNDED, OrderStatus::FAILED ], true ) ) {
			$service->refundFull( $fresh, 'provider_' . $mapped );
		}

		do_action( 'clickpop/order/status_changed', (int) $order->id, $mapped );
	}

	/** فاصلهٔ همگام‌سازی با گذشت زمان بازتر می‌شود. */
	private static function nextSync( object $order, int $attempts ): string {
		$age = time() - (int) strtotime( (string) $order->created_at . ' UTC' );

		$interval = match ( true ) {
			$age < 6 * HOUR_IN_SECONDS  => 5 * MINUTE_IN_SECONDS,
			$age < DAY_IN_SECONDS       => 30 * MINUTE_IN_SECONDS,
			default                     => 2 * HOUR_IN_SECONDS,
		};

		if ( $attempts > 200 ) {
			$interval = DAY_IN_SECONDS;
		}

		return gmdate( 'Y-m-d H:i:s', time() + $interval );
	}

	/** @param object[] $orders */
	private static function pushBack( OrderRepository $repo, array $orders ): void {
		foreach ( $orders as $order ) {
			$repo->update(
				(int) $order->id,
				[
					'sync_attempts' => (int) $order->sync_attempts + 1,
					'next_sync_at'  => gmdate( 'Y-m-d H:i:s', time() + 15 * MINUTE_IN_SECONDS ),
				]
			);
		}
	}

	/**
	 * سفارش‌هایی که تماس اولیه با سرویس‌دهنده در آن‌ها نتیجه نداد.
	 *
	 * چون شناسهٔ راه دور نداریم، تنها تصمیم امن پس از مهلت، بازگشت کامل وجه است.
	 */
	public static function reconcileOrphans(): void {
		$repo    = new OrderRepository();
		$service = new OrderService();
		$orphans = $repo->orphans( 30 );

		foreach ( $orphans as $order ) {
			$age = time() - (int) strtotime( (string) $order->created_at . ' UTC' );

			if ( $age < 15 * MINUTE_IN_SECONDS ) {
				continue;
			}

			$service->refundFull( $order, 'orphan_timeout' );
			$repo->update(
				(int) $order->id,
				[
					'status'        => OrderStatus::FAILED,
					'error_message' => 'orphan_timeout',
					'next_sync_at'  => null,
				]
			);

			do_action( 'clickpop/order/orphan_resolved', (int) $order->id );
		}
	}
}
