<?php
declare( strict_types=1 );

namespace ClickPop\Core\Wallet;

use ClickPop\Core\Repositories\WalletRepository;

defined( 'ABSPATH' ) || exit;

/**
 * تنها نقطهٔ تغییر موجودی. هر تغییر، یک ردیف دائمی در دفتر کل می‌سازد.
 */
final class WalletService {

	public function __construct( private readonly WalletRepository $repo = new WalletRepository() ) {}

	public function balance( int $user_id ): int {
		$this->repo->ensureRow( $user_id );

		return $this->repo->balance( $user_id );
	}

	/**
	 * برداشت اتمیک برای سفارش.
	 *
	 * @return bool false یعنی موجودی کافی نبود.
	 */
	public function debitForOrder( int $user_id, int $amount, int $order_id ): bool {
		$this->repo->ensureRow( $user_id );

		if ( ! $this->repo->debitAtomic( $user_id, $amount ) ) {
			return false;
		}

		$this->repo->ledger(
			[
				'user_id'       => $user_id,
				'type'          => 'order',
				'direction'     => 'debit',
				'amount'        => $amount,
				'balance_after' => $this->repo->balance( $user_id ),
				'status'        => 'succeeded',
				'ref_type'      => 'order',
				'ref_id'        => $order_id,
			]
		);

		return true;
	}

	public function refund( int $user_id, int $amount, int $order_id, string $reason ): bool {
		if ( $amount <= 0 ) {
			return false;
		}

		if ( ! $this->repo->credit( $user_id, $amount ) ) {
			return false;
		}

		$this->repo->ledger(
			[
				'user_id'       => $user_id,
				'type'          => 'refund',
				'direction'     => 'credit',
				'amount'        => $amount,
				'balance_after' => $this->repo->balance( $user_id ),
				'status'        => 'succeeded',
				'ref_type'      => 'order',
				'ref_id'        => $order_id,
				'reason'        => mb_substr( $reason, 0, 255 ),
			]
		);

		do_action( 'clickpop/wallet/refunded', $user_id, $amount, $order_id );

		return true;
	}

	/**
	 * تعدیل دستی توسط ادمین — دلیل اجباری است و در ممیزی ثبت می‌شود.
	 */
	public function adjust( int $user_id, int $signed_amount, string $reason ): bool {
		if ( 0 === $signed_amount || '' === trim( $reason ) ) {
			return false;
		}

		$credit = $signed_amount > 0;
		$amount = abs( $signed_amount );

		$ok = $credit
			? $this->repo->credit( $user_id, $amount )
			: $this->repo->debitAtomic( $user_id, $amount );

		if ( ! $ok ) {
			return false;
		}

		$this->repo->ledger(
			[
				'user_id'       => $user_id,
				'type'          => 'adjust',
				'direction'     => $credit ? 'credit' : 'debit',
				'amount'        => $amount,
				'balance_after' => $this->repo->balance( $user_id ),
				'status'        => 'succeeded',
				'ref_type'      => 'manual',
				'reason'        => mb_substr( $reason, 0, 255 ),
			]
		);

		\ClickPop\Core\Support\Audit::log(
			'wallet.adjust',
			'user',
			$user_id,
			null,
			[
				'amount' => $signed_amount,
				'reason' => $reason,
			],
			$reason
		);

		return true;
	}

	public function repository(): WalletRepository {
		return $this->repo;
	}
}
