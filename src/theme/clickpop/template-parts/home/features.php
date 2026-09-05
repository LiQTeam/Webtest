<?php
/**
 * ویژگی‌ها — کارت با آیکن نرم.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_items = clickpop_content_list( 'features' );

if ( ! $cp_items ) {
	return;
}
?>
<section class="cp-section cp-section--soft" id="features">
	<div class="cp-wrap">
		<div class="cp-sechead cp-sechead--center">
			<h2 class="cp-section__t"><?php clickpop_the_content_field( 'features_title' ); ?></h2>
			<?php if ( '' !== (string) clickpop_content( 'features_text', '' ) ) : ?>
				<p class="cp-sechead__p"><?php clickpop_the_content_field( 'features_text' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="cp-grid cp-grid--features">
			<?php foreach ( $cp_items as $cp_item ) : ?>
				<article class="cp-feature">
					<span class="cp-feature__ico">
						<?php clickpop_icon( (string) ( $cp_item['icon'] ?? 'check' ) ); ?>
					</span>
					<h3 class="cp-feature__t"><?php echo esc_html( (string) ( $cp_item['title'] ?? '' ) ); ?></h3>
					<p class="cp-feature__p"><?php echo esc_html( (string) ( $cp_item['text'] ?? '' ) ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
