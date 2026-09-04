<?php
declare( strict_types=1 );

namespace ClickPop\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * تبدیل تاریخ میلادی ↔ شمسی، بدون وابستگی خارجی.
 *
 * تمام زمان‌ها در دیتابیس UTC میلادی‌اند؛ این کلاس فقط برای نمایش است.
 */
final class Jalali {

	/** @return array{0:int,1:int,2:int} سال، ماه، روز شمسی */
	public static function fromGregorian( int $gy, int $gm, int $gd ): array {
		$g_d_m = [ 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 ];
		$gy2   = ( $gm > 2 ) ? $gy + 1 : $gy;
		$days  = 355666 + ( 365 * $gy ) + intdiv( $gy2 + 3, 4 ) - intdiv( $gy2 + 99, 100 )
			+ intdiv( $gy2 + 399, 400 ) + $gd + $g_d_m[ $gm - 1 ];

		$jy    = -1595 + ( 33 * intdiv( $days, 12053 ) );
		$days %= 12053;
		$jy   += 4 * intdiv( $days, 1461 );
		$days %= 1461;

		if ( $days > 365 ) {
			$jy   += intdiv( $days - 1, 365 );
			$days  = ( $days - 1 ) % 365;
		}

		if ( $days < 186 ) {
			$jm = 1 + intdiv( $days, 31 );
			$jd = 1 + ( $days % 31 );
		} else {
			$jm = 7 + intdiv( $days - 186, 30 );
			$jd = 1 + ( ( $days - 186 ) % 30 );
		}

		return [ $jy, $jm, $jd ];
	}

	/**
	 * قالب‌بندی یک زمان UTC به تاریخ شمسی محلی.
	 *
	 * @param string $mysql_utc زمان به شکل 'Y-m-d H:i:s' در UTC.
	 */
	public static function format( string $mysql_utc, bool $with_time = true ): string {
		$ts = strtotime( $mysql_utc . ' UTC' );
		if ( false === $ts ) {
			return '';
		}
		$ts += (int) ( (float) get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS );

		[ $jy, $jm, $jd ] = self::fromGregorian(
			(int) gmdate( 'Y', $ts ),
			(int) gmdate( 'n', $ts ),
			(int) gmdate( 'j', $ts )
		);

		$date = sprintf( '%04d/%02d/%02d', $jy, $jm, $jd );
		if ( $with_time ) {
			$date .= ' ' . gmdate( 'H:i', $ts );
		}

		return $date;
	}
}
