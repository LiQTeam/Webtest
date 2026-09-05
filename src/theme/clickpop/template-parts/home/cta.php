<?php
/**
 * بنر فراخوان پایانی.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_url = (string) clickpop_content( 'cta_url', '' );

if ( '' === $cp_url ) {
	$cp_url = is_user_logged_in() ? clickpop_dashboard_url() : wp_registration_url();
}

if ( '' === $cp_url ) {
	$cp_url = home_url( '/' );
}
?>
<section class="cp-section">
	<div class="cp-wrap">
		<div class="cp-cta">
			<span class="cp-cta__blob cp-cta__blob--a" aria-hidden="true"></span>
			<span class="cp-cta__blob cp-cta__blob--b" aria-hidden="true"></span>

			<div class="cp-cta__in">
				<h2 class="cp-cta__t"><?php clickpop_the_content_field( 'cta_title' ); ?></h2>
				<?php if ( '' !== (string) clickpop_content( 'cta_text', '' ) ) : ?>
					<p class="cp-cta__p"><?php clickpop_the_content_field( 'cta_text' ); ?></p>
				<?php endif; ?>
				<a class="cp-btn cp-btn--accent cp-btn--lg" href="<?php echo esc_url( $cp_url ); ?>">
					<?php clickpop_the_content_field( 'cta_button' ); ?>
					<?php clickpop_icon( 'arrow', 'cp-btn__ico' ); ?>
				</a>
			</div>
		</div>
	</div>
</section>
