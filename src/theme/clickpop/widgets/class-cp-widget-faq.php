<?php
/**
 * ویجت پرسش‌های پرتکرار.
 *
 * فقط پرسش‌هایی که واقعاً روی صفحه دیده می‌شوند به FAQPage schema می‌روند —
 * schema برای محتوای پنهان، نقض راهنمای گوگل است.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

final class CP_Widget_Faq extends CP_Widget_Base {

	public function get_name(): string {
		return 'clickpop_faq';
	}

	public function get_title(): string {
		return __( 'پرسش‌های پرتکرار', 'clickpop' );
	}

	public function get_icon(): string {
		return 'eicon-help-o';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => __( 'محتوا', 'clickpop' ) ] );

		$this->add_control(
			'heading',
			[
				'label'   => __( 'عنوان بخش', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'پرسش‌های پرتکرار', 'clickpop' ),
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'q',
			[
				'label' => __( 'پرسش', 'clickpop' ),
				'type'  => \Elementor\Controls_Manager::TEXT,
			]
		);

		$repeater->add_control(
			'a',
			[
				'label' => __( 'پاسخ', 'clickpop' ),
				'type'  => \Elementor\Controls_Manager::TEXTAREA,
				'rows'  => 4,
			]
		);

		$this->add_control(
			'items',
			[
				'label'       => __( 'پرسش‌ها', 'clickpop' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ q }}}',
			]
		);

		$this->add_control(
			'schema',
			[
				'label'        => __( 'تولید FAQPage schema', 'clickpop' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'description'  => __( 'فقط وقتی روشن باشد که این پرسش‌ها واقعاً روی همین صفحه دیده می‌شوند.', 'clickpop' ),
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

		$uid = 'cp-faq-' . $this->get_id();

		echo '<section class="cp-section" id="faq">';

		if ( ! empty( $s['heading'] ) ) {
			$this->heading( 'h2', (string) $s['heading'], 'cp-section__t' );
		}

		echo '<div class="cp-faq">';

		foreach ( $items as $index => $item ) {
			$panel_id = $uid . '-' . $index;
			$open     = 0 === $index;

			echo '<div class="cp-qa">';
			printf(
				'<button type="button" class="cp-qa__q" aria-expanded="%1$s" aria-controls="%2$s">%3$s<span class="cp-qa__i" aria-hidden="true"></span></button>',
				$open ? 'true' : 'false',
				esc_attr( $panel_id ),
				esc_html( (string) ( $item['q'] ?? '' ) )
			);
			printf(
				'<div class="cp-qa__a" id="%1$s"%2$s>%3$s</div>',
				esc_attr( $panel_id ),
				$open ? '' : ' hidden',
				esc_html( (string) ( $item['a'] ?? '' ) )
			);
			echo '</div>';
		}

		echo '</div></section>';

		if ( 'yes' === ( $s['schema'] ?? '' ) ) {
			$this->printSchema( $items );
		}
	}

	/** @param array<int,array<string,mixed>> $items */
	private function printSchema( array $items ): void {
		$questions = [];

		foreach ( $items as $item ) {
			if ( empty( $item['q'] ) || empty( $item['a'] ) ) {
				continue;
			}

			$questions[] = [
				'@type'          => 'Question',
				'name'           => wp_strip_all_tags( (string) $item['q'] ),
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => wp_strip_all_tags( (string) $item['a'] ),
				],
			];
		}

		if ( ! $questions ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>',
			wp_json_encode(
				[
					'@context'   => 'https://schema.org',
					'@type'      => 'FAQPage',
					'mainEntity' => $questions,
				],
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			)
		);
	}
}
