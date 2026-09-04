<?php
declare( strict_types=1 );

namespace ClickPop\Core\Repositories;

use ClickPop\Core\Database\Installer;

defined( 'ABSPATH' ) || exit;

/**
 * دسترسی سطح پایین به کیف پول و دفتر کل.
 *
 * برداشت با یک عبارت شرطی انجام می‌شود تا شرط رقابتی (race condition) ممکن نباشد.
 */
final class WalletRepository {

	public function ensureRow( int $user_id ): void {
		global $wpdb;

		$table = Installer::table( 'wallets' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"INSERT IGNORE INTO {$table} (user_id, balance, held, updated_at) VALUES (%d, 0, 0, %s)",
				$user_id,
				current_time( 'mysql', true )
			)
		);
	}

	public function balance( int $user_id ): int {
		global $wpdb;

		$table = Installer::table( 'wallets' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT balance FROM {$table} WHERE user_id = %d", $user_id ) );
	}

	/**
	 * برداشت اتمیک.
	 *
	 * @return bool false یعنی موجودی کافی نبود؛ هیچ ردیفی تغییر نکرده است.
	 */
	public function debitAtomic( int $user_id, int $amount_rials ): bool {
		global $wpdb;

		if ( $amount_rials <= 0 ) {
			return false;
		}

		$table = Installer::table( 'wallets' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$affected = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET balance = balance - %d, updated_at = %s
				 WHERE user_id = %d AND balance >= %d",
				$amount_rials,
				current_time( 'mysql', true ),
				$user_id,
				$amount_rials
			)
		);

		return is_int( $affected ) && $affected > 0;
	}

	public function credit( int $user_id, int $amount_rials ): bool {
		global $wpdb;

		if ( $amount_rials <= 0 ) {
			return false;
		}

		$this->ensureRow( $user_id );
		$table = Installer::table( 'wallets' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$affected = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET balance = balance + %d, updated_at = %s WHERE user_id = %d",
				$amount_rials,
				current_time( 'mysql', true ),
				$user_id
			)
		);

		return is_int( $affected ) && $affected > 0;
	}

	/** ثبت ردیف دفتر کل — فقط INSERT، هرگز UPDATE. */
	public function ledger( array $data ): int {
		global $wpdb;

		$defaults = [
			'user_id'       => 0,
			'type'          => 'adjust',
			'direction'     => 'debit',
			'amount'        => 0,
			'balance_after' => 0,
			'status'        => 'succeeded',
			'ref_type'      => null,
			'ref_id'        => null,
			'gateway'       => null,
			'authority'     => null,
			'gateway_ref'   => null,
			'reason'        => null,
			'ip'            => \ClickPop\Core\Support\RateLimiter::ipForStorage(),
			'created_by'    => get_current_user_id() ?: null,
			'created_at'    => current_time( 'mysql', true ),
		];

		$row = array_merge( $defaults, $data );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$ok = $wpdb->insert( Installer::table( 'transactions' ), $row );

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/** @return object[] */
	public function transactions( int $user_id, int $limit = 20, int $offset = 0 ): array {
		global $wpdb;

		$table = Installer::table( 'transactions' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND status = 'succeeded'
				 ORDER BY id DESC LIMIT %d OFFSET %d",
				$user_id,
				$limit,
				$offset
			)
		);
	}
}
