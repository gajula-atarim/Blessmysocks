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
		] );
	}
}, 20 );

require_once get_stylesheet_directory() . '/inc/store.php';
