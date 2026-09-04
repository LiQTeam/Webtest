<?php
declare( strict_types=1 );

namespace ClickPop\Core\Frontend;

defined( 'ABSPATH' ) || exit;

final class Shortcodes {

	public static function register(): void {
		add_shortcode( 'clickpop_dashboard', [ DashboardRenderer::class, 'render' ] );
		add_shortcode( 'clickpop_order_form', [ DashboardRenderer::class, 'renderOrderForm' ] );
		add_shortcode( 'clickpop_services', [ DashboardRenderer::class, 'renderServices' ] );
	}
}
