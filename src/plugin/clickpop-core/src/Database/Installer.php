<?php
declare( strict_types=1 );

namespace ClickPop\Core\Database;

defined( 'ABSPATH' ) || exit;

/**
 * ساخت و مهاجرت جداول اختصاصی.
 *
 * تمام مبالغ BIGINT بر حسب ریال‌اند و تمام زمان‌ها UTC.
 */
final class Installer {

	public const OPTION_DB_VERSION = 'clickpop_db_version';

	public static function activate(): void {
		self::migrate();
		self::registerRoles();
		\ClickPop\Core\Sync\Scheduler::schedule();
		flush_rewrite_rules();
	}

	/** در هر بارگذاری بررسی می‌شود تا به‌روزرسانی افزونه، اسکیما را هم جلو ببرد. */
	public static function maybeMigrate(): void {
		if ( (int) get_option( self::OPTION_DB_VERSION, 0 ) < CLICKPOP_DB_VERSION ) {
			self::migrate();
			self::registerRoles();
		}
	}

	public static function table( string $name ): string {
		global $wpdb;

		return $wpdb->prefix . 'cp_' . $name;
	}

	private static function migrate(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$sql     = [];

		$sql[] = 'CREATE TABLE ' . self::table( 'providers' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			slug VARCHAR(64) NOT NULL,
			name VARCHAR(191) NOT NULL,
			driver VARCHAR(64) NOT NULL DEFAULT 'smm_v2',
			api_url VARCHAR(255) NOT NULL,
			api_key_enc TEXT NOT NULL,
			currency CHAR(3) NOT NULL DEFAULT 'IRT',
			rate_unit SMALLINT UNSIGNED NOT NULL DEFAULT 1000,
			status VARCHAR(16) NOT NULL DEFAULT 'active',
			balance_cache BIGINT NOT NULL DEFAULT 0,
			latency_ms INT UNSIGNED NULL,
			failure_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			circuit_open_until DATETIME NULL,
			last_sync_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uq_slug (slug)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'categories' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			provider_id BIGINT UNSIGNED NOT NULL,
			brand VARCHAR(64) NOT NULL,
			brand_slug VARCHAR(64) NOT NULL,
			name VARCHAR(191) NOT NULL,
			slug VARCHAR(191) NOT NULL,
			sort_order SMALLINT NOT NULL DEFAULT 0,
			status VARCHAR(16) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uq_prov_slug (provider_id, slug),
			KEY idx_brand_status (brand_slug, status, sort_order)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'services' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			provider_id BIGINT UNSIGNED NOT NULL,
			remote_service_id VARCHAR(64) NOT NULL,
			category_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(255) NOT NULL,
			type VARCHAR(32) NOT NULL DEFAULT 'default',
			cost_rate BIGINT UNSIGNED NOT NULL DEFAULT 0,
			sale_rate BIGINT UNSIGNED NOT NULL DEFAULT 0,
			min_qty INT UNSIGNED NOT NULL DEFAULT 1,
			max_qty INT UNSIGNED NOT NULL DEFAULT 1,
			dripfeed TINYINT(1) NOT NULL DEFAULT 0,
			refill TINYINT(1) NOT NULL DEFAULT 0,
			cancel TINYINT(1) NOT NULL DEFAULT 0,
			description TEXT NULL,
			template_link VARCHAR(255) NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'active',
			raw_json LONGTEXT NULL,
			payload_hash CHAR(40) NOT NULL DEFAULT '',
			sort_order SMALLINT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uq_provider_service (provider_id, remote_service_id),
			KEY idx_cat_status (category_id, status, sort_order)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'pricing_rules' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			scope VARCHAR(16) NOT NULL DEFAULT 'global',
			scope_ref VARCHAR(191) NULL,
			margin_type VARCHAR(16) NOT NULL DEFAULT 'percent',
			margin_value BIGINT NOT NULL DEFAULT 0,
			min_profit BIGINT UNSIGNED NOT NULL DEFAULT 0,
			round_step INT UNSIGNED NOT NULL DEFAULT 1000,
			priority SMALLINT NOT NULL DEFAULT 0,
			active TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY idx_scope (scope, scope_ref, active, priority)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'wallets' ) . " (
			user_id BIGINT UNSIGNED NOT NULL,
			balance BIGINT NOT NULL DEFAULT 0,
			held BIGINT UNSIGNED NOT NULL DEFAULT 0,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (user_id)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'transactions' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			type VARCHAR(16) NOT NULL,
			direction VARCHAR(8) NOT NULL,
			amount BIGINT UNSIGNED NOT NULL,
			balance_after BIGINT NOT NULL DEFAULT 0,
			status VARCHAR(16) NOT NULL DEFAULT 'succeeded',
			ref_type VARCHAR(32) NULL,
			ref_id BIGINT UNSIGNED NULL,
			gateway VARCHAR(32) NULL,
			authority VARCHAR(128) NULL,
			gateway_ref VARCHAR(128) NULL,
			reason VARCHAR(255) NULL,
			ip VARCHAR(45) NULL,
			created_by BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uq_authority (gateway, authority),
			KEY idx_user_time (user_id, created_at),
			KEY idx_type_status (type, status, created_at),
			KEY idx_ref (ref_type, ref_id)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'orders' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			service_id BIGINT UNSIGNED NOT NULL,
			provider_id BIGINT UNSIGNED NOT NULL,
			remote_order_id VARCHAR(64) NULL,
			idempotency_key CHAR(36) NOT NULL,
			link VARCHAR(500) NOT NULL,
			quantity INT UNSIGNED NOT NULL,
			sale_rate BIGINT UNSIGNED NOT NULL DEFAULT 0,
			cost_rate BIGINT UNSIGNED NOT NULL DEFAULT 0,
			charge BIGINT UNSIGNED NOT NULL DEFAULT 0,
			cost BIGINT UNSIGNED NOT NULL DEFAULT 0,
			refunded BIGINT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'reserved',
			provider_status VARCHAR(64) NULL,
			start_count INT UNSIGNED NULL,
			remains INT UNSIGNED NULL,
			error_message VARCHAR(500) NULL,
			sync_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			next_sync_at DATETIME NULL,
			ip VARCHAR(45) NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			completed_at DATETIME NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uq_idempotency (idempotency_key),
			KEY idx_remote (provider_id, remote_order_id),
			KEY idx_user_status (user_id, status, created_at),
			KEY idx_sync (status, next_sync_at)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'tickets' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			department VARCHAR(32) NOT NULL DEFAULT 'technical',
			subject VARCHAR(255) NOT NULL,
			order_id BIGINT UNSIGNED NULL,
			status VARCHAR(16) NOT NULL DEFAULT 'open',
			priority VARCHAR(16) NOT NULL DEFAULT 'normal',
			assigned_to BIGINT UNSIGNED NULL,
			last_reply_at DATETIME NOT NULL,
			last_reply_by VARCHAR(8) NOT NULL DEFAULT 'user',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY idx_user_status (user_id, status, last_reply_at),
			KEY idx_queue (status, department, last_reply_at)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'ticket_messages' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			ticket_id BIGINT UNSIGNED NOT NULL,
			author_id BIGINT UNSIGNED NOT NULL,
			is_staff TINYINT(1) NOT NULL DEFAULT 0,
			is_internal TINYINT(1) NOT NULL DEFAULT 0,
			body TEXT NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY idx_ticket_time (ticket_id, created_at)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'audit_log' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			actor_id BIGINT UNSIGNED NULL,
			action VARCHAR(64) NOT NULL,
			object_type VARCHAR(32) NOT NULL,
			object_id BIGINT UNSIGNED NULL,
			before_json TEXT NULL,
			after_json TEXT NULL,
			reason VARCHAR(255) NULL,
			ip VARCHAR(45) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY idx_object (object_type, object_id, created_at),
			KEY idx_actor (actor_id, created_at)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'api_log' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			provider_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(32) NOT NULL,
			http_code SMALLINT UNSIGNED NULL,
			latency_ms INT UNSIGNED NULL,
			ok TINYINT(1) NOT NULL DEFAULT 0,
			error VARCHAR(500) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY idx_provider_time (provider_id, created_at)
		) {$charset};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		self::seedDefaultRule();

