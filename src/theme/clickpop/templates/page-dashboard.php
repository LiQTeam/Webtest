<?php
/**
 * Template Name: داشبورد کلیک‌پاپ
 * Template Post Type: page
 *
 * برگهٔ تمام‌عرض بدون سایدبار، مخصوص شورتکد [clickpop_dashboard].
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="cp-wrap cp-content cp-content--wide">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</div>
<?php
get_footer();
