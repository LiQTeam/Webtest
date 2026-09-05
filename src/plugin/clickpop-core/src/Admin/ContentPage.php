<?php
declare( strict_types=1 );

namespace ClickPop\Core\Admin;

use ClickPop\Core\Support\Audit;

defined( 'ABSPATH' ) || exit;

/**
 * پنل مدیریت محتوا و ظاهر سایت.
 *
 * فرم از ContentSchema ساخته می‌شود؛ پاک‌سازی ورودی بر اساس نوع هر فیلد
 * انجام می‌شود، نه با یک تابع عمومی. هیچ HTML خامی پذیرفته نمی‌شود.
 */
final class ContentPage {

	private const CAP    = 'clickpop_manage_pricing';
	private const NONCE  = 'clickpop_content';
	public const  OPTION = 'clickpop_site_content';

	public static function register(): void {
		add_action( 'admin_post_clickpop_save_content', [ self::class, 'handleSave' ] );
		add_action( 'admin_post_clickpop_reset_content', [ self::class, 'handleReset' ] );
	}

	/** @return array<string,mixed> */
	public static function values(): array {
		$stored = get_option( self::OPTION, [] );

		return is_array( $stored ) ? array_merge( ContentSchema::defaults(), $stored ) : ContentSchema::defaults();
	}

