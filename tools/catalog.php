<?php
/**
 * Bring the WooCommerce catalogue in line with the Shop and Product designs.
 *
 * Included by tools/deploy.php (uses its $B, $get and $log). Idempotent: names, prices, categories,
 * tags, photos and Size variations are set to the values below on every run; nothing is deleted.
 */

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

// Media library attachment for a design asset, uploaded from the repo the first time it is needed.
$media = function ( $name ) use ( $B ) {
	$found = get_posts( [
		'post_type'      => 'attachment',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		// '/bms-<stem>.' also finds the attachment once tools/optimize.php has switched it to WebP.
		'meta_query'     => [ [ 'key' => '_wp_attached_file', 'value' => '/bms-' . pathinfo( $name, PATHINFO_FILENAME ) . '.', 'compare' => 'LIKE' ] ], // phpcs:ignore WordPress.DB.SlowDBQuery
	] );
	if ( $found ) {
		return (int) $found[0];
	}
	$tmp = download_url( $B . 'assets/' . $name, 60 );
	if ( is_wp_error( $tmp ) ) {
		throw new Exception( 'asset ' . $name . ': ' . $tmp->get_error_message() );
	}
	$id = media_handle_sideload( [ 'name' => 'bms-' . $name, 'tmp_name' => $tmp ], 0 );
	if ( is_wp_error( $id ) ) {
		throw new Exception( 'asset ' . $name . ': ' . $id->get_error_message() );
	}
	return (int) $id;
};

// Categories: the three ranges the Shop filter uses. The older homepage ranges sit under Socks.
$term = function ( $slug, $name, $parent = 0 ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $t ) {
		$r = wp_insert_term( $name, 'product_cat', [ 'slug' => $slug, 'parent' => $parent ] );
		return (int) $r['term_id'];
	}
	wp_update_term( $t->term_id, 'product_cat', [ 'name' => $name, 'parent' => $parent ] );
	return (int) $t->term_id;
};
$cats = [
	'socks' => $term( 'socks', 'Socks' ),
	'tees'  => $term( 'tees', 'T-shirts' ),
	'gifts' => $term( 'gifts', 'Gifts & more' ),
];
$sub = [
	'everyday-socks'  => $term( 'everyday-socks', 'Everyday Socks', $cats['socks'] ),
	'statement-socks' => $term( 'statement-socks', 'Statement Socks', $cats['socks'] ),
];
$tags = [];
foreach ( [ 'bestseller' => 'Bestseller', 'new' => 'New' ] as $slug => $name ) {
	$t             = get_term_by( 'slug', $slug, 'product_tag' );
	$tags[ $slug ] = $t ? (int) $t->term_id : (int) wp_insert_term( $name, 'product_tag', [ 'slug' => $slug ] )['term_id'];
}

$desc = [
	'socks' => 'Made with a soft cotton-rich blend, a reinforced heel and toe, and a blessing knitted into the cuff. The ribbed cuff finishes a crew height that sits above the ankle.',
	'tees'  => 'Made from soft, breathable cotton with a gentle message of faith on the front. A relaxed unisex cut for everyday wear.',
	'gifts' => 'A thoughtful way to share a blessing with family, friends or your church community.',
];
$sizes = [ 'socks' => [ 'S', 'M', 'L' ], 'tees' => [ 'XS', 'S', 'M', 'L', 'XL' ], 'gifts' => [] ];

