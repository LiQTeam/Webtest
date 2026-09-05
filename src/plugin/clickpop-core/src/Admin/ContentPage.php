<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * ویرایشگر محتوای صفحهٔ اصلی — جایگزین صفحه‌ساز.
 *
 * خروجی در یک گزینهٔ آرایه‌ای ذخیره می‌شود که تم می‌خواند.
 * هیچ HTML خامی از کاربر پذیرفته نمی‌شود؛ همه‌چیز متن ساده است.
 */
final class ContentPage {

	private const CAP    = 'clickpop_manage_pricing';
	private const NONCE  = 'clickpop_content';
	public const  OPTION = 'clickpop_site_content';

	/** تعداد ردیف‌های هر فهرست تکرارشونده در فرم. */
	private const ROWS = 4;

	public static function register(): void {
		add_action( 'admin_post_clickpop_save_content', [ self::class, 'handleSave' ] );
	}

	/** @return array<string,mixed> */
	public static function current(): array {
		$stored = get_option( self::OPTION, [] );

		return is_array( $stored ) ? $stored : [];
	}

	private static function get( string $key, string $fallback = '' ): string {
		$data = self::current();

		return isset( $data[ $key ] ) && is_string( $data[ $key ] ) ? $data[ $key ] : $fallback;
	}

