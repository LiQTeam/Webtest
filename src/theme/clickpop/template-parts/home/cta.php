<?php
/**
 * فراخوان پایانی.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_url = is_user_logged_in() ? clickpop_dashboard_url() : wp_registration_url();

if ( '' === $cp_url ) {
	$cp_url = home_url( '/' );
}
?>
<section class="cp-section">
	<div class="cp-wrap">
		<div class="cp-cta">
			<h2 class="cp-cta__t"><?php clickpop_the_content_field( 'cta_title' ); ?></h2>
			<p class="cp-cta__p"><?php clickpop_the_content_field( 'cta_text' ); ?></p>
			<a class="cp-btn cp-btn--accent cp-btn--lg" href="<?php echo esc_url( $cp_url ); ?>">
				<?php clickpop_the_content_field( 'cta_button' ); ?>
			</a>
		</div>
	</div>
</section>
