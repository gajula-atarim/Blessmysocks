<?php
/**
 * Checkout and order confirmation: the site's voice on top of WooCommerce's own pages.
 *
 * The checkout itself is WooCommerce's Checkout block (styled in bms.css). The order confirmation page gets
 * a personal thank-you heading and a way back to the shop; its order and address tables are WooCommerce's.
 *
 * @package hello-elementor-child
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

// "Thank you, Laura." heading with what happens next, in place of WooCommerce's one-line notice.
add_filter( 'woocommerce_thankyou_order_received_text', function ( $text, $order ) {
	if ( ! $order instanceof WC_Order ) {
		return $text;
	}
	$first = trim( $order->get_billing_first_name() );
	$email = $order->get_billing_email();
	return sprintf(
		'<span class="bms-eyebrow">Order received</span><span class="bms-ty-title">%s</span><span class="bms-ty-text">%s</span>',
		esc_html( $first ? sprintf( 'Thank you, %s.', $first ) : 'Thank you.' ),
		esc_html( 'Your order is packed by hand in Australia and dispatched within 1–2 business days with tracking.' . ( $email ? ' A confirmation is on its way to ' . $email . '.' : '' ) )
	);
}, 10, 2 );

// After the order and address details: back to the shop, or get in touch.
add_action( 'woocommerce_thankyou', function () {
	?>
	<div class="bms-ty-actions">
		<a class="bms-ty-btn" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">Continue shopping</a>
		<a class="bms-ty-link" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Questions about your order? Get in touch</a>
	</div>
	<?php
}, 30 );

// Body class for the confirmation page, so the page title can step aside for the thank-you heading.
add_filter( 'body_class', function ( $classes ) {
	if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
		$classes[] = 'bms-order-received';
	}
	return $classes;
} );