	/** @return array<int,array<string,string>> */
	private static function getList( string $key ): array {
		$data = self::current();

		return isset( $data[ $key ] ) && is_array( $data[ $key ] ) ? array_values( $data[ $key ] ) : [];
	}

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		echo '<div class="wrap cp-admin cp-content-editor">';
		printf( '<h1>%s</h1>', esc_html__( 'محتوای سایت', 'clickpop-core' ) );
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'متن‌های صفحهٔ اصلی. فیلدهای خالی، مقدار پیش‌فرض تم را می‌گیرند — یعنی صفحه هیچ‌وقت خالی نمی‌ماند.', 'clickpop-core' )
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط نمایش پیام.
		if ( isset( $_GET['cp_msg'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['cp_msg'] ) ) )
			);
		}

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( self::NONCE );
		echo '<input type="hidden" name="action" value="clickpop_save_content">';

		/* ── هیرو ── */
		self::section( __( 'بخش ابتدایی (هیرو)', 'clickpop-core' ) );
		echo '<table class="form-table" role="presentation"><tbody>';
		self::text( 'hero_eyebrow', __( 'برچسب کوچک بالای عنوان', 'clickpop-core' ) );
		self::textarea( 'hero_title', __( 'عنوان اصلی', 'clickpop-core' ), 2 );
		self::textarea( 'hero_text', __( 'توضیح زیر عنوان', 'clickpop-core' ), 4 );
		self::text( 'hero_cta_text', __( 'متن دکمهٔ اصلی', 'clickpop-core' ) );
		self::text( 'hero_cta_url', __( 'لینک دکمهٔ اصلی', 'clickpop-core' ), 'text', '#services' );
		self::text( 'hero_alt_text', __( 'متن دکمهٔ دوم', 'clickpop-core' ) );
		self::text( 'hero_alt_url', __( 'لینک دکمهٔ دوم', 'clickpop-core' ), 'text', '#how' );
		echo '</tbody></table>';

		/* ── سرویس‌ها ── */
		self::section( __( 'بخش سرویس‌ها', 'clickpop-core' ) );
		echo '<table class="form-table" role="presentation"><tbody>';
		self::text( 'services_title', __( 'عنوان بخش', 'clickpop-core' ) );
		self::textarea( 'services_text', __( 'توضیح بخش', 'clickpop-core' ), 3 );
		echo '</tbody></table>';
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'کارت پلتفرم‌ها و قیمت‌ها خودکار از دیتابیس ساخته می‌شوند و نیازی به ویرایش دستی ندارند.', 'clickpop-core' )
		);

		/* ── مراحل ── */
		self::section( __( 'مراحل کار', 'clickpop-core' ) );
		echo '<table class="form-table" role="presentation"><tbody>';
		self::text( 'steps_title', __( 'عنوان بخش', 'clickpop-core' ) );
		echo '</tbody></table>';
		self::repeater( 'steps', [ 'title' => __( 'عنوان مرحله', 'clickpop-core' ), 'text' => __( 'توضیح', 'clickpop-core' ) ] );

		/* ── آمار ── */
		self::section( __( 'آمارها', 'clickpop-core' ) );
		self::repeater( 'stats', [ 'value' => __( 'عدد یا متن', 'clickpop-core' ), 'label' => __( 'برچسب', 'clickpop-core' ) ] );

		/* ── پرسش‌ها ── */
		self::section( __( 'پرسش‌های پرتکرار', 'clickpop-core' ) );
		echo '<table class="form-table" role="presentation"><tbody>';
		self::text( 'faq_title', __( 'عنوان بخش', 'clickpop-core' ) );
		echo '</tbody></table>';
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'این پرسش‌ها به‌صورت خودکار داده‌ساختار FAQPage گوگل را هم تولید می‌کنند. فقط پرسش‌هایی را بنویسید که واقعاً روی صفحه دیده می‌شوند.', 'clickpop-core' )
		);
		self::repeater( 'faq', [ 'q' => __( 'پرسش', 'clickpop-core' ), 'a' => __( 'پاسخ', 'clickpop-core' ) ], true );

		/* ── فراخوان پایانی ── */
		self::section( __( 'فراخوان پایانی', 'clickpop-core' ) );
		echo '<table class="form-table" role="presentation"><tbody>';
		self::text( 'cta_title', __( 'عنوان', 'clickpop-core' ) );
		self::text( 'cta_text', __( 'توضیح', 'clickpop-core' ) );
		self::text( 'cta_button', __( 'متن دکمه', 'clickpop-core' ) );
		echo '</tbody></table>';

		/* ── تماس و شبکه‌ها ── */
		self::section( __( 'اطلاعات تماس و شبکه‌های اجتماعی', 'clickpop-core' ) );
		echo '<table class="form-table" role="presentation"><tbody>';
		self::text( 'contact_phone', __( 'تلفن', 'clickpop-core' ) );
		self::text( 'contact_email', __( 'ایمیل', 'clickpop-core' ), 'email' );
		self::textarea( 'contact_address', __( 'نشانی', 'clickpop-core' ), 2 );
		self::text( 'social_instagram', __( 'اینستاگرام (آدرس کامل)', 'clickpop-core' ), 'url' );
		self::text( 'social_telegram', __( 'تلگرام (آدرس کامل)', 'clickpop-core' ), 'url' );
		self::text( 'social_x', __( 'ایکس / توییتر (آدرس کامل)', 'clickpop-core' ), 'url' );
		echo '</tbody></table>';

		submit_button( __( 'ذخیرهٔ محتوا', 'clickpop-core' ) );
		echo '</form></div>';
	}

	private static function section( string $title ): void {
		printf( '<h2 class="cp-sec">%s</h2>', esc_html( $title ) );
	}

	private static function text( string $key, string $label, string $type = 'text', string $placeholder = '' ): void {
		printf(
			'<tr><th scope="row"><label for="cp-c-%1$s">%2$s</label></th><td>
				<input type="%3$s" id="cp-c-%1$s" name="cp[%1$s]" class="regular-text" value="%4$s" placeholder="%5$s"></td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( $type ),
			esc_attr( self::get( $key ) ),
			esc_attr( $placeholder )
		);
	}

	private static function textarea( string $key, string $label, int $rows ): void {
		printf(
			'<tr><th scope="row"><label for="cp-c-%1$s">%2$s</label></th><td>
				<textarea id="cp-c-%1$s" name="cp[%1$s]" rows="%3$d" class="large-text">%4$s</textarea></td></tr>',
			esc_attr( $key ),
			esc_html( $label ),
			$rows,
			esc_textarea( self::get( $key ) )
		);
	}

	/**
	 * فهرست تکرارشونده با تعداد ردیف ثابت — بدون جاوااسکریپت، بدون شکنندگی.
	 *
	 * @param array<string,string> $fields نام فیلد => برچسب.
	 */
	private static function repeater( string $key, array $fields, bool $long = false ): void {
		$rows = self::getList( $key );

		echo '<table class="widefat striped cp-repeater"><thead><tr>';
		printf( '<th style="width:40px">#</th>' );
		foreach ( $fields as $label ) {
			printf( '<th>%s</th>', esc_html( $label ) );
		}
		echo '</tr></thead><tbody>';

		for ( $i = 0; $i < self::ROWS; $i++ ) {
			$row = $rows[ $i ] ?? [];

			echo '<tr>';
			printf( '<td>%s</td>', esc_html( number_format_i18n( $i + 1 ) ) );

			foreach ( $fields as $field => $label ) {
				$value = isset( $row[ $field ] ) && is_string( $row[ $field ] ) ? $row[ $field ] : '';
				$name  = sprintf( 'cp_list[%s][%d][%s]', $key, $i, $field );

				if ( $long && 'a' === $field ) {
					printf(
						'<td><textarea name="%1$s" rows="3" class="large-text" aria-label="%2$s">%3$s</textarea></td>',
						esc_attr( $name ),
						esc_attr( $label ),
						esc_textarea( $value )
					);
				} else {
					printf(
						'<td><input type="text" name="%1$s" class="large-text" value="%2$s" aria-label="%3$s"></td>',
						esc_attr( $name ),
						esc_attr( $value ),
						esc_attr( $label )
					);
				}
			}

			echo '</tr>';
		}

		echo '</tbody></table>';
		printf( '<p class="description">%s</p>', esc_html__( 'ردیف‌های خالی نادیده گرفته می‌شوند.', 'clickpop-core' ) );
	}

	public static function handleSave(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::NONCE );

		$data = [];

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- هر مقدار جداگانه پاک‌سازی می‌شود.
		$raw = isset( $_POST['cp'] ) ? (array) wp_unslash( $_POST['cp'] ) : [];

		$url_keys      = [ 'hero_cta_url', 'hero_alt_url', 'social_instagram', 'social_telegram', 'social_x' ];
		$textarea_keys = [ 'hero_title', 'hero_text', 'services_text', 'contact_address' ];

		foreach ( $raw as $key => $value ) {
			$key = sanitize_key( (string) $key );

			if ( ! is_scalar( $value ) ) {
				continue;
			}

			$value = (string) $value;

			if ( in_array( $key, $url_keys, true ) ) {
				// لنگر داخلی (#services) مجاز است، بقیه باید URL معتبر باشند.
				$data[ $key ] = str_starts_with( $value, '#' )
					? sanitize_text_field( $value )
					: esc_url_raw( $value );
				continue;
			}

			if ( 'contact_email' === $key ) {
				$data[ $key ] = sanitize_email( $value );
				continue;
			}

			$data[ $key ] = in_array( $key, $textarea_keys, true )
				? sanitize_textarea_field( $value )
				: sanitize_text_field( $value );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- هر مقدار جداگانه پاک‌سازی می‌شود.
		$lists = isset( $_POST['cp_list'] ) ? (array) wp_unslash( $_POST['cp_list'] ) : [];

		foreach ( [ 'steps', 'stats', 'faq' ] as $list_key ) {
			$rows = isset( $lists[ $list_key ] ) && is_array( $lists[ $list_key ] ) ? $lists[ $list_key ] : [];
			$out  = [];

			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$clean = [];

				foreach ( $row as $field => $value ) {
					if ( ! is_scalar( $value ) ) {
						continue;
					}

					$clean[ sanitize_key( (string) $field ) ] = sanitize_textarea_field( (string) $value );
				}

				// ردیفی که همهٔ فیلدهایش خالی است ذخیره نمی‌شود.
				if ( array_filter( $clean, static fn( string $v ): bool => '' !== trim( $v ) ) ) {
					$out[] = $clean;
				}
			}

			$data[ $list_key ] = $out;
		}

		update_option( self::OPTION, $data, false );

		\ClickPop\Core\Support\Audit::log( 'content.update', 'site', null, null, [ 'keys' => array_keys( $data ) ] );

		wp_safe_redirect(
			add_query_arg(
				[
					'page'   => Menu::SLUG . '-content',
					'cp_msg' => rawurlencode( __( 'محتوای سایت ذخیره شد.', 'clickpop-core' ) ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
