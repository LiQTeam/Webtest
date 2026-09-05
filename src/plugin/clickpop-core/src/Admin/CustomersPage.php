<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Support\Audit;
use ClickPop\Core\Support\Jalali;
use ClickPop\Core\Support\Money;
use ClickPop\Core\Tickets\TicketService;
use ClickPop\Core\Wallet\WalletService;

defined( 'ABSPATH' ) || exit;

/**
 * مدیریت مشتریان.
 *
 * فهرست با موجودی، تعداد سفارش، مجموع خرید و آخرین فعالیت؛
 * و نمای تک‌کاربر با تعدیل موجودی، سفارش‌ها، تراکنش‌ها و تیکت‌ها.
 */
final class CustomersPage {

	private const CAP      = 'clickpop_manage_orders';
	private const NONCE    = 'clickpop_customer_action';
	private const PER_PAGE = 30;

	public static function register(): void {
		add_action( 'admin_post_clickpop_customer_action', [ self::class, 'handle' ] );
	}

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		echo '<div class="wrap cp-admin">';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط نمایش پیام.
		if ( isset( $_GET['cp_msg'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['cp_msg'] ) ) )
			);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- انتخاب نما.
		$user_id = isset( $_GET['customer'] ) ? absint( wp_unslash( $_GET['customer'] ) ) : 0;

		if ( $user_id > 0 ) {
			self::renderSingle( $user_id );
		} else {
			self::renderList();
		}

		echo '</div>';
	}

	private static function renderList(): void {
		global $wpdb;

		$orders  = Installer::table( 'orders' );
		$wallets = Installer::table( 'wallets' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- جست‌وجوی فقط-خواندنی.
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sort = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'spent';

		$order_by = match ( $sort ) {
			'balance' => 'balance DESC',
			'orders'  => 'order_count DESC',
			'recent'  => 'last_order DESC',
			default   => 'spent DESC',
		};

		$args = [
			'number'  => self::PER_PAGE,
			'paged'   => $paged,
			'orderby' => 'registered',
			'order'   => 'DESC',
		];

		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = [ 'user_login', 'user_email', 'display_name' ];
		}

		$query = new \WP_User_Query( $args );
		$users = $query->get_results();
		$total = (int) $query->get_total();

		printf( '<h1>%s</h1>', esc_html__( 'مشتریان', 'clickpop-core' ) );

		echo '<div class="cp-toolbar">';
		printf(
			'<form method="get" class="cp-toolbar__form"><input type="hidden" name="page" value="%s">
				<input type="search" name="s" value="%s" placeholder="%s">
				<select name="sort">',
			esc_attr( Menu::SLUG . '-customers' ),
			esc_attr( $search ),
			esc_attr__( 'نام، ایمیل یا نام کاربری', 'clickpop-core' )
		);
		foreach ( [
			'spent'   => __( 'بیشترین خرید', 'clickpop-core' ),
			'balance' => __( 'بیشترین موجودی', 'clickpop-core' ),
			'orders'  => __( 'بیشترین سفارش', 'clickpop-core' ),
			'recent'  => __( 'آخرین سفارش', 'clickpop-core' ),
		] as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $key, $sort, false ),
				esc_html( $label )
			);
		}
		printf( '</select><button class="button">%s</button></form>', esc_html__( 'اعمال', 'clickpop-core' ) );
		echo '</div>';

		echo '<table class="widefat striped cp-customers"><thead><tr>';
		foreach ( [ 'مشتری', 'موجودی', 'سفارش‌ها', 'مجموع خرید', 'آخرین سفارش', 'عضویت', '' ] as $heading ) {
			printf( '<th>%s</th>', esc_html( $heading ) );
		}
		echo '</tr></thead><tbody>';

		if ( ! $users ) {
			printf( '<tr><td colspan="7">%s</td></tr>', esc_html__( 'کاربری پیدا نشد.', 'clickpop-core' ) );
		}

		foreach ( $users as $user ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$stats = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(*) AS order_count,
					        COALESCE(SUM(charge - refunded), 0) AS spent,
					        MAX(created_at) AS last_order
					 FROM {$orders} WHERE user_id = %d",
					$user->ID
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$balance = (int) $wpdb->get_var( $wpdb->prepare( "SELECT balance FROM {$wallets} WHERE user_id = %d", $user->ID ) );

			$url = admin_url( 'admin.php?page=' . Menu::SLUG . '-customers&customer=' . (int) $user->ID );

			echo '<tr>';
			printf(
				'<td><a href="%1$s"><strong>%2$s</strong></a><br><small class="cp-muted">%3$s</small></td>',
				esc_url( $url ),
				esc_html( $user->display_name ),
				esc_html( $user->user_email )
			);
			printf( '<td><strong>%s</strong></td>', esc_html( Money::fromRials( $balance )->format( false ) ) );
			printf( '<td>%s</td>', esc_html( number_format_i18n( (int) ( $stats->order_count ?? 0 ) ) ) );
			printf( '<td>%s</td>', esc_html( Money::fromRials( (int) ( $stats->spent ?? 0 ) )->format( false ) ) );
			printf(
				'<td>%s</td>',
				empty( $stats->last_order ) ? '—' : esc_html( Jalali::format( (string) $stats->last_order, false ) )
			);
			printf( '<td>%s</td>', esc_html( Jalali::format( (string) $user->user_registered, false ) ) );
			printf( '<td><a class="button button-small" href="%s">%s</a></td>', esc_url( $url ), esc_html__( 'مدیریت', 'clickpop-core' ) );
			echo '</tr>';
		}

		echo '</tbody></table>';

		self::pagination( $total, $paged, [ 's' => $search, 'sort' => $sort ] );
	}

	private static function renderSingle( int $user_id ): void {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			printf( '<h1>%s</h1><p>%s</p>', esc_html__( 'مشتری', 'clickpop-core' ), esc_html__( 'کاربر پیدا نشد.', 'clickpop-core' ) );

			return;
		}

		global $wpdb;

		$orders_tbl = Installer::table( 'orders' );
		$txn_tbl    = Installer::table( 'transactions' );
		$services   = Installer::table( 'services' );

		$wallet  = new WalletService();
		$balance = $wallet->balance( $user_id );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total,
				        COALESCE(SUM(charge - refunded), 0) AS spent,
				        COALESCE(SUM(charge - cost - refunded), 0) AS profit,
				        SUM(CASE WHEN status IN ('processing','in_progress') THEN 1 ELSE 0 END) AS running,
				        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed
				 FROM {$orders_tbl} WHERE user_id = %d",
				$user_id
			)
		);

		printf( '<h1>%s</h1>', esc_html( $user->display_name ) );
		printf(
			'<p><a href="%s">&larr; %s</a> &nbsp;·&nbsp; <a href="%s">%s</a></p>',
			esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-customers' ) ),
			esc_html__( 'بازگشت به فهرست', 'clickpop-core' ),
			esc_url( get_edit_user_link( $user_id ) ),
			esc_html__( 'ویرایش پروفایل وردپرس', 'clickpop-core' )
		);

		echo '<div class="cp-grid">';
		self::card( __( 'موجودی کیف پول', 'clickpop-core' ), Money::fromRials( $balance )->format(), 'wallet' );
		self::card( __( 'مجموع خرید', 'clickpop-core' ), Money::fromRials( (int) ( $stats->spent ?? 0 ) )->format(), '' );
		self::card( __( 'سود شما از این مشتری', 'clickpop-core' ), Money::fromRials( (int) ( $stats->profit ?? 0 ) )->format(), '' );
		self::card( __( 'سفارش', 'clickpop-core' ), number_format_i18n( (int) ( $stats->total ?? 0 ) ), '' );
		self::card( __( 'در حال انجام', 'clickpop-core' ), number_format_i18n( (int) ( $stats->running ?? 0 ) ), ( (int) ( $stats->running ?? 0 ) ) > 0 ? 'warn' : '' );
		self::card( __( 'تکمیل‌شده', 'clickpop-core' ), number_format_i18n( (int) ( $stats->completed ?? 0 ) ), '' );
		echo '</div>';

		echo '<div class="cp-thread-wrap"><div class="cp-thread">';

		/* ── سفارش‌های اخیر ── */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$recent = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.*, s.name AS service_name FROM {$orders_tbl} o
				 LEFT JOIN {$services} s ON s.id = o.service_id
				 WHERE o.user_id = %d ORDER BY o.id DESC LIMIT 15",
				$user_id
			)
		);

		echo '<div class="cp-box"><h2>' . esc_html__( 'سفارش‌های اخیر', 'clickpop-core' ) . '</h2>';

		if ( ! $recent ) {
			printf( '<p class="description">%s</p>', esc_html__( 'سفارشی ثبت نکرده است.', 'clickpop-core' ) );
		} else {
			echo '<table class="widefat striped"><thead><tr>';
			foreach ( [ 'شناسه', 'سرویس', 'تعداد', 'مبلغ', 'وضعیت', 'تاریخ' ] as $h ) {
				printf( '<th>%s</th>', esc_html( $h ) );
			}
			echo '</tr></thead><tbody>';

			foreach ( $recent as $row ) {
				printf(
					'<tr><td><a href="%1$s">#%2$d</a></td><td>%3$s</td><td>%4$s</td><td>%5$s</td>
					 <td><span class="cp-pill cp-pill--%6$s">%7$s</span></td><td>%8$s</td></tr>',
					esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-orders&order=' . (int) $row->id ) ),
					(int) $row->id,
					esc_html( (string) ( $row->service_name ?? '—' ) ),
					esc_html( number_format_i18n( (int) $row->quantity ) ),
					esc_html( Money::fromRials( (int) $row->charge )->format( false ) ),
					esc_attr( \ClickPop\Core\Orders\OrderStatus::tone( (string) $row->status ) ),
					esc_html( \ClickPop\Core\Orders\OrderStatus::label( (string) $row->status ) ),
					esc_html( Jalali::format( (string) $row->created_at, false ) )
				);
			}

			echo '</tbody></table>';
		}
		echo '</div>';

		/* ── تراکنش‌ها ── */
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$txns = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$txn_tbl} WHERE user_id = %d ORDER BY id DESC LIMIT 15", $user_id )
		);

		echo '<div class="cp-box"><h2>' . esc_html__( 'تراکنش‌های اخیر', 'clickpop-core' ) . '</h2>';

		if ( ! $txns ) {
			printf( '<p class="description">%s</p>', esc_html__( 'تراکنشی ندارد.', 'clickpop-core' ) );
		} else {
			echo '<table class="widefat striped"><thead><tr>';
			foreach ( [ 'نوع', 'جهت', 'مبلغ', 'موجودی پس از', 'وضعیت', 'دلیل', 'تاریخ' ] as $h ) {
				printf( '<th>%s</th>', esc_html( $h ) );
			}
			echo '</tr></thead><tbody>';

			foreach ( $txns as $row ) {
				printf(
					'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
					esc_html( (string) $row->type ),
					esc_html( 'credit' === $row->direction ? '+' : '−' ),
					esc_html( Money::fromRials( (int) $row->amount )->format( false ) ),
					esc_html( Money::fromRials( (int) $row->balance_after )->format( false ) ),
					esc_html( (string) $row->status ),
					esc_html( (string) ( $row->reason ?? '' ) ),
					esc_html( Jalali::format( (string) $row->created_at ) )
				);
			}

			echo '</tbody></table>';
		}
		echo '</div>';

		echo '</div><aside class="cp-thread-side">';

		/* ── تعدیل موجودی ── */
		echo '<div class="cp-box"><h2>' . esc_html__( 'تعدیل موجودی', 'clickpop-core' ) . '</h2>';

		if ( current_user_can( 'clickpop_adjust_balance' ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( self::NONCE );
			echo '<input type="hidden" name="action" value="clickpop_customer_action">';
			echo '<input type="hidden" name="do" value="adjust">';
			printf( '<input type="hidden" name="user_id" value="%d">', $user_id );

			printf(
				'<p><label for="cp-adj">%s</label><br><input type="number" step="1" id="cp-adj" name="amount_toman" class="widefat" required></p>
				 <p class="description">%s</p>',
				esc_html__( 'مبلغ (تومان)', 'clickpop-core' ),
				esc_html__( 'مثبت = افزایش، منفی = کسر.', 'clickpop-core' )
			);
			printf(
				'<p><label for="cp-adj-r">%s</label><br><input type="text" id="cp-adj-r" name="reason" class="widefat" required maxlength="255"></p>',
				esc_html__( 'دلیل (اجباری)', 'clickpop-core' )
			);

			submit_button( __( 'ثبت تعدیل', 'clickpop-core' ), 'secondary', 'submit', false );
			echo '</form>';
		} else {
			printf( '<p class="description">%s</p>', esc_html__( 'برای این کار به دسترسی «تعدیل موجودی» نیاز دارید.', 'clickpop-core' ) );
		}

		echo '</div>';

		/* ── تیکت‌ها ── */
		$tickets = ( new TicketService() )->forUser( $user_id, 10 );

		echo '<div class="cp-box"><h2>' . esc_html__( 'تیکت‌ها', 'clickpop-core' ) . '</h2>';

		if ( ! $tickets ) {
			printf( '<p class="description">%s</p>', esc_html__( 'تیکتی ندارد.', 'clickpop-core' ) );
		} else {
			echo '<ul class="cp-linklist">';
			foreach ( $tickets as $ticket ) {
				printf(
					'<li><a href="%s">%s</a><br><small class="cp-muted">%s · %s</small></li>',
					esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-tickets&ticket=' . (int) $ticket->id ) ),
					esc_html( (string) $ticket->subject ),
					esc_html( TicketsPage::statuses()[ $ticket->status ] ?? (string) $ticket->status ),
					esc_html( Jalali::format( (string) $ticket->last_reply_at, false ) )
				);
			}
			echo '</ul>';
		}

		echo '</div>';

		/* ── اطلاعات حساب ── */
		echo '<div class="cp-box"><h2>' . esc_html__( 'حساب', 'clickpop-core' ) . '</h2><table class="cp-kv">';
		self::kv( __( 'نام کاربری', 'clickpop-core' ), $user->user_login );
		self::kv( __( 'ایمیل', 'clickpop-core' ), $user->user_email );
		self::kv( __( 'نقش', 'clickpop-core' ), implode( '، ', $user->roles ) );
		self::kv( __( 'عضویت', 'clickpop-core' ), Jalali::format( (string) $user->user_registered ) );
		echo '</table></div>';

		echo '</aside></div>';
	}

	public static function handle(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::NONCE );

		$user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		$do      = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$message = __( 'عملیات انجام نشد.', 'clickpop-core' );

		if ( 'adjust' === $do && $user_id > 0 ) {
			if ( ! current_user_can( 'clickpop_adjust_balance' ) ) {
				wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
			}

			$toman  = isset( $_POST['amount_toman'] ) ? (int) wp_unslash( $_POST['amount_toman'] ) : 0;
			$reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';

			if ( 0 !== $toman && '' !== trim( $reason ) ) {
				$ok = ( new WalletService() )->adjust( $user_id, $toman * Money::RIAL_PER_TOMAN, $reason );

				$message = $ok
					? __( 'تعدیل ثبت شد.', 'clickpop-core' )
					: __( 'تعدیل انجام نشد — موجودی برای کسر کافی نیست.', 'clickpop-core' );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'     => Menu::SLUG . '-customers',
					'customer' => $user_id,
					'cp_msg'   => rawurlencode( $message ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private static function card( string $label, string $value, string $tone ): void {
		printf(
			'<div class="cp-card %s"><span class="cp-card__l">%s</span><strong class="cp-card__v">%s</strong></div>',
			esc_attr( $tone ),
			esc_html( $label ),
			esc_html( $value )
		);
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
			array_filter( array_merge( [ 'page' => Menu::SLUG . '-customers' ], $args ) ),
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
