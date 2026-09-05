<?php
/**
 * صفحهٔ اصلی.
 *
 * بدون صفحه‌ساز: بخش‌ها قالب PHP هستند و متن‌هایشان از
 * «کلیک‌پاپ ← محتوای سایت» می‌آید. قیمت‌ها زنده از افزونه خوانده می‌شوند.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/home/hero' );
get_template_part( 'template-parts/home/brands' );
get_template_part( 'template-parts/home/services' );

// اگر برگهٔ تعیین‌شده به‌عنوان صفحهٔ اصلی محتوایی دارد، همان‌جا رندر می‌شود.
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();

		$clickpop_body = trim( (string) get_the_content() );

		if ( '' !== $clickpop_body ) :
			?>
			<section class="cp-section">
				<div class="cp-wrap cp-prose">
					<?php the_content(); ?>
				</div>
			</section>
			<?php
		endif;
	endwhile;
endif;

get_template_part( 'template-parts/home/steps' );
get_template_part( 'template-parts/home/stats' );
get_template_part( 'template-parts/home/faq' );
get_template_part( 'template-parts/home/cta' );

get_footer();
