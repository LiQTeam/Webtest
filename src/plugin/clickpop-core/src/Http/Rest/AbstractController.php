<?php
declare( strict_types=1 );

namespace ClickPop\Core\Http\Rest;

use ClickPop\Core\Support\Money;
use ClickPop\Core\Support\RateLimiter;
use WP_Error;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

abstract class AbstractController {

	abstract public function routes(): void;

	protected function ns(): string {
		return RestBootstrap::NAMESPACE;
	}

	/** بررسی قابلیت — هیچ route حساسی permission_callback بازگشت‌دهندهٔ true ثابت ندارد. */
	protected function can( string $capability ): callable {
		return static function () use ( $capability ): bool|WP_Error {
			if ( ! is_user_logged_in() ) {
				return new WP_Error( 'cp_auth_required', __( 'برای این کار باید وارد حساب شوید.', 'clickpop-core' ), [ 'status' => 401 ] );
			}

			if ( ! current_user_can( $capability ) ) {
				return new WP_Error( 'cp_forbidden', __( 'دسترسی لازم را ندارید.', 'clickpop-core' ), [ 'status' => 403 ] );
			}

			return true;
		};
	}

	protected function guardRate( string $bucket, int $limit, int $window ): ?WP_Error {
		if ( RateLimiter::hit( $bucket, $limit, $window ) ) {
			return null;
		}

		return new WP_Error(
			'cp_rate_limited',
			__( 'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.', 'clickpop-core' ),
			[ 'status' => 429 ]
		);
	}

	protected function money( int $rials ): array {
		$money = Money::fromRials( $rials );

		return [
			'rials'   => $rials,
			'tomans'  => $money->tomans(),
			'display' => $money->format(),
		];
	}

	protected function ok( mixed $data, int $status = 200 ): WP_REST_Response {
		$response = new WP_REST_Response( $data, $status );
		$response->header( 'Cache-Control', 'private, no-store' );

		return $response;
	}
}
