<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Providers\ProviderManager;
use ClickPop\Core\Setup\Checklist;
use ClickPop\Core\Support\Jalali;
use ClickPop\Core\Support\Money;
use ClickPop\Core\Sync\ServiceSync;

defined( 'ABSPATH' ) || exit;

/**
 * نمای کلی: چک‌لیست آماده‌بودن، شاخص‌های ۳۰ روز، نمودار ۱۴ روز و سلامت سرویس‌دهنده.
 */
final class DashboardPage {

	public static function render(): void {
		if ( ! current_user_can( 'clickpop_manage_orders' ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		echo '<div class="wrap cp-admin">';
		printf( '<h1>%s</h1>', esc_html__( 'کلیک‌پاپ — نمای کلی', 'clickpop-core' ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط نمایش پیام نتیجه.
		if ( isset( $_GET['cp_msg'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['cp_msg'] ) ) )
			);
		}

		self::checklist();
		self::kpis();
		self::chart();
		self::health();

		echo '</div>';
	}

	private static function checklist(): void {
		$items    = Checklist::items();
		$blockers = 0;

		foreach ( $items as $item ) {
			if ( $item['blocking'] && 'ok' !== $item['state'] ) {
				++$blockers;
			}
		}

		printf(
			'<div class="cp-panelbox %s"><div class="cp-panelbox__h"><h2>%s</h2><span class="cp-pill cp-pill--%s">%s</span></div><ul class="cp-check">',
			$blockers > 0 ? 'is-blocked' : '',
			esc_html__( 'آمادگی برای شروع فروش', 'clickpop-core' ),
			esc_attr( $blockers > 0 ? 'bad' : 'ok' ),
			esc_html(
				$blockers > 0
					? sprintf(
						/* translators: %d: blocking issue count */
						__( '%d مورد بازدارنده', 'clickpop-core' ),
						$blockers
					)
					: __( 'آماده', 'clickpop-core' )
			)
		);

		foreach ( $items as $item ) {
			printf(
				'<li class="cp-check__row cp-check__row--%1$s">
					<span class="cp-check__dot" aria-hidden="true"></span>
					<span class="cp-check__body"><strong>%2$s</strong><span>%3$s</span></span>
					%4$s
				</li>',
				esc_attr( $item['state'] ),
				esc_html( $item['label'] ),
				esc_html( $item['detail'] ),
				'' !== $item['action'] && 'ok' !== $item['state']
					? sprintf(
						'<a class="button button-small" href="%s">%s</a>',
						esc_url( $item['action'] ),
						esc_html( $item['action_label'] )
					)
					: ''
			);
		}

		echo '</ul></div>';
	}

	private static function kpis(): void {
		global $wpdb;

		$orders = Installer::table( 'orders' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$now = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total,
				        COALESCE(SUM(charge - refunded), 0) AS revenue,
				        COALESCE(SUM(charge - cost - refunded), 0) AS profit,
				        SUM(CASE WHEN status IN ('processing','in_progress') THEN 1 ELSE 0 END) AS running,
				        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
				        SUM(CASE WHEN status IN ('failed','canceled') THEN 1 ELSE 0 END) AS failed
				 FROM {$orders} WHERE created_at >= %s",
				gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS )
			)
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$prev = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total, COALESCE(SUM(charge - refunded), 0) AS revenue
				 FROM {$orders} WHERE created_at >= %s AND created_at < %s",
				gmdate( 'Y-m-d H:i:s', time() - 60 * DAY_IN_SECONDS ),
				gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS )
			)
		);

		$total    = (int) ( $now->total ?? 0 );
		$revenue  = (int) ( $now->revenue ?? 0 );
		$completed = (int) ( $now->completed ?? 0 );
		$rate      = $total > 0 ? round( ( $completed / $total ) * 100 ) : 0;

		echo '<div class="cp-grid">';
		self::card(
			__( 'سفارش ۳۰ روز', 'clickpop-core' ),
			number_format_i18n( $total ),
			self::delta( $total, (int) ( $prev->total ?? 0 ) )
		);
		self::card(
			__( 'فروش ۳۰ روز', 'clickpop-core' ),
			Money::fromRials( $revenue )->format(),
			self::delta( $revenue, (int) ( $prev->revenue ?? 0 ) )
		);
		self::card( __( 'سود ناخالص ۳۰ روز', 'clickpop-core' ), Money::fromRials( (int) ( $now->profit ?? 0 ) )->format(), '' );
		self::card( __( 'در حال انجام', 'clickpop-core' ), number_format_i18n( (int) ( $now->running ?? 0 ) ), '' );
		self::card( __( 'نرخ تکمیل', 'clickpop-core' ), number_format_i18n( $rate ) . '٪', '' );
		self::card(
			__( 'ناموفق / لغو', 'clickpop-core' ),
			number_format_i18n( (int) ( $now->failed ?? 0 ) ),
			'',
			( (int) ( $now->failed ?? 0 ) ) > 0 ? 'warn' : ''
		);
		echo '</div>';
	}

	/** نمودار ستونی ۱۴ روز اخیر — SVG درون‌خطی، بدون کتابخانه. */
	private static function chart(): void {
		global $wpdb;

		$orders = Installer::table( 'orders' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(created_at) AS d, COUNT(*) AS c, COALESCE(SUM(charge - refunded), 0) AS rev
				 FROM {$orders} WHERE created_at >= %s GROUP BY DATE(created_at)",
				gmdate( 'Y-m-d 00:00:00', time() - 13 * DAY_IN_SECONDS )
			)
		);

		$map = [];
		foreach ( (array) $rows as $row ) {
			$map[ (string) $row->d ] = [
				'count' => (int) $row->c,
				'rev'   => (int) $row->rev,
			];
		}

		$days = [];
		for ( $i = 13; $i >= 0; $i-- ) {
			$key    = gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS );
			$days[] = [
				'key'   => $key,
				'label' => Jalali::format( $key . ' 00:00:00', false ),
				'count' => $map[ $key ]['count'] ?? 0,
				'rev'   => $map[ $key ]['rev'] ?? 0,
			];
		}

		$max = max( 1, max( array_column( $days, 'count' ) ) );

		echo '<div class="cp-panelbox"><div class="cp-panelbox__h"><h2>' . esc_html__( 'سفارش‌های ۱۴ روز اخیر', 'clickpop-core' ) . '</h2></div>';
		echo '<div class="cp-chart">';

		foreach ( $days as $day ) {
			$height = (int) round( ( $day['count'] / $max ) * 100 );

			printf(
				'<div class="cp-chart__col" title="%1$s"><span class="cp-chart__bar" style="height:%2$d%%"></span><span class="cp-chart__n">%3$s</span><span class="cp-chart__d">%4$s</span></div>',
				esc_attr(
					sprintf(
						/* translators: 1: date, 2: order count, 3: revenue */
						__( '%1$s — %2$s سفارش — %3$s', 'clickpop-core' ),
						$day['label'],
						number_format_i18n( $day['count'] ),
						Money::fromRials( $day['rev'] )->format()
					)
				),
				max( 2, $height ),
				esc_html( number_format_i18n( $day['count'] ) ),
				esc_html( mb_substr( $day['label'], 5 ) )
			);
		}

		echo '</div></div>';
	}

	private static function health(): void {
		$provider = ProviderManager::primaryRow();
		$last     = (array) get_option( ServiceSync::OPTION_LAST_RESULT, [] );

		echo '<div class="cp-panelbox"><div class="cp-panelbox__h"><h2>' . esc_html__( 'سلامت سرویس‌دهنده', 'clickpop-core' ) . '</h2>';
		printf(
			'<a class="button button-primary" href="%s">%s</a>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=clickpop_sync_services' ), 'clickpop_sync' ) ),
			esc_html__( 'همگام‌سازی دستی', 'clickpop-core' )
		);
		echo '</div>';

		echo '<table class="widefat striped cp-table"><tbody>';
		self::row( __( 'وضعیت', 'clickpop-core' ), $provider ? esc_html( (string) $provider->status ) : esc_html__( 'تنظیم نشده', 'clickpop-core' ) );
		self::row(
			__( 'آخرین همگام‌سازی', 'clickpop-core' ),
			$provider && $provider->last_sync_at ? esc_html( Jalali::format( (string) $provider->last_sync_at ) ) : '—'
		);
		self::row(
			__( 'تأخیر آخرین پاسخ', 'clickpop-core' ),
			$provider && $provider->latency_ms ? esc_html( number_format_i18n( (int) $provider->latency_ms ) . ' ms' ) : '—'
		);
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

		echo '</tbody></table></div>';
	}

	private static function delta( int $now, int $prev ): string {
		if ( $prev <= 0 ) {
			return '';
		}

		$change = (int) round( ( ( $now - $prev ) / $prev ) * 100 );

		if ( 0 === $change ) {
			return '';
		}

		return sprintf(
			'<span class="cp-delta cp-delta--%s">%s%s٪</span>',
			$change > 0 ? 'up' : 'down',
			$change > 0 ? '+' : '−',
			number_format_i18n( abs( $change ) )
		);
	}

	private static function card( string $label, string $value, string $delta = '', string $tone = '' ): void {
		printf(
			'<div class="cp-card %s"><span class="cp-card__l">%s</span><strong class="cp-card__v">%s</strong>%s</div>',
			esc_attr( $tone ),
			esc_html( $label ),
			esc_html( $value ),
			wp_kses_post( $delta )
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
