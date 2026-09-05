<?php
/**
 * بخش سرویس‌ها — کارت پلتفرم یا کارت تک‌سرویس.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_mode  = (string) clickpop_content( 'services_mode', 'brands' );
$cp_limit = (int) clickpop_content( 'services_limit', 8 );
$cp_dash  = clickpop_dashboard_url();
?>
<section class="cp-section" id="services">
	<div class="cp-wrap">
		<div class="cp-sechead">
			<h2 class="cp-section__t"><?php clickpop_the_content_field( 'services_title' ); ?></h2>
			<?php if ( '' !== (string) clickpop_content( 'services_text', '' ) ) : ?>
				<p class="cp-sechead__p"><?php clickpop_the_content_field( 'services_text' ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( 'services' === $cp_mode ) : ?>
			<?php
			$cp_tree    = clickpop_service_tree();
			$cp_printed = 0;
			?>
			<?php if ( ! $cp_tree ) : ?>
				<p class="cp-empty"><?php esc_html_e( 'فهرست سرویس‌ها هنوز همگام نشده است.', 'clickpop' ); ?></p>
			<?php else : ?>
				<div class="cp-grid cp-grid--cards">
					<?php foreach ( $cp_tree as $cp_brand ) : ?>
						<?php foreach ( $cp_brand['categories'] as $cp_cat ) : ?>
							<?php foreach ( $cp_cat['services'] as $cp_svc ) : ?>
								<?php if ( $cp_printed >= $cp_limit ) { break 3; } ?>
								<?php ++$cp_printed; ?>
								<article class="cp-card cp-card--svc">
									<span class="cp-card__brand">
										<?php clickpop_brand_icon( (string) $cp_brand['slug'] ); ?>
										<?php echo esc_html( (string) $cp_brand['label'] ); ?>
									</span>
									<h3 class="cp-card__t"><?php echo esc_html( (string) $cp_svc['name'] ); ?></h3>
									<div class="cp-card__tags">
										<?php if ( ! empty( $cp_svc['refill'] ) ) : ?>
											<span class="cp-tag cp-tag--on"><?php esc_html_e( 'جبران ریزش', 'clickpop' ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $cp_svc['cancel'] ) ) : ?>
											<span class="cp-tag"><?php esc_html_e( 'قابل لغو', 'clickpop' ); ?></span>
										<?php endif; ?>
									</div>
									<div class="cp-card__foot">
										<span class="cp-card__range">
											<?php
											printf(
												/* translators: 1: min, 2: max */
												esc_html__( '%1$s تا %2$s', 'clickpop' ),
												esc_html( number_format_i18n( (int) $cp_svc['min'] ) ),
												esc_html( number_format_i18n( (int) $cp_svc['max'] ) )
											);
											?>
										</span>
										<strong class="cp-card__price"><?php echo esc_html( clickpop_format_rials( (int) $cp_svc['rate'] ) ); ?></strong>
									</div>
								</article>
							<?php endforeach; ?>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		<?php else : ?>
			<?php $cp_brands = clickpop_brand_summary(); ?>
			<?php if ( ! $cp_brands ) : ?>
				<p class="cp-empty"><?php esc_html_e( 'فهرست سرویس‌ها هنوز همگام نشده است.', 'clickpop' ); ?></p>
			<?php else : ?>
				<div class="cp-grid cp-grid--cards">
					<?php foreach ( $cp_brands as $cp_brand ) : ?>
						<article class="cp-card cp-card--brand">
							<span class="cp-card__ico cp-card__ico--<?php echo esc_attr( (string) $cp_brand['slug'] ); ?>">
								<?php clickpop_brand_icon( (string) $cp_brand['slug'] ); ?>
							</span>
							<h3 class="cp-card__t"><?php echo esc_html( (string) $cp_brand['label'] ); ?></h3>
							<p class="cp-card__meta">
								<?php
								printf(
									/* translators: %s: service count */
									esc_html__( '%s سرویس فعال', 'clickpop' ),
									esc_html( number_format_i18n( (int) $cp_brand['count'] ) )
								);
								?>
							</p>
							<div class="cp-card__foot">
								<span class="cp-card__from"><?php esc_html_e( 'شروع از', 'clickpop' ); ?></span>
								<strong class="cp-card__price"><?php echo esc_html( (string) $cp_brand['from_display'] ); ?></strong>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( '' !== $cp_dash ) : ?>
			<p class="cp-section__more">
				<a class="cp-btn cp-btn--ghost" href="<?php echo esc_url( $cp_dash ); ?>">
					<?php clickpop_the_content_field( 'services_btn' ); ?>
					<?php clickpop_icon( 'arrow', 'cp-btn__ico' ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</section>
