<?php
/**
 * مراحل کار — شماره‌گذاری چون محتوا واقعاً یک توالی است.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_steps = clickpop_content_list( 'steps' );

if ( ! $cp_steps ) {
	return;
}
?>
<section class="cp-section cp-section--alt" id="how">
	<div class="cp-wrap">
		<h2 class="cp-section__t"><?php clickpop_the_content_field( 'steps_title' ); ?></h2>

		<ol class="cp-steps">
			<?php foreach ( $cp_steps as $cp_i => $cp_step ) : ?>
				<li class="cp-step">
					<span class="cp-step__n" aria-hidden="true"><?php echo esc_html( number_format_i18n( $cp_i + 1 ) ); ?></span>
					<h3 class="cp-step__t"><?php echo esc_html( (string) ( $cp_step['title'] ?? '' ) ); ?></h3>
					<p class="cp-step__p"><?php echo esc_html( (string) ( $cp_step['text'] ?? '' ) ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
