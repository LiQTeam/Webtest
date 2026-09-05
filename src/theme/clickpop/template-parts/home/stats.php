<?php
/**
 * آمارها.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_stats = clickpop_content_list( 'stats' );

if ( ! $cp_stats ) {
	return;
}
?>
<section class="cp-section">
	<div class="cp-wrap">
		<div class="cp-stats">
			<?php foreach ( $cp_stats as $cp_stat ) : ?>
				<div class="cp-stat">
					<span class="cp-stat__v"><?php echo esc_html( (string) ( $cp_stat['value'] ?? '' ) ); ?></span>
					<span class="cp-stat__l"><?php echo esc_html( (string) ( $cp_stat['label'] ?? '' ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
