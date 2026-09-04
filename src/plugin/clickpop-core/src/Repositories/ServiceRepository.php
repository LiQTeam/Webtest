<?php
declare( strict_types=1 );

namespace ClickPop\Core\Repositories;

use ClickPop\Core\Database\Installer;

defined( 'ABSPATH' ) || exit;

/**
 * تنها لایه‌ای که روی جداول سرویس و دسته SQL می‌نویسد.
 */
final class ServiceRepository {

	private const CACHE_GROUP = 'clickpop';
	private const TREE_KEY    = 'services_tree';

	public function find( int $id ): ?object {
		global $wpdb;

		$table = Installer::table( 'services' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

		return $row ?: null;
	}

	/** سرویس فعال به همراه اطلاعات دسته — برای اعتبارسنجی سفارش. */
	public function findActiveWithCategory( int $id ): ?object {
		global $wpdb;

		$services   = Installer::table( 'services' );
		$categories = Installer::table( 'categories' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT s.*, c.brand, c.brand_slug, c.name AS category_name
				 FROM {$services} s
				 INNER JOIN {$categories} c ON c.id = s.category_id
				 WHERE s.id = %d AND s.status = 'active'",
				$id
			)
		);

		return $row ?: null;
	}

	/**
	 * درخت برند → دسته → سرویس برای فرم سفارش و صفحهٔ سرویس‌ها.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function tree(): array {
		$cached = wp_cache_get( self::TREE_KEY, self::CACHE_GROUP );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$transient = get_transient( 'clickpop_' . self::TREE_KEY );
		if ( is_array( $transient ) ) {
			wp_cache_set( self::TREE_KEY, $transient, self::CACHE_GROUP, 900 );
			return $transient;
		}

		global $wpdb;

		$services   = Installer::table( 'services' );
		$categories = Installer::table( 'categories' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results(
			"SELECT s.id, s.name, s.min_qty, s.max_qty, s.sale_rate, s.dripfeed, s.refill,
			        s.cancel, s.description, s.template_link,
			        c.id AS category_id, c.name AS category_name, c.brand, c.brand_slug
			 FROM {$services} s
			 INNER JOIN {$categories} c ON c.id = s.category_id
			 WHERE s.status = 'active' AND c.status = 'active'
			 ORDER BY c.brand_slug ASC, c.sort_order ASC, c.name ASC, s.sort_order ASC, s.sale_rate ASC"
		);

		$tree = [];
		foreach ( (array) $rows as $row ) {
			$brand = (string) $row->brand_slug;
			$cat   = (int) $row->category_id;

			if ( ! isset( $tree[ $brand ] ) ) {
				$tree[ $brand ] = [
					'slug'       => $brand,
					'label'      => trim( (string) $row->brand ),
					'categories' => [],
				];
			}
			if ( ! isset( $tree[ $brand ]['categories'][ $cat ] ) ) {
				$tree[ $brand ]['categories'][ $cat ] = [
					'id'       => $cat,
					'label'    => (string) $row->category_name,
					'services' => [],
				];
			}

			$tree[ $brand ]['categories'][ $cat ]['services'][] = [
				'id'            => (int) $row->id,
				'name'          => (string) $row->name,
				'min'           => (int) $row->min_qty,
				'max'           => (int) $row->max_qty,
				'rate'          => (int) $row->sale_rate,
				'dripfeed'      => (bool) $row->dripfeed,
				'refill'        => (bool) $row->refill,
				'cancel'        => (bool) $row->cancel,
				'description'   => (string) $row->description,
				'template_link' => (string) $row->template_link,
			];
		}

		// بازنشانی کلیدها تا خروجی JSON آرایه بماند، نه شیء.
		$tree = array_values(
			array_map(
				static function ( array $brand ): array {
					$brand['categories'] = array_values( $brand['categories'] );
					return $brand;
				},
				$tree
			)
		);

		wp_cache_set( self::TREE_KEY, $tree, self::CACHE_GROUP, 900 );
		set_transient( 'clickpop_' . self::TREE_KEY, $tree, 900 );

		return $tree;
	}

	public static function flushTree(): void {
		wp_cache_delete( self::TREE_KEY, self::CACHE_GROUP );
		delete_transient( 'clickpop_' . self::TREE_KEY );
	}

	/** @return array<string,int> شمارش سرویس‌ها بر اساس وضعیت. */
	public function statusCounts(): array {
		global $wpdb;

		$table = Installer::table( 'services' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( "SELECT status, COUNT(*) AS c FROM {$table} GROUP BY status" );

		$out = [];
		foreach ( (array) $rows as $row ) {
			$out[ (string) $row->status ] = (int) $row->c;
		}

		return $out;
	}
}
