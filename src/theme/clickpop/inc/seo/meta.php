<?php
/**
 * متاتگ‌های OpenGraph و Twitter.
 *
 * اگر Rank Math یا Yoast فعال باشد، این ماژول کاملاً خاموش می‌شود
 * تا تگ تکراری تولید نشود (خطای رایج Search Console).
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

function clickpop_seo_plugin_active(): bool {
	return defined( 'RANK_MATH_VERSION' ) || defined( 'WPSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

add_action(
	'wp_head',
	static function (): void {
		if ( clickpop_seo_plugin_active() || is_admin() ) {
			return;
		}

		$title       = wp_get_document_title();
		$description = clickpop_meta_description();
		$url         = is_singular() ? (string) get_permalink() : home_url( add_query_arg( [] ) );
		$image       = clickpop_meta_image();

		$tags = [
			[ 'name', 'description', $description ],
			[ 'property', 'og:type', is_singular( 'post' ) ? 'article' : 'website' ],
			[ 'property', 'og:site_name', get_bloginfo( 'name' ) ],
			[ 'property', 'og:locale', get_locale() ],
			[ 'property', 'og:title', $title ],
			[ 'property', 'og:description', $description ],
			[ 'property', 'og:url', $url ],
			[ 'property', 'og:image', $image ],
			[ 'name', 'twitter:card', 'summary_large_image' ],
			[ 'name', 'twitter:title', $title ],
			[ 'name', 'twitter:description', $description ],
			[ 'name', 'twitter:image', $image ],
		];

		foreach ( $tags as [ $attribute, $key, $value ] ) {
			if ( '' === (string) $value ) {
				continue;
			}

			printf(
				'<meta %1$s="%2$s" content="%3$s">' . "\n",
				esc_attr( $attribute ),
				esc_attr( $key ),
				esc_attr( (string) $value )
			);
		}

		if ( is_singular() ) {
			printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $url ) );
		}
	},
	5
);

function clickpop_meta_description(): string {
	if ( is_singular() ) {
		$excerpt = wp_strip_all_tags( (string) get_the_excerpt() );

		if ( '' !== $excerpt ) {
			return wp_html_excerpt( $excerpt, 160, '…' );
		}
	}

	return wp_html_excerpt( (string) get_bloginfo( 'description' ), 160, '…' );
}

function clickpop_meta_image(): string {
	if ( is_singular() && has_post_thumbnail() ) {
		$src = wp_get_attachment_image_src( (int) get_post_thumbnail_id(), 'large' );

		if ( is_array( $src ) ) {
			return (string) $src[0];
		}
	}

	return CLICKPOP_THEME_URI . '/assets/brand/clickpop-logo-stacked.svg';
}

/**
 * داشبورد و مسیرهای بازگشت پرداخت هرگز نباید ایندکس شوند.
 */
add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		global $post;

		$is_dashboard = $post instanceof WP_Post
			&& has_shortcode( (string) $post->post_content, 'clickpop_dashboard' );

		if ( $is_dashboard || is_search() || get_query_var( 'clickpop_pay' ) ) {
			$robots['noindex']  = true;
			$robots['nofollow'] = true;
		}

		return $robots;
	}
);
