<?php
/**
 * Bless My Socks Shop and Product pages.
 *
 * Both use WooCommerce's own templates (so every product is listed and laid out automatically) with the
 * design's pieces added through WooCommerce hooks; product cards come from woocommerce/content-product.php.
 * Names, prices, photos, sizes and descriptions are edited on the WooCommerce product screen.
 *
 * @package hello-elementor-child
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/**
 * The Shop filter's ranges: slug => [ tab label, eyebrow, intro, fit line ].
 */
function bms_ranges() {
	return [
		'all'   => [ 'All', 'The full range', 'Soft socks, tees and gifts, each carrying a quiet word of faith. Packed by hand in Australia.', 'Socks come in S, M and L.' ],
		'socks' => [ 'Socks', 'The sock range', 'Soft ribbed cotton crew socks with a blessing knitted into the cuff. Choose Rose, Cream, Sage, Lilac or Oat, or a pair with a little more personality.', 'Sizes: S AU 4–7 · M AU 7–11 · L AU 11–14.' ],
		'tees'  => [ 'T-shirts', 'The tee range', 'Soft, relaxed cotton tees carrying a gentle message of faith. Choose White, Rose or Sage.', 'Unisex sizes XS to XL.' ],
		'gifts' => [ 'Gifts & more', 'Made to share', 'Thoughtful gifts for church, family and friends. Pair them with socks for a blessing in a box.', 'Gift items are one size.' ],
	];
}

