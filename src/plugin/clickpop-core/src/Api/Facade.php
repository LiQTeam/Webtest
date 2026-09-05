<?php
declare( strict_types=1 );

namespace ClickPop\Core\Api;

use ClickPop\Core\Repositories\ServiceRepository;
use ClickPop\Core\Support\Money;
use ClickPop\Core\Wallet\WalletService;

defined( 'ABSPATH' ) || exit;

/**
 * قرارداد پایدار برای مصرف تم و کدهای بیرونی.
 *
 * تم فقط از این کلاس استفاده می‌کند؛ هیچ‌گاه مستقیم به جدول یا Repository دست نمی‌زند.
 * امضای متدهای این کلاس بین نسخه‌های مینور شکسته نمی‌شود.
 */
final class Facade {

	/**
	 * درخت برند → دسته → سرویس.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function serviceTree(): array {
		return ( new ServiceRepository() )->tree();
	}

	/**
	 * ارزان‌ترین سرویس هر برند — برای کارت‌های «شروع قیمت از».
	 *
	 * @return array<int,array{slug:string,label:string,from:int,from_display:string,count:int}>
	 */
	public static function brandSummary(): array {
		$out = [];

		foreach ( self::serviceTree() as $brand ) {
			$min   = null;
			$count = 0;

			foreach ( $brand['categories'] as $category ) {
				foreach ( $category['services'] as $service ) {
					++$count;
					if ( null === $min || $service['rate'] < $min ) {
						$min = (int) $service['rate'];
					}
				}
			}

			$out[] = [
				'slug'         => (string) $brand['slug'],
				'label'        => (string) $brand['label'],
				'from'         => (int) ( $min ?? 0 ),
				'from_display' => Money::fromRials( (int) ( $min ?? 0 ) )->format(),
				'count'        => $count,
			];
		}

		return $out;
	}

	public static function walletBalanceDisplay( int $user_id = 0 ): string {
		$user_id = $user_id ?: get_current_user_id();

		if ( $user_id <= 0 ) {
			return '';
		}

		return Money::fromRials( ( new WalletService() )->balance( $user_id ) )->format();
	}

	public static function dashboardUrl(): string {
		$page = (int) get_option( 'clickpop_dashboard_page_id', 0 );

		return $page > 0 ? (string) get_permalink( $page ) : home_url( '/' );
	}

	/**
	 * آمار واقعی سایت برای نمایش عمومی.
	 *
	 * فقط اعداد جمعی برمی‌گردد؛ هیچ دادهٔ کاربری بیرون نمی‌رود.
	 *
	 * @return array{services:int,completed:int,rate:int}
	 */
	public static function siteStats(): array {
		$cached = get_transient( 'clickpop_site_stats' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		$services = \ClickPop\Core\Database\Installer::table( 'services' );
		$orders   = \ClickPop\Core\Database\Installer::table( 'orders' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$active = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$services} WHERE status = 'active'" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			"SELECT COUNT(*) AS total,
			        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
			 FROM {$orders}"
		);

		$total     = (int) ( $row->total ?? 0 );
		$completed = (int) ( $row->completed ?? 0 );

		$stats = [
			'services'  => $active,
			'completed' => $completed,
			'rate'      => $total > 0 ? (int) round( ( $completed / $total ) * 100 ) : 0,
		];

		set_transient( 'clickpop_site_stats', $stats, 15 * MINUTE_IN_SECONDS );

		return $stats;
	}

	public static function isReady(): bool {
		return (bool) self::serviceTree();
	}
}
