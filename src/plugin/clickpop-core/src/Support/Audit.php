<?php
declare( strict_types=1 );

namespace ClickPop\Core\Support;

use ClickPop\Core\Database\Installer;

defined( 'ABSPATH' ) || exit;

/**
 * ثبت رد پای اعمال حساس. بدون این، تعدیل موجودی قابل ردیابی نیست.
 */
final class Audit {

	public static function log(
		string $action,
		string $object_type,
		?int $object_id = null,
		?array $before = null,
		?array $after = null,
		?string $reason = null
	): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			Installer::table( 'audit_log' ),
			[
				'actor_id'    => get_current_user_id() ?: null,
				'action'      => $action,
				'object_type' => $object_type,
				'object_id'   => $object_id,
				'before_json' => null === $before ? null : wp_json_encode( $before ),
				'after_json'  => null === $after ? null : wp_json_encode( $after ),
				'reason'      => null === $reason ? null : mb_substr( $reason, 0, 255 ),
				'ip'          => RateLimiter::ipForStorage(),
				'created_at'  => current_time( 'mysql', true ),
			]
		);
	}
}
