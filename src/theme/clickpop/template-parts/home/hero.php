<?php
/**
 * بنر اصلی — سه چیدمان: دو ستونه با کارت سفارش، وسط‌چین، دو ستونه با تصویر.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_layout  = (string) clickpop_content( 'hero_layout', 'split' );
$cp_eyebrow = (string) clickpop_content( 'hero_eyebrow', '' );
$cp_badges  = clickpop_content_list( 'hero_badges' );
$cp_image   = (int) clickpop_content( 'hero_image', 0 );
$cp_brands  = clickpop_brand_summary();
?>
<section class="cp-hero cp-hero--<?php echo esc_attr( $cp_layout ); ?>">
	<span class="cp-hero__glow" aria-hidden="true"></span>
	<span class="cp-hero__grid" aria-hidden="true"></span>

	<div class="cp-wrap cp-hero__in">
		<div class="cp-hero__copy">
			<?php if ( '' !== $cp_eyebrow ) : ?>
				<span class="cp-eyebrow">
					<span class="cp-eyebrow__dot" aria-hidden="true"></span>
					<?php echo esc_html( $cp_eyebrow ); ?>
				</span>
			<?php endif; ?>

			<?php
			clickpop_highlighted_title(
				(string) clickpop_content( 'hero_title', '' ),
				(string) clickpop_content( 'hero_highlight', '' ),
				'h1',
				'cp-hero__t'
			);
			?>

			<p class="cp-hero__p"><?php clickpop_the_content_field( 'hero_text' ); ?></p>

			<div class="cp-hero__cta">
				<a class="cp-btn cp-btn--primary cp-btn--lg" href="<?php echo esc_url( (string) clickpop_content( 'hero_cta_url', '#services' ) ); ?>">
					<?php clickpop_the_content_field( 'hero_cta_text' ); ?>
					<?php clickpop_icon( 'arrow', 'cp-btn__ico' ); ?>
				</a>
				<a class="cp-btn cp-btn--ghost cp-btn--lg" href="<?php echo esc_url( (string) clickpop_content( 'hero_alt_url', '#how' ) ); ?>">
					<?php clickpop_the_content_field( 'hero_alt_text' ); ?>
				</a>
			</div>

			<?php if ( $cp_badges ) : ?>
				<ul class="cp-hero__badges">
					<?php foreach ( $cp_badges as $cp_badge ) : ?>
						<li>
							<?php clickpop_icon( (string) ( $cp_badge['icon'] ?? 'check' ), 'cp-ico cp-ico--sm' ); ?>
							<?php echo esc_html( (string) ( $cp_badge['text'] ?? '' ) ); ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<?php if ( 'image' === $cp_layout && $cp_image > 0 ) : ?>
			<div class="cp-hero__media">
				<?php
				echo wp_get_attachment_image(
					$cp_image,
					'large',
					false,
					[
						'class'         => 'cp-hero__img',
						'fetchpriority' => 'high',
						'decoding'      => 'async',
					]
				);
				?>
			</div>
		<?php elseif ( 'split' === $cp_layout ) : ?>
			<div class="cp-hero__card">
				<span class="cp-hero__blob" aria-hidden="true"></span>

				<div class="cp-ocard">
					<div class="cp-ocard__h">
						<h2 class="cp-ocard__t"><?php esc_html_e( 'ثبت سفارش سریع', 'clickpop' ); ?></h2>
						<span class="cp-live"><span class="cp-live__dot" aria-hidden="true"></span><?php esc_html_e( 'قیمت لحظه‌ای', 'clickpop' ); ?></span>
					</div>

					<?php if ( $cp_brands ) : ?>
						<ul class="cp-ocard__rows">
							<?php foreach ( array_slice( $cp_brands, 0, 4 ) as $cp_brand ) : ?>
								<li class="cp-ocard__row">
									<span class="cp-ocard__ico"><?php clickpop_brand_icon( (string) $cp_brand['slug'] ); ?></span>
									<span class="cp-ocard__name"><?php echo esc_html( (string) $cp_brand['label'] ); ?></span>
									<span class="cp-ocard__price"><?php echo esc_html( (string) $cp_brand['from_display'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="cp-ocard__empty"><?php esc_html_e( 'به‌محض همگام‌سازی، قیمت‌ها اینجا نمایش داده می‌شوند.', 'clickpop' ); ?></p>
					<?php endif; ?>

					<a class="cp-btn cp-btn--primary cp-btn--wide" href="<?php echo esc_url( clickpop_dashboard_url() ?: '#services' ); ?>">
						<?php esc_html_e( 'ورود به پنل و ثبت سفارش', 'clickpop' ); ?>
					</a>

					<p class="cp-ocard__note">
						<?php clickpop_icon( 'lock', 'cp-ico cp-ico--xs' ); ?>
						<?php esc_html_e( 'قیمت روی سرور محاسبه می‌شود؛ رمز حساب هرگز لازم نیست.', 'clickpop' ); ?>
					</p>
				</div>
			</div>
		<?php endif; ?>
	</div>
</section>

<?php if ( clickpop_on( 'brands_enabled' ) && $cp_brands ) : ?>
	<div class="cp-brandstrip">
		<div class="cp-wrap cp-brandstrip__in">
			<?php foreach ( $cp_brands as $cp_brand ) : ?>
				<span class="cp-brandchip">
					<?php clickpop_brand_icon( (string) $cp_brand['slug'] ); ?>
					<?php echo esc_html( (string) $cp_brand['label'] ); ?>
				</span>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>
