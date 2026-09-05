<?php
/**
 * پابرگ سایت.
 *
 * @package ClickPop
 */

defined( 'ABSPATH' ) || exit;
?>
</main>

<footer class="cp-footer" role="contentinfo">
		<div class="cp-wrap">
			<div class="cp-footer__grid">
				<div>
					<?php clickpop_logo(); ?>
					<p class="cp-footer__about">
						<?php
						$clickpop_about = (string) clickpop_content( 'footer_about', '' );
						echo esc_html( '' !== $clickpop_about ? $clickpop_about : (string) get_bloginfo( 'description' ) );
						?>
					</p>
					<?php clickpop_social_links(); ?>
				</div>

				<?php
				$clickpop_contact = array_filter(
					[
						__( 'تلفن', 'clickpop' )   => (string) clickpop_content( 'contact_phone', '' ),
						__( 'ایمیل', 'clickpop' )  => (string) clickpop_content( 'contact_email', '' ),
						__( 'نشانی', 'clickpop' )  => (string) clickpop_content( 'contact_address', '' ),
						__( 'ساعت کاری', 'clickpop' ) => (string) clickpop_content( 'contact_hours', '' ),
					]
				);
				?>
				<?php if ( $clickpop_contact ) : ?>
					<div>
						<h4 class="cp-fwidget__t"><?php esc_html_e( 'تماس با ما', 'clickpop' ); ?></h4>
						<ul class="cp-footer__contact">
							<?php foreach ( $clickpop_contact as $clickpop_label => $clickpop_value ) : ?>
								<li>
									<span class="cp-footer__ck"><?php echo esc_html( $clickpop_label ); ?>:</span>
									<span><?php echo esc_html( $clickpop_value ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

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

			<?php clickpop_trust_badges(); ?>

			<div class="cp-footer__bot">
				<span>
					<?php
					$clickpop_note = (string) clickpop_content( 'footer_note', '' );

					if ( '' !== $clickpop_note ) {
						echo esc_html( $clickpop_note );
					} else {
						printf(
							/* translators: 1: year, 2: site name */
							esc_html__( '© %1$s %2$s — همهٔ حقوق محفوظ است.', 'clickpop' ),
							esc_html( wp_date( 'Y' ) ),
							esc_html( get_bloginfo( 'name' ) )
						);
					}
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

<?php wp_footer(); ?>
</body>
</html>
