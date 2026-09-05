<?php
/**
 * پرسش‌های پرتکرار + FAQPage schema.
 *
 * schema فقط برای پرسش‌هایی تولید می‌شود که همین‌جا روی صفحه دیده می‌شوند.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;

$cp_faq = clickpop_content_list( 'faq' );

if ( ! $cp_faq ) {
	return;
}
?>
<section class="cp-section cp-section--alt" id="faq">
	<div class="cp-wrap">
		<h2 class="cp-section__t"><?php clickpop_the_content_field( 'faq_title' ); ?></h2>

		<div class="cp-faq">
			<?php foreach ( $cp_faq as $cp_i => $cp_item ) : ?>
				<?php $cp_panel = 'cp-faq-' . $cp_i; ?>
				<div class="cp-qa">
					<button
						type="button"
						class="cp-qa__q"
						aria-expanded="<?php echo 0 === $cp_i ? 'true' : 'false'; ?>"
						aria-controls="<?php echo esc_attr( $cp_panel ); ?>"
					>
						<?php echo esc_html( (string) ( $cp_item['q'] ?? '' ) ); ?>
						<span class="cp-qa__i" aria-hidden="true"></span>
					</button>
					<div class="cp-qa__a" id="<?php echo esc_attr( $cp_panel ); ?>" <?php echo 0 === $cp_i ? '' : 'hidden'; ?>>
						<?php echo esc_html( (string) ( $cp_item['a'] ?? '' ) ); ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php clickpop_faq_schema( $cp_faq ); ?>