/** Range picked in the Shop URL (?cat=socks), or 'all'. */
function bms_shop_range() {
	$cat = isset( $_GET['cat'] ) ? sanitize_key( wp_unslash( $_GET['cat'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification
	return array_key_exists( $cat, bms_ranges() ) ? $cat : 'all';
}

/** Design facts about a product: range, rib family, swatch colour and badge. */
function bms_product_info( $product ) {
	$range = 'gifts';
	foreach ( [ 'socks', 'tees', 'gifts' ] as $slug ) {
		if ( has_term( $slug, 'product_cat', $product->get_id() ) ) {
			$range = $slug;
			break;
		}
	}
	$tag = '';
	foreach ( [ 'bestseller' => 'Bestseller', 'new' => 'New' ] as $slug => $label ) {
		if ( has_term( $slug, 'product_tag', $product->get_id() ) ) {
			$tag = $label;
			break;
		}
	}
	return [
		'range'  => $range,
		'rib'    => 'rib' === $product->get_meta( 'bms_family' ),
		'swatch' => (string) $product->get_meta( 'bms_swatch' ),
		'tag'    => $tag,
	];
}

/** Published products in Shop order (menu order), loaded once per request. */
function bms_all_products() {
	static $all = null;
	if ( null === $all ) {
		$all = wc_get_products( [ 'status' => 'publish', 'limit' => -1, 'orderby' => 'menu_order', 'order' => 'ASC', 'visibility' => 'catalog' ] );
	}
	return $all;
}

/** The rib crew colours (Rose, Cream, Sage, Lilac, Oat). */
function bms_rib_products() {
	return array_values( array_filter( bms_all_products(), function ( $p ) {
		return 'rib' === $p->get_meta( 'bms_family' );
	} ) );
}

function bms_price_label( $product, $qty = 1 ) {
	return wp_strip_all_tags( wc_price( (float) $product->get_price() * $qty ) );
}

/** Colour dots under rib crew cards: one per colour, the current one ringed. */
function bms_dots( $product ) {
	if ( 'rib' !== $product->get_meta( 'bms_family' ) ) {
		return '';
	}
	$out = '';
	foreach ( bms_rib_products() as $p ) {
		$out .= sprintf(
			'<a class="bms-dot-link%s" href="%s" aria-label="%s" title="%s" style="background:%s"></a>',
			$p->get_id() === $product->get_id() ? ' is-current' : '',
			esc_url( $p->get_permalink() ),
			esc_attr( $p->get_name() ),
			esc_attr( str_replace( ' Rib Crew', '', $p->get_name() ) ),
			esc_attr( $p->get_meta( 'bms_swatch' ) )
		);
	}
	return $out;
}

/** A product card (Shop grid and "Pairs well with"). */
function bms_card( $product, $classes = '' ) {
	$info = bms_product_info( $product );
	$link = esc_url( $product->get_permalink() );
	ob_start();
	?>
	<div class="bms-card__media">
		<a href="<?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput ?>" tabindex="-1" aria-hidden="true">
			<?php echo wp_get_attachment_image( $product->get_image_id(), 'woocommerce_single', false, [ 'alt' => $product->get_name(), 'loading' => 'lazy' ] ); ?>
		</a>
		<?php if ( $info['tag'] ) : ?><span class="bms-card__tag"><?php echo esc_html( $info['tag'] ); ?></span><?php endif; ?>
	</div>
	<a class="bms-card__row" href="<?php echo $link; // phpcs:ignore WordPress.Security.EscapeOutput ?>"><span class="bms-card__name"><?php echo esc_html( $product->get_name() ); ?></span><span class="bms-card__price"><?php echo esc_html( bms_price_label( $product ) ); ?></span></a>
	<div class="bms-card__dots"><?php echo bms_dots( $product ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php
	return ob_get_clean();
}

// Variable products' default size (M) for quick "Add +" buttons: [ product ID => variation ID ].
function bms_default_variations() {
	$map = get_transient( 'bms_default_variations' );
	if ( false === $map ) {
		$map = [];
		foreach ( wc_get_products( [ 'type' => 'variable', 'status' => 'publish', 'limit' => -1 ] ) as $p ) {
			$want = $p->get_default_attributes();
			foreach ( $p->get_children() as $vid ) {
				$v = wc_get_product( $vid );
				if ( $v && ( empty( $want['size'] ) || $v->get_attribute( 'size' ) === $want['size'] ) ) {
					$map[ $p->get_id() ] = $vid;
					break;
				}
			}
		}
		set_transient( 'bms_default_variations', $map, DAY_IN_SECONDS );
	}
	return $map;
}
add_action( 'woocommerce_update_product', function () {
	delete_transient( 'bms_default_variations' );
} );

// ---------------------------------------------------------------- Shop page

add_filter( 'woocommerce_show_page_title', '__return_false' );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
add_filter( 'loop_shop_per_page', function () {
	return 48; // the whole range on one page, so the filter can work instantly
} );

// Range pages (/product-category/socks/ and the homepage's older ranges) open the Shop with that filter on.
add_action( 'template_redirect', function () {
	if ( ! is_product_category() ) {
		return;
	}
	$slug = get_queried_object()->slug;
	$map  = [ 'socks' => 'socks', 'everyday-socks' => 'socks', 'statement-socks' => 'socks', 'tees' => 'tees', 'gifts' => 'gifts' ];
	$url  = wc_get_page_permalink( 'shop' );
	wp_safe_redirect( isset( $map[ $slug ] ) ? add_query_arg( 'cat', $map[ $slug ], $url ) : $url );
	exit;
} );

// Intro, product count and range filter above the grid.
add_action( 'woocommerce_before_shop_loop', function () {
	if ( ! is_shop() ) {
		return;
	}
	$ranges = bms_ranges();
	$cur    = bms_shop_range();
	$counts = [ 'all' => 0, 'socks' => 0, 'tees' => 0, 'gifts' => 0 ];
	foreach ( bms_all_products() as $p ) {
		$counts['all']++;
		$counts[ bms_product_info( $p )['range'] ]++;
	}
	$copy = [];
	foreach ( $ranges as $k => $r ) {
		$copy[ $k ] = [ 'title' => 'all' === $k ? 'All products' : $r[0], 'eyebrow' => $r[1], 'intro' => $r[2], 'fit' => $r[3], 'count' => $counts[ $k ] ];
	}
	$box = wc_get_product( 68 );
	?>
	<section class="bms-shop-intro" data-bms-ranges="<?php echo esc_attr( wp_json_encode( $copy ) ); ?>">
		<span class="bms-eyebrow" data-bms-copy="eyebrow"><?php echo esc_html( $copy[ $cur ]['eyebrow'] ); ?></span>
		<h1 class="bms-shop-title" data-bms-copy="title"><?php echo esc_html( $copy[ $cur ]['title'] ); ?></h1>
		<p class="bms-shop-lead" data-bms-copy="intro"><?php echo esc_html( $copy[ $cur ]['intro'] ); ?></p>
		<p class="bms-shop-fit"><span data-bms-copy="fit"><?php echo esc_html( $copy[ $cur ]['fit'] ); ?></span> <a href="<?php echo esc_url( home_url( '/faq/#size' ) ); ?>">Find your fit</a> or <a href="<?php echo esc_url( $box ? $box->get_permalink() : home_url( '/shop/' ) ); ?>">build a gift box</a>.</p>
		<span class="bms-shop-count" data-bms-copy="count"><?php echo esc_html( $copy[ $cur ]['count'] . ( 1 === $copy[ $cur ]['count'] ? ' product' : ' products' ) ); ?></span>
		<nav class="bms-shop-tabs" aria-label="Filter products">
			<?php foreach ( $ranges as $k => $r ) : ?>
				<a href="<?php echo esc_url( 'all' === $k ? wc_get_page_permalink( 'shop' ) : add_query_arg( 'cat', $k, wc_get_page_permalink( 'shop' ) ) ); ?>" data-bms-range="<?php echo esc_attr( $k ); ?>" class="<?php echo $k === $cur ? 'is-active' : ''; ?>" aria-pressed="<?php echo $k === $cur ? 'true' : 'false'; ?>"><?php echo esc_html( $r[0] ); ?></a>
			<?php endforeach; ?>
		</nav>
	</section>
	<?php
}, 15 );

// ---------------------------------------------------------------- Product page

remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
foreach ( [ 'woocommerce_template_single_title' => 5, 'woocommerce_template_single_rating' => 10, 'woocommerce_template_single_price' => 10, 'woocommerce_template_single_excerpt' => 20, 'woocommerce_template_single_add_to_cart' => 30, 'woocommerce_template_single_meta' => 40, 'woocommerce_template_single_sharing' => 50 ] as $fn => $prio ) {
	remove_action( 'woocommerce_single_product_summary', $fn, $prio );
}
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

/** Per-range product copy from the design: [ eyebrow, fit, care, size note, fit & feel panel ]. */
function bms_range_copy( $info ) {
	$care = [
		'socks' => 'Cotton-rich blend with a little stretch. Machine wash cold on a gentle cycle; dry flat. Packed by hand in Australia.',
		'tees'  => '100% cotton. Machine wash cold, inside out; line dry. Packed by hand in Australia.',
		'gifts' => 'Wipe clean or follow the care label.',
	][ $info['range'] ];
	return [
		'eyebrow' => [ 'socks' => $info['rib'] ? 'Everyday rib socks' : 'Statement socks', 'tees' => 'Grace tees', 'gifts' => 'Gifts & more' ][ $info['range'] ],
		'fit'     => [ 'socks' => 'S fits AU 4–7, M fits AU 7–11, L fits AU 11–14. Crew height.', 'tees' => 'Relaxed unisex fit, XS to XL.', 'gifts' => 'One size.' ][ $info['range'] ],
		'care'    => $care,
		'feel'    => [
			'socks' => 'Knitted with a little stretch to hug the foot and recover its shape. S fits AU 4–7 (foot length 22–24 cm), M fits AU 7–11 (24–26.5 cm), L fits AU 11–14 (26.5–29 cm). Between sizes? Size up. The cuff stays put without pinching.',
			'tees'  => 'Relaxed unisex fit in XS to XL. Choose your usual size, or size down for a closer fit.',
			'gifts' => 'One size.',
		][ $info['range'] ],
		'panel_care' => $care . ( 'gifts' === $info['range'] ? '' : ' Turn inside out before washing to protect the knitted message. No bleach, fabric softener or ironing.' ),
	];
}

/** Size buttons: [ label, hint, note shown under the buttons ]. */
function bms_size_options( $info ) {
	if ( 'socks' === $info['range'] ) {
		return [
			[ 'S', 'AU 4–7', 'Foot length 22–24 cm · Between sizes? Size up.' ],
			[ 'M', 'AU 7–11', 'Foot length 24–26.5 cm · Between sizes? Size up.' ],
			[ 'L', 'AU 11–14', 'Foot length 26.5–29 cm · Between sizes? Size up.' ],
		];
	}
	if ( 'tees' === $info['range'] ) {
		return array_map( function ( $s ) {
			return [ $s, '', 'Size down for a closer fit.' ];
		}, [ 'XS', 'S', 'M', 'L', 'XL' ] );
	}
	return [ [ 'One size', '', '' ] ];
}

// Gallery: main photo and thumbnails that swap it.
add_action( 'woocommerce_before_single_product_summary', function () {
	global $product;
	$ids = array_values( array_filter( array_merge( [ $product->get_image_id() ], $product->get_gallery_image_ids() ) ) );
	?>
	<div class="bms-pdp-gallery">
		<div class="bms-pdp-main"><?php echo wp_get_attachment_image( $ids[0] ?? 0, 'woocommerce_single', false, [ 'alt' => $product->get_name(), 'data-bms-main' => '1' ] ); ?></div>
		<?php if ( count( $ids ) > 1 ) : ?>
			<div class="bms-pdp-thumbs">
				<?php foreach ( $ids as $i => $id ) : ?>
					<button type="button" class="bms-pdp-thumb<?php echo 0 === $i ? ' is-active' : ''; ?>" aria-label="View image <?php echo (int) $i + 1; ?>" data-src="<?php echo esc_url( wp_get_attachment_image_url( $id, 'woocommerce_single' ) ); ?>" data-srcset="<?php echo esc_attr( (string) wp_get_attachment_image_srcset( $id, 'woocommerce_single' ) ); ?>"><?php echo wp_get_attachment_image( $id, 'thumbnail' ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}, 20 );

// Summary: name, price, notes, colour, size, quantity and add to cart, details and panels.
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	$info   = bms_product_info( $product );
	$copy   = bms_range_copy( $info );
	$sizes  = bms_size_options( $info );
	$box    = wc_get_product( 68 );
	$shop   = wc_get_page_permalink( 'shop' );
	$vars   = [];
	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( $v ) {
				$vars[ $v->get_attribute( 'size' ) ] = $vid;
			}
		}
	}
	$default = $product->is_type( 'variable' ) ? ( $product->get_default_attributes()['size'] ?? $sizes[0][0] ) : $sizes[0][0];
	$note    = '';
	foreach ( $sizes as $s ) {
		if ( $s[0] === $default ) {
			$note = $s[2];
		}
	}
	?>
	<div class="bms-pdp-head">
		<span class="bms-eyebrow"><?php echo esc_html( $copy['eyebrow'] ); ?></span>
		<h1 class="bms-pdp-title"><?php echo esc_html( $product->get_name() ); ?></h1>
		<span class="bms-pdp-price"><?php echo esc_html( bms_price_label( $product ) ); ?></span>
	</div>
	<div class="bms-pdp-note">Packed by hand in Australia · Dispatched within 1–2 business days with tracking</div>
	<?php if ( $info['rib'] ) : ?>
		<div class="bms-pdp-colours">
			<span class="bms-pdp-label">Colour · <?php echo esc_html( str_replace( ' Rib Crew', '', $product->get_name() ) ); ?></span>
			<div class="bms-pdp-swatches">
				<?php foreach ( bms_rib_products() as $p ) : ?>
					<a href="<?php echo esc_url( $p->get_permalink() ); ?>" class="bms-pdp-swatch<?php echo $p->get_id() === $product->get_id() ? ' is-current' : ''; ?>" aria-label="<?php echo esc_attr( str_replace( ' Rib Crew', '', $p->get_name() ) ); ?>" title="<?php echo esc_attr( str_replace( ' Rib Crew', '', $p->get_name() ) ); ?>"><?php echo wp_get_attachment_image( $p->get_image_id(), 'thumbnail' ); ?></a>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
	<form class="bms-pdp-form" action="<?php echo esc_url( $product->get_permalink() ); ?>" method="post" data-price="<?php echo esc_attr( (float) $product->get_price() ); ?>" data-product="<?php echo (int) $product->get_id(); ?>">
		<input type="hidden" name="add-to-cart" value="<?php echo (int) $product->get_id(); ?>">
		<?php if ( $vars ) : ?>
			<input type="hidden" name="product_id" value="<?php echo (int) $product->get_id(); ?>">
			<input type="hidden" name="variation_id" value="<?php echo (int) ( $vars[ $default ] ?? 0 ); ?>">
			<input type="hidden" name="attribute_size" value="<?php echo esc_attr( $default ); ?>">
		<?php endif; ?>
		<div class="bms-pdp-size">
			<div class="bms-pdp-size__head"><span class="bms-micro">Size</span><a href="<?php echo esc_url( home_url( '/faq/#size' ) ); ?>">Size guide</a></div>
			<div class="bms-pdp-size__opts" role="group" aria-label="Size">
				<?php foreach ( $sizes as $s ) : ?>
					<button type="button" class="bms-size<?php echo $s[0] === $default ? ' is-active' : ''; ?>" aria-pressed="<?php echo $s[0] === $default ? 'true' : 'false'; ?>" data-size="<?php echo esc_attr( $s[0] ); ?>" data-variation="<?php echo (int) ( $vars[ $s[0] ] ?? 0 ); ?>" data-note="<?php echo esc_attr( $s[2] ); ?>"><span><?php echo esc_html( $s[0] ); ?></span><small><?php echo esc_html( $s[1] ? $s[1] : ' ' ); ?></small></button>
				<?php endforeach; ?>
			</div>
			<?php if ( $note ) : ?><span class="bms-pdp-size__note" data-bms-size-note><?php echo esc_html( $note ); ?></span><?php endif; ?>
		</div>
		<div class="bms-pdp-buy">
			<div class="bms-pdp-qty">
				<button type="button" aria-label="Decrease" data-bms-qty="-1">−</button>
				<input type="number" name="quantity" value="1" min="1" step="1" inputmode="numeric" aria-label="Quantity">
				<button type="button" aria-label="Increase" data-bms-qty="1">+</button>
			</div>
			<button type="submit" class="bms-pdp-add">Add to cart · <span data-bms-total><?php echo esc_html( bms_price_label( $product ) ); ?></span></button>
		</div>
	</form>
	<div class="bms-pdp-ticks"><span>✓ Tracked shipping across Australia</span><span>✓ 30-day faulty replacement</span></div>
	<div class="bms-pdp-copy">
		<?php if ( $product->get_short_description() ) : ?><p><?php echo wp_kses_post( $product->get_short_description() ); ?></p><?php endif; ?>
		<?php if ( $product->get_description() ) : ?><p><?php echo wp_kses_post( $product->get_description() ); ?></p><?php endif; ?>
		<p><strong>Size &amp; fit</strong> <?php echo esc_html( $copy['fit'] ); ?> <a href="<?php echo esc_url( home_url( '/faq/#size' ) ); ?>">Check the size guide</a></p>
		<p><strong>Materials &amp; care</strong> <?php echo esc_html( $copy['care'] ); ?></p>
		<p>Explore <a href="<?php echo esc_url( add_query_arg( 'cat', $info['range'], $shop ) ); ?>">all <?php echo esc_html( $info['range'] ); ?> colours</a> or <a href="<?php echo esc_url( $box ? $box->get_permalink() : $shop ); ?>">build a gift box</a>.</p>
	</div>
	<div class="bms-pdp-panels">
		<?php
		$panels = [
			'Materials & care'   => $copy['panel_care'],
			'Fit & feel'         => $copy['feel'],
			'Shipping & returns' => 'Dispatched within 1–2 business days via Australia Post, tracked. Australia: Standard A$9.95 (2–7 business days) or Express A$14.95 (1–3 business days). International costs are shown at checkout. If something isn’t right, email us within 30 days of delivery with your order number and a photo, and we’ll replace it at no cost. To exchange unworn items for another colour or size, request an exchange within 30 days; you cover return postage and we cover the replacement’s postage. Worn socks are excluded from change-of-mind returns and exchanges only. These conditions do not limit your rights under the Australian Consumer Law.',
		];
		foreach ( $panels as $title => $body ) :
			?>
			<details class="bms-pdp-panel"><summary><?php echo esc_html( $title ); ?><span aria-hidden="true"></span></summary><p><?php echo esc_html( $body ); ?></p></details>
		<?php endforeach; ?>
	</div>
	<a class="bms-pdp-guide" href="<?php echo esc_url( home_url( '/faq/' ) ); ?>">Sock fit, fabric and care guide</a>
	<?php
}, 5 );

// "Pairs well with": same range in another style first, then other ranges, three in all.
add_action( 'woocommerce_after_single_product_summary', function () {
	global $product;
	$info = bms_product_info( $product );
	$all  = array_filter( bms_all_products(), function ( $p ) use ( $product ) {
		return $p->get_id() !== $product->get_id();
	} );
	$same = array_filter( $all, function ( $p ) use ( $info ) {
		$i = bms_product_info( $p );
		return $i['range'] === $info['range'] && ( ! $info['rib'] || ! $i['rib'] );
	} );
	$rest = array_filter( $all, function ( $p ) use ( $info ) {
		return bms_product_info( $p )['range'] !== $info['range'];
	} );
	$pick = array_slice( array_merge( array_values( $same ), array_values( $rest ) ), 0, 3 );
	$box  = wc_get_product( 68 );
	?>
	<section class="bms-pdp-related">
		<div class="bms-pdp-related__head">
			<div><span class="bms-eyebrow">Mix &amp; match</span><h2>Pairs well with</h2></div>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">All styles →</a>
		</div>
		<div class="bms-pdp-related__grid">
			<?php foreach ( $pick as $p ) : ?>
				<div class="bms-card"><?php echo bms_card( $p ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<?php endforeach; ?>
		</div>
		<a class="bms-pdp-related__box" href="<?php echo esc_url( $box ? $box->get_permalink() : wc_get_page_permalink( 'shop' ) ); ?>">Build a gift box →</a>
	</section>
	<?php
}, 20 );
