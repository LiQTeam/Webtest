<?php
/**
 * قالب پیش‌فرض.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="cp-wrap cp-content">
	<?php if ( have_posts() ) : ?>
		<?php if ( is_archive() || is_home() ) : ?>
			<h1 class="cp-page__t"><?php echo esc_html( is_home() ? (string) get_the_title( (int) get_option( 'page_for_posts' ) ) : wp_strip_all_tags( (string) get_the_archive_title() ) ); ?></h1>
		<?php endif; ?>

		<div class="cp-grid cp-grid--posts">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content/card', 'post' );
			endwhile;
			?>
		</div>

		<?php
		the_posts_pagination(
			[
				'mid_size'  => 1,
				'prev_text' => __( 'قبلی', 'clickpop' ),
				'next_text' => __( 'بعدی', 'clickpop' ),
			]
		);
		?>
	<?php else : ?>
		<p class="cp-empty"><?php esc_html_e( 'محتوایی پیدا نشد.', 'clickpop' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
