<?php
/**
 * ویجت شمارنده — انیمیشن با احترام به prefers-reduced-motion.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

final class CP_Widget_Counters extends CP_Widget_Base {

	public function get_name(): string {
		return 'clickpop_counters';
	}

	public function get_title(): string {
		return __( 'شمارنده‌های آماری', 'clickpop' );
	}

	public function get_icon(): string {
		return 'eicon-counter';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => __( 'محتوا', 'clickpop' ) ] );

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'value',
			[
				'label'   => __( 'عدد', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 1000,
			]
		);

		$repeater->add_control(
			'suffix',
			[
				'label' => __( 'پسوند', 'clickpop' ),
				'type'  => \Elementor\Controls_Manager::TEXT,
			]
		);

		$repeater->add_control(
			'label',
			[
				'label'   => __( 'برچسب', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'سفارش تحویل‌شده', 'clickpop' ),
			]
		);

		$this->add_control(
			'items',
			[
				'label'       => __( 'آمارها', 'clickpop' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ label }}}',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$items = is_array( $s['items'] ?? null ) ? $s['items'] : [];

		if ( ! $items ) {
			return;
		}

		echo '<div class="cp-stats">';

		foreach ( $items as $item ) {
			$value = (int) ( $item['value'] ?? 0 );

			echo '<div class="cp-stat">';
			printf(
				'<span class="cp-stat__v" data-cp-count="%1$d">%2$s<em>%3$s</em></span>',
				$value,
				esc_html( number_format_i18n( $value ) ),
				esc_html( (string) ( $item['suffix'] ?? '' ) )
			);
			printf( '<span class="cp-stat__l">%s</span>', esc_html( (string) ( $item['label'] ?? '' ) ) );
			echo '</div>';
		}

		echo '</div>';
	}
}
