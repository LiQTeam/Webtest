<?php
/**
 * کارت نوشته در فهرست.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;
?>
<article <?php post_class( 'cp-card cp-card--post' ); ?>>
	<?php if ( has_post_thumbnail() ) : ?>
		<a class="cp-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( 'medium_large', [ 'loading' => 'lazy' ] ); ?>
		</a>
	<?php endif; ?>

	<h2 class="cp-card__t">
		<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</h2>

	<p class="cp-card__meta">
		<time datetime="<?php echo esc_attr( (string) get_the_date( 'c' ) ); ?>"><?php echo esc_html( (string) get_the_date() ); ?></time>
	</p>

	<p class="cp-card__excerpt"><?php echo esc_html( wp_html_excerpt( wp_strip_all_tags( (string) get_the_excerpt() ), 140, '…' ) ); ?></p>
</article>
