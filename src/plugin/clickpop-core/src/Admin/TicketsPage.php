<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Support\Audit;
use ClickPop\Core\Support\Jalali;
use ClickPop\Core\Tickets\TicketService;

defined( 'ABSPATH' ) || exit;

/**
 * میز پشتیبانی در پنل مدیریت: فهرست، رشتهٔ گفت‌وگو، پاسخ، یادداشت داخلی،
 * تغییر وضعیت و اولویت، واگذاری به کارشناس.
 */
final class TicketsPage {

	private const CAP   = 'clickpop_manage_tickets';
	private const NONCE = 'clickpop_ticket_action';

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- انتخاب نمای فقط-خواندنی.
		$ticket_id = isset( $_GET['ticket'] ) ? absint( wp_unslash( $_GET['ticket'] ) ) : 0;

		echo '<div class="wrap cp-admin">';

		self::flash();

		if ( $ticket_id > 0 ) {
			self::renderThread( $ticket_id );
		} else {
			self::renderList();
		}

		echo '</div>';
	}

	private static function flash(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط نمایش متن.
		if ( ! isset( $_GET['cp_msg'] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( sanitize_text_field( wp_unslash( $_GET['cp_msg'] ) ) )
		);
	}

	/** @return array<string,string> */
	public static function statuses(): array {
		return [
			'open'         => __( 'باز', 'clickpop-core' ),
			'answered'     => __( 'پاسخ داده شده', 'clickpop-core' ),
			'pending_user' => __( 'منتظر کاربر', 'clickpop-core' ),
			'closed'       => __( 'بسته', 'clickpop-core' ),
		];
	}

	/** @return array<string,string> */
	public static function priorities(): array {
		return [
			'low'    => __( 'کم', 'clickpop-core' ),
			'normal' => __( 'عادی', 'clickpop-core' ),
			'high'   => __( 'فوری', 'clickpop-core' ),
		];
	}

	private static function renderList(): void {
		global $wpdb;

		$tickets = Installer::table( 'tickets' );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فیلتر فقط-خواندنی.
		$status = isset( $_GET['cp_status'] ) ? sanitize_key( wp_unslash( $_GET['cp_status'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;

		$per_page = 30;
		$where    = '1=1';
		$params   = [];

		if ( '' !== $status && array_key_exists( $status, self::statuses() ) ) {
			$where   .= ' AND status = %s';
			$params[] = $status;
		}

		$params[] = $per_page;
		$params[] = ( $paged - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$tickets} WHERE {$where}
				 ORDER BY FIELD(priority,'high','normal','low'), last_reply_at DESC
				 LIMIT %d OFFSET %d",
				$params
			)
		);

		printf( '<h1>%s</h1>', esc_html__( 'تیکت‌های پشتیبانی', 'clickpop-core' ) );

		echo '<ul class="subsubsub">';
		printf(
			'<li><a href="%s" class="%s">%s</a> | </li>',
			esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-tickets' ) ),
			'' === $status ? 'current' : '',
			esc_html__( 'همه', 'clickpop-core' )
		);
		foreach ( self::statuses() as $key => $label ) {
			printf(
				'<li><a href="%s" class="%s">%s</a> | </li>',
				esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-tickets&cp_status=' . $key ) ),
				$status === $key ? 'current' : '',
				esc_html( $label )
			);
		}
		echo '</ul><br class="clear">';

		echo '<table class="widefat striped"><thead><tr>';
		foreach ( [ 'شناسه', 'موضوع', 'کاربر', 'بخش', 'اولویت', 'وضعیت', 'آخرین پاسخ', '' ] as $heading ) {
			printf( '<th>%s</th>', esc_html( $heading ) );
		}
		echo '</tr></thead><tbody>';

		if ( ! $rows ) {
			printf( '<tr><td colspan="8">%s</td></tr>', esc_html__( 'تیکتی ثبت نشده است.', 'clickpop-core' ) );
		}

		foreach ( (array) $rows as $row ) {
			$user = get_userdata( (int) $row->user_id );
			$url  = admin_url( 'admin.php?page=' . Menu::SLUG . '-tickets&ticket=' . (int) $row->id );

			echo '<tr>';
			printf( '<td>#%d</td>', (int) $row->id );
			printf( '<td><strong><a href="%s">%s</a></strong></td>', esc_url( $url ), esc_html( (string) $row->subject ) );
			printf( '<td>%s</td>', esc_html( $user ? $user->display_name : '—' ) );
			printf( '<td>%s</td>', esc_html( TicketService::departments()[ $row->department ] ?? (string) $row->department ) );
			printf(
				'<td><span class="cp-pill cp-pill--%s">%s</span></td>',
				esc_attr( 'high' === $row->priority ? 'bad' : ( 'low' === $row->priority ? 'run' : 'warn' ) ),
				esc_html( self::priorities()[ $row->priority ] ?? (string) $row->priority )
			);
			printf(
				'<td><span class="cp-pill cp-pill--%s">%s</span></td>',
				esc_attr( 'closed' === $row->status ? 'ok' : ( 'answered' === $row->status ? 'run' : 'warn' ) ),
				esc_html( self::statuses()[ $row->status ] ?? (string) $row->status )
			);
			printf(
				'<td>%s<br><small>%s</small></td>',
				esc_html( Jalali::format( (string) $row->last_reply_at ) ),
				esc_html( 'staff' === $row->last_reply_by ? __( 'توسط پشتیبانی', 'clickpop-core' ) : __( 'توسط کاربر', 'clickpop-core' ) )
			);
			printf( '<td><a class="button button-small" href="%s">%s</a></td>', esc_url( $url ), esc_html__( 'باز کردن', 'clickpop-core' ) );
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	private static function renderThread( int $ticket_id ): void {
		global $wpdb;

		$tickets = Installer::table( 'tickets' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ticket = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tickets} WHERE id = %d", $ticket_id ) );

		if ( ! $ticket ) {
			printf( '<h1>%s</h1><p>%s</p>', esc_html__( 'تیکت', 'clickpop-core' ), esc_html__( 'تیکت پیدا نشد.', 'clickpop-core' ) );

			return;
		}

		$service  = new TicketService();
		$messages = $service->messages( $ticket_id, true );
		$user     = get_userdata( (int) $ticket->user_id );

		printf(
			'<h1>%s <span class="cp-muted">#%d</span></h1>',
			esc_html( (string) $ticket->subject ),
			(int) $ticket->id
		);

		printf(
			'<p><a href="%s">&larr; %s</a></p>',
			esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-tickets' ) ),
			esc_html__( 'بازگشت به فهرست', 'clickpop-core' )
		);

		echo '<div class="cp-thread-wrap">';

		/* ── ستون گفت‌وگو ── */
		echo '<div class="cp-thread">';

		foreach ( $messages as $message ) {
			$author = get_userdata( (int) $message->author_id );
			$class  = $message->is_internal ? 'note' : ( $message->is_staff ? 'staff' : 'user' );

			printf( '<article class="cp-msg cp-msg--%s">', esc_attr( $class ) );
			printf(
				'<header class="cp-msg__h"><strong>%s</strong><span>%s</span>%s</header>',
				esc_html( $author ? $author->display_name : '—' ),
				esc_html( Jalali::format( (string) $message->created_at ) ),
				$message->is_internal
					? '<em class="cp-msg__badge">' . esc_html__( 'یادداشت داخلی — کاربر نمی‌بیند', 'clickpop-core' ) . '</em>'
					: ''
			);
			// متن به‌صورت متن ساده ذخیره می‌شود؛ اینجا فقط escape و شکستن خط.
			printf( '<div class="cp-msg__b">%s</div>', nl2br( esc_html( (string) $message->body ) ) );
			echo '</article>';
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="cp-reply">';
		wp_nonce_field( self::NONCE );
		echo '<input type="hidden" name="action" value="clickpop_ticket_reply">';
		printf( '<input type="hidden" name="ticket_id" value="%d">', (int) $ticket->id );

		printf( '<label for="cp-reply-body"><strong>%s</strong></label>', esc_html__( 'پاسخ', 'clickpop-core' ) );
		echo '<textarea id="cp-reply-body" name="body" rows="6" required maxlength="5000"></textarea>';

		printf(
			'<p><label><input type="checkbox" name="internal" value="1"> %s</label></p>',
			esc_html__( 'یادداشت داخلی (برای کاربر نمایش داده نمی‌شود)', 'clickpop-core' )
		);

		printf(
			'<p><label><input type="checkbox" name="close" value="1"> %s</label></p>',
			esc_html__( 'بعد از ارسال، تیکت را ببند', 'clickpop-core' )
		);

		self::cannedResponses();

		submit_button( __( 'ارسال پاسخ', 'clickpop-core' ) );
		echo '</form>';
		echo '</div>';

		/* ── ستون کناری ── */
		echo '<aside class="cp-thread-side">';

		echo '<div class="cp-box"><h2>' . esc_html__( 'اطلاعات', 'clickpop-core' ) . '</h2><table class="cp-kv">';
		self::kv( __( 'کاربر', 'clickpop-core' ), $user ? $user->display_name : '—' );
		self::kv( __( 'ایمیل', 'clickpop-core' ), $user ? $user->user_email : '—' );
		self::kv( __( 'بخش', 'clickpop-core' ), TicketService::departments()[ $ticket->department ] ?? (string) $ticket->department );
		self::kv( __( 'ایجاد', 'clickpop-core' ), Jalali::format( (string) $ticket->created_at ) );

		if ( $ticket->order_id ) {
			self::kv( __( 'سفارش مرتبط', 'clickpop-core' ), '#' . (int) $ticket->order_id );
		}

		if ( $user ) {
			self::kv(
				__( 'موجودی کیف پول', 'clickpop-core' ),
				\ClickPop\Core\Support\Money::fromRials(
					( new \ClickPop\Core\Wallet\WalletService() )->balance( (int) $user->ID )
				)->format()
			);
		}

		echo '</table></div>';

		echo '<div class="cp-box"><h2>' . esc_html__( 'مدیریت', 'clickpop-core' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::NONCE );
		echo '<input type="hidden" name="action" value="clickpop_ticket_update">';
		printf( '<input type="hidden" name="ticket_id" value="%d">', (int) $ticket->id );

		printf( '<p><label for="cp-t-status">%s</label><br>', esc_html__( 'وضعیت', 'clickpop-core' ) );
		echo '<select id="cp-t-status" name="status" class="widefat">';
		foreach ( self::statuses() as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $key, (string) $ticket->status, false ),
				esc_html( $label )
			);
		}
		echo '</select></p>';

		printf( '<p><label for="cp-t-priority">%s</label><br>', esc_html__( 'اولویت', 'clickpop-core' ) );
		echo '<select id="cp-t-priority" name="priority" class="widefat">';
		foreach ( self::priorities() as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $key, (string) $ticket->priority, false ),
				esc_html( $label )
			);
		}
		echo '</select></p>';

		printf( '<p><label for="cp-t-assign">%s</label><br>', esc_html__( 'واگذاری به', 'clickpop-core' ) );
		wp_dropdown_users(
			[
				'name'              => 'assigned_to',
				'id'                => 'cp-t-assign',
				'selected'          => (int) $ticket->assigned_to,
				'show_option_none'  => __( '— بدون مسئول —', 'clickpop-core' ),
				'option_none_value' => 0,
				'capability'        => [ 'clickpop_manage_tickets' ],
				'class'             => 'widefat',
			]
		);
		echo '</p>';

		submit_button( __( 'ذخیره', 'clickpop-core' ), 'secondary', 'submit', false );
		echo '</form></div>';

		echo '</aside></div>';
	}

	private static function cannedResponses(): void {
		$canned = (array) apply_filters(
			'clickpop/tickets/canned',
			[
				__( 'سفارش شما در صف سرویس‌دهنده است و به‌محض شروع، شمارندهٔ پیشرفت در داشبورد به‌روز می‌شود.', 'clickpop-core' ),
				__( 'لطفاً تا پایان سفارش، صفحه را عمومی نگه دارید. خصوصی‌شدن صفحه سفارش را ناقص می‌کند.', 'clickpop-core' ),
				__( 'مبلغ سفارش ناقص، به نسبت مقدار انجام‌نشده به کیف پول شما برگشت خورد. در تب کیف پول قابل مشاهده است.', 'clickpop-core' ),
				__( 'اگر مبلغ از حساب شما کسر شده ولی موجودی اضافه نشده، معمولاً ظرف ۷۲ ساعت توسط بانک برمی‌گردد.', 'clickpop-core' ),
			]
		);

		echo '<details class="cp-canned"><summary>' . esc_html__( 'پاسخ‌های آماده', 'clickpop-core' ) . '</summary><ul>';

		foreach ( $canned as $text ) {
			printf(
				'<li><button type="button" class="button button-small" data-cp-canned="%s">%s</button></li>',
				esc_attr( (string) $text ),
				esc_html( wp_html_excerpt( (string) $text, 60, '…' ) )
			);
		}

		echo '</ul></details>';
	}

	private static function kv( string $key, string $value ): void {
		printf( '<tr><th scope="row">%s</th><td>%s</td></tr>', esc_html( $key ), esc_html( $value ) );
	}

	/* ─────────────────────────── اکشن‌ها ─────────────────────────── */

	public static function handleReply(): void {
		self::guard();

		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
		$body      = isset( $_POST['body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body'] ) ) : '';
		$internal  = ! empty( $_POST['internal'] );
		$close     = ! empty( $_POST['close'] );

		$message = __( 'پاسخ ارسال نشد؛ متن خالی بود.', 'clickpop-core' );

		if ( $ticket_id > 0 && '' !== trim( $body ) ) {
			$ok = ( new TicketService() )->reply( $ticket_id, get_current_user_id(), $body, true, $internal );

			if ( $ok ) {
				$message = $internal
					? __( 'یادداشت داخلی ثبت شد.', 'clickpop-core' )
					: __( 'پاسخ ارسال شد.', 'clickpop-core' );

				if ( $close ) {
					self::setFields( $ticket_id, [ 'status' => 'closed' ] );
					$message .= ' ' . __( 'تیکت بسته شد.', 'clickpop-core' );
				}

				Audit::log( 'ticket.reply', 'ticket', $ticket_id, null, [ 'internal' => $internal ] );
			}
		}

		self::back( $ticket_id, $message );
	}

	public static function handleUpdate(): void {
		self::guard();

		$ticket_id = isset( $_POST['ticket_id'] ) ? absint( wp_unslash( $_POST['ticket_id'] ) ) : 0;
		$status    = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$priority  = isset( $_POST['priority'] ) ? sanitize_key( wp_unslash( $_POST['priority'] ) ) : '';
		$assigned  = isset( $_POST['assigned_to'] ) ? absint( wp_unslash( $_POST['assigned_to'] ) ) : 0;

		$fields = [];

		if ( array_key_exists( $status, self::statuses() ) ) {
			$fields['status'] = $status;
		}
		if ( array_key_exists( $priority, self::priorities() ) ) {
			$fields['priority'] = $priority;
		}
		$fields['assigned_to'] = $assigned ?: null;

		if ( $ticket_id > 0 && $fields ) {
			self::setFields( $ticket_id, $fields );
			Audit::log( 'ticket.update', 'ticket', $ticket_id, null, $fields );
		}

		self::back( $ticket_id, __( 'تیکت به‌روزرسانی شد.', 'clickpop-core' ) );
	}

	/** @param array<string,mixed> $fields */
	private static function setFields( int $ticket_id, array $fields ): void {
		global $wpdb;

		$fields['updated_at'] = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update( Installer::table( 'tickets' ), $fields, [ 'id' => $ticket_id ] );
	}

	private static function guard(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::NONCE );
	}

	private static function back( int $ticket_id, string $message ): void {
		wp_safe_redirect(
			add_query_arg(
				[
					'page'   => Menu::SLUG . '-tickets',
					'ticket' => $ticket_id,
					'cp_msg' => rawurlencode( $message ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
