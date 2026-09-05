<?php
/**
 * حالت روشن/تیره بدون فلیکر.
 *
 * حالت پیش‌فرض از پنل «محتوا و ظاهر» می‌آید (پیش‌فرض: روشن).
 * اسکریپت عمداً inline و مسدودکننده است و پیش از هر CSS چاپ می‌شود:
 * هر راهکار مبتنی بر DOMContentLoaded یک فریم با تم اشتباه رندر می‌کند.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	static function (): void {
		$mode = (string) clickpop_content( 'default_mode', 'light' );

		if ( ! in_array( $mode, [ 'light', 'dark', 'system' ], true ) ) {
			$mode = 'light';
		}

		$fallback = 'system' === $mode
			? "matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light'"
			: "'" . $mode . "'";
		?>
<script>
(function(){try{
var t=localStorage.getItem('cp-theme');
if(t!=='light'&&t!=='dark'){t=<?php echo $fallback; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- رشتهٔ ثابت داخلی، نه ورودی کاربر. ?>;}
document.documentElement.setAttribute('data-theme',t);
}catch(e){document.documentElement.setAttribute('data-theme','light');}})();
</script>
		<?php
	},
	1
);

/** دکمهٔ تعویض تم. */
function clickpop_theme_toggle(): void {
	if ( ! clickpop_on( 'show_theme_toggle' ) ) {
		return;
	}
	?>
	<button
		type="button"
		class="cp-iconbtn"
		data-cp-theme-toggle
		aria-label="<?php esc_attr_e( 'تغییر تم روشن و تیره', 'clickpop' ); ?>"
	>
		<svg class="cp-ico-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false">
			<circle cx="12" cy="12" r="4"/>
			<path d="M12 2.5v2M12 19.5v2M4.6 4.6l1.4 1.4M18 18l1.4 1.4M2.5 12h2M19.5 12h2M4.6 19.4l1.4-1.4M18 6l1.4-1.4"/>
		</svg>
		<svg class="cp-ico-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
			<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>
		</svg>
	</button>
	<?php
}
