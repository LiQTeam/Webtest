<?php
declare( strict_types=1 );

namespace ClickPop\Core\Http\Rest;

use ClickPop\Core\Gateways\GatewayManager;
use ClickPop\Core\Gateways\PaymentController;
use ClickPop\Core\Support\Jalali;
use ClickPop\Core\Support\Money;
use ClickPop\Core\Wallet\WalletService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class WalletController extends AbstractController {

	public function routes(): void {
		register_rest_route(
			$this->ns(),
			'/wallet',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'show' ],
				'permission_callback' => $this->can( 'clickpop_view_own_orders' ),
			]
		);

		register_rest_route(
			$this->ns(),
			'/wallet/transactions',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'transactions' ],
				'permission_callback' => $this->can( 'clickpop_view_own_orders' ),
				'args'                => [
					'page' => [
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		register_rest_route(
			$this->ns(),
			'/wallet/topup',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'topup' ],
				'permission_callback' => $this->can( 'clickpop_topup_wallet' ),
				'args'                => [
					'amount_tomans' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'gateway'       => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);
	}

	public function show(): WP_REST_Response {
		$wallet = new WalletService();

		return $this->ok(
			[
				'balance'  => $this->money( $wallet->balance( get_current_user_id() ) ),
				'gateways' => array_map(
					static fn( $gateway ): array => [
						'slug'  => $gateway->slug(),
						'label' => $gateway->label(),
					],
					array_values( GatewayManager::enabled() )
				),
			]
		);
	}

	public function transactions( WP_REST_Request $request ): WP_REST_Response {
		$page     = max( 1, (int) $request['page'] );
		$per_page = 20;

		$rows = ( new WalletService() )->repository()->transactions(
			get_current_user_id(),
			$per_page,
			( $page - 1 ) * $per_page
		);

		$items = array_map(
			function ( object $row ): array {
				return [
					'id'         => (int) $row->id,
					'type'       => (string) $row->type,
					'direction'  => (string) $row->direction,
					'amount'     => $this->money( (int) $row->amount ),
					'reason'     => (string) ( $row->reason ?? '' ),
					'gateway'    => (string) ( $row->gateway ?? '' ),
					'ref'        => (string) ( $row->gateway_ref ?? '' ),
					'created_fa' => Jalali::format( (string) $row->created_at ),
				];
			},
			$rows
		);

		return $this->ok(
			[
				'page'  => $page,
				'items' => $items,
			]
		);
	}

	public function topup( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$limited = $this->guardRate( 'topup', 5, 60 );
		if ( $limited ) {
			return $limited;
		}

		$rials  = Money::fromTomans( (int) $request['amount_tomans'] )->rials();
		$result = PaymentController::start( get_current_user_id(), $rials, (string) $request['gateway'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->ok( $result );
	}
}
