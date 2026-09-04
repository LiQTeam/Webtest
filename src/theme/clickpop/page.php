<?php
/**
 * قالب تک‌نوشته و برگه.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

get_header();

$clickpop_done = false;

if ( clickpop_elementor_active() && function_exists( 'elementor_theme_do_location' ) ) {
	$clickpop_done = elementor_theme_do_location( 'single' );
}

if ( ! $clickpop_done ) :
	while ( have_posts() ) :
		the_post();
		?>
		<article <?php post_class( 'cp-wrap cp-content' ); ?>>
			<h1 class="cp-page__t"><?php the_title(); ?></h1>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="cp-page__thumb">
					<?php the_post_thumbnail( 'large', [ 'fetchpriority' => 'high' ] ); ?>
				</figure>
			<?php endif; ?>

			<div class="cp-prose">
				<?php the_content(); ?>
			</div>
		</article>
		<?php
	endwhile;
endif;

get_footer();
