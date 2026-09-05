<?php
declare( strict_types=1 );

namespace ClickPop\Core\Setup;

defined( 'ABSPATH' ) || exit;

/**
 * ساخت خودکار صفحه‌های لازم هنگام فعال‌سازی.
 *
 * هر صفحه با یک متای شناسه علامت‌گذاری می‌شود تا در اجراهای بعدی دوباره ساخته نشود
 * و اگر مدیر آن را حذف کرد، دوباره تحمیل نشود.
 */
final class PageInstaller {

	private const META_KEY = '_clickpop_page';

	/** @return array<string,array<string,string>> */
	private static function definitions(): array {
		return [
			'dashboard' => [
				'title'    => __( 'داشبورد', 'clickpop-core' ),
				'slug'     => 'dashboard',
				'content'  => '[clickpop_dashboard]',
				'template' => 'templates/page-dashboard.php',
				'option'   => 'clickpop_dashboard_page_id',
			],
			'services'  => [
				'title'    => __( 'سرویس‌ها و قیمت‌ها', 'clickpop-core' ),
				'slug'     => 'services-pricing',
				'content'  => '',
				'template' => 'templates/page-services.php',
				'option'   => 'clickpop_services_page_id',
			],
			'terms'     => [
				'title'    => __( 'قوانین و مقررات', 'clickpop-core' ),
				'slug'     => 'terms',
				'content'  => self::termsBody(),
				'template' => '',
				'option'   => 'clickpop_terms_page_id',
			],
		];
	}

	/** @return array<string,int> */
	public static function ensure(): array {
		$result = [];

		foreach ( self::definitions() as $key => $def ) {
			$existing = (int) get_option( $def['option'], 0 );

			if ( $existing > 0 && 'publish' === get_post_status( $existing ) ) {
				$result[ $key ] = $existing;
				continue;
			}

			$found = self::findByMeta( $key );

			if ( $found > 0 ) {
				update_option( $def['option'], $found, false );
				$result[ $key ] = $found;
				continue;
			}

			$page_id = wp_insert_post(
				[
					'post_title'   => $def['title'],
					'post_name'    => $def['slug'],
					'post_content' => $def['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'meta_input'   => [ self::META_KEY => $key ],
				],
				true
			);

			if ( is_wp_error( $page_id ) || ! $page_id ) {
				continue;
			}

			$page_id = (int) $page_id;

			if ( '' !== $def['template'] ) {
				update_post_meta( $page_id, '_wp_page_template', $def['template'] );
			}

			update_option( $def['option'], $page_id, false );
			$result[ $key ] = $page_id;
		}

		return $result;
	}

	private static function findByMeta( string $key ): int {
		$pages = get_posts(
			[
				'post_type'      => 'page',
				'post_status'    => [ 'publish', 'draft', 'private' ],
				'posts_per_page' => 1,
				'fields'         => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_key'       => self::META_KEY,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value'     => $key,
			]
		);

		return $pages ? (int) $pages[0] : 0;
	}

	/**
	 * ساخت منوی اصلی اگر هیچ منویی به جایگاه primary وصل نباشد.
	 *
	 * @param array<string,int> $pages
	 */
	public static function ensureMenu( array $pages ): void {
		$locations = get_nav_menu_locations();

		if ( ! empty( $locations['primary'] ) && is_nav_menu( $locations['primary'] ) ) {
			return;
		}

		$menu_name = __( 'منوی اصلی کلیک‌پاپ', 'clickpop-core' );
		$menu      = wp_get_nav_menu_object( $menu_name );
		$menu_id   = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $menu_name );

		if ( $menu_id <= 0 ) {
			return;
		}

		if ( ! wp_get_nav_menu_items( $menu_id ) ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				[
					'menu-item-title'  => __( 'خانه', 'clickpop-core' ),
					'menu-item-url'    => home_url( '/' ),
					'menu-item-status' => 'publish',
				]
			);

			foreach ( [ 'services', 'dashboard', 'terms' ] as $key ) {
				if ( empty( $pages[ $key ] ) ) {
					continue;
				}

				wp_update_nav_menu_item(
					$menu_id,
					0,
					[
						'menu-item-object-id' => (int) $pages[ $key ],
						'menu-item-object'    => 'page',
						'menu-item-type'      => 'post_type',
						'menu-item-status'    => 'publish',
					]
				);
			}
		}

		$locations['primary'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/** متن اولیهٔ صفحهٔ قوانین — نقطهٔ شروع، نه متن حقوقی نهایی. */
	private static function termsBody(): string {
		return implode(
			"\n\n",
			[
				__( 'با ثبت سفارش در این سایت، شرایط زیر را می‌پذیرید. این متن نمونه است؛ پیش از شروع فروش آن را با شرایط واقعی کسب‌وکار خود جایگزین کنید.', 'clickpop-core' ),
				__( 'صفحهٔ هدف باید تا پایان سفارش عمومی بماند. خصوصی‌شدن صفحه در میانهٔ کار سفارش را ناقص می‌کند و فقط مقدار انجام‌نشده به کیف پول برمی‌گردد.', 'clickpop-core' ),
				__( 'مبالغ برگشتی به کیف پول کاربر واریز می‌شود و قابل برداشت نقدی نیست.', 'clickpop-core' ),
				__( 'هیچ‌گاه رمز حساب کاربری شما درخواست نمی‌شود؛ فقط لینک عمومی صفحه یا پست لازم است.', 'clickpop-core' ),
				__( 'زمان تحویل اعلام‌شده تخمینی است و به شرایط سرویس‌دهنده بستگی دارد.', 'clickpop-core' ),
			]
		);
	}
}
