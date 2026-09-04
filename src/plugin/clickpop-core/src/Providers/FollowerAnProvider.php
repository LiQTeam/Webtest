<?php
declare( strict_types=1 );

namespace ClickPop\Core\Providers;

defined( 'ABSPATH' ) || exit;

/**
 * درایور SMM Panel API v2 (سازگار با followeran.ir).
 *
 * نکته‌های استخراج‌شده از مستندات سرویس‌دهنده:
 *  - فیلد brand فاصلهٔ انتهایی دارد و باید trim شود.
 *  - created_at تاریخ شمسی است و هرگز به توابع تاریخ میلادی داده نمی‌شود.
 *  - نام پارامتر حالت گروهی status در مستندات متناقض است؛ هر دو حالت پشتیبانی می‌شود.
 */
final class FollowerAnProvider extends AbstractProvider {

	public function services(): array {
		$result = $this->call( 'services' );

		if ( ! $result['ok'] || ! is_array( $result['data'] ) ) {
			return $result;
		}

		$clean = [];
		foreach ( $result['data'] as $item ) {
			if ( ! is_array( $item ) || empty( $item['service'] ) ) {
				continue;
			}
			$clean[] = [
				'service'       => (string) $item['service'],
				'name'          => trim( (string) ( $item['name'] ?? '' ) ),
				'type'          => (string) ( $item['type'] ?? 'default' ),
				'rate'          => (float) ( $item['rate'] ?? 0 ),
				'min'           => max( 1, (int) ( $item['min'] ?? 1 ) ),
				'max'           => max( 1, (int) ( $item['max'] ?? 1 ) ),
				'desc'          => (string) ( $item['desc'] ?? '' ),
				'template_link' => (string) ( $item['template_link'] ?? '' ),
				'dripfeed'      => ! empty( $item['dripfeed'] ),
				'refill'        => ! empty( $item['refill'] ),
				'cancel'        => ! empty( $item['cancel'] ),
				'category'      => trim( (string) ( $item['category'] ?? '' ) ),
				'brand'         => trim( (string) ( $item['brand'] ?? '' ) ),
				'raw'           => $item,
			];
		}

		$result['data'] = $clean;

		return $result;
	}

	public function addOrder( string $remote_service_id, string $link, int $quantity ): array {
		$body = [
			'service'  => $remote_service_id,
			'link'     => $link,
			'quantity' => (string) $quantity,
			'is_test'  => ( defined( 'CLICKPOP_PROVIDER_TEST_MODE' ) && constant( 'CLICKPOP_PROVIDER_TEST_MODE' ) ) ? '1' : '0',
		];

		// تلاش دوباره روی ثبت سفارش ممنوع است — ممکن است سفارش اول ثبت شده باشد.
		$result = $this->call( 'add', $body, false );

		if ( ! $result['ok'] ) {
			return $result;
		}

		$data = is_array( $result['data'] ) ? $result['data'] : [];

		if ( ( $data['status'] ?? '' ) !== 'success' || empty( $data['order'] ) ) {
			return [
				'ok'    => false,
				'data'  => $data,
				'code'  => $result['code'],
				'error' => (string) ( $data['error'] ?? __( 'سرویس‌دهنده سفارش را نپذیرفت.', 'clickpop-core' ) ),
			];
		}

		$result['data'] = [ 'order' => (string) $data['order'] ];

		return $result;
	}

	public function status( array $remote_ids ): array {
		$remote_ids = array_values( array_filter( array_map( 'strval', $remote_ids ) ) );

		if ( ! $remote_ids ) {
			return [
				'ok'    => true,
				'data'  => [],
				'code'  => 200,
				'error' => '',
			];
		}

		$single = 1 === count( $remote_ids );
		$result = $this->call( 'status', $single ? [ 'order' => $remote_ids[0] ] : [ 'orders' => implode( ',', $remote_ids ) ] );

		if ( ! $result['ok'] || ! is_array( $result['data'] ) ) {
			return $result;
		}

		$result['data'] = $this->normalizeStatus( $result['data'], $remote_ids );

		return $result;
	}

	/**
	 * یکسان‌سازی هر دو شکل پاسخ (تکی و گروهی) به نگاشت remote_id => وضعیت.
	 *
	 * @param array<mixed> $data
	 * @param string[]     $remote_ids
	 * @return array<string,array<string,mixed>>
	 */
	private function normalizeStatus( array $data, array $remote_ids ): array {
		$out = [];

		if ( isset( $data['status'] ) && ! isset( $data[0] ) ) {
			$id         = (string) ( $data['order'] ?? $remote_ids[0] );
			$out[ $id ] = $this->mapRow( $data );

			return $out;
		}

		foreach ( $data as $key => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$id         = (string) ( $row['order'] ?? $key );
			$out[ $id ] = $this->mapRow( $row );
		}

		return $out;
	}

	/** @param array<string,mixed> $row */
	private function mapRow( array $row ): array {
		return [
			'status'      => (string) ( $row['status'] ?? '' ),
			'charge'      => (float) ( $row['charge'] ?? 0 ),
			'start_count' => isset( $row['start_count'] ) && null !== $row['start_count'] ? (int) $row['start_count'] : null,
			'remains'     => isset( $row['remains'] ) ? (int) $row['remains'] : null,
			// تاریخ شمسی سرویس‌دهنده؛ فقط برای نمایش، هرگز برای محاسبه.
			'created_at'  => (string) ( $row['created_at'] ?? '' ),
		];
	}

	public function balance(): array {
		$result = $this->call( 'balance' );

		if ( ! $result['ok'] || ! is_array( $result['data'] ) ) {
			return $result;
		}

		$result['data'] = [
			'balance'  => (float) ( $result['data']['balance'] ?? 0 ),
			'currency' => (string) ( $result['data']['currency'] ?? 'IRT' ),
		];

		return $result;
	}
}
