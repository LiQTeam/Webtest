<?php
declare( strict_types=1 );

namespace ClickPop\Core\Sync;

use ClickPop\Core\Database\Installer;
use ClickPop\Core\Pricing\PriceCalculator;
use ClickPop\Core\Providers\ProviderManager;
use ClickPop\Core\Repositories\ServiceRepository;
use ClickPop\Core\Support\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * همگام‌سازی فهرست سرویس‌ها.
 *
 * - تفاوت‌گیری با hash: فقط ردیف‌های تغییرکرده نوشته می‌شوند.
 * - سرویس حذف‌شده آرشیو می‌شود، هرگز DELETE نمی‌شود (سفارش‌های تاریخی به آن ارجاع دارند).
 * - جهش قیمت بیش از آستانه، سرویس را به صف تأیید دستی می‌برد تا زیر قیمت تمام‌شده فروخته نشود.
 */
final class ServiceSync {

	public const OPTION_LAST_RESULT = 'clickpop_last_service_sync';

	/** @return array{ok:bool,message:string,created:int,updated:int,archived:int,flagged:int} */
	public static function run(): array {
		$provider = ProviderManager::active();

		if ( ! $provider ) {
			return self::result( false, __( 'سرویس‌دهندهٔ فعالی تنظیم نشده است.', 'clickpop-core' ) );
		}

		$response = $provider->services();

		if ( ! $response['ok'] || ! is_array( $response['data'] ) ) {
			return self::result( false, $response['error'] ?: __( 'دریافت فهرست سرویس‌ها ناموفق بود.', 'clickpop-core' ) );
		}

		global $wpdb;

		$provider_id  = $provider->id();
		$multiplier   = $provider->currencyMultiplier();
		$threshold    = (int) get_option( 'clickpop_price_jump_percent', 20 );
		$now          = current_time( 'mysql', true );
		$services_tbl = Installer::table( 'services' );

		$created  = 0;
		$updated  = 0;
		$flagged  = 0;
		$seen     = [];

		foreach ( $response['data'] as $item ) {
			$category_id = self::ensureCategory( $provider_id, (string) $item['brand'], (string) $item['category'], $now );
			if ( $category_id <= 0 ) {
				continue;
			}

			$remote_id = (string) $item['service'];
			$seen[]    = $remote_id;

			$cost_rate = (int) round( (float) $item['rate'] * $multiplier );
			$hash      = sha1(
				implode(
					'|',
					[ $item['name'], $cost_rate, $item['min'], $item['max'], $item['type'], $item['desc'], (int) $item['dripfeed'], (int) $item['refill'], (int) $item['cancel'] ]
				)
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$existing = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id, payload_hash, cost_rate, status FROM {$services_tbl}
					 WHERE provider_id = %d AND remote_service_id = %s",
					$provider_id,
					$remote_id
				)
			);

			if ( $existing && $existing->payload_hash === $hash ) {
				continue;
			}

			$brand_slug = self::brandSlug( (string) $item['brand'] );
			$sale_rate  = PriceCalculator::saleRate( $cost_rate, $brand_slug, $category_id, (int) ( $existing->id ?? 0 ) );

			$status = 'active';
			if ( $existing ) {
				$old = (int) $existing->cost_rate;
				if ( $old > 0 && $cost_rate > $old && ( ( $cost_rate - $old ) * 100 / $old ) > $threshold ) {
					$status = 'review';
					++$flagged;
				} elseif ( 'archived' !== $existing->status ) {
					$status = (string) $existing->status;
				}
			}

			$row = [
				'provider_id'       => $provider_id,
				'remote_service_id' => $remote_id,
				'category_id'       => $category_id,
				'name'              => mb_substr( (string) $item['name'], 0, 255 ),
				'type'              => mb_substr( (string) $item['type'], 0, 32 ),
				'cost_rate'         => $cost_rate,
				'sale_rate'         => $sale_rate,
				'min_qty'           => (int) $item['min'],
				'max_qty'           => (int) $item['max'],
				'dripfeed'          => $item['dripfeed'] ? 1 : 0,
				'refill'            => $item['refill'] ? 1 : 0,
				'cancel'            => $item['cancel'] ? 1 : 0,
				'description'       => wp_kses_post( (string) $item['desc'] ),
				'template_link'     => esc_url_raw( (string) $item['template_link'] ),
				'status'            => $status,
				'raw_json'          => wp_json_encode( $item['raw'] ),
				'payload_hash'      => $hash,
				'updated_at'        => $now,
			];

			if ( $existing ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->update( $services_tbl, $row, [ 'id' => (int) $existing->id ] );
				++$updated;
			} else {
				$row['created_at'] = $now;
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->insert( $services_tbl, $row );
				++$created;
			}
		}

