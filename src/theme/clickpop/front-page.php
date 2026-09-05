<?php
/**
 * صفحهٔ اصلی.
 *
 * بخش‌ها، ترتیبشان و روشن/خاموش بودنشان از پنل «محتوا و ظاهر» می‌آید.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

get_header();

foreach ( clickpop_home_sections() as $clickpop_section ) {
	get_template_part( 'template-parts/home/' . $clickpop_section );

	// محتوای خود برگهٔ صفحهٔ اصلی، بلافاصله بعد از بخش سرویس‌ها.
	if ( 'services' === $clickpop_section && have_posts() ) {
		while ( have_posts() ) {
			the_post();

			if ( '' !== trim( (string) get_the_content() ) ) {
				echo '<section class="cp-section"><div class="cp-wrap cp-prose">';
				the_content();
				echo '</div></section>';
			}
		}
	}
}

get_footer();
