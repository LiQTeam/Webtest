<?php
/**
 * ویجت مراحل — شماره‌گذاری فقط چون محتوا واقعاً یک توالی است.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

final class CP_Widget_Steps extends CP_Widget_Base {

	public function get_name(): string {
		return 'clickpop_steps';
	}

	public function get_title(): string {
		return __( 'مراحل کار', 'clickpop' );
	}

	public function get_icon(): string {
		return 'eicon-number-field';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => __( 'محتوا', 'clickpop' ) ] );

		$this->add_control(
			'heading',
			[
				'label'   => __( 'عنوان بخش', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'سه مرحله تا تحویل', 'clickpop' ),
			]
		);

		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'title',
			[
				'label'   => __( 'عنوان مرحله', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'کیف پول را شارژ کن', 'clickpop' ),
			]
		);

		$repeater->add_control(
			'text',
			[
				'label'   => __( 'توضیح', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'rows'    => 3,
			]
		);

		$this->add_control(
			'items',
			[
				'label'       => __( 'مراحل', 'clickpop' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => [
					[
						'title' => __( 'کیف پول را شارژ کن', 'clickpop' ),
						'text'  => __( 'پرداخت از درگاه بانکی با تأیید سمت سرور؛ هر شارژ یک ردیف دائمی در دفتر تراکنش‌ها می‌سازد.', 'clickpop' ),
					],
					[
						'title' => __( 'سرویس و لینک را بده', 'clickpop' ),
						'text'  => __( 'لینک با فهرست دامنه‌های مجاز همان پلتفرم بررسی می‌شود و تعداد باید داخل بازهٔ سرویس باشد.', 'clickpop' ),
					],
					[
						'title' => __( 'پیشرفت را زنده ببین', 'clickpop' ),
						'text'  => __( 'شمارندهٔ باقی‌مانده هر پنج دقیقه به‌روز می‌شود؛ سفارش ناقص خودکار برگشت می‌خورد.', 'clickpop' ),
					],
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s     = $this->get_settings_for_display();
		$items = is_array( $s['items'] ?? null ) ? $s['items'] : [];

		echo '<section class="cp-section" id="how">';

		if ( ! empty( $s['heading'] ) ) {
			$this->heading( 'h2', (string) $s['heading'], 'cp-section__t' );
		}

		echo '<ol class="cp-steps">';

		foreach ( $items as $index => $item ) {
			echo '<li class="cp-step">';
			printf( '<span class="cp-step__n" aria-hidden="true">%s</span>', esc_html( number_format_i18n( $index + 1 ) ) );
			printf( '<h3 class="cp-step__t">%s</h3>', esc_html( (string) ( $item['title'] ?? '' ) ) );

			if ( ! empty( $item['text'] ) ) {
				printf( '<p class="cp-step__p">%s</p>', esc_html( (string) $item['text'] ) );
			}

			echo '</li>';
		}

		echo '</ol></section>';
	}
}
