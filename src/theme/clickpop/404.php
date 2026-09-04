<?php
/**
 * صفحهٔ ۴۰۴.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="cp-wrap cp-content cp-404">
	<h1 class="cp-page__t"><?php esc_html_e( 'این صفحه پیدا نشد', 'clickpop' ); ?></h1>
	<p class="cp-muted"><?php esc_html_e( 'شاید آدرس تغییر کرده باشد. از این‌جا ادامه بدهید:', 'clickpop' ); ?></p>
	<p>
		<a class="cp-btn cp-btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php esc_html_e( 'بازگشت به خانه', 'clickpop' ); ?>
		</a>
	</p>
	<?php get_search_form(); ?>
</div>
<?php
get_footer();
