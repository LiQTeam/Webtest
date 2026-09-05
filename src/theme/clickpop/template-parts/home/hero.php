<?php
/**
 * بخش هیرو.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_eyebrow = (string) clickpop_content( 'hero_eyebrow', '' );
$cp_cta_url = (string) clickpop_content( 'hero_cta_url', '#services' );
$cp_alt_url = (string) clickpop_content( 'hero_alt_url', '#how' );
?>
<section class="cp-hero">
	<div class="cp-wrap cp-hero__in">
		<?php if ( '' !== $cp_eyebrow ) : ?>
			<span class="cp-eyebrow"><?php echo esc_html( $cp_eyebrow ); ?></span>
		<?php endif; ?>

		<h1 class="cp-hero__t"><?php clickpop_the_content_field( 'hero_title' ); ?></h1>
		<p class="cp-hero__p"><?php clickpop_the_content_field( 'hero_text' ); ?></p>

		<div class="cp-hero__cta">
			<a class="cp-btn cp-btn--primary cp-btn--lg" href="<?php echo esc_url( $cp_cta_url ); ?>">
				<?php clickpop_the_content_field( 'hero_cta_text' ); ?>
			</a>
			<a class="cp-btn cp-btn--ghost cp-btn--lg" href="<?php echo esc_url( $cp_alt_url ); ?>">
				<?php clickpop_the_content_field( 'hero_alt_text' ); ?>
			</a>
		</div>
	</div>
</section>
