<?php
/**
 * Bless My Socks store rules and the slide-out cart drawer.
 *
 * Bundle pricing: 3+ items save 20%, and 5+ items also ship free.
 *
 * @package hello-elementor-child
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

// Show Australian dollars as "A$" to match the design.
add_filter( 'woocommerce_currency_symbol', function ( $symbol, $currency ) {
	return 'AUD' === $currency ? 'A$' : $symbol;
}, 10, 2 );

// 3 or more items in the cart: 20% bundle saving.
add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	$qty = $cart->get_cart_contents_count();
	if ( $qty >= 3 ) {
		$cart->add_fee( __( 'Bundle saving (20%)', 'hello-elementor-child' ), -0.2 * (float) $cart->get_subtotal() );
	}
} );

// 5+ items in the cart: flat-rate shipping becomes free.
add_filter( 'woocommerce_package_rates', function ( $rates ) {
	if ( ! WC()->cart || WC()->cart->get_cart_contents_count() < 5 ) {
		return $rates;
	}
	foreach ( $rates as $rate ) {
		if ( 'flat_rate' === $rate->get_method_id() ) {
			$rate->set_cost( 0 );
			$rate->set_taxes( [] );
			$rate->set_label( $rate->get_label() . ' · free on 5+ pairs' );
		}
	}
	return $rates;
}, 20 );

// Cart drawer shell; bms.js fills it from the Store API.
add_action( 'wp_footer', function () {
	?>
	<div class="bms-drawer" id="bms-drawer" aria-hidden="true">
		<div class="bms-drawer__scrim" data-bms-close></div>
		<aside class="bms-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="bms-drawer-title">
			<div class="bms-drawer__head">
				<span class="bms-drawer__title" id="bms-drawer-title"><?php esc_html_e( 'Cart', 'hello-elementor-child' ); ?> (<span data-bms-count>0</span>)</span>
				<button type="button" class="bms-drawer__close" aria-label="<?php esc_attr_e( 'Close', 'hello-elementor-child' ); ?>" data-bms-close>&times;</button>
			</div>
			<div class="bms-drawer__body" data-bms-lines>
				<div class="bms-drawer__empty">
					<span><?php esc_html_e( 'Your cart is empty.', 'hello-elementor-child' ); ?></span>
					<button type="button" class="bms-pill" data-bms-close><?php esc_html_e( 'Shop socks', 'hello-elementor-child' ); ?></button>
				</div>
			</div>
			<div class="bms-drawer__foot">
				<div class="bms-drawer__row bms-drawer__saving" data-bms-saving hidden><span><?php esc_html_e( 'Bundle saving', 'hello-elementor-child' ); ?></span><span data-bms-saving-amount></span></div>
				<div class="bms-drawer__row"><span><?php esc_html_e( 'Subtotal', 'hello-elementor-child' ); ?></span><span data-bms-subtotal>A$0.00</span></div>
				<span class="bms-drawer__hint" data-bms-hint></span>
				<a class="bms-pill bms-pill--lg" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Checkout', 'hello-elementor-child' ); ?></a>
				<span class="bms-drawer__note"><?php esc_html_e( 'PayPal, card, Apple Pay and Google Pay', 'hello-elementor-child' ); ?></span>
			</div>
		</aside>
	</div>
	<?php
} );
