<?php
/**
 * Template Name: فهرست سرویس‌ها و قیمت‌ها
 * Template Post Type: page
 *
 * جدول کامل سرویس‌ها، دسته‌بندی‌شده بر اساس پلتفرم. بدون صفحه‌ساز.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

get_header();

$cp_tree = clickpop_service_tree();
?>
<div class="cp-wrap cp-content">
	<h1 class="cp-page__t"><?php the_title(); ?></h1>

	<?php
	while ( have_posts() ) :
		the_post();

		if ( '' !== trim( (string) get_the_content() ) ) :
			?>
			<div class="cp-prose"><?php the_content(); ?></div>
			<?php
		endif;
	endwhile;
	?>

	<?php if ( ! $cp_tree ) : ?>
		<p class="cp-empty"><?php esc_html_e( 'فهرست سرویس‌ها هنوز همگام نشده است.', 'clickpop' ); ?></p>
	<?php endif; ?>

	<?php foreach ( $cp_tree as $cp_brand ) : ?>
		<section class="cp-catalog" aria-labelledby="cp-cat-<?php echo esc_attr( (string) $cp_brand['slug'] ); ?>">
			<h2 class="cp-catalog__t" id="cp-cat-<?php echo esc_attr( (string) $cp_brand['slug'] ); ?>">
				<?php clickpop_brand_icon( (string) $cp_brand['slug'] ); ?>
				<?php echo esc_html( (string) $cp_brand['label'] ); ?>
			</h2>

			<?php foreach ( $cp_brand['categories'] as $cp_cat ) : ?>
				<h3 class="cp-catalog__c"><?php echo esc_html( (string) $cp_cat['label'] ); ?></h3>

				<div class="cp-tablewrap">
					<table class="cp-ptable">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'سرویس', 'clickpop' ); ?></th>
								<th scope="col"><?php esc_html_e( 'قیمت هر ۱۰۰۰', 'clickpop' ); ?></th>
								<th scope="col"><?php esc_html_e( 'حداقل', 'clickpop' ); ?></th>
								<th scope="col"><?php esc_html_e( 'حداکثر', 'clickpop' ); ?></th>
								<th scope="col"><?php esc_html_e( 'قابلیت‌ها', 'clickpop' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $cp_cat['services'] as $cp_svc ) : ?>
								<tr>
									<td class="cp-ptable__n"><?php echo esc_html( (string) $cp_svc['name'] ); ?></td>
									<td><strong><?php echo esc_html( clickpop_format_rials( (int) $cp_svc['rate'] ) ); ?></strong></td>
									<td><?php echo esc_html( number_format_i18n( (int) $cp_svc['min'] ) ); ?></td>
									<td><?php echo esc_html( number_format_i18n( (int) $cp_svc['max'] ) ); ?></td>
									<td>
										<?php if ( ! empty( $cp_svc['refill'] ) ) : ?>
											<span class="cp-tag cp-tag--on"><?php esc_html_e( 'جبران ریزش', 'clickpop' ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $cp_svc['cancel'] ) ) : ?>
											<span class="cp-tag"><?php esc_html_e( 'قابل لغو', 'clickpop' ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $cp_svc['dripfeed'] ) ) : ?>
											<span class="cp-tag"><?php esc_html_e( 'تدریجی', 'clickpop' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endforeach; ?>
		</section>
	<?php endforeach; ?>

	<?php $cp_dash = clickpop_dashboard_url(); ?>
	<?php if ( '' !== $cp_dash ) : ?>
		<p class="cp-section__more">
			<a class="cp-btn cp-btn--primary cp-btn--lg" href="<?php echo esc_url( $cp_dash ); ?>">
				<?php esc_html_e( 'ثبت سفارش', 'clickpop' ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
<?php
get_footer();
