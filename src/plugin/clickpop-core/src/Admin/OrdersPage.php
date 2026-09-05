<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Orders\OrderStatus;
use ClickPop\Core\Support\Jalali;
use ClickPop\Core\Support\Money;

defined( 'ABSPATH' ) || exit;

/**
 * مدیریت سفارش‌ها: فهرست با فیلتر و جست‌وجو، و نمای جزئیات با اعمال دستی.
 */
final class OrdersPage {

	private const PER_PAGE = 30;

	public static function render(): void {
		if ( ! current_user_can( 'clickpop_manage_orders' ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		echo '<div class="wrap cp-admin">';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط نمایش پیام نتیجه.
		if ( isset( $_GET['cp_msg'] ) ) {
			printf(
				'<div class="notice notice-info is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['cp_msg'] ) ) )
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- انتخاب نما.
		$order_id = isset( $_GET['order'] ) ? absint( wp_unslash( $_GET['order'] ) ) : 0;

		if ( $order_id > 0 ) {
			self::renderSingle( $order_id );
		} else {
			self::renderList();
		}

		echo '</div>';
	}

	private static function renderList(): void {
		global $wpdb;

		$orders   = Installer::table( 'orders' );
		$services = Installer::table( 'services' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فیلتر فقط-خواندنی.
		$status = isset( $_GET['cp_status'] ) ? sanitize_key( wp_unslash( $_GET['cp_status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;

		$where  = '1=1';
		$params = [];

		if ( '' !== $status && array_key_exists( $status, OrderStatus::labels() ) ) {
			$where   .= ' AND o.status = %s';
			$params[] = $status;
		}

		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND (o.link LIKE %s OR o.remote_order_id LIKE %s OR s.name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = (int) $wpdb->get_var(
			$params
				? $wpdb->prepare( "SELECT COUNT(*) FROM {$orders} o LEFT JOIN {$services} s ON s.id = o.service_id WHERE {$where}", $params )
				: "SELECT COUNT(*) FROM {$orders} o LEFT JOIN {$services} s ON s.id = o.service_id WHERE {$where}"
		);

		$page_params   = $params;
		$page_params[] = self::PER_PAGE;
		$page_params[] = ( $paged - 1 ) * self::PER_PAGE;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.*, s.name AS service_name
				 FROM {$orders} o LEFT JOIN {$services} s ON s.id = o.service_id
				 WHERE {$where} ORDER BY o.id DESC LIMIT %d OFFSET %d",
				$page_params
			)
		);

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

		printf(
			'<form method="get"><input type="hidden" name="page" value="%s"><p class="search-box">
				<input type="search" name="s" value="%s" placeholder="%s"><button class="button">%s</button></p></form>',
			esc_attr( Menu::SLUG . '-orders' ),
			esc_attr( $search ),
			esc_attr__( 'لینک، شناسهٔ سرویس‌دهنده یا نام سرویس', 'clickpop-core' ),
			esc_html__( 'جست‌وجو', 'clickpop-core' )
		);

		echo '<table class="widefat striped"><thead><tr>';
		foreach ( [ 'شناسه', 'کاربر', 'سرویس', 'تعداد', 'مبلغ', 'سود', 'باقی‌مانده', 'وضعیت', 'تاریخ', '' ] as $heading ) {
			printf( '<th>%s</th>', esc_html( $heading ) );
		}
		echo '</tr></thead><tbody>';

		if ( ! $rows ) {
			printf( '<tr><td colspan="10">%s</td></tr>', esc_html__( 'سفارشی با این فیلتر پیدا نشد.', 'clickpop-core' ) );
		}

		foreach ( (array) $rows as $row ) {
			$user   = get_userdata( (int) $row->user_id );
			$url    = admin_url( 'admin.php?page=' . Menu::SLUG . '-orders&order=' . (int) $row->id );
			$profit = (int) $row->charge - (int) $row->cost - (int) $row->refunded;

			echo '<tr>';
			printf( '<td><a href="%s"><strong>#%d</strong></a></td>', esc_url( $url ), (int) $row->id );
			printf( '<td>%s</td>', esc_html( $user ? $user->display_name : '—' ) );
			printf( '<td>%s</td>', esc_html( (string) ( $row->service_name ?? '—' ) ) );
			printf( '<td>%s</td>', esc_html( number_format_i18n( (int) $row->quantity ) ) );
			printf( '<td>%s</td>', esc_html( Money::fromRials( (int) $row->charge )->format( false ) ) );
			printf(
				'<td class="%s">%s</td>',
				$profit < 0 ? 'cp-neg' : '',
				esc_html( Money::fromRials( $profit )->format( false ) )
			);
			printf( '<td>%s</td>', null === $row->remains ? '—' : esc_html( number_format_i18n( (int) $row->remains ) ) );
			printf(
				'<td><span class="cp-pill cp-pill--%s">%s</span></td>',
				esc_attr( OrderStatus::tone( (string) $row->status ) ),
				esc_html( OrderStatus::label( (string) $row->status ) )
			);
			printf( '<td>%s</td>', esc_html( Jalali::format( (string) $row->created_at, false ) ) );
			printf( '<td><a class="button button-small" href="%s">%s</a></td>', esc_url( $url ), esc_html__( 'مدیریت', 'clickpop-core' ) );
			echo '</tr>';
		}

		echo '</tbody></table>';

		self::pagination( $total, $paged, [ 'cp_status' => $status, 's' => $search ] );
	}

	private static function renderSingle( int $order_id ): void {
		global $wpdb;

		$orders   = Installer::table( 'orders' );
		$services = Installer::table( 'services' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$order = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT o.*, s.name AS service_name FROM {$orders} o
				 LEFT JOIN {$services} s ON s.id = o.service_id WHERE o.id = %d",
				$order_id
			)
		);

		if ( ! $order ) {
			printf( '<h1>%s</h1><p>%s</p>', esc_html__( 'سفارش', 'clickpop-core' ), esc_html__( 'سفارش پیدا نشد.', 'clickpop-core' ) );

			return;
		}

		$user   = get_userdata( (int) $order->user_id );
		$profit = (int) $order->charge - (int) $order->cost - (int) $order->refunded;

		printf( '<h1>%s #%d</h1>', esc_html__( 'سفارش', 'clickpop-core' ), (int) $order->id );
		printf(
			'<p><a href="%s">&larr; %s</a></p>',
			esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-orders' ) ),
			esc_html__( 'بازگشت به فهرست', 'clickpop-core' )
		);

		echo '<div class="cp-thread-wrap"><div class="cp-thread">';

		echo '<div class="cp-box"><h2>' . esc_html__( 'جزئیات', 'clickpop-core' ) . '</h2><table class="cp-kv">';
		self::kv( __( 'کاربر', 'clickpop-core' ), $user ? $user->display_name . ' (' . $user->user_email . ')' : '—' );
		self::kv( __( 'سرویس', 'clickpop-core' ), (string) ( $order->service_name ?? '—' ) );
		self::kv( __( 'لینک هدف', 'clickpop-core' ), (string) $order->link );
		self::kv( __( 'تعداد', 'clickpop-core' ), number_format_i18n( (int) $order->quantity ) );
		self::kv( __( 'مبلغ دریافتی', 'clickpop-core' ), Money::fromRials( (int) $order->charge )->format() );
		self::kv( __( 'هزینهٔ سرویس‌دهنده', 'clickpop-core' ), Money::fromRials( (int) $order->cost )->format() );
		self::kv( __( 'برگشت‌خورده', 'clickpop-core' ), Money::fromRials( (int) $order->refunded )->format() );
		self::kv( __( 'سود خالص', 'clickpop-core' ), Money::fromRials( $profit )->format() );
		self::kv( __( 'شناسهٔ سرویس‌دهنده', 'clickpop-core' ), (string) ( $order->remote_order_id ?? '—' ) );
		self::kv( __( 'وضعیت خام سرویس‌دهنده', 'clickpop-core' ), (string) ( $order->provider_status ?? '—' ) );
		self::kv( __( 'شمارندهٔ شروع', 'clickpop-core' ), null === $order->start_count ? '—' : number_format_i18n( (int) $order->start_count ) );
		self::kv( __( 'باقی‌مانده', 'clickpop-core' ), null === $order->remains ? '—' : number_format_i18n( (int) $order->remains ) );
		self::kv( __( 'ثبت', 'clickpop-core' ), Jalali::format( (string) $order->created_at ) );
		self::kv( __( 'آخرین تغییر', 'clickpop-core' ), Jalali::format( (string) $order->updated_at ) );
		self::kv( __( 'تعداد تلاش همگام‌سازی', 'clickpop-core' ), number_format_i18n( (int) $order->sync_attempts ) );

		if ( $order->error_message ) {
			self::kv( __( 'پیام خطا', 'clickpop-core' ), (string) $order->error_message );
		}

		echo '</table></div>';

		self::transactions( (int) $order->id );

		echo '</div><aside class="cp-thread-side">';

		/* ── اعمال ── */
		echo '<div class="cp-box"><h2>' . esc_html__( 'اعمال', 'clickpop-core' ) . '</h2>';

		self::actionForm(
			$order_id,
			'resync',
			__( 'بررسی وضعیت از سرویس‌دهنده', 'clickpop-core' ),
			__( 'همین حالا وضعیت را می‌خواند، بدون انتظار برای کرون.', 'clickpop-core' ),
			'primary'
		);

		echo '<hr>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( OrderActions::NONCE );
		echo '<input type="hidden" name="action" value="clickpop_order_action">';
		echo '<input type="hidden" name="do" value="set_status">';
		printf( '<input type="hidden" name="order_id" value="%d">', $order_id );
		printf( '<p><label for="cp-o-status">%s</label><br>', esc_html__( 'تغییر دستی وضعیت', 'clickpop-core' ) );
		echo '<select id="cp-o-status" name="status" class="widefat">';
		foreach ( OrderStatus::labels() as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $key, (string) $order->status, false ),
				esc_html( $label )
			);
		}
		echo '</select></p>';
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'این کار فقط برچسب وضعیت را عوض می‌کند و هیچ پولی جابه‌جا نمی‌کند.', 'clickpop-core' )
		);
		submit_button( __( 'ذخیرهٔ وضعیت', 'clickpop-core' ), 'secondary', 'submit', false );
		echo '</form>';

		echo '<hr>';

		$remaining = (int) $order->charge - (int) $order->refunded;

		if ( $remaining > 0 ) {
			self::actionForm(
				$order_id,
				'refund_full',
				sprintf(
					/* translators: %s: amount */
					__( 'بازگشت %s به کیف پول', 'clickpop-core' ),
					Money::fromRials( $remaining )->format()
				),
				__( 'یک ردیف بازگشت وجه در دفتر کل ثبت می‌شود. قابل بازگشت نیست.', 'clickpop-core' ),
				'delete',
				__( 'مطمئنی؟ این عمل مبلغ را به کیف پول کاربر برمی‌گرداند و برگشت‌پذیر نیست.', 'clickpop-core' )
			);
		} else {
			printf( '<p class="description">%s</p>', esc_html__( 'مبلغ این سفارش کاملاً برگشت خورده است.', 'clickpop-core' ) );
		}

		echo '</div></aside></div>';
	}

	private static function transactions( int $order_id ): void {
		global $wpdb;

		$table = Installer::table( 'transactions' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE ref_type = 'order' AND ref_id = %d ORDER BY id ASC",
				$order_id
			)
		);

		echo '<div class="cp-box"><h2>' . esc_html__( 'تراکنش‌های این سفارش', 'clickpop-core' ) . '</h2>';

		if ( ! $rows ) {
			printf( '<p class="description">%s</p></div>', esc_html__( 'تراکنشی ثبت نشده است.', 'clickpop-core' ) );

			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		foreach ( [ 'شناسه', 'نوع', 'جهت', 'مبلغ', 'موجودی پس از', 'دلیل', 'تاریخ' ] as $heading ) {
			printf( '<th>%s</th>', esc_html( $heading ) );
		}
		echo '</tr></thead><tbody>';

		foreach ( (array) $rows as $row ) {
			echo '<tr>';
			printf( '<td>#%d</td>', (int) $row->id );
			printf( '<td>%s</td>', esc_html( (string) $row->type ) );
			printf( '<td>%s</td>', esc_html( 'credit' === $row->direction ? '+' : '−' ) );
			printf( '<td>%s</td>', esc_html( Money::fromRials( (int) $row->amount )->format( false ) ) );
			printf( '<td>%s</td>', esc_html( Money::fromRials( (int) $row->balance_after )->format( false ) ) );
			printf( '<td>%s</td>', esc_html( (string) ( $row->reason ?? '' ) ) );
			printf( '<td>%s</td>', esc_html( Jalali::format( (string) $row->created_at ) ) );
			echo '</tr>';
		}

		echo '</tbody></table></div>';
	}

	private static function actionForm( int $order_id, string $do, string $label, string $hint, string $style, string $confirm = '' ): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( OrderActions::NONCE );
		echo '<input type="hidden" name="action" value="clickpop_order_action">';
		printf( '<input type="hidden" name="do" value="%s">', esc_attr( $do ) );
		printf( '<input type="hidden" name="order_id" value="%d">', $order_id );
		printf(
			'<p><button type="submit" class="button button-%1$s"%2$s>%3$s</button></p><p class="description">%4$s</p>',
			esc_attr( $style ),
			'' === $confirm ? '' : ' onclick="return confirm(' . esc_attr( wp_json_encode( $confirm ) ) . ')"',
			esc_html( $label ),
			esc_html( $hint )
		);
		echo '</form>';
	}

	private static function kv( string $key, string $value ): void {
		printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html( $key ), esc_html( $value ) );
	}

	/** @param array<string,string> $args */
	private static function pagination( int $total, int $paged, array $args ): void {
		$pages = (int) ceil( $total / self::PER_PAGE );

		if ( $pages < 2 ) {
			return;
		}

		$base = add_query_arg(
			array_filter( array_merge( [ 'page' => Menu::SLUG . '-orders' ], $args ) ),
			admin_url( 'admin.php' )
		);

		echo '<div class="tablenav"><div class="tablenav-pages">';
		echo wp_kses_post(
			paginate_links(
				[
					'base'      => add_query_arg( 'paged', '%#%', $base ),
					'format'    => '',
					'current'   => $paged,
					'total'     => $pages,
					'prev_text' => __( 'قبلی', 'clickpop-core' ),
					'next_text' => __( 'بعدی', 'clickpop-core' ),
				]
			) ?? ''
		);
		echo '</div></div>';
	}
}
