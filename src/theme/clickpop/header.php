<?php
/**
 * سربرگ سایت.
 *
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

<?php if ( clickpop_on( 'topbar_enabled' ) && '' !== (string) clickpop_content( 'topbar_text', '' ) ) : ?>
	<div class="cp-notice">
		<div class="cp-wrap cp-notice__in">
			<?php clickpop_icon( 'sparkles', 'cp-ico cp-ico--sm' ); ?>
			<span><?php clickpop_the_content_field( 'topbar_text' ); ?></span>
			<?php if ( '' !== (string) clickpop_content( 'topbar_link_text', '' ) ) : ?>
				<a href="<?php echo esc_url( (string) clickpop_content( 'topbar_link_url', '#' ) ); ?>">
					<?php clickpop_the_content_field( 'topbar_link_text' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
<?php endif; ?>

<header class="cp-topbar<?php echo clickpop_on( 'header_sticky' ) ? ' is-sticky' : ''; ?>" role="banner">
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
							<?php clickpop_the_content_field( 'header_cta_text' ); ?>
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

<main class="cp-site-main" id="cp-main">