		update_option( self::OPTION_DB_VERSION, CLICKPOP_DB_VERSION, false );
	}

	/** یک قاعدهٔ سود سراسری پیش‌فرض تا سیستم بدون پیکربندی هم قیمت بدهد. */
	private static function seedDefaultRule(): void {
		global $wpdb;

		$table = self::table( 'pricing_rules' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$exists = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

		if ( $exists > 0 ) {
			return;
		}

		$now = current_time( 'mysql', true );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$table,
			[
				'scope'        => 'global',
				'scope_ref'    => null,
				'margin_type'  => 'percent',
				'margin_value' => 2000,
				'min_profit'   => 0,
				'round_step'   => 1000,
				'priority'     => 0,
				'active'       => 1,
				'created_at'   => $now,
				'updated_at'   => $now,
			],
			[ '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s' ]
		);
	}

	private static function registerRoles(): void {
		$customer = [
			'read'                     => true,
			'clickpop_place_order'     => true,
			'clickpop_view_own_orders' => true,
			'clickpop_topup_wallet'    => true,
			'clickpop_open_ticket'     => true,
		];

		remove_role( 'clickpop_customer' );
		add_role( 'clickpop_customer', __( 'مشتری کلیک‌پاپ', 'clickpop-core' ), $customer );

		$admin = get_role( 'administrator' );
		if ( $admin instanceof \WP_Role ) {
			foreach ( array_keys( $customer ) as $cap ) {
				$admin->add_cap( $cap );
			}
			foreach ( [ 'clickpop_manage_orders', 'clickpop_manage_pricing', 'clickpop_adjust_balance', 'clickpop_manage_providers', 'clickpop_manage_tickets', 'clickpop_view_audit_log' ] as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		$subscriber = get_role( 'subscriber' );
		if ( $subscriber instanceof \WP_Role ) {
			foreach ( array_keys( $customer ) as $cap ) {
				$subscriber->add_cap( $cap );
			}
		}
	}
}
