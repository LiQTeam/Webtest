<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Support\Money;

defined( 'ABSPATH' ) || exit;

final class ServicesPage {

	public static function render(): void {
		if ( ! current_user_can( 'clickpop_manage_pricing' ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		global $wpdb;

		$services   = Installer::table( 'services' );
		$categories = Installer::table( 'categories' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- جست‌وجوی فقط-خواندنی.
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;

		$per_page = 50;
		$where    = '1=1';
		$params   = [];

		if ( '' !== $search ) {
			$where   .= ' AND s.name LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $search ) . '%';
		}

		$params[] = $per_page;
		$params[] = ( $paged - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.*, c.brand, c.name AS category_name
				 FROM {$services} s LEFT JOIN {$categories} c ON c.id = s.category_id
				 WHERE {$where} ORDER BY c.brand ASC, s.name ASC LIMIT %d OFFSET %d",
				$params
			)
		);

		echo '<div class="wrap cp-admin">';
		printf( '<h1>%s</h1>', esc_html__( 'سرویس‌ها و قیمت', 'clickpop-core' ) );

		printf(
			'<form method="get"><input type="hidden" name="page" value="%s"><p class="search-box">
				<input type="search" name="s" value="%s"><button class="button">%s</button></p></form>',
			esc_attr( Menu::SLUG . '-services' ),
			esc_attr( $search ),
			esc_html__( 'جست‌وجو', 'clickpop-core' )
		);

		echo '<table class="widefat striped"><thead><tr>';
		foreach ( [ 'پلتفرم', 'دسته', 'سرویس', 'شناسهٔ سرویس‌دهنده', 'قیمت تمام‌شده', 'قیمت فروش', 'سود', 'بازه', 'وضعیت' ] as $heading ) {
			printf( '<th>%s</th>', esc_html( $heading ) );
		}
		echo '</tr></thead><tbody>';

		if ( ! $rows ) {
			printf( '<tr><td colspan="9">%s</td></tr>', esc_html__( 'سرویسی همگام نشده است. ابتدا همگام‌سازی را اجرا کنید.', 'clickpop-core' ) );
		}

		foreach ( (array) $rows as $row ) {
			$profit = (int) $row->sale_rate - (int) $row->cost_rate;

			echo '<tr>';
			printf( '<td>%s</td>', esc_html( (string) ( $row->brand ?? '—' ) ) );
			printf( '<td>%s</td>', esc_html( (string) ( $row->category_name ?? '—' ) ) );
			printf( '<td>%s</td>', esc_html( (string) $row->name ) );
			printf( '<td>%s</td>', esc_html( (string) $row->remote_service_id ) );
			printf( '<td>%s</td>', esc_html( Money::fromRials( (int) $row->cost_rate )->format( false ) ) );
			printf( '<td><strong>%s</strong></td>', esc_html( Money::fromRials( (int) $row->sale_rate )->format( false ) ) );
			printf(
				'<td class="%s">%s</td>',
				$profit <= 0 ? 'cp-neg' : '',
				esc_html( Money::fromRials( $profit )->format( false ) )
			);
			printf(
				'<td>%s — %s</td>',
				esc_html( number_format_i18n( (int) $row->min_qty ) ),
				esc_html( number_format_i18n( (int) $row->max_qty ) )
			);
			printf(
				'<td><span class="cp-pill cp-pill--%s">%s</span></td>',
				esc_attr( 'review' === $row->status ? 'warn' : ( 'active' === $row->status ? 'ok' : 'bad' ) ),
				esc_html( (string) $row->status )
			);
			echo '</tr>';
		}

		echo '</tbody></table>';
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'قیمت فروش هنگام همگام‌سازی از قاعدهٔ سود محاسبه و ذخیره می‌شود. سرویس با وضعیت review به‌خاطر جهش قیمت سرویس‌دهنده از فروش خارج شده است.', 'clickpop-core' )
		);
		echo '</div>';
	}
}
