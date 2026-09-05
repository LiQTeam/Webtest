<?php
/**
 * نوار پلتفرم‌ها — فقط برندهایی که واقعاً سرویس فعال دارند.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_brands = clickpop_brand_summary();

if ( ! $cp_brands ) {
	return;
}
?>
<div class="cp-brandstrip">
	<div class="cp-wrap cp-brandstrip__in">
		<?php foreach ( $cp_brands as $cp_brand ) : ?>
			<span class="cp-brandchip">
				<?php clickpop_brand_icon( (string) $cp_brand['slug'] ); ?>
				<?php echo esc_html( (string) $cp_brand['label'] ); ?>
			</span>
		<?php endforeach; ?>
	</div>
</div>
