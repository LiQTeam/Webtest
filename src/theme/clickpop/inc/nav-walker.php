<?php
/**
 * منوی قابل‌دسترس: aria-current روی صفحهٔ جاری، aria-expanded روی زیرمنو.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

final class ClickPop_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * @param string   $output
	 * @param WP_Post  $data_object
	 * @param int      $depth
	 * @param stdClass $args
	 * @param int      $current_object_id
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$classes   = empty( $data_object->classes ) ? [] : (array) $data_object->classes;
		$classes[] = 'cp-menu__item';

		$is_current = in_array( 'current-menu-item', $classes, true );
		$has_child  = in_array( 'menu-item-has-children', $classes, true );

		$output .= sprintf( '<li class="%s">', esc_attr( implode( ' ', array_filter( $classes ) ) ) );

		$output .= sprintf(
			'<a class="cp-menu__link" href="%1$s"%2$s%3$s>%4$s</a>',
			esc_url( (string) $data_object->url ),
			$is_current ? ' aria-current="page"' : '',
			$has_child ? ' aria-expanded="false"' : '',
			esc_html( (string) $data_object->title )
		);
	}
}

/**
 * خروجی منوی اصلی با پشتیبان مناسب وقتی منو تنظیم نشده است.
 */
function clickpop_primary_menu(): void {
	if ( ! has_nav_menu( 'primary' ) ) {
		return;
	}

	wp_nav_menu(
		[
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'cp-menu',
			'depth'          => 2,
			'walker'         => new ClickPop_Nav_Walker(),
			'fallback_cb'    => false,
		]
	);
}
