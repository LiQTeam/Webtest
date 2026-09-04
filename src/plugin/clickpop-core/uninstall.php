<?php
/**
 * حذف افزونه.
 *
 * دادهٔ مالی به‌صورت پیش‌فرض حذف نمی‌شود. حذف کامل فقط وقتی انجام می‌شود که
 * مدیر صراحتاً گزینهٔ clickpop_delete_data_on_uninstall را روشن کرده باشد.
 *
 * @package ClickPop\Core
 */

declare( strict_types=1 );

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! get_option( 'clickpop_delete_data_on_uninstall', false ) ) {
	return;
}

global $wpdb;

$tables = [
	'api_log',
	'audit_log',
	'ticket_messages',
	'tickets',
	'orders',
	'transactions',
	'wallets',
	'pricing_rules',
	'services',
	'categories',
	'providers',
];

foreach ( $tables as $table ) {
	$name = $wpdb->prefix . 'cp_' . $table;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$name}" );
}

$options = [
	'clickpop_db_version',
	'clickpop_price_jump_percent',
	'clickpop_gateway_zarinpal_enabled',
	'clickpop_zarinpal_merchant',
	'clickpop_zarinpal_sandbox',
	'clickpop_topup_min',
	'clickpop_topup_max',
	'clickpop_dashboard_page_id',
	'clickpop_last_service_sync',
	'clickpop_delete_data_on_uninstall',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

remove_role( 'clickpop_customer' );