		$archived = self::archiveMissing( $provider_id, $seen, $now );

		ServiceRepository::flushTree();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			Installer::table( 'providers' ),
			[
				'last_sync_at' => $now,
				'updated_at'   => $now,
			],
			[ 'id' => $provider_id ]
		);

		return self::result(
			true,
			__( 'همگام‌سازی انجام شد.', 'clickpop-core' ),
			$created,
			$updated,
			$archived,
			$flagged
		);
	}

	/** @param string[] $seen */
	private static function archiveMissing( int $provider_id, array $seen, string $now ): int {
		global $wpdb;

		if ( ! $seen ) {
			return 0;
		}

		$table        = Installer::table( 'services' );
		$placeholders = implode( ',', array_fill( 0, count( $seen ), '%s' ) );
		$params       = array_merge( [ $now, $provider_id ], $seen );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$affected = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'archived', updated_at = %s
				 WHERE provider_id = %d AND status <> 'archived'
				   AND remote_service_id NOT IN ({$placeholders})",
				$params
			)
		);

		return is_int( $affected ) ? $affected : 0;
	}

	private static function ensureCategory( int $provider_id, string $brand, string $category, string $now ): int {
		global $wpdb;

		$brand    = Validator::normalizeFa( $brand );
		$category = Validator::normalizeFa( $category );

		if ( '' === $category ) {
			$category = __( 'سایر', 'clickpop-core' );
		}
		if ( '' === $brand ) {
			$brand = __( 'عمومی', 'clickpop-core' );
		}

		$brand_slug = self::brandSlug( $brand );
		// sanitize_title روی متن فارسی رشتهٔ خالی می‌دهد؛ به هش کوتاه تکیه می‌کنیم.
		$slug  = $brand_slug . '-' . substr( md5( $category ), 0, 10 );
		$table = Installer::table( 'categories' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$id = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE provider_id = %d AND slug = %s", $provider_id, $slug )
		);

		if ( $id > 0 ) {
			return $id;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$table,
			[
				'provider_id' => $provider_id,
				'brand'       => mb_substr( $brand, 0, 64 ),
				'brand_slug'  => $brand_slug,
				'name'        => mb_substr( $category, 0, 191 ),
				'slug'        => $slug,
				'status'      => 'active',
				'created_at'  => $now,
				'updated_at'  => $now,
			]
		);

		return (int) $wpdb->insert_id;
	}

	/** نگاشت نام فارسی برند به اسلاگ لاتین پایدار. */
	public static function brandSlug( string $brand ): string {
		$brand = Validator::normalizeFa( $brand );

		$map = (array) apply_filters(
			'clickpop/sync/brand_slugs',
			[
				'اینستاگرام' => 'instagram',
				'تلگرام'     => 'telegram',
				'یوتیوب'     => 'youtube',
				'یوتیوپ'     => 'youtube',
				'تیک تاک'    => 'tiktok',
				'تیک‌تاک'    => 'tiktok',
				'توییتر'     => 'twitter',
				'ایکس'       => 'twitter',
				'اسپاتیفای'  => 'spotify',
				'ساندکلاد'   => 'soundcloud',
				'ساند کلاد'  => 'soundcloud',
			]
		);

		foreach ( $map as $needle => $slug ) {
			if ( false !== mb_strpos( $brand, (string) $needle ) ) {
				return (string) $slug;
			}
		}

		return 'brand-' . substr( md5( $brand ), 0, 8 );
	}

	private static function result( bool $ok, string $message, int $created = 0, int $updated = 0, int $archived = 0, int $flagged = 0 ): array {
		$out = compact( 'ok', 'message', 'created', 'updated', 'archived', 'flagged' );
		$out['time'] = current_time( 'mysql', true );

		update_option( self::OPTION_LAST_RESULT, $out, false );

		return $out;
	}
}
