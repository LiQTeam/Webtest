<?php
/**
 * سربرگ سایت.
 *
 * اگر المنتور پرو هدر سفارشی داشته باشد، همان رندر می‌شود.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<?php clickpop_skip_link(); ?>

<?php
$clickpop_header_done = false;

if ( clickpop_elementor_active() && function_exists( 'elementor_theme_do_location' ) ) {
	$clickpop_header_done = elementor_theme_do_location( 'header' );
}

if ( ! $clickpop_header_done ) :
	?>
	<header class="cp-topbar" role="banner">
		<div class="cp-wrap cp-topbar__in">
			<?php clickpop_logo(); ?>

			<nav class="cp-nav" aria-label="<?php esc_attr_e( 'ناوبری اصلی', 'clickpop' ); ?>">
				<?php clickpop_primary_menu(); ?>
			</nav>

			<div class="cp-topbar__act">
				<?php clickpop_theme_toggle(); ?>

				<?php if ( class_exists( \ClickPop\Core\Api\Facade::class ) ) : ?>
					<?php if ( is_user_logged_in() ) : ?>
						<a class="cp-btn cp-btn--primary cp-btn--sm" href="<?php echo esc_url( \ClickPop\Core\Api\Facade::dashboardUrl() ); ?>">
							<?php esc_html_e( 'داشبورد', 'clickpop' ); ?>
						</a>
					<?php else : ?>
						<a class="cp-btn cp-btn--primary cp-btn--sm" href="<?php echo esc_url( wp_login_url() ); ?>">
							<?php esc_html_e( 'ورود / ثبت‌نام', 'clickpop' ); ?>
						</a>
					<?php endif; ?>
				<?php endif; ?>

				<button
					type="button"
					class="cp-iconbtn cp-burger"
					data-cp-burger
					aria-expanded="false"
					aria-controls="cp-mobile-nav"
					aria-label="<?php esc_attr_e( 'منو', 'clickpop' ); ?>"
				>
					<span aria-hidden="true"></span>
				</button>
			</div>
		</div>

		<div class="cp-mobilenav" id="cp-mobile-nav" hidden>
			<?php clickpop_primary_menu(); ?>
		</div>
	</header>
<?php endif; ?>

<main class="cp-site-main" id="cp-main">