// [ product ID, name, price, range, sub-range, rib swatch, tag, photos, colour line ] in Shop order.
$catalog = [
	[ 52, 'Rose Rib Crew', 20, 'socks', 'everyday-socks', '#D9877F', 'bestseller', [ 'pastel-rose.png', 'cat-ev-rose.png' ], 'Rose adds a soft, warm blush to everyday outfits. Let it peek above your sneakers or pair it with simple neutrals.' ],
	[ 53, 'Cream Rib Crew', 20, 'socks', 'everyday-socks', '#F1E9DA', 'bestseller', [ 'pastel-cream.png', 'cat-ev-cream.png' ], 'Cream is the quiet everyday pair that goes with everything, from Sunday best to weekend jeans.' ],
	[ 54, 'Sage Rib Crew', 20, 'socks', 'everyday-socks', '#8C9F78', '', [ 'pastel-sage.png', 'cat-ev-sage.png' ], 'Sage brings a calm, earthy green that sits beautifully with denim, linen and soft neutrals.' ],
	[ 55, 'Lilac Rib Crew', 20, 'socks', 'everyday-socks', '#B9A6D3', '', [ 'pastel-lilac.png', 'cat-ev-lilac.png' ], 'Lilac is a gentle pastel that lifts plain outfits without shouting.' ],
	[ 56, 'Oat Rib Crew', 20, 'socks', 'everyday-socks', '#E3B08E', '', [ 'pastel-peach.png', 'cat-ev-tan.png' ], 'Oat is a warm, natural tone that pairs easily with browns, creams and denim.' ],
	[ 64, 'Blush Frill Bow Crew', 22, 'socks', 'statement-socks', '', 'new', [ 'pastel-blush.png', 'cat-ev-blush.png' ], 'Blush with a frilled cuff and little bow, made for days that call for something sweet.' ],
	[ 58, 'Hearts Crew', 22, 'socks', 'statement-socks', '', '', [ 'cat-st-hearts.png' ], 'Little hearts all over, a reminder that you are loved with every step.' ],
	[ 59, 'Daisy Frill Crew', 22, 'socks', 'statement-socks', '', '', [ 'cat-st-daisy.png' ], 'Cheerful daisies and a frilled cuff for a fresh, springtime feel.' ],
	[ 60, 'Little Bows Crew', 22, 'socks', 'statement-socks', '', '', [ 'cat-st-bows.png' ], 'Tiny bows dotted across a soft rib for a playful, pretty pair.' ],
	[ 61, 'Flower Crew', 22, 'socks', 'statement-socks', '', '', [ 'cat-st-flower.png' ], 'A single flower motif on a soft pastel rib, simple and sweet.' ],
	[ 63, 'Cherry Crew', 22, 'socks', 'statement-socks', '', '', [ 'cat-st-cherry.png' ], 'Bright little cherries for a pair with a touch of fun.' ],
	[ 65, 'Grace Tee, White', 45, 'tees', '', '', 'new', [ 'cat-gf-tee-white.png' ], 'Crisp white goes with everything and lets the message shine.' ],
	[ 66, 'Grace Tee, Rose', 45, 'tees', '', '', '', [ 'cat-gf-tee-rose.png' ], 'A soft rose tone that feels warm and easy to wear.' ],
	[ 67, 'Grace Tee, Sage', 45, 'tees', '', '', '', [ 'cat-gf-tee-sage.png' ], 'A calm sage green that pairs well with denim and neutrals.' ],
	[ 68, 'Blessing Gift Box', 55, 'gifts', '', '', '', [ 'cat-gf-box.png' ], '' ],
	[ 69, 'Blessed Tote', 30, 'gifts', '', '', '', [ 'cat-gf-tote.png' ], '' ],
	[ 70, 'Blessing Card', 6, 'gifts', '', '', '', [ 'cat-gf-card.png' ], '' ],
];

foreach ( $catalog as $order => $row ) {
	list( $id, $name, $price, $range, $subrange, $swatch, $tag, $photos, $line ) = $row;
	if ( ! get_post( $id ) ) {
		throw new Exception( 'product ' . $id . ' missing' );
	}
	$opts = $sizes[ $range ];
	$type = $opts ? 'variable' : 'simple';
	wp_set_object_terms( $id, $type, 'product_type' );
	wc_delete_product_transients( $id );
	$p = $opts ? new WC_Product_Variable( $id ) : new WC_Product_Simple( $id );

	$p->set_name( $name );
	$p->set_status( 'publish' );
	$p->set_menu_order( $order );
	$p->set_short_description( $line );
	$p->set_description( $desc[ $range ] );
	$p->set_category_ids( array_values( array_filter( [ $cats[ $range ], $subrange ? $sub[ $subrange ] : 0 ] ) ) );
	$p->set_tag_ids( $tag ? [ $tags[ $tag ] ] : [] );
	$ids = array_map( $media, $photos );
	$p->set_image_id( $ids[0] );
	$p->set_gallery_image_ids( array_slice( $ids, 1 ) );
	$p->update_meta_data( 'bms_swatch', $swatch ); // rib colour dot on the Shop page (Custom Fields)
	$p->update_meta_data( 'bms_family', $swatch ? 'rib' : '' );

	if ( $opts ) {
		$attr = new WC_Product_Attribute();
		$attr->set_name( 'Size' );
		$attr->set_options( $opts );
		$attr->set_visible( true );
		$attr->set_variation( true );
		$p->set_attributes( [ $attr ] );
		$p->set_default_attributes( [ 'size' => 'M' ] );
		$p->save();
		$have = [];
		foreach ( $p->get_children() as $vid ) {
			$v                                 = wc_get_product( $vid );
			$have[ $v->get_attribute( 'size' ) ] = $v;
		}
		foreach ( $opts as $i => $s ) {
			$v = $have[ $s ] ?? new WC_Product_Variation();
			unset( $have[ $s ] );
			$v->set_parent_id( $id );
			$v->set_attributes( [ 'size' => $s ] );
			$v->set_regular_price( (string) $price );
			$v->set_sale_price( '' );
			$v->set_status( 'publish' );
			$v->set_stock_status( 'instock' );
			$v->set_menu_order( $i );
			$v->save();
		}
		foreach ( $have as $v ) {
			$v->delete( true ); // a size no longer offered
		}
		WC_Product_Variable::sync( $id );
	} else {
		$p->set_attributes( [] );
		$p->set_regular_price( (string) $price );
		$p->set_sale_price( '' );
		$p->save();
	}
	wc_delete_product_transients( $id );
}

// Not in the design: kept as drafts, not deleted.
foreach ( [ 57, 62 ] as $id ) {
	if ( get_post( $id ) ) {
		wp_update_post( [ 'ID' => $id, 'post_status' => 'draft' ] );
	}
}

update_option( 'woocommerce_default_catalog_orderby', 'menu_order' );
delete_transient( 'bms_default_variations' );
$log['catalog'] = count( $catalog );
