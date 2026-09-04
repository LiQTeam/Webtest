<?php
declare( strict_types=1 );

namespace ClickPop\Core\Gateways;

defined( 'ABSPATH' ) || exit;

final class GatewayManager {

	/** @return array<string,AbstractGateway> */
	public static function all(): array {
		$gateways = [ new ZarinPalGateway() ];

		/** @var AbstractGateway[] $gateways */
		$gateways = (array) apply_filters( 'clickpop/gateways', $gateways );

		$map = [];
		foreach ( $gateways as $gateway ) {
			if ( $gateway instanceof AbstractGateway ) {
				$map[ $gateway->slug() ] = $gateway;
			}
		}

		return $map;
	}

	public static function get( string $slug ): ?AbstractGateway {
		return self::all()[ $slug ] ?? null;
	}

	public static function enabled(): array {
		$enabled = [];

		foreach ( self::all() as $slug => $gateway ) {
			if ( get_option( 'clickpop_gateway_' . $slug . '_enabled', false ) ) {
				$enabled[ $slug ] = $gateway;
			}
		}

		return $enabled;
	}
}
