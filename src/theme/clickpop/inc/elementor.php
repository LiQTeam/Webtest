<?php
/**
 * یکپارچگی با المنتور.
 *
 * همه‌چیز پشت گارد did_action('elementor/loaded') است:
 * سایت بدون المنتور هم باید کامل بالا بیاید.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function clickpop_elementor_active(): bool {
	return (bool) did_action( 'elementor/loaded' );
}

/**
 * پشتیبانی از Theme Builder المنتور پرو (هدر، فوتر، تک‌نوشته، آرشیو).
 */
add_action(
	'elementor/theme/register_locations',
	static function ( $manager ): void {
		$manager->register_all_core_location();
	}
);

/**
 * دستهٔ اختصاصی ویجت‌ها تا در پنل المنتور گم نشوند.
 */
add_action(
	'elementor/elements/categories_registered',
	static function ( $manager ): void {
		$manager->add_category(
			'clickpop',
			[
				'title' => __( 'کلیک‌پاپ', 'clickpop' ),
				'icon'  => 'eicon-woo-settings',
			]
		);
	}
);

/**
 * ثبت ویجت‌های تم.
 */
add_action(
	'elementor/widgets/register',
	static function ( $manager ): void {
		require_once CLICKPOP_THEME_DIR . '/widgets/class-cp-widget-base.php';

		$widgets = [
			'class-cp-widget-hero.php'     => 'CP_Widget_Hero',
			'class-cp-widget-services.php' => 'CP_Widget_Services',
			'class-cp-widget-steps.php'    => 'CP_Widget_Steps',
			'class-cp-widget-counters.php' => 'CP_Widget_Counters',
			'class-cp-widget-faq.php'      => 'CP_Widget_Faq',
		];

		foreach ( $widgets as $file => $class ) {
			$path = CLICKPOP_THEME_DIR . '/widgets/' . $file;

			if ( ! is_readable( $path ) ) {
				continue;
			}

			require_once $path;

			if ( class_exists( $class ) ) {
				$manager->register( new $class() );
			}
		}
	}
);

/**
 * حذف asset های المنتور که استفاده نمی‌شوند.
 * فونت‌آیکن سنگین است و ما آیکن‌ها را SVG می‌دهیم.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() || is_customize_preview() ) {
			return;
		}

		if ( ! (bool) apply_filters( 'clickpop/elementor/dequeue_icon_fonts', true ) ) {
			return;
		}

		wp_dequeue_style( 'elementor-icons' );
		wp_dequeue_style( 'font-awesome' );
		wp_dequeue_style( 'elementor-icons-shared-0' );
		wp_dequeue_style( 'elementor-icons-fa-solid' );
		wp_dequeue_style( 'elementor-icons-fa-regular' );
		wp_dequeue_style( 'elementor-icons-fa-brands' );
	},
	200
);
