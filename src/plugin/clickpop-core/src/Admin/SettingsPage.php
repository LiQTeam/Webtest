<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Pricing\PriceCalculator;
use ClickPop\Core\Providers\ProviderManager;
use ClickPop\Core\Repositories\ServiceRepository;
use ClickPop\Core\Support\Encryption;
use ClickPop\Core\Support\Money;
use ClickPop\Core\Sync\ServiceSync;

defined( 'ABSPATH' ) || exit;

final class SettingsPage {

	private const CAP   = 'clickpop_manage_providers';
	private const NONCE = 'clickpop_settings';

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		$provider = ProviderManager::primaryRow();
		$rule     = self::globalRule();

		echo '<div class="wrap cp-admin">';
		printf( '<h1>%s</h1>', esc_html__( 'تنظیمات کلیک‌پاپ', 'clickpop-core' ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط نمایش پیام نتیجه.
		if ( isset( $_GET['cp_msg'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['cp_msg'] ) ) )
			);
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::NONCE );
		echo '<input type="hidden" name="action" value="clickpop_save_settings">';

		echo '<h2>' . esc_html__( 'سرویس‌دهندهٔ SMM', 'clickpop-core' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';

		self::text( 'api_url', __( 'آدرس API', 'clickpop-core' ), (string) ( $provider->api_url ?? 'https://my.followeran.ir/api/v2' ), 'url' );

		$masked = $provider ? Encryption::mask( Encryption::decrypt( (string) $provider->api_key_enc ) ) : '';
		printf(
			'<tr><th scope="row"><label for="cp-api-key">%s</label></th><td>
				<input type="password" id="cp-api-key" name="api_key" class="regular-text" autocomplete="off" value="">
				<p class="description">%s %s</p></td></tr>',
			esc_html__( 'کلید API', 'clickpop-core' ),
			esc_html__( 'خالی بگذارید تا تغییر نکند. مقدار فعلی:', 'clickpop-core' ),
			esc_html( '' !== $masked ? $masked : __( 'تنظیم نشده', 'clickpop-core' ) )
		);

		self::number( 'rate_unit', __( 'واحد قیمت (rate به ازای چند عدد)', 'clickpop-core' ), (int) ( $provider->rate_unit ?? 1000 ) );
		self::text( 'currency', __( 'واحد پول سرویس‌دهنده', 'clickpop-core' ), (string) ( $provider->currency ?? 'IRT' ) );

		printf(
			'<tr><th scope="row">%s</th><td><label><input type="checkbox" name="provider_active" value="1" %s> %s</label></td></tr>',
			esc_html__( 'وضعیت', 'clickpop-core' ),
			checked( 'active', (string) ( $provider->status ?? 'active' ), false ),
			esc_html__( 'فعال', 'clickpop-core' )
		);

		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'قاعدهٔ سود سراسری', 'clickpop-core' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';
		self::number( 'margin_percent', __( 'درصد سود (مثلاً ۲۰ برای ۲۰٪)', 'clickpop-core' ), (int) ( ( $rule->margin_value ?? 2000 ) / 100 ) );
		self::number( 'round_step_toman', __( 'گام گرد کردن (تومان)', 'clickpop-core' ), (int) ( ( $rule->round_step ?? 1000 ) / Money::RIAL_PER_TOMAN ) );
		self::number( 'price_jump', __( 'آستانهٔ جهش قیمت برای بازبینی دستی (٪)', 'clickpop-core' ), (int) get_option( 'clickpop_price_jump_percent', 20 ) );
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'درگاه پرداخت — زرین‌پال', 'clickpop-core' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';
		printf(
			'<tr><th scope="row">%s</th><td><label><input type="checkbox" name="zarinpal_enabled" value="1" %s> %s</label></td></tr>',
			esc_html__( 'فعال‌سازی', 'clickpop-core' ),
			checked( true, (bool) get_option( 'clickpop_gateway_zarinpal_enabled', false ), false ),
			esc_html__( 'درگاه زرین‌پال فعال باشد', 'clickpop-core' )
		);
		self::text( 'zarinpal_merchant', __( 'کد پذیرنده (Merchant ID)', 'clickpop-core' ), (string) get_option( 'clickpop_zarinpal_merchant', '' ) );
		printf(
			'<tr><th scope="row">%s</th><td><label><input type="checkbox" name="zarinpal_sandbox" value="1" %s> %s</label></td></tr>',
			esc_html__( 'محیط تست', 'clickpop-core' ),
			checked( true, (bool) get_option( 'clickpop_zarinpal_sandbox', false ), false ),
			esc_html__( 'استفاده از سندباکس', 'clickpop-core' )
		);
		self::number( 'topup_min_toman', __( 'حداقل شارژ (تومان)', 'clickpop-core' ), (int) ( (int) get_option( 'clickpop_topup_min', 100000 ) / Money::RIAL_PER_TOMAN ) );
		self::number( 'topup_max_toman', __( 'حداکثر شارژ (تومان)', 'clickpop-core' ), (int) ( (int) get_option( 'clickpop_topup_max', 500000000 ) / Money::RIAL_PER_TOMAN ) );
		echo '</tbody></table>';

		echo '<h2>' . esc_html__( 'صفحهٔ داشبورد', 'clickpop-core' ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody><tr><th scope="row">';
		echo '<label for="cp-dash-page">' . esc_html__( 'برگهٔ داشبورد', 'clickpop-core' ) . '</label></th><td>';
		wp_dropdown_pages(
			[
				'name'              => 'dashboard_page_id',
				'id'                => 'cp-dash-page',
				'selected'          => (int) get_option( 'clickpop_dashboard_page_id', 0 ),
				'show_option_none'  => __( '— انتخاب نشده —', 'clickpop-core' ),
				'option_none_value' => 0,
			]
		);
		printf( '<p class="description">%s <code>[clickpop_dashboard]</code></p>', esc_html__( 'برگه‌ای که این شورتکد را دارد:', 'clickpop-core' ) );
		echo '</td></tr></tbody></table>';

		submit_button( __( 'ذخیرهٔ تنظیمات', 'clickpop-core' ) );
		echo '</form></div>';
	}

	public static function handleSave(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::NONCE );

		ProviderManager::save(
			[
				'slug'      => 'primary',
				'name'      => 'Primary SMM Provider',
				'driver'    => 'smm_v2',
				'api_url'   => isset( $_POST['api_url'] ) ? esc_url_raw( wp_unslash( $_POST['api_url'] ) ) : '',
				'api_key'   => isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '',
				'currency'  => isset( $_POST['currency'] ) ? sanitize_text_field( wp_unslash( $_POST['currency'] ) ) : 'IRT',
				'rate_unit' => isset( $_POST['rate_unit'] ) ? absint( wp_unslash( $_POST['rate_unit'] ) ) : 1000,
				'status'    => empty( $_POST['provider_active'] ) ? 'paused' : 'active',
			]
		);

		self::saveGlobalRule(
			isset( $_POST['margin_percent'] ) ? absint( wp_unslash( $_POST['margin_percent'] ) ) : 20,
			isset( $_POST['round_step_toman'] ) ? absint( wp_unslash( $_POST['round_step_toman'] ) ) : 100
		);

		update_option( 'clickpop_price_jump_percent', isset( $_POST['price_jump'] ) ? absint( wp_unslash( $_POST['price_jump'] ) ) : 20 );
		update_option( 'clickpop_gateway_zarinpal_enabled', ! empty( $_POST['zarinpal_enabled'] ) );
		update_option( 'clickpop_zarinpal_merchant', isset( $_POST['zarinpal_merchant'] ) ? sanitize_text_field( wp_unslash( $_POST['zarinpal_merchant'] ) ) : '' );
		update_option( 'clickpop_zarinpal_sandbox', ! empty( $_POST['zarinpal_sandbox'] ) );
		update_option( 'clickpop_topup_min', ( isset( $_POST['topup_min_toman'] ) ? absint( wp_unslash( $_POST['topup_min_toman'] ) ) : 10000 ) * Money::RIAL_PER_TOMAN );
		update_option( 'clickpop_topup_max', ( isset( $_POST['topup_max_toman'] ) ? absint( wp_unslash( $_POST['topup_max_toman'] ) ) : 50000000 ) * Money::RIAL_PER_TOMAN );
		update_option( 'clickpop_dashboard_page_id', isset( $_POST['dashboard_page_id'] ) ? absint( wp_unslash( $_POST['dashboard_page_id'] ) ) : 0 );

		PriceCalculator::flush();
		ServiceRepository::flushTree();

		wp_safe_redirect(
			add_query_arg(
				[
					'page'   => Menu::SLUG . '-settings',
					'cp_msg' => rawurlencode( __( 'تنظیمات ذخیره شد.', 'clickpop-core' ) ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public static function handleSync(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( 'clickpop_sync' );

		$result = ServiceSync::run();

		wp_safe_redirect(
			add_query_arg(
				[
					'page'   => Menu::SLUG,
					'cp_msg' => rawurlencode( (string) $result['message'] ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private static function globalRule(): ?object {
		global $wpdb;

		$table = Installer::table( 'pricing_rules' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_row( "SELECT * FROM {$table} WHERE scope = 'global' ORDER BY id ASC LIMIT 1" ) ?: null;
	}

	private static function saveGlobalRule( int $percent, int $round_toman ): void {
		global $wpdb;

		$table = Installer::table( 'pricing_rules' );
		$now   = current_time( 'mysql', true );
		$rule  = self::globalRule();

		$data = [
			'margin_type'  => 'percent',
			'margin_value' => max( 0, $percent ) * 100,
			'round_step'   => max( 1, $round_toman ) * Money::RIAL_PER_TOMAN,
			'active'       => 1,
			'updated_at'   => $now,
		];

		if ( $rule ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update( $table, $data, [ 'id' => (int) $rule->id ] );

			return;
		}

		$data['scope']      = 'global';
		$data['min_profit'] = 0;
		$data['priority']   = 0;
		$data['created_at'] = $now;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert( $table, $data );
	}

	private static function text( string $name, string $label, string $value, string $type = 'text' ): void {
		printf(
			'<tr><th scope="row"><label for="cp-%1$s">%2$s</label></th><td><input type="%3$s" id="cp-%1$s" name="%1$s" class="regular-text" value="%4$s"></td></tr>',
			esc_attr( $name ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( $value )
		);
	}

	private static function number( string $name, string $label, int $value ): void {
		printf(
			'<tr><th scope="row"><label for="cp-%1$s">%2$s</label></th><td><input type="number" min="0" step="1" id="cp-%1$s" name="%1$s" class="small-text" value="%3$d"></td></tr>',
			esc_attr( $name ),
			esc_html( $label ),
			$value
		);
	}
}
