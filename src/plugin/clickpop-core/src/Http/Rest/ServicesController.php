<?php
declare( strict_types=1 );

namespace ClickPop\Core\Http\Rest;

use ClickPop\Core\Pricing\PriceCalculator;
use ClickPop\Core\Providers\ProviderManager;
use ClickPop\Core\Repositories\ServiceRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

final class ServicesController extends AbstractController {

	public function routes(): void {
		register_rest_route(
			$this->ns(),
			'/services/tree',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'tree' ],
				'permission_callback' => '__return_true', // فهرست عمومی سرویس‌ها؛ بدون دادهٔ کاربر.
			]
		);

		register_rest_route(
			$this->ns(),
			'/services/quote',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'quote' ],
				'permission_callback' => $this->can( 'clickpop_place_order' ),
				'args'                => [
					'service_id' => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
					'quantity'   => [
						'required'          => true,
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	public function tree(): WP_REST_Response {
		$response = new WP_REST_Response( ( new ServiceRepository() )->tree() );
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	public function quote( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$limited = $this->guardRate( 'quote', 120, 60 );
		if ( $limited ) {
			return $limited;
		}

		$service = ( new ServiceRepository() )->findActiveWithCategory( (int) $request['service_id'] );

		if ( ! $service ) {
			return new WP_Error( 'cp_service_unavailable', __( 'این سرویس در دسترس نیست.', 'clickpop-core' ), [ 'status' => 409 ] );
		}

		$quantity = (int) $request['quantity'];

		if ( $quantity < (int) $service->min_qty || $quantity > (int) $service->max_qty ) {
			return new WP_Error(
				'cp_quantity_out_of_range',
				sprintf(
					/* translators: 1: min, 2: max */
					__( 'تعداد باید بین %1$s و %2$s باشد.', 'clickpop-core' ),
					number_format_i18n( (int) $service->min_qty ),
					number_format_i18n( (int) $service->max_qty )
				),
				[ 'status' => 422 ]
			);
		}

		$provider  = ProviderManager::byId( (int) $service->provider_id );
		$rate_unit = $provider ? $provider->rateUnit() : 1000;
		$charge    = PriceCalculator::chargeFor( (int) $service->sale_rate, $quantity, $rate_unit );

		return $this->ok(
			[
				'service_id' => (int) $service->id,
				'quantity'   => $quantity,
				'charge'     => $this->money( $charge ),
			]
		);
	}
}
