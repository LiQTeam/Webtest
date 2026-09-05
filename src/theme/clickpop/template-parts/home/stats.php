<?php
/**
 * آمارها — با گزینهٔ استفاده از عدد واقعی سایت.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_stats = clickpop_content_list( 'stats' );

if ( clickpop_on( 'stats_live' ) ) {
	$cp_live = clickpop_live_stats();

	if ( $cp_live ) {
		array_splice(
			$cp_stats,
			0,
			2,
			[
				[
					'value' => number_format_i18n( (int) $cp_live['services'] ),
					'label' => __( 'سرویس فعال', 'clickpop' ),
				],
				[
					'value' => number_format_i18n( (int) $cp_live['completed'] ),
					'label' => __( 'سفارش تحویل‌شده', 'clickpop' ),
				],
			]
		);
	}
}

if ( ! $cp_stats ) {
	return;
}
?>
<section class="cp-section cp-section--tight">
	<div class="cp-wrap">
		<div class="cp-statsband">
			<?php foreach ( $cp_stats as $cp_stat ) : ?>
				<div class="cp-stat">
					<span class="cp-stat__v"><?php echo esc_html( (string) ( $cp_stat['value'] ?? '' ) ); ?></span>
					<span class="cp-stat__l"><?php echo esc_html( (string) ( $cp_stat['label'] ?? '' ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
