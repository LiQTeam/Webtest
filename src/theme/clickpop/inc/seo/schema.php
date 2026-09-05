<?php
/**
 * دادهٔ ساخت‌یافته به‌صورت یک گراف واحد.
 *
 * به‌جای چند بلوک پراکنده، همهٔ نودها در یک @graph با ارجاع متقابل قرار می‌گیرند —
 * این همان شکلی است که گوگل بهتر می‌فهمد و از تناقض بین بلوک‌ها جلوگیری می‌کند.
 *
 * AggregateRating عمداً تولید نمی‌شود: Rich Result جعلی ریسک جریمهٔ دستی دارد.
 *
 * @package ClickPop
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || is_feed() || is_404() ) {
			return;
		}

		$graph = array_values( array_filter( array_merge(
			clickpop_schema_core(),
			clickpop_schema_breadcrumbs(),
			clickpop_schema_current()
		) ) );

		if ( ! $graph ) {
			return;
		}

		printf(
			'<script type="application/ld+json">%s</script>' . "\n",
			wp_json_encode(
				[
					'@context' => 'https://schema.org',
					'@graph'   => $graph,
				],
				JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
			)
		);
	},
	20
);

/** @return array<int,array<string,mixed>> */
function clickpop_schema_core(): array {
	$home = trailingslashit( home_url( '/' ) );
	$logo = CLICKPOP_THEME_URI . '/assets/brand/clickpop-logo-horizontal.svg';

	$organization = [
		'@type'         => 'Organization',
		'@id'           => $home . '#organization',
		'name'          => get_bloginfo( 'name' ),
		'alternateName' => 'ClickPop',
		'url'           => $home,
		'logo'          => [
			'@type' => 'ImageObject',
			'url'   => $logo,
		],
	];

	$social = array_filter(
		[
			(string) clickpop_content( 'social_instagram', '' ),
			(string) clickpop_content( 'social_telegram', '' ),
			(string) clickpop_content( 'social_x', '' ),
		]
	);

	if ( $social ) {
		$organization['sameAs'] = array_values( $social );
	}

	$website = [
		'@type'      => 'WebSite',
		'@id'        => $home . '#website',
		'url'        => $home,
		'name'       => get_bloginfo( 'name' ),
		'publisher'  => [ '@id' => $home . '#organization' ],
		'inLanguage' => get_bloginfo( 'language' ),
		'potentialAction' => [
			'@type'       => 'SearchAction',
			'target'      => [
				'@type'       => 'EntryPoint',
				'urlTemplate' => $home . '?s={search_term_string}',
			],
			'query-input' => 'required name=search_term_string',
		],
	];

	return [ $organization, $website ];
}

/** @return array<int,array<string,mixed>> */
function clickpop_schema_breadcrumbs(): array {
	if ( is_front_page() ) {
		return [];
	}

	$items = [
		[
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => __( 'خانه', 'clickpop' ),
			'item'     => trailingslashit( home_url( '/' ) ),
		],
	];

	if ( is_singular() ) {
		$items[] = [
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => get_the_title(),
			'item'     => (string) get_permalink(),
		];
	} elseif ( is_archive() ) {
		$items[] = [
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => wp_strip_all_tags( (string) get_the_archive_title() ),
		];
	}

	return [
		[
			'@type'           => 'BreadcrumbList',
			'@id'             => ( get_permalink() ?: home_url( '/' ) ) . '#breadcrumb',
			'itemListElement' => $items,
		],
	];
}

/**
 * نود صفحهٔ جاری. برای صفحهٔ سرویس، نود Service با Offer ساخته می‌شود.
 *
 * @return array<int,array<string,mixed>>
 */
function clickpop_schema_current(): array {
	if ( ! is_singular() ) {
		return [];
	}

	$url  = (string) get_permalink();
	$home = trailingslashit( home_url( '/' ) );

	$page = [
		'@type'      => 'WebPage',
		'@id'        => $url . '#webpage',
		'url'        => $url,
		'name'       => get_the_title(),
		'isPartOf'   => [ '@id' => $home . '#website' ],
		'inLanguage' => get_bloginfo( 'language' ),
	];

	if ( ! is_singular( 'cp_service_page' ) ) {
		return [ $page ];
	}

	$price = get_post_meta( get_the_ID(), '_cp_display_price_toman', true );

	$service = [
		'@type'       => 'Service',
		'@id'         => $url . '#service',
		'name'        => get_the_title(),
		'description' => wp_strip_all_tags( (string) get_the_excerpt() ),
		'provider'    => [ '@id' => $home . '#organization' ],
		'areaServed'  => 'IR',
		'serviceType' => __( 'خدمات رشد شبکه‌های اجتماعی', 'clickpop' ),
	];

	if ( '' !== $price ) {
		$service['offers'] = [
			'@type'         => 'Offer',
			'price'         => (string) (int) $price,
			'priceCurrency' => 'IRR',
			'availability'  => 'https://schema.org/InStock',
			'url'           => $url,
		];
	}

	return [ $page, $service ];
}
