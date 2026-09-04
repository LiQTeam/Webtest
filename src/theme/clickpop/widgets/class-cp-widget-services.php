<?php
/**
 * ویجت شبکهٔ سرویس‌ها.
 *
 * داده از فساد افزونه خوانده می‌شود؛ تم هیچ کوئری‌ای نمی‌زند.
 * اگر افزونه نصب نباشد، ویجت پیام روشن می‌دهد و صفحه نمی‌شکند.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

final class CP_Widget_Services extends CP_Widget_Base {

	public function get_name(): string {
		return 'clickpop_services';
	}

	public function get_title(): string {
		return __( 'شبکهٔ سرویس‌ها', 'clickpop' );
	}

	public function get_icon(): string {
		return 'eicon-price-table';
	}

	protected function register_controls(): void {
		$this->start_controls_section( 'content', [ 'label' => __( 'محتوا', 'clickpop' ) ] );

		$this->add_control(
			'heading',
			[
				'label'   => __( 'عنوان بخش', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => __( 'سرویس‌ها و قیمت‌ها', 'clickpop' ),
			]
		);

		$this->add_control(
			'mode',
			[
				'label'   => __( 'نمایش', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'brands',
				'options' => [
					'brands'   => __( 'کارت پلتفرم‌ها (شروع قیمت از)', 'clickpop' ),
					'services' => __( 'کارت سرویس‌ها', 'clickpop' ),
				],
			]
		);

		$this->add_control(
			'limit',
			[
				'label'   => __( 'حداکثر تعداد کارت سرویس', 'clickpop' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 9,
				'min'     => 3,
				'max'     => 48,
				'condition' => [ 'mode' => 'services' ],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s = $this->get_settings_for_display();

		if ( ! class_exists( \ClickPop\Core\Api\Facade::class ) ) {
			printf(
				'<p class="cp-empty">%s</p>',
				esc_html__( 'افزونهٔ ClickPop Core فعال نیست؛ فهرست سرویس‌ها در دسترس نیست.', 'clickpop' )
			);

			return;
		}

		echo '<section class="cp-section" id="services">';

		if ( ! empty( $s['heading'] ) ) {
			$this->heading( 'h2', (string) $s['heading'], 'cp-section__t' );
		}

		if ( 'brands' === $s['mode'] ) {
			$this->renderBrands();
		} else {
			$this->renderServices( (int) $s['limit'] );
		}

		echo '</section>';
	}

	private function renderBrands(): void {
		$brands = \ClickPop\Core\Api\Facade::brandSummary();

		if ( ! $brands ) {
			printf( '<p class="cp-empty">%s</p>', esc_html__( 'فهرست سرویس‌ها هنوز همگام نشده است.', 'clickpop' ) );

			return;
		}

		echo '<div class="cp-grid cp-grid--cards">';

		foreach ( $brands as $brand ) {
			echo '<article class="cp-card">';
			printf( '<h3 class="cp-card__t">%s</h3>', esc_html( $brand['label'] ) );
			printf(
				'<p class="cp-card__meta">%s</p>',
				esc_html( sprintf( /* translators: %s: service count */ __( '%s سرویس فعال', 'clickpop' ), number_format_i18n( $brand['count'] ) ) )
			);
			echo '<div class="cp-card__foot">';
			printf( '<span class="cp-card__from">%s</span>', esc_html__( 'شروع از', 'clickpop' ) );
			printf( '<strong class="cp-card__price">%s</strong>', esc_html( $brand['from_display'] ) );
			echo '</div></article>';
		}

		echo '</div>';
	}

	private function renderServices( int $limit ): void {
		$tree    = \ClickPop\Core\Api\Facade::serviceTree();
		$printed = 0;

		echo '<div class="cp-grid cp-grid--cards">';

		foreach ( $tree as $brand ) {
			foreach ( $brand['categories'] as $category ) {
				foreach ( $category['services'] as $service ) {
					if ( $printed >= $limit ) {
						break 3;
					}

					++$printed;

					echo '<article class="cp-card">';
					printf( '<span class="cp-card__brand">%s</span>', esc_html( $brand['label'] ) );
					printf( '<h3 class="cp-card__t">%s</h3>', esc_html( $service['name'] ) );
					echo '<div class="cp-card__foot">';
					printf(
						'<span class="cp-card__range">%s</span>',
						esc_html(
							sprintf(
								/* translators: 1: min, 2: max */
								__( '%1$s تا %2$s', 'clickpop' ),
								number_format_i18n( (int) $service['min'] ),
								number_format_i18n( (int) $service['max'] )
							)
						)
					);
					printf(
						'<strong class="cp-card__price">%s</strong>',
						esc_html( \ClickPop\Core\Support\Money::fromRials( (int) $service['rate'] )->format() )
					);
					echo '</div></article>';
				}
			}
		}

		if ( 0 === $printed ) {
			printf( '<p class="cp-empty">%s</p>', esc_html__( 'سرویسی برای نمایش نیست.', 'clickpop' ) );
		}

		echo '</div>';
	}
}
