<?php
declare( strict_types=1 );

namespace ClickPop\Core\Support;

defined( 'ABSPATH' ) || exit;

/**
 * مبلغ به‌صورت عدد صحیح ریال.
 *
 * هیچ float در مسیر مالی وجود ندارد. نمایش به تومان فقط در لایهٔ خروجی انجام می‌شود.
 */
final class Money {

	public const RIAL_PER_TOMAN = 10;

	private function __construct( private readonly int $rials ) {}

	public static function fromRials( int|float|string $value ): self {
		return new self( (int) round( (float) $value ) );
	}

	public static function fromTomans( int|float|string $value ): self {
		return new self( (int) round( (float) $value * self::RIAL_PER_TOMAN ) );
	}

	public static function zero(): self {
		return new self( 0 );
	}

	public function rials(): int {
		return $this->rials;
	}

	public function tomans(): int {
		return intdiv( $this->rials, self::RIAL_PER_TOMAN );
	}

	public function add( self $other ): self {
		return new self( $this->rials + $other->rials );
	}

	public function sub( self $other ): self {
		return new self( $this->rials - $other->rials );
	}

	/** ضرب در کسر با گرد کردن به بالا — برای «قیمت × تعداد ÷ ۱۰۰۰». */
	public function mulDiv( int $multiplier, int $divisor ): self {
		if ( $divisor <= 0 ) {
			return new self( 0 );
		}
		return new self( (int) ceil( ( $this->rials * $multiplier ) / $divisor ) );
	}

	public function isNegative(): bool {
		return $this->rials < 0;
	}

	public function greaterThan( self $other ): bool {
		return $this->rials > $other->rials;
	}

	/** رشتهٔ نمایشی تومان با جداکنندهٔ هزارگان و ارقام فارسی. */
	public function format( bool $with_unit = true ): string {
		$out = number_format_i18n( $this->tomans() );
		return $with_unit ? $out . ' ' . __( 'تومان', 'clickpop-core' ) : $out;
	}
}
