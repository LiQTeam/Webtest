<?php
/**
 * نظر مشتریان.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_items = clickpop_content_list( 'testimonials' );

if ( ! $cp_items ) {
	return;
}
?>
<section class="cp-section" id="testimonials">
	<div class="cp-wrap">
		<div class="cp-sechead cp-sechead--center">
			<h2 class="cp-section__t"><?php clickpop_the_content_field( 'testimonials_title' ); ?></h2>
		</div>

		<div class="cp-grid cp-grid--quotes">
			<?php foreach ( $cp_items as $cp_item ) : ?>
				<figure class="cp-quote">
					<span class="cp-quote__mark" aria-hidden="true"><?php clickpop_icon( 'quote' ); ?></span>
					<blockquote class="cp-quote__b"><?php echo esc_html( (string) ( $cp_item['text'] ?? '' ) ); ?></blockquote>
					<figcaption class="cp-quote__by">
						<strong><?php echo esc_html( (string) ( $cp_item['name'] ?? '' ) ); ?></strong>
						<?php if ( ! empty( $cp_item['role'] ) ) : ?>
							<span><?php echo esc_html( (string) $cp_item['role'] ); ?></span>
						<?php endif; ?>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
