<?php
/**
 * توکن‌های پویا از پنل ظاهر.
 *
 * رنگ برند به یک طیف ۹تایی گسترش می‌یابد تا کنتراست متن و پس‌زمینه
 * در هر دو تم حفظ شود — نه اینکه یک رنگ خام همه‌جا تکرار شود.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * روشن/تیره کردن یک رنگ HEX.
 *
 * @param float $amount عدد مثبت = روشن‌تر، منفی = تیره‌تر (بین ‎-1‎ و ‎1‎).
 */
function clickpop_shade( string $hex, float $amount ): string {
	$hex = ltrim( $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) ) {
		return '#' . $hex;
	}

	$out = '#';

	for ( $i = 0; $i < 3; $i++ ) {
		$channel = (int) hexdec( substr( $hex, $i * 2, 2 ) );

		$channel = $amount >= 0
			? (int) round( $channel + ( 255 - $channel ) * $amount )
			: (int) round( $channel * ( 1 + $amount ) );

		$out .= str_pad( dechex( max( 0, min( 255, $channel ) ) ), 2, '0', STR_PAD_LEFT );
	}

	return strtoupper( $out );
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$primary = (string) clickpop_content( 'brand_primary', '#1668FF' );
		$accent  = (string) clickpop_content( 'brand_accent', '#FF7A1A' );
		$radius  = (string) clickpop_content( 'radius', 'md' );
		$width   = (int) clickpop_content( 'container_width', 1180 );

		$radius_map = [
			'sm' => [ '.375rem', '.5rem', '.75rem', '.875rem' ],
			'md' => [ '.5rem', '.875rem', '1.125rem', '1.375rem' ],
			'lg' => [ '.75rem', '1.125rem', '1.5rem', '2rem' ],
		];

		$r = $radius_map[ $radius ] ?? $radius_map['md'];

		$css = sprintf(
			':root{
--cp-blue-50:%1$s;--cp-blue-100:%2$s;--cp-blue-200:%3$s;--cp-blue-300:%4$s;
--cp-blue-400:%5$s;--cp-blue-500:%6$s;--cp-blue-600:%7$s;--cp-blue-700:%8$s;--cp-blue-800:%9$s;
--cp-orange-300:%10$s;--cp-orange-400:%11$s;--cp-orange-500:%12$s;--cp-orange-700:%13$s;--cp-orange-800:%14$s;
--cp-color-link:%8$s;--cp-color-brand:%8$s;--cp-color-brand-soft:%6$s;
--cp-color-focus:%8$s;--cp-color-info:%8$s;--cp-color-info-bg:%1$s;
--cp-color-accent:%13$s;--cp-color-accent-fill:%12$s;
--cp-sh-brand:0 14px 38px %15$s;
--cp-r-sm:%16$s;--cp-r-md:%17$s;--cp-r-lg:%18$s;--cp-r-xl:%19$s;
--cp-container:%20$dpx;
}
:root[data-theme="dark"]{
--cp-color-link:%4$s;--cp-color-brand:%5$s;--cp-color-brand-soft:%5$s;
--cp-color-focus:%4$s;--cp-color-info:%4$s;--cp-color-info-bg:%21$s;
--cp-color-accent:%11$s;--cp-color-accent-fill:%12$s;
}',
			clickpop_shade( $primary, 0.94 ),
			clickpop_shade( $primary, 0.86 ),
			clickpop_shade( $primary, 0.72 ),
			clickpop_shade( $primary, 0.55 ),
			clickpop_shade( $primary, 0.30 ),
			$primary,
			clickpop_shade( $primary, -0.14 ),
			clickpop_shade( $primary, -0.30 ),
			clickpop_shade( $primary, -0.48 ),
			clickpop_shade( $accent, 0.55 ),
			clickpop_shade( $accent, 0.32 ),
			$accent,
			clickpop_shade( $accent, -0.32 ),
			clickpop_shade( $accent, -0.48 ),
			clickpop_rgba( $primary, 0.26 ),
			$r[0],
			$r[1],
			$r[2],
			$r[3],
			max( 960, min( 1600, $width ) ),
			clickpop_shade( $primary, -0.62 )
		);

		$custom = trim( (string) clickpop_content( 'custom_css', '' ) );

		if ( '' !== $custom ) {
			$css .= "\n" . wp_strip_all_tags( $custom );
		}

		wp_add_inline_style( 'clickpop-main', $css );
	},
	30
);

/** تبدیل HEX به rgba برای سایه. */
function clickpop_rgba( string $hex, float $alpha ): string {
	$hex = ltrim( $hex, '#' );

	if ( 6 !== strlen( $hex ) ) {
		return 'rgba(22,104,255,' . $alpha . ')';
	}

	return sprintf(
		'rgba(%d,%d,%d,%s)',
		(int) hexdec( substr( $hex, 0, 2 ) ),
		(int) hexdec( substr( $hex, 2, 2 ) ),
		(int) hexdec( substr( $hex, 4, 2 ) ),
		(string) $alpha
	);
}
