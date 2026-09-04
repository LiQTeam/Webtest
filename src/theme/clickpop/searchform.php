<?php
/**
 * فرم جست‌وجو با برچسب واقعی (نه placeholder به‌جای label).
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$clickpop_search_id = 'cp-search-' . wp_unique_id();
?>
<form role="search" method="get" class="cp-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="cp-search__label" for="<?php echo esc_attr( $clickpop_search_id ); ?>">
		<?php esc_html_e( 'جست‌وجو در سایت', 'clickpop' ); ?>
	</label>
	<div class="cp-search__row">
		<input
			type="search"
			id="<?php echo esc_attr( $clickpop_search_id ); ?>"
			class="cp-search__input"
			name="s"
			value="<?php echo esc_attr( get_search_query() ); ?>"
			required
		>
		<button type="submit" class="cp-btn cp-btn--primary"><?php esc_html_e( 'جست‌وجو', 'clickpop' ); ?></button>
	</div>
</form>
