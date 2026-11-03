<?php
/**
 * Hello Elementor Child functions.
 *
 * @package hello-elementor-child
 */

defined( 'ABSPATH' ) || exit;

define( 'BMS_CHILD_VERSION', '1.0.0' );

add_action( 'wp_enqueue_scripts', function () {
	$dir = get_stylesheet_directory();
	$uri = get_stylesheet_directory_uri();

	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'bms-jost', 'https://fonts.googleapis.com/css2?family=Jost:ital,wght@0,300;0,400;0,500;1,300;1,400&display=swap', [], null );
	wp_enqueue_style( 'bms', $uri . '/assets/bms.css', [ 'parent-style' ], filemtime( $dir . '/assets/bms.css' ) );
	wp_enqueue_script( 'bms', $uri . '/assets/bms.js', [], filemtime( $dir . '/assets/bms.js' ), true );

	if ( function_exists( 'WC' ) ) {
		wp_localize_script( 'bms', 'BMS', [
			'store'    => esc_url_raw( rest_url( 'wc/store/v1/' ) ),
			'nonce'    => wp_create_nonce( 'wc_store_api' ),
			'checkout' => wc_get_checkout_url(),
			'cart'     => wc_get_cart_url(),
			'shop'     => wc_get_page_permalink( 'shop' ),
			// Default size (M) for variable products, so quick "Add +" buttons add a real variation.
			'defaults' => function_exists( 'bms_default_variations' ) ? bms_default_variations() : [],
		] );
	}
}, 20 );

// Jost is loaded once, in the <head>, by the stylesheet above (weights 300/400/500 and italics). Elementor would
// add a second full copy of the family later in the page, which only costs extra downloads.
add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
add_filter( 'wp_resource_hints', function ( $urls, $type ) {
	if ( 'preconnect' === $type ) {
		$urls[] = [ 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' ];
		$urls[] = 'https://fonts.googleapis.com';
	}
	return $urls;
}, 10, 2 );

// Contact Form 7: keep the form markup as written (no automatic <p>/<br> wrapping), so bms.css can lay it out.
add_filter( 'wpcf7_autop_or_not', '__return_false' );

require_once get_stylesheet_directory() . '/inc/store.php';
require_once get_stylesheet_directory() . '/inc/shop.php';
require_once get_stylesheet_directory() . '/inc/checkout.php';