	private static function value( string $key ): mixed {
		return self::values()[ $key ] ?? '';
	}

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ) );
		}

		$tabs = ContentSchema::tabs();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- انتخاب تب نمایشی.
		$active = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'appearance';

		if ( ! isset( $tabs[ $active ] ) ) {
			$active = 'appearance';
		}

		echo '<div class="wrap cp-admin cp-content-editor">';
		printf( '<h1>%s</h1>', esc_html__( 'محتوا و ظاهر سایت', 'clickpop-core' ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فقط نمایش پیام.
		if ( isset( $_GET['cp_msg'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( sanitize_text_field( wp_unslash( $_GET['cp_msg'] ) ) )
			);
		}

		/* ── نوار تب‌ها ── */
		echo '<nav class="cp-tabs" aria-label="' . esc_attr__( 'بخش‌های تنظیمات', 'clickpop-core' ) . '">';
		foreach ( $tabs as $slug => $tab ) {
			printf(
				'<a href="%1$s" class="cp-tabs__item%2$s"%3$s><span class="dashicons dashicons-%4$s" aria-hidden="true"></span>%5$s</a>',
				esc_url( admin_url( 'admin.php?page=' . Menu::SLUG . '-content&tab=' . $slug ) ),
				$active === $slug ? ' is-active' : '',
				$active === $slug ? ' aria-current="page"' : '',
				esc_attr( (string) ( $tab['icon'] ?? 'admin-generic' ) ),
				esc_html( (string) $tab['label'] )
			);
		}
		echo '</nav>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="cp-cform">';
		wp_nonce_field( self::NONCE );
		echo '<input type="hidden" name="action" value="clickpop_save_content">';
		printf( '<input type="hidden" name="tab" value="%s">', esc_attr( $active ) );

		$tab = $tabs[ $active ];

		if ( ! empty( $tab['intro'] ) ) {
			printf( '<div class="cp-intro">%s</div>', esc_html( (string) $tab['intro'] ) );
		}

		echo '<div class="cp-fields">';
		foreach ( $tab['fields'] as $key => $field ) {
			self::field( $key, $field );
		}
		echo '</div>';

		echo '<div class="cp-formbar">';
		submit_button( __( 'ذخیرهٔ تغییرات', 'clickpop-core' ), 'primary', 'submit', false );
		printf(
			'<a class="button button-link-delete" href="%s" onclick="return confirm(%s)">%s</a>',
			esc_url(
				wp_nonce_url(
					admin_url( 'admin-post.php?action=clickpop_reset_content&tab=' . $active ),
					'clickpop_reset_content'
				)
			),
			esc_attr( wp_json_encode( __( 'همهٔ فیلدهای این بخش به مقدار پیش‌فرض برمی‌گردند. ادامه می‌دهید؟', 'clickpop-core' ) ) ),
			esc_html__( 'بازگشت این بخش به پیش‌فرض', 'clickpop-core' )
		);
		echo '</div>';

		echo '</form></div>';
	}

	/** @param array<string,mixed> $field */
	private static function field( string $key, array $field ): void {
		$type  = (string) ( $field['type'] ?? 'text' );
		$id    = 'cp-f-' . $key;
		$value = self::value( $key );

		if ( 'repeater' === $type ) {
			self::repeater( $key, $field );

			return;
		}

		printf( '<div class="cp-field cp-field--%s">', esc_attr( $type ) );
		printf( '<label class="cp-field__label" for="%s">%s</label>', esc_attr( $id ), esc_html( (string) $field['label'] ) );
		echo '<div class="cp-field__control">';

		self::control( $key, $id, $type, $value, $field );

		if ( ! empty( $field['help'] ) ) {
			printf( '<p class="cp-field__help">%s</p>', esc_html( (string) $field['help'] ) );
		}

		echo '</div></div>';
	}

	/** @param array<string,mixed> $field */
	private static function control( string $name, string $id, string $type, mixed $value, array $field ): void {
		$attr_name = 'cp[' . $name . ']';

		switch ( $type ) {
			case 'toggle':
				printf(
					'<label class="cp-switch"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s><span class="cp-switch__ui" aria-hidden="true"></span></label>',
					esc_attr( $id ),
					esc_attr( $attr_name ),
					checked( 1, (int) $value, false )
				);
				break;

			case 'select':
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $attr_name ) );
				foreach ( (array) ( $field['options'] ?? [] ) as $opt_key => $opt_label ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( (string) $opt_key ),
						selected( (string) $opt_key, (string) $value, false ),
						esc_html( (string) $opt_label )
					);
				}
				echo '</select>';
				break;

			case 'color':
				printf(
					'<span class="cp-colorwrap"><input type="color" id="%1$s" name="%2$s" value="%3$s"><input type="text" class="cp-colorhex" value="%3$s" readonly tabindex="-1" aria-hidden="true"></span>',
					esc_attr( $id ),
					esc_attr( $attr_name ),
					esc_attr( (string) $value )
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%s" name="%s" value="%s" min="%s" max="%s" step="1" class="small-text">',
					esc_attr( $id ),
					esc_attr( $attr_name ),
					esc_attr( (string) $value ),
					esc_attr( (string) ( $field['min'] ?? 0 ) ),
					esc_attr( (string) ( $field['max'] ?? 9999 ) )
				);
				break;

			case 'textarea':
			case 'code':
				printf(
					'<textarea id="%s" name="%s" rows="%d" class="%s">%s</textarea>',
					esc_attr( $id ),
					esc_attr( $attr_name ),
					(int) ( $field['rows'] ?? ( 'code' === $type ? 8 : 3 ) ),
					'code' === $type ? 'cp-code' : '',
					esc_textarea( (string) $value )
				);
				break;

			case 'image':
				$img_id = (int) $value;
				$src    = $img_id > 0 ? wp_get_attachment_image_url( $img_id, 'medium' ) : '';

				printf(
					'<div class="cp-media" data-cp-media>
						<input type="hidden" id="%1$s" name="%2$s" value="%3$d" data-cp-media-input>
						<div class="cp-media__preview" data-cp-media-preview>%4$s</div>
						<button type="button" class="button" data-cp-media-pick>%5$s</button>
						<button type="button" class="button button-link-delete" data-cp-media-clear>%6$s</button>
					</div>',
					esc_attr( $id ),
					esc_attr( $attr_name ),
					$img_id,
					$src ? '<img src="' . esc_url( $src ) . '" alt="">' : '',
					esc_html__( 'انتخاب تصویر', 'clickpop-core' ),
					esc_html__( 'حذف', 'clickpop-core' )
				);
				break;

			case 'url':
			case 'email':
			case 'text':
			default:
				printf(
					'<input type="%s" id="%s" name="%s" value="%s" class="regular-text">',
					esc_attr( 'url' === $type ? 'text' : $type ),
					esc_attr( $id ),
					esc_attr( $attr_name ),
					esc_attr( (string) $value )
				);
				break;
		}
	}

	/** @param array<string,mixed> $field */
	private static function repeater( string $key, array $field ): void {
		$columns = (array) ( $field['columns'] ?? [] );
		$rows    = self::value( $key );
		$rows    = is_array( $rows ) ? array_values( $rows ) : [];

		printf(
			'<div class="cp-rep" data-cp-rep data-cp-rep-key="%s"><div class="cp-rep__head"><h3>%s</h3><button type="button" class="button button-secondary" data-cp-rep-add>%s</button></div>',
			esc_attr( $key ),
			esc_html( (string) $field['label'] ),
			esc_html__( '+ افزودن ردیف', 'clickpop-core' )
		);

		echo '<div class="cp-rep__rows" data-cp-rep-rows>';

		foreach ( $rows as $index => $row ) {
			self::repeaterRow( $key, (int) $index, $columns, is_array( $row ) ? $row : [] );
		}

		echo '</div>';

		// الگوی ردیف خالی برای افزودن با جاوااسکریپت.
		echo '<template data-cp-rep-tpl>';
		self::repeaterRow( $key, 0, $columns, [], true );
		echo '</template>';

		echo '</div>';
	}

	/**
	 * @param array<string,array<string,string>> $columns
	 * @param array<string,mixed>                $row
	 */
	private static function repeaterRow( string $key, int $index, array $columns, array $row, bool $template = false ): void {
		$i = $template ? '__INDEX__' : (string) $index;

		echo '<div class="cp-rep__row" data-cp-rep-row>';
		echo '<span class="cp-rep__grip" aria-hidden="true"></span>';
		echo '<div class="cp-rep__cols">';

		foreach ( $columns as $col => $def ) {
			$name  = sprintf( 'cp_rep[%s][%s][%s]', $key, $i, $col );
			$value = isset( $row[ $col ] ) ? $row[ $col ] : '';

			echo '<label class="cp-rep__col">';
			printf( '<span class="cp-rep__collabel">%s</span>', esc_html( (string) $def['label'] ) );

			switch ( (string) $def['type'] ) {
				case 'textarea':
					printf( '<textarea name="%s" rows="2">%s</textarea>', esc_attr( $name ), esc_textarea( (string) $value ) );
					break;

				case 'icon':
					printf( '<select name="%s" class="cp-iconsel">', esc_attr( $name ) );
					foreach ( ContentSchema::icons() as $ic => $label ) {
						printf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $ic ),
							selected( $ic, (string) $value, false ),
							esc_html( $label )
						);
					}
					echo '</select>';
					break;

				case 'network':
					printf( '<select name="%s">', esc_attr( $name ) );
					foreach ( ContentSchema::networks() as $net => $label ) {
						printf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $net ),
							selected( $net, (string) $value, false ),
							esc_html( $label )
						);
					}
					echo '</select>';
					break;

				case 'image':
					$img = (int) $value;
					$src = $img > 0 ? wp_get_attachment_image_url( $img, 'thumbnail' ) : '';
					printf(
						'<span class="cp-media cp-media--sm" data-cp-media>
							<input type="hidden" name="%1$s" value="%2$d" data-cp-media-input>
							<span class="cp-media__preview" data-cp-media-preview>%3$s</span>
							<button type="button" class="button button-small" data-cp-media-pick>%4$s</button>
						</span>',
						esc_attr( $name ),
						$img,
						$src ? '<img src="' . esc_url( $src ) . '" alt="">' : '',
						esc_html__( 'تصویر', 'clickpop-core' )
					);
					break;

				default:
					printf( '<input type="text" name="%s" value="%s">', esc_attr( $name ), esc_attr( (string) $value ) );
					break;
			}

			echo '</label>';
		}

		echo '</div>';
		printf(
			'<button type="button" class="cp-rep__del" data-cp-rep-del aria-label="%s">&times;</button>',
			esc_attr__( 'حذف ردیف', 'clickpop-core' )
		);
		echo '</div>';
	}

	/* ─────────────────────────── ذخیره ─────────────────────────── */

	public static function handleSave(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( self::NONCE );

		$tabs = ContentSchema::tabs();
		$tab  = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';

		if ( ! isset( $tabs[ $tab ] ) ) {
			self::back( 'appearance', __( 'بخش نامعتبر.', 'clickpop-core' ) );
		}

		$data   = self::values();
		$fields = $tabs[ $tab ]['fields'];

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- هر مقدار جداگانه و بر اساس نوعش پاک‌سازی می‌شود.
		$raw = isset( $_POST['cp'] ) ? (array) wp_unslash( $_POST['cp'] ) : [];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$rep = isset( $_POST['cp_rep'] ) ? (array) wp_unslash( $_POST['cp_rep'] ) : [];

		foreach ( $fields as $key => $field ) {
			$type = (string) ( $field['type'] ?? 'text' );

			if ( 'repeater' === $type ) {
				$data[ $key ] = self::cleanRepeater( $rep[ $key ] ?? [], (array) ( $field['columns'] ?? [] ) );
				continue;
			}

			if ( 'toggle' === $type ) {
				$data[ $key ] = isset( $raw[ $key ] ) ? 1 : 0;
				continue;
			}

			$data[ $key ] = self::cleanValue( $raw[ $key ] ?? '', $type, $field );
		}

		update_option( self::OPTION, $data, false );

		Audit::log( 'content.update', 'site', null, null, [ 'tab' => $tab ] );

		self::back( $tab, __( 'تغییرات ذخیره شد.', 'clickpop-core' ) );
	}

	/** @param array<string,mixed> $field */
	private static function cleanValue( mixed $value, string $type, array $field = [] ): mixed {
		if ( is_array( $value ) ) {
			return '';
		}

		$value = (string) $value;

		return match ( $type ) {
			'number' => max(
				(int) ( $field['min'] ?? 0 ),
				min( (int) ( $field['max'] ?? 9999 ), absint( $value ) )
			),
			'image'  => absint( $value ),
			'email'  => sanitize_email( $value ),
			// لنگر داخلی مثل ‎#services‎ مجاز است؛ بقیه باید URL معتبر باشند.
			'url'    => str_starts_with( $value, '#' ) ? sanitize_text_field( $value ) : esc_url_raw( $value ),
			'color'  => self::cleanColor( $value ),
			'select' => array_key_exists( $value, (array) ( $field['options'] ?? [] ) ) ? $value : (string) ( $field['default'] ?? '' ),
			// CSS سفارشی: تگ و کاراکترهای خطرناک حذف می‌شوند تا به بردار XSS تبدیل نشود.
			'code'   => self::cleanCss( $value ),
			'textarea' => sanitize_textarea_field( $value ),
			default  => sanitize_text_field( $value ),
		};
	}

	private static function cleanColor( string $value ): string {
		$hex = sanitize_hex_color( $value );

		return is_string( $hex ) ? $hex : '#1668FF';
	}

	/** CSS سفارشی بدون تگ و بدون توالی‌های اجرایی. */
	private static function cleanCss( string $css ): string {
		$css = wp_strip_all_tags( $css );
		$css = str_replace( [ '<', '>' ], '', $css );
		$css = preg_replace( '/javascript\s*:/i', '', $css ) ?? '';
		$css = preg_replace( '/expression\s*\(/i', '', $css ) ?? '';
		$css = preg_replace( '/@import/i', '', $css ) ?? '';

		return mb_substr( trim( $css ), 0, 20000 );
	}

	/**
	 * @param mixed                              $rows
	 * @param array<string,array<string,string>> $columns
	 * @return array<int,array<string,mixed>>
	 */
	private static function cleanRepeater( mixed $rows, array $columns ): array {
		if ( ! is_array( $rows ) ) {
			return [];
		}

		$out = [];

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = [];

			foreach ( $columns as $col => $def ) {
				$value = $row[ $col ] ?? '';

				if ( is_array( $value ) ) {
					continue;
				}

				$clean[ $col ] = match ( (string) $def['type'] ) {
					'textarea' => sanitize_textarea_field( (string) $value ),
					'url'      => str_starts_with( (string) $value, '#' ) ? sanitize_text_field( (string) $value ) : esc_url_raw( (string) $value ),
					'image'    => absint( $value ),
					'icon'     => array_key_exists( (string) $value, ContentSchema::icons() ) ? (string) $value : 'check',
					'network'  => array_key_exists( (string) $value, ContentSchema::networks() ) ? (string) $value : 'instagram',
					default    => sanitize_text_field( (string) $value ),
				};
			}

			// ردیفی که هیچ مقدار معناداری ندارد ذخیره نمی‌شود.
			$meaningful = array_filter(
				$clean,
				static fn( $v ): bool => is_int( $v ) ? $v > 0 : '' !== trim( (string) $v )
			);

			if ( $meaningful ) {
				$out[] = $clean;
			}
		}

		return $out;
	}

	public static function handleReset(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'دسترسی لازم را ندارید.', 'clickpop-core' ), '', [ 'response' => 403 ] );
		}

		check_admin_referer( 'clickpop_reset_content' );

		$tabs = ContentSchema::tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		if ( ! isset( $tabs[ $tab ] ) ) {
			self::back( 'appearance', __( 'بخش نامعتبر.', 'clickpop-core' ) );
		}

		$data = self::values();

		foreach ( $tabs[ $tab ]['fields'] as $key => $field ) {
			$data[ $key ] = $field['default'] ?? '';
		}

		update_option( self::OPTION, $data, false );

		Audit::log( 'content.reset', 'site', null, null, [ 'tab' => $tab ] );

		self::back( $tab, __( 'این بخش به پیش‌فرض برگشت.', 'clickpop-core' ) );
	}

	private static function back( string $tab, string $message ): never {
		wp_safe_redirect(
			add_query_arg(
				[
					'page'   => Menu::SLUG . '-content',
					'tab'    => $tab,
					'cp_msg' => rawurlencode( $message ),
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
