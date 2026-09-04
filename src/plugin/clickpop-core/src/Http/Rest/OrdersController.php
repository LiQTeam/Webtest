<?php
declare( strict_types=1 );

namespace ClickPop\Core\Http\Rest;

use ClickPop\Core\Orders\OrderService;
use ClickPop\Core\Orders\OrderStatus;
use ClickPop\Core\Repositories\OrderRepository;
use ClickPop\Core\Support\Jalali;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class OrdersController extends AbstractController {

	public function routes(): void {
		register_rest_route(
			$this->ns(),
			'/orders',
			[
				[
					'methods'             => 'GET',
					'callback'            => [ $this, 'index' ],
					'permission_callback' => $this->can( 'clickpop_view_own_orders' ),
					'args'                => [
						'page'   => [
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						],
						'status' => [
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_key',
						],
					],
				],
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'create' ],
					'permission_callback' => $this->can( 'clickpop_place_order' ),
					'args'                => [
						'service_id'      => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'quantity'        => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'link'            => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'esc_url_raw',
						],
						'idempotency_key' => [
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);

		register_rest_route(
			$this->ns(),
			'/orders/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'show' ],
				'permission_callback' => $this->can( 'clickpop_view_own_orders' ),
				'args'                => [
					'id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	public function index( WP_REST_Request $request ): WP_REST_Response {
		$page     = max( 1, (int) $request['page'] );
		$per_page = 20;
		$status   = (string) $request['status'];
		$statuses = ( '' !== $status && array_key_exists( $status, OrderStatus::labels() ) ) ? [ $status ] : [];

		$rows = ( new OrderRepository() )->forUser(
			get_current_user_id(),
			$statuses,
			$per_page,
			( $page - 1 ) * $per_page
		);

		return $this->ok(
			[
				'page'  => $page,
				'items' => array_map( [ $this, 'shape' ], $rows ),
			]
		);
	}

	public function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$order = ( new OrderRepository() )->findOwned( (int) $request['id'], get_current_user_id() );

		if ( ! $order ) {
			return new WP_Error( 'cp_not_found', __( 'سفارش پیدا نشد.', 'clickpop-core' ), [ 'status' => 404 ] );
		}

		return $this->ok( $this->shape( $order ) );
	}

	public function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$limited = $this->guardRate( 'order', 10, 60 );
		if ( $limited ) {
			return $limited;
		}

		$result = ( new OrderService() )->place(
			get_current_user_id(),
			(int) $request['service_id'],
			(string) $request['link'],
			(int) $request['quantity'],
			(string) $request['idempotency_key']
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->ok( $this->shape( $result ), 201 );
	}

	private function shape( object $order ): array {
		$quantity = max( 1, (int) $order->quantity );
		$remains  = null === $order->remains ? null : (int) $order->remains;
		$progress = null === $remains ? null : (int) round( ( ( $quantity - $remains ) / $quantity ) * 100 );

		return [
			'id'           => (int) $order->id,
			'service_id'   => (int) $order->service_id,
			'service_name' => (string) ( $order->service_name ?? '' ),
			'link'         => (string) $order->link,
			'quantity'     => $quantity,
			'charge'       => $this->money( (int) $order->charge ),
			'refunded'     => $this->money( (int) $order->refunded ),
			'status'       => (string) $order->status,
			'status_label' => OrderStatus::label( (string) $order->status ),
			'status_tone'  => OrderStatus::tone( (string) $order->status ),
			'start_count'  => null === $order->start_count ? null : (int) $order->start_count,
			'remains'      => $remains,
			'progress'     => $progress,
			'created_at'   => (string) $order->created_at,
			'created_fa'   => Jalali::format( (string) $order->created_at ),
		];
	}
}
