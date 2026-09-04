<?php
declare( strict_types=1 );

namespace ClickPop\Core\Repositories;

use ClickPop\Core\Database\Installer;

defined( 'ABSPATH' ) || exit;

final class OrderRepository {

	public function insert( array $data ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = $wpdb->insert( Installer::table( 'orders' ), $data );

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	public function update( int $id, array $data ): bool {
		global $wpdb;

		$data['updated_at'] = current_time( 'mysql', true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return false !== $wpdb->update( Installer::table( 'orders' ), $data, [ 'id' => $id ] );
	}

	public function find( int $id ): ?object {
		global $wpdb;

		$table = Installer::table( 'orders' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

		return $row ?: null;
	}

	/** مالکیت در خود کوئری اعمال می‌شود، نه بعد از واکشی (دفاع در برابر IDOR). */
	public function findOwned( int $id, int $user_id ): ?object {
		global $wpdb;

		$table = Installer::table( 'orders' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND user_id = %d", $id, $user_id )
		);

		return $row ?: null;
	}

	public function findByIdempotencyKey( string $key ): ?object {
		global $wpdb;

		$table = Installer::table( 'orders' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE idempotency_key = %s", $key ) );

		return $row ?: null;
	}

	/**
	 * @param string[] $statuses
	 * @return object[]
	 */
	public function forUser( int $user_id, array $statuses = [], int $limit = 20, int $offset = 0 ): array {
		global $wpdb;

		$orders   = Installer::table( 'orders' );
		$services = Installer::table( 'services' );

		$where  = 'o.user_id = %d';
		$params = [ $user_id ];

		if ( $statuses ) {
			$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
			$where       .= " AND o.status IN ({$placeholders})";
			$params       = array_merge( $params, $statuses );
		}

		$params[] = $limit;
		$params[] = $offset;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.*, s.name AS service_name
				 FROM {$orders} o
				 LEFT JOIN {$services} s ON s.id = o.service_id
				 WHERE {$where}
				 ORDER BY o.id DESC
				 LIMIT %d OFFSET %d",
				$params
			)
		);
	}

	/**
	 * سفارش‌های نیازمند همگام‌سازی وضعیت.
	 *
	 * @return object[]
	 */
	public function dueForSync( int $limit = 100 ): array {
		global $wpdb;

		$table = Installer::table( 'orders' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				 WHERE status IN ('processing','in_progress')
				   AND remote_order_id IS NOT NULL
				   AND (next_sync_at IS NULL OR next_sync_at <= %s)
				 ORDER BY next_sync_at ASC
				 LIMIT %d",
				current_time( 'mysql', true ),
				$limit
			)
		);
	}

	/** سفارش‌هایی که تماس با سرویس‌دهنده در آن‌ها تعیین تکلیف نشده است. */
	public function orphans( int $limit = 30 ): array {
		global $wpdb;

		$table = Installer::table( 'orders' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status IN ('reserved','pending_verify') AND created_at < %s LIMIT %d",
				gmdate( 'Y-m-d H:i:s', time() - 5 * MINUTE_IN_SECONDS ),
				$limit
			)
		);
	}

	/**
	 * افزایش مبلغ برگشتی به‌صورت اتمیک — دو اجرای همزمان کرون نمی‌تواند دو بار برگشت بزند.
	 */
	public function addRefundGuarded( int $order_id, int $current_refunded, int $add ): bool {
		global $wpdb;

		$table = Installer::table( 'orders' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$affected = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET refunded = refunded + %d, updated_at = %s
				 WHERE id = %d AND refunded = %d",
				$add,
				current_time( 'mysql', true ),
				$order_id,
				$current_refunded
			)
		);

		return is_int( $affected ) && $affected > 0;
	}

	/** @return array<string,int> */
	public function statsForUser( int $user_id ): array {
		global $wpdb;

		$table = Installer::table( 'orders' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT status, COUNT(*) AS c FROM {$table} WHERE user_id = %d GROUP BY status", $user_id )
		);

		$out = [];
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row->status ] = (int) $row->c;
		}

		return $out;
	}
}
