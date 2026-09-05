<?php
/**
 * کارت پلتفرم‌ها با «شروع قیمت از» — داده زنده از افزونه.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_brands = clickpop_brand_summary();
?>
<section class="cp-section" id="services">
	<div class="cp-wrap">
		<div class="cp-sechead">
			<h2 class="cp-section__t"><?php clickpop_the_content_field( 'services_title' ); ?></h2>
			<p class="cp-sechead__p"><?php clickpop_the_content_field( 'services_text' ); ?></p>
		</div>

		<?php if ( ! $cp_brands ) : ?>
			<p class="cp-empty"><?php esc_html_e( 'فهرست سرویس‌ها هنوز همگام نشده است.', 'clickpop' ); ?></p>
		<?php else : ?>
			<div class="cp-grid cp-grid--cards">
				<?php foreach ( $cp_brands as $cp_brand ) : ?>
					<article class="cp-card cp-card--brand">
						<span class="cp-card__ico"><?php clickpop_brand_icon( (string) $cp_brand['slug'] ); ?></span>
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

			<?php $cp_dash = clickpop_dashboard_url(); ?>
			<?php if ( '' !== $cp_dash ) : ?>
				<p class="cp-section__more">
					<a class="cp-btn cp-btn--ghost" href="<?php echo esc_url( $cp_dash ); ?>">
						<?php esc_html_e( 'دیدن همهٔ سرویس‌ها و ثبت سفارش', 'clickpop' ); ?>
					</a>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</section>
