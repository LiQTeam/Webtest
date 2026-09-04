<?php
declare( strict_types=1 );

namespace ClickPop\Core\Pricing;

use ClickPop\Core\Database\Installer;

defined( 'ABSPATH' ) || exit;

/**
 * موتور قیمت‌گذاری — تابع خالص روی قواعد ذخیره‌شده.
 *
 * ترتیب اولویت قاعده: service ← category ← brand ← global
 * تمام مبالغ ورودی و خروجی ریال صحیح‌اند.
 */
final class PriceCalculator {

	/** @var array<string,object>|null */
	private static ?array $rules = null;

	/**
	 * قیمت فروش به ازای rate_unit را از قیمت تمام‌شده حساب می‌کند.
	 *
	 * @param int    $cost_rate  قیمت تمام‌شده (ریال به ازای rate_unit).
	 * @param string $brand_slug اسلاگ برند.
	 * @param int    $category_id شناسهٔ دسته.
	 * @param int    $service_id شناسهٔ سرویس (۰ اگر هنوز ذخیره نشده).
	 */
	public static function saleRate( int $cost_rate, string $brand_slug, int $category_id, int $service_id = 0 ): int {
		if ( $cost_rate <= 0 ) {
			return 0;
		}

		$rule = self::resolveRule( $brand_slug, $category_id, $service_id );

		if ( null === $rule ) {
			return $cost_rate;
		}

		$profit = 'fixed' === $rule->margin_type
			? (int) $rule->margin_value
			: (int) ceil( $cost_rate * ( (int) $rule->margin_value / 10000 ) );

		$profit = max( $profit, (int) $rule->min_profit );
		$sale   = $cost_rate + $profit;

		$step = max( 1, (int) $rule->round_step );

		return (int) ( ceil( $sale / $step ) * $step );
	}

	/** مبلغ نهایی سفارش: قیمت واحد × تعداد ÷ rate_unit، گرد شده به بالا. */
	public static function chargeFor( int $sale_rate, int $quantity, int $rate_unit ): int {
		$rate_unit = max( 1, $rate_unit );

		return (int) ceil( ( $sale_rate * $quantity ) / $rate_unit );
	}

	private static function resolveRule( string $brand_slug, int $category_id, int $service_id ): ?object {
		$rules = self::rules();

		$candidates = [
			'service:' . $service_id,
			'category:' . $category_id,
			'brand:' . $brand_slug,
			'global:',
		];

		foreach ( $candidates as $key ) {
			if ( isset( $rules[ $key ] ) ) {
				return $rules[ $key ];
			}
		}

		return null;
	}

	/** @return array<string,object> */
	private static function rules(): array {
		if ( null !== self::$rules ) {
			return self::$rules;
		}

		global $wpdb;

		$table = Installer::table( 'pricing_rules' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE active = 1 ORDER BY priority DESC, id ASC" );

		$map = [];
		foreach ( (array) $rows as $row ) {
			$key = $row->scope . ':' . (string) $row->scope_ref;
			if ( ! isset( $map[ $key ] ) ) {
				$map[ $key ] = $row;
			}
		}

		self::$rules = $map;

		return $map;
	}

	public static function flush(): void {
		self::$rules = null;
	}
}
