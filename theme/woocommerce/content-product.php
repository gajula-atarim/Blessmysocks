<?php
/**
 * Shop grid card, as in the Shop design: photo with badge, name and price, rib colour dots.
 *
 * Overrides woocommerce/templates/content-product.php. data-bms-range drives the instant range filter.
 *
 * @package hello-elementor-child
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$bms_range = bms_product_info( $product )['range'];
$bms_cur   = bms_shop_range();
$bms_class = 'bms-card' . ( is_shop() && 'all' !== $bms_cur && $bms_cur !== $bms_range ? ' is-hidden' : '' );
?>
<li <?php wc_product_class( $bms_class, $product ); ?> data-bms-range="<?php echo esc_attr( $bms_range ); ?>">
	<?php echo bms_card( $product ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</li>
