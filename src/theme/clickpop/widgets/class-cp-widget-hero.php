<?php
/**
 * ویجت Hero.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

final class CP_Widget_Hero extends CP_Widget_Base {

	public function get_name(): string {
		return 'clickpop_hero';
	}

	public function get_title(): string {
		return __( 'هیرو کلیک‌پاپ', 'clickpop' );
	}

	public function get_icon(): string {
		return 'eicon-banner';
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'content',
			[ 'label' => __( 'محتوا', 'clickpop' ) ]
		);

		$this->add_control(
			'eyebrow',
			[
				'label'   => __( 'برچسب بالای عنوان', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'اتصال مستقیم API', 'clickpop' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => __( 'عنوان', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => __( 'رشد شبکهٔ اجتماعی‌ات را به ثانیه بسپار', 'clickpop' ),
			]
		);

		$this->add_control(
			'title_tag',
			[
				'label'   => __( 'تگ عنوان', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'h1',
				'options' => [
					'h1' => 'H1',
					'h2' => 'H2',
					'h3' => 'H3',
				],
			]
		);

		$this->add_control(
			'text',
			[
				'label'   => __( 'توضیح', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'rows'    => 4,
				'default' => __( 'فالوور، لایک، ویو، ممبر و کامنت برای اینستاگرام، تلگرام، یوتیوب و تیک‌تاک — با پیگیری لحظه‌ای سفارش.', 'clickpop' ),
			]
		);

		$this->add_control(
			'cta_text',
			[
				'label'   => __( 'متن دکمهٔ اصلی', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'شروع سفارش', 'clickpop' ),
			]
		);

		$this->add_control(
			'cta_link',
			[
				'label'   => __( 'لینک دکمهٔ اصلی', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => [ 'url' => '#services' ],
			]
		);

		$this->add_control(
			'cta2_text',
			[
				'label'   => __( 'متن دکمهٔ دوم', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'چطور کار می‌کند', 'clickpop' ),
			]
		);

		$this->add_control(
			'cta2_link',
			[
				'label'   => __( 'لینک دکمهٔ دوم', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => [ 'url' => '#how' ],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		echo '<section class="cp-hero"><div class="cp-hero__in">';

		if ( ! empty( $s['eyebrow'] ) ) {
			printf( '<span class="cp-eyebrow">%s</span>', esc_html( (string) $s['eyebrow'] ) );
		}

		$this->heading( (string) $s['title_tag'], (string) $s['title'], 'cp-hero__t' );

		if ( ! empty( $s['text'] ) ) {
			printf( '<p class="cp-hero__p">%s</p>', esc_html( (string) $s['text'] ) );
		}

		echo '<div class="cp-hero__cta">';

		foreach ( [ [ 'cta_text', 'cta_link', 'cp-btn--primary' ], [ 'cta2_text', 'cta2_link', 'cp-btn--ghost' ] ] as [ $text_key, $link_key, $class ] ) {
			if ( empty( $s[ $text_key ] ) ) {
				continue;
			}

			$link   = is_array( $s[ $link_key ] ) ? $s[ $link_key ] : [];
			$target = ! empty( $link['is_external'] ) ? ' target="_blank" rel="noopener"' : '';

			printf(
				'<a class="cp-btn %1$s cp-btn--lg" href="%2$s"%3$s>%4$s</a>',
				esc_attr( $class ),
				esc_url( (string) ( $link['url'] ?? '#' ) ),
				$target, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- رشتهٔ ثابت داخلی.
				esc_html( (string) $s[ $text_key ] )
			);
		}

		echo '</div></div></section>';
	}
}
