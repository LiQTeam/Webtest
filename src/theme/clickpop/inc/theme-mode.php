<?php
/**
 * دارک‌مود بدون فلیکر.
 *
 * اسکریپت زیر عمداً inline و مسدودکننده است و قبل از هر CSS در <head> چاپ می‌شود:
 * هر راهکار مبتنی بر DOMContentLoaded یک فریم با تم اشتباه رندر می‌کند.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	static function (): void {
		?>
<script>
(function(){try{
var t=localStorage.getItem('cp-theme');
if(t!=='light'&&t!=='dark'){t=matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light';}
document.documentElement.setAttribute('data-theme',t);
}catch(e){}})();
</script>
		<?php
	},
	1
);

/**
 * دکمهٔ تعویض تم — سه‌حالته: روشن، تیره، پیروی از سیستم.
 */
function clickpop_theme_toggle(): void {
	?>
	<button
		type="button"
		class="cp-iconbtn"
		data-cp-theme-toggle
		aria-label="<?php esc_attr_e( 'تغییر تم روشن و تیره', 'clickpop' ); ?>"
	>
		<svg class="cp-ico-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false">
			<circle cx="12" cy="12" r="4"/>
			<path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
		</svg>
		<svg class="cp-ico-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
			<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>
		</svg>
	</button>
	<?php
}
