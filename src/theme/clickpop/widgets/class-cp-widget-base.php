<?php
/**
 * پایهٔ ویجت‌های المنتور کلیک‌پاپ.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

abstract class CP_Widget_Base extends \Elementor\Widget_Base {

	public function get_categories(): array {
		return [ 'clickpop' ];
	}

	/** ویجت‌های تم به CSS خود تم متکی‌اند، نه استایل درون‌خطی. */
	public function get_style_depends(): array {
		return [ 'clickpop-main' ];
	}

	public function get_custom_help_url(): string {
		return 'https://clickpop.ir/docs/';
	}

	/**
	 * خروجی امن یک عنوان با سطح دلخواه.
	 */
	protected function heading( string $tag, string $text, string $class = '' ): void {
		$allowed = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span' ];
		$tag     = in_array( $tag, $allowed, true ) ? $tag : 'h2';

		printf(
			'<%1$s class="%2$s">%3$s</%1$s>',
			esc_html( $tag ),
			esc_attr( $class ),
			esc_html( $text )
		);
	}
}
