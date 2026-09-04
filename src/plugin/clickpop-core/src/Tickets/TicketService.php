<?php
declare( strict_types=1 );

namespace ClickPop\Core\Tickets;

use ClickPop\Core\Database\Installer;

defined( 'ABSPATH' ) || exit;

/**
 * میز پشتیبانی. متن پیام‌ها به‌صورت متن ساده ذخیره می‌شود، نه HTML —
 * این کار بردار XSS ذخیره‌شده در پنل ادمین را کاملاً حذف می‌کند.
 */
final class TicketService {

	/** @return array<string,string> */
	public static function departments(): array {
		return (array) apply_filters(
			'clickpop/tickets/departments',
			[
				'technical' => __( 'فنی', 'clickpop-core' ),
				'billing'   => __( 'مالی و پرداخت', 'clickpop-core' ),
				'sales'     => __( 'فروش', 'clickpop-core' ),
			]
		);
	}

	public function create( int $user_id, string $department, string $subject, string $body, ?int $order_id = null ): int {
		global $wpdb;

		if ( ! array_key_exists( $department, self::departments() ) ) {
			$department = 'technical';
		}

		$now = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = $wpdb->insert(
			Installer::table( 'tickets' ),
			[
				'user_id'       => $user_id,
				'department'    => $department,
				'subject'       => mb_substr( sanitize_text_field( $subject ), 0, 255 ),
				'order_id'      => $order_id ?: null,
				'status'        => 'open',
				'priority'      => 'normal',
				'last_reply_at' => $now,
				'last_reply_by' => 'user',
				'created_at'    => $now,
				'updated_at'    => $now,
			]
		);

		if ( ! $ok ) {
			return 0;
		}

		$ticket_id = (int) $wpdb->insert_id;
		$this->reply( $ticket_id, $user_id, $body, false );

		return $ticket_id;
	}

	public function reply( int $ticket_id, int $author_id, string $body, bool $is_staff, bool $is_internal = false ): bool {
		global $wpdb;

		$body = trim( sanitize_textarea_field( $body ) );

		if ( '' === $body ) {
			return false;
		}

		$now = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = $wpdb->insert(
			Installer::table( 'ticket_messages' ),
			[
				'ticket_id'   => $ticket_id,
				'author_id'   => $author_id,
				'is_staff'    => $is_staff ? 1 : 0,
				'is_internal' => $is_internal ? 1 : 0,
				'body'        => mb_substr( $body, 0, 5000 ),
				'created_at'  => $now,
			]
		);

		if ( ! $ok ) {
			return false;
		}

		if ( ! $is_internal ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update(
				Installer::table( 'tickets' ),
				[
					'status'        => $is_staff ? 'answered' : 'open',
					'last_reply_at' => $now,
					'last_reply_by' => $is_staff ? 'staff' : 'user',
					'updated_at'    => $now,
				],
				[ 'id' => $ticket_id ]
			);
		}

		return true;
	}

	/** @return object[] */
	public function forUser( int $user_id, int $limit = 20 ): array {
		global $wpdb;

		$table = Installer::table( 'tickets' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY last_reply_at DESC LIMIT %d",
				$user_id,
				$limit
			)
		);
	}

	public function findOwned( int $ticket_id, int $user_id ): ?object {
		global $wpdb;

		$table = Installer::table( 'tickets' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND user_id = %d", $ticket_id, $user_id )
		);

		return $row ?: null;
	}

	/**
	 * پیام‌های قابل نمایش به کاربر — یادداشت‌های داخلی کارکنان هرگز برنمی‌گردند.
	 *
	 * @return object[]
	 */
	public function messages( int $ticket_id, bool $include_internal = false ): array {
		global $wpdb;

		$table = Installer::table( 'ticket_messages' );
		$where = $include_internal ? '' : ' AND is_internal = 0';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE ticket_id = %d{$where} ORDER BY id ASC LIMIT 200",
				$ticket_id
			)
		);
	}
}
