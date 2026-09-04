<?php
declare( strict_types=1 );

namespace ClickPop\Core\Orders;

defined( 'ABSPATH' ) || exit;

/**
 * وضعیت‌های داخلی سفارش و نگاشت وضعیت سرویس‌دهنده.
 */
final class OrderStatus {

	public const RESERVED       = 'reserved';
	public const PENDING_VERIFY = 'pending_verify';
	public const PROCESSING     = 'processing';
	public const IN_PROGRESS    = 'in_progress';
	public const COMPLETED      = 'completed';
	public const PARTIAL        = 'partial';
	public const CANCELED       = 'canceled';
	public const REFUNDED       = 'refunded';
	public const FAILED         = 'failed';

	/** @return array<string,string> */
	public static function labels(): array {
		return [
			self::RESERVED       => __( 'در حال ثبت', 'clickpop-core' ),
			self::PENDING_VERIFY => __( 'در حال بررسی', 'clickpop-core' ),
			self::PROCESSING     => __( 'در صف', 'clickpop-core' ),
			self::IN_PROGRESS    => __( 'در حال انجام', 'clickpop-core' ),
			self::COMPLETED      => __( 'تکمیل شده', 'clickpop-core' ),
			self::PARTIAL        => __( 'ناقص — برگشت خورد', 'clickpop-core' ),
			self::CANCELED       => __( 'لغو — بازگشت کامل', 'clickpop-core' ),
			self::REFUNDED       => __( 'بازگشت وجه', 'clickpop-core' ),
			self::FAILED         => __( 'ناموفق', 'clickpop-core' ),
		];
	}

	public static function label( string $status ): string {
		return self::labels()[ $status ] ?? $status;
	}

	/** کلاس CSS نشان وضعیت — رنگ به‌تنهایی برای کوررنگی کافی نیست، آیکن هم در قالب هست. */
	public static function tone( string $status ): string {
		return match ( $status ) {
			self::COMPLETED                              => 'ok',
			self::IN_PROGRESS, self::PROCESSING          => 'run',
			self::RESERVED, self::PENDING_VERIFY         => 'wait',
			self::PARTIAL                                => 'part',
			self::CANCELED, self::REFUNDED, self::FAILED => 'bad',
			default                                      => 'wait',
		};
	}

	/** نگاشت رشتهٔ سرویس‌دهنده به وضعیت داخلی. null یعنی ناشناخته. */
	public static function fromProvider( string $raw ): ?string {
		$key = strtolower( trim( $raw ) );

		return match ( $key ) {
			'pending', 'awaiting', 'queue', 'queued' => self::PROCESSING,
			'in progress', 'inprogress', 'processing', 'active' => self::IN_PROGRESS,
			'completed', 'complete', 'success' => self::COMPLETED,
			'partial' => self::PARTIAL,
			'canceled', 'cancelled' => self::CANCELED,
			'refunded' => self::REFUNDED,
			'fail', 'failed', 'error' => self::FAILED,
			default => null,
		};
	}

	/** وضعیت‌های پایانی — دیگر همگام‌سازی نمی‌شوند. */
	public static function isFinal( string $status ): bool {
		return in_array(
			$status,
			[ self::COMPLETED, self::PARTIAL, self::CANCELED, self::REFUNDED, self::FAILED ],
			true
		);
	}
}
