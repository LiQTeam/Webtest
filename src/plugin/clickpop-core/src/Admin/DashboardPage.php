<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Providers\ProviderManager;
use ClickPop\Core\Repositories\ServiceRepository;
use ClickPop\Core\Support\Money;
use ClickPop\Core\Sync\ServiceSync;

defined( 'ABSPATH' ) || exit;

final class DashboardPage {

	public static function render(): void {
		if ( ! current_user_can( 'clickpop_manage_orders' ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		global $wpdb;

		$orders   = Installer::table( 'orders' );
		$provider = ProviderManager::primaryRow();
		$last     = (array) get_option( ServiceSync::OPTION_LAST_RESULT, [] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total,
				        SUM(charge) AS revenue,
				        SUM(cost) AS cost,
				        SUM(CASE WHEN status IN ('processing','in_progress') THEN 1 ELSE 0 END) AS running,
				        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
				 FROM {$orders} WHERE created_at >= %s",
				gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS )
			)
		);

		$counts  = ( new ServiceRepository() )->statusCounts();
		$revenue = (int) ( $stats->revenue ?? 0 );
		$cost    = (int) ( $stats->cost ?? 0 );

		echo '<div class="wrap cp-admin">';
		printf( '<h1>%s</h1>', esc_html__( 'کلیک‌پاپ — نمای کلی', 'clickpop-core' ) );

		echo '<div class="cp-grid">';
		self::card( __( 'سفارش ۳۰ روز اخیر', 'clickpop-core' ), number_format_i18n( (int) ( $stats->total ?? 0 ) ) );
		self::card( __( 'در حال انجام', 'clickpop-core' ), number_format_i18n( (int) ( $stats->running ?? 0 ) ) );
		self::card( __( 'فروش ۳۰ روز', 'clickpop-core' ), Money::fromRials( $revenue )->format() );
		self::card( __( 'سود ناخالص ۳۰ روز', 'clickpop-core' ), Money::fromRials( $revenue - $cost )->format() );
		self::card( __( 'سرویس فعال', 'clickpop-core' ), number_format_i18n( $counts['active'] ?? 0 ) );
		self::card(
			__( 'نیازمند بازبینی قیمت', 'clickpop-core' ),
			number_format_i18n( $counts['review'] ?? 0 ),
			( $counts['review'] ?? 0 ) > 0 ? 'warn' : ''
		);
		echo '</div>';

		echo '<h2>' . esc_html__( 'سلامت سرویس‌دهنده', 'clickpop-core' ) . '</h2>';
		echo '<table class="widefat striped cp-table"><tbody>';
		self::row( __( 'وضعیت', 'clickpop-core' ), $provider ? esc_html( (string) $provider->status ) : esc_html__( 'تنظیم نشده', 'clickpop-core' ) );
		self::row( __( 'آخرین همگام‌سازی', 'clickpop-core' ), $provider && $provider->last_sync_at ? esc_html( \ClickPop\Core\Support\Jalali::format( (string) $provider->last_sync_at ) ) : '—' );
		self::row( __( 'تأخیر آخرین پاسخ', 'clickpop-core' ), $provider && $provider->latency_ms ? esc_html( number_format_i18n( (int) $provider->latency_ms ) . ' ms' ) : '—' );
		self::row( __( 'موجودی سرویس‌دهنده (کش)', 'clickpop-core' ), $provider ? esc_html( Money::fromRials( (int) $provider->balance_cache )->format() ) : '—' );
		self::row(
			__( 'مدارشکن', 'clickpop-core' ),
			$provider && $provider->circuit_open_until && strtotime( (string) $provider->circuit_open_until . ' UTC' ) > time()
				? '<strong style="color:#b32d2e">' . esc_html__( 'باز — تماس‌ها موقتاً متوقف است', 'clickpop-core' ) . '</strong>'
				: esc_html__( 'بسته', 'clickpop-core' )
		);
		if ( $last ) {
			self::row(
				__( 'نتیجهٔ آخرین همگام‌سازی', 'clickpop-core' ),
				esc_html(
					sprintf(
						/* translators: 1: created, 2: updated, 3: archived, 4: flagged */
						__( 'جدید: %1$s · به‌روز: %2$s · آرشیو: %3$s · بازبینی: %4$s', 'clickpop-core' ),
						number_format_i18n( (int) ( $last['created'] ?? 0 ) ),
						number_format_i18n( (int) ( $last['updated'] ?? 0 ) ),
						number_format_i18n( (int) ( $last['archived'] ?? 0 ) ),
						number_format_i18n( (int) ( $last['flagged'] ?? 0 ) )
					)
				)
			);
		}
		echo '</tbody></table>';

		printf(
			'<p><a class="button button-primary" href="%s">%s</a></p>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=clickpop_sync_services' ), 'clickpop_sync' ) ),
			esc_html__( 'همگام‌سازی دستی سرویس‌ها', 'clickpop-core' )
		);

		echo '</div>';
	}

	private static function card( string $label, string $value, string $tone = '' ): void {
		printf(
			'<div class="cp-card %s"><span class="cp-card__l">%s</span><strong class="cp-card__v">%s</strong></div>',
			esc_attr( $tone ),
			esc_html( $label ),
			esc_html( $value )
		);
	}

	private static function row( string $label, string $value_html ): void {
		printf(
			'<tr><th scope="row" style="width:240px">%s</th><td>%s</td></tr>',
			esc_html( $label ),
			wp_kses_post( $value_html )
		);
	}
}
