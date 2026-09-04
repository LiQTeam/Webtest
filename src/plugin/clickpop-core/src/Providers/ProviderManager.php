<?php
declare( strict_types=1 );

namespace ClickPop\Core\Providers;

use ClickPop\Core\Database\Installer;

defined( 'ABSPATH' ) || exit;

final class ProviderManager {

	public static function active(): ?AbstractProvider {
		global $wpdb;

		$table = Installer::table( 'providers' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( "SELECT * FROM {$table} WHERE status = 'active' ORDER BY id ASC LIMIT 1" );

		return $row ? self::make( $row ) : null;
	}

	public static function byId( int $id ): ?AbstractProvider {
		global $wpdb;

		$table = Installer::table( 'providers' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

		return $row ? self::make( $row ) : null;
	}

	private static function make( object $row ): AbstractProvider {
		/** @var array<string,class-string<AbstractProvider>> $drivers */
		$drivers = (array) apply_filters(
			'clickpop/providers/drivers',
			[
				'smm_v2'     => FollowerAnProvider::class,
				'followeran' => FollowerAnProvider::class,
			]
		);

		$class = $drivers[ (string) $row->driver ] ?? FollowerAnProvider::class;

		return new $class( $row );
	}

	/** ذخیره یا به‌روزرسانی تنظیمات سرویس‌دهنده از پنل ادمین. */
	public static function save( array $data ): int {
		global $wpdb;

		$table = Installer::table( 'providers' );
		$now   = current_time( 'mysql', true );

		$row = [
			'slug'       => sanitize_key( $data['slug'] ?? 'primary' ),
			'name'       => sanitize_text_field( $data['name'] ?? 'Primary' ),
			'driver'     => sanitize_key( $data['driver'] ?? 'smm_v2' ),
			'api_url'    => esc_url_raw( $data['api_url'] ?? '' ),
			'currency'   => strtoupper( substr( sanitize_text_field( $data['currency'] ?? 'IRT' ), 0, 3 ) ),
			'rate_unit'  => max( 1, absint( $data['rate_unit'] ?? 1000 ) ),
			'status'     => in_array( $data['status'] ?? 'active', [ 'active', 'paused', 'disabled' ], true ) ? $data['status'] : 'active',
			'updated_at' => $now,
		];

		if ( ! empty( $data['api_key'] ) ) {
			$row['api_key_enc'] = \ClickPop\Core\Support\Encryption::encrypt( (string) $data['api_key'] );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$existing = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s", $row['slug'] ) );

		if ( $existing > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $table, $row, [ 'id' => $existing ] );

			return $existing;
		}

		$row['created_at']  = $now;
		$row['api_key_enc'] = $row['api_key_enc'] ?? '';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( $table, $row );

		return (int) $wpdb->insert_id;
	}

	public static function primaryRow(): ?object {
		global $wpdb;

		$table = Installer::table( 'providers' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( "SELECT * FROM {$table} ORDER BY id ASC LIMIT 1" ) ?: null;
	}
}
