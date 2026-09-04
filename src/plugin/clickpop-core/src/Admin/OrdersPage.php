<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Orders\OrderStatus;
use ClickPop\Core\Support\Jalali;
use ClickPop\Core\Support\Money;

defined( 'ABSPATH' ) || exit;

final class OrdersPage {

	public static function render(): void {
		if ( ! current_user_can( 'clickpop_manage_orders' ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		global $wpdb;

		$orders   = Installer::table( 'orders' );
		$services = Installer::table( 'services' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فیلتر فقط-خواندنی روی فهرست.
		$status = isset( $_GET['cp_status'] ) ? sanitize_key( wp_unslash( $_GET['cp_status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;

		$per_page = 30;
		$offset   = ( $paged - 1 ) * $per_page;

		$where  = '1=1';
		$params = [];

		if ( '' !== $status && array_key_exists( $status, OrderStatus::labels() ) ) {
			$where   .= ' AND o.status = %s';
			$params[] = $status;
		}

		$params[] = $per_page;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.*, s.name AS service_name
				 FROM {$orders} o LEFT JOIN {$services} s ON s.id = o.service_id
				 WHERE {$where} ORDER BY o.id DESC LIMIT %d OFFSET %d",
				$params
			)
		);

		echo '<div class="wrap cp-admin">';
		printf( '<h1>%s</h1>', esc_html__( 'سفارش‌ها', 'clickpop-core' ) );

		echo '<ul class="subsubsub">';
		printf(
			'<li><a href="%s" class="%s">%s</a> | </li>',
			esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-orders' ) ),
			'' === $status ? 'current' : '',
			esc_html__( 'همه', 'clickpop-core' )
		);
		foreach ( OrderStatus::labels() as $key => $label ) {
			printf(
				'<li><a href="%s" class="%s">%s</a> | </li>',
				esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-orders&cp_status=' . $key ) ),
				$status === $key ? 'current' : '',
				esc_html( $label )
			);
		}
		echo '</ul><br class="clear">';

		echo '<table class="widefat striped"><thead><tr>';
		foreach ( [ 'شناسه', 'کاربر', 'سرویس', 'تعداد', 'مبلغ', 'برگشتی', 'باقی‌مانده', 'وضعیت', 'شناسهٔ سرویس‌دهنده', 'تاریخ' ] as $heading ) {
			printf( '<th>%s</th>', esc_html( $heading ) );
		}
		echo '</tr></thead><tbody>';

		if ( ! $rows ) {
			printf( '<tr><td colspan="10">%s</td></tr>', esc_html__( 'سفارشی ثبت نشده است.', 'clickpop-core' ) );
		}

		foreach ( (array) $rows as $row ) {
			$user = get_userdata( (int) $row->user_id );

			echo '<tr>';
			printf( '<td>#%d</td>', (int) $row->id );
			printf( '<td>%s</td>', esc_html( $user ? $user->display_name : '—' ) );
			printf( '<td>%s</td>', esc_html( (string) ( $row->service_name ?? '—' ) ) );
			printf( '<td>%s</td>', esc_html( number_format_i18n( (int) $row->quantity ) ) );
			printf( '<td>%s</td>', esc_html( Money::fromRials( (int) $row->charge )->format( false ) ) );
			printf( '<td>%s</td>', esc_html( Money::fromRials( (int) $row->refunded )->format( false ) ) );
			printf( '<td>%s</td>', null === $row->remains ? '—' : esc_html( number_format_i18n( (int) $row->remains ) ) );
			printf(
				'<td><span class="cp-pill cp-pill--%s">%s</span></td>',
				esc_attr( OrderStatus::tone( (string) $row->status ) ),
				esc_html( OrderStatus::label( (string) $row->status ) )
			);
			printf( '<td>%s</td>', esc_html( (string) ( $row->remote_order_id ?? '—' ) ) );
			printf( '<td>%s</td>', esc_html( Jalali::format( (string) $row->created_at ) ) );
			echo '</tr>';
		}

		echo '</tbody></table>';

		printf(
			'<p class="description">%s</p>',
			esc_html__( 'وضعیت‌ها هر ۵ دقیقه با سرویس‌دهنده همگام می‌شوند. بازگشت وجه سفارش‌های ناقص و لغوشده خودکار است.', 'clickpop-core' )
		);

		echo '</div>';
	}
}
