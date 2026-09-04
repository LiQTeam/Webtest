<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Support\Jalali;
use ClickPop\Core\Support\Money;
use ClickPop\Core\Wallet\WalletService;

defined( 'ABSPATH' ) || exit;

final class WalletPage {

	private const CAP   = 'clickpop_adjust_balance';
	private const NONCE = 'clickpop_adjust';

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		global $wpdb;

		$transactions = Installer::table( 'transactions' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT * FROM {$transactions} ORDER BY id DESC LIMIT 50"
		);

		echo '<div class="wrap cp-admin">';
		printf( '<h1>%s</h1>', esc_html__( 'کیف پول کاربران', 'clickpop-core' ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط نمایش پیام.
		if ( isset( $_GET['cp_msg'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['cp_msg'] ) ) )
			);
		}

		echo '<h2>' . esc_html__( 'تعدیل دستی موجودی', 'clickpop-core' ) . '</h2>';
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'هر تعدیل، یک ردیف دائمی در دفتر کل و یک رکورد در گزارش ممیزی می‌سازد. دلیل اجباری است.', 'clickpop-core' )
		);

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="cp-adjust">';
		wp_nonce_field( self::NONCE );
		echo '<input type="hidden" name="action" value="clickpop_adjust_balance">';
		echo '<table class="form-table" role="presentation"><tbody>';

		echo '<tr><th scope="row"><label for="cp-user">' . esc_html__( 'کاربر', 'clickpop-core' ) . '</label></th><td>';
		wp_dropdown_users(
			[
				'name'     => 'user_id',
				'id'       => 'cp-user',
				'show'     => 'display_name_with_login',
				'number'   => 200,
				'orderby'  => 'registered',
				'order'    => 'DESC',
			]
		);
		echo '</td></tr>';

		printf(
			'<tr><th scope="row"><label for="cp-amount">%s</label></th><td>
				<input type="number" step="1" id="cp-amount" name="amount_toman" class="regular-text" required>
				<p class="description">%s</p></td></tr>',
			esc_html__( 'مبلغ (تومان)', 'clickpop-core' ),
			esc_html__( 'عدد مثبت = افزایش موجودی، عدد منفی = کسر موجودی.', 'clickpop-core' )
		);

		printf(
			'<tr><th scope="row"><label for="cp-reason">%s</label></th><td>
				<input type="text" id="cp-reason" name="reason" class="regular-text" required maxlength="255"></td></tr>',
			esc_html__( 'دلیل', 'clickpop-core' )
		);

		echo '</tbody></table>';
		submit_button( __( 'ثبت تعدیل', 'clickpop-core' ) );
		echo '</form>';

		echo '<h2>' . esc_html__( 'آخرین تراکنش‌ها', 'clickpop-core' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr>';
		foreach ( [ 'شناسه', 'کاربر', 'نوع', 'جهت', 'مبلغ', 'وضعیت', 'مرجع', 'دلیل', 'تاریخ' ] as $heading ) {
			printf( '<th>%s</th>', esc_html( $heading ) );
		}
		echo '</tr></thead><tbody>';

		foreach ( (array) $rows as $row ) {
			$user = get_userdata( (int) $row->user_id );

			echo '<tr>';
			printf( '<td>#%d</td>', (int) $row->id );
			printf( '<td>%s</td>', esc_html( $user ? $user->display_name : '—' ) );
			printf( '<td>%s</td>', esc_html( (string) $row->type ) );
			printf( '<td>%s</td>', esc_html( 'credit' === $row->direction ? '+' : '−' ) );
			printf( '<td>%s</td>', esc_html( Money::fromRials( (int) $row->amount )->format( false ) ) );
			printf( '<td>%s</td>', esc_html( (string) $row->status ) );
			printf( '<td>%s</td>', esc_html( trim( (string) $row->ref_type . ' ' . (string) $row->ref_id ) ) );
			printf( '<td>%s</td>', esc_html( (string) ( $row->reason ?? '' ) ) );
			printf( '<td>%s</td>', esc_html( Jalali::format( (string) $row->created_at ) ) );
			echo '</tr>';
		}

		echo '</tbody></table></div>';
	}

	public static function handleAdjust(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::NONCE );

		$user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		$toman   = isset( $_POST['amount_toman'] ) ? (int) wp_unslash( $_POST['amount_toman'] ) : 0;
		$reason  = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';

		$message = __( 'تعدیل انجام نشد. ورودی‌ها یا موجودی را بررسی کنید.', 'clickpop-core' );

		if ( $user_id > 0 && 0 !== $toman && '' !== trim( $reason ) ) {
			$ok = ( new WalletService() )->adjust( $user_id, $toman * Money::RIAL_PER_TOMAN, $reason );

			if ( $ok ) {
				$message = __( 'تعدیل ثبت شد.', 'clickpop-core' );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'page'   => Menu::SLUG . '-wallet',
					'cp_msg' => rawurlencode( $message ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
