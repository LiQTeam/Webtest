<?php
/**
 * پابرگ سایت.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<?php
$clickpop_footer_done = false;

if ( clickpop_elementor_active() && function_exists( 'elementor_theme_do_location' ) ) {
	$clickpop_footer_done = elementor_theme_do_location( 'footer' );
}

if ( ! $clickpop_footer_done ) :
	?>
	<footer class="cp-footer" role="contentinfo">
		<div class="cp-wrap">
			<div class="cp-footer__grid">
				<div>
					<?php clickpop_logo(); ?>
					<p class="cp-footer__about"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
				</div>

				<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
					<?php dynamic_sidebar( 'footer-1' ); ?>
				<?php endif; ?>

				<?php if ( has_nav_menu( 'footer' ) ) : ?>
					<nav aria-label="<?php esc_attr_e( 'ناوبری فوتر', 'clickpop' ); ?>">
						<h4 class="cp-fwidget__t"><?php esc_html_e( 'دسترسی سریع', 'clickpop' ); ?></h4>
						<?php
						wp_nav_menu(
							[
								'theme_location' => 'footer',
								'container'      => false,
								'menu_class'     => 'cp-footer__menu',
								'depth'          => 1,
								'fallback_cb'    => false,
							]
						);
						?>
					</nav>
				<?php endif; ?>
			</div>

			<div class="cp-footer__bot">
				<span>
					<?php
					printf(
						/* translators: 1: year, 2: site name */
						esc_html__( '© %1$s %2$s — همهٔ حقوق محفوظ است.', 'clickpop' ),
						esc_html( wp_date( 'Y' ) ),
						esc_html( get_bloginfo( 'name' ) )
					);
					?>
				</span>

				<?php if ( has_nav_menu( 'legal' ) ) : ?>
					<?php
					wp_nav_menu(
						[
							'theme_location' => 'legal',
							'container'      => false,
							'menu_class'     => 'cp-footer__legal',
							'depth'          => 1,
							'fallback_cb'    => false,
						]
					);
					?>
				<?php endif; ?>
			</div>
		</div>
	</footer>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
