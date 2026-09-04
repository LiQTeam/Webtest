<?php
declare( strict_types=1 );

namespace ClickPop\Core\Sync;

defined( 'ABSPATH' ) || exit;

/**
 * زمان‌بندی کارهای دوره‌ای روی WP-Cron.
 *
 * روی سرور واقعی، WP-Cron باید با system cron صدا زده شود
 * (DISABLE_WP_CRON=true + یک کرون سیستمی هر دقیقه) وگرنه همگام‌سازی قابل اتکا نیست.
 */
final class Scheduler {

	public const HOOK_SERVICES = 'clickpop_sync_services';
	public const HOOK_ORDERS   = 'clickpop_sync_orders';
	public const HOOK_ORPHANS  = 'clickpop_reconcile_orphans';

	public static function register(): void {
		add_filter( 'cron_schedules', [ self::class, 'intervals' ] );

		add_action( self::HOOK_SERVICES, [ ServiceSync::class, 'run' ] );
		add_action( self::HOOK_ORDERS, [ OrderStatusSync::class, 'run' ] );
		add_action( self::HOOK_ORPHANS, [ OrderStatusSync::class, 'reconcileOrphans' ] );
	}

	/**
	 * @param array<string,array{interval:int,display:string}> $schedules
	 * @return array<string,array{interval:int,display:string}>
	 */
	public static function intervals( array $schedules ): array {
		$schedules['clickpop_5min'] = [
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => __( 'هر ۵ دقیقه (کلیک‌پاپ)', 'clickpop-core' ),
		];
		$schedules['clickpop_6h']   = [
			'interval' => 6 * HOUR_IN_SECONDS,
			'display'  => __( 'هر ۶ ساعت (کلیک‌پاپ)', 'clickpop-core' ),
		];

		return $schedules;
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK_SERVICES ) ) {
			wp_schedule_event( time() + 60, 'clickpop_6h', self::HOOK_SERVICES );
		}
		if ( ! wp_next_scheduled( self::HOOK_ORDERS ) ) {
			wp_schedule_event( time() + 120, 'clickpop_5min', self::HOOK_ORDERS );
		}
		if ( ! wp_next_scheduled( self::HOOK_ORPHANS ) ) {
			wp_schedule_event( time() + 180, 'clickpop_5min', self::HOOK_ORPHANS );
		}
	}

	public static function unschedule(): void {
		foreach ( [ self::HOOK_SERVICES, self::HOOK_ORDERS, self::HOOK_ORPHANS ] as $hook ) {
			$timestamp = wp_next_scheduled( $hook );
			while ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
				$timestamp = wp_next_scheduled( $hook );
			}
		}
	}
}
