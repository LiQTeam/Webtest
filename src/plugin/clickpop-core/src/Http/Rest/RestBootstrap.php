<?php
declare( strict_types=1 );

namespace ClickPop\Core\Http\Rest;

defined( 'ABSPATH' ) || exit;

final class RestBootstrap {

	public const NAMESPACE = 'clickpop/v1';

	public static function register(): void {
		add_action(
			'rest_api_init',
			static function (): void {
				( new ServicesController() )->routes();
				( new OrdersController() )->routes();
				( new WalletController() )->routes();
				( new TicketsController() )->routes();
			}
		);
	}
}
