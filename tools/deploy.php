<?php
/**
 * Server-side deploy for the Bless My Socks homepage build.
 *
 * Run through Atarim's execute-php with $B set to this repo's raw URL at a pinned commit:
 *   $B = 'https://raw.githubusercontent.com/gajula-atarim/Blessmysocks/<sha>/';
 *   return eval( '?>' . wp_remote_retrieve_body( wp_remote_get( $B . 'tools/deploy.php' ) ) );
 *
 * Idempotent: re-running updates the same menus, templates and page.
 */

@set_time_limit( 120 );

$get = function ( $path ) use ( $B ) {
	$r = wp_remote_get( $B . $path, [ 'timeout' => 30 ] );
	if ( is_wp_error( $r ) || 200 !== wp_remote_retrieve_response_code( $r ) ) {
		throw new Exception( 'fetch failed: ' . $path );
	}
	return wp_remote_retrieve_body( $r );
};
$log = [];

// 1. Child theme files.
$dir = get_stylesheet_directory();
foreach ( [ 'functions.php', 'inc/store.php', 'inc/shop.php', 'woocommerce/content-product.php', 'assets/bms.css', 'assets/bms.js' ] as $f ) {
	wp_mkdir_p( dirname( "$dir/$f" ) );
	file_put_contents( "$dir/$f", $get( "theme/$f" ) );
	// Without this, OPcache can keep serving the previous PHP until it revalidates.
	if ( function_exists( 'opcache_invalidate' ) ) {
		opcache_invalidate( "$dir/$f", true );
	}
}
$log['theme'] = 'ok';

// 1a. Product catalogue (names, prices, sizes, photos) to match the Shop and Product designs. Runs once: after
// that, products are managed in WooCommerce and later deploys must not overwrite the shop owner's edits.
if ( ! get_option( 'bms_catalog_done' ) ) {
	eval( '?>' . $get( 'tools/catalog.php' ) );
	update_option( 'bms_catalog_done', gmdate( 'c' ), false );
}

// 1b. Lighter images (WebP) and cloud video; fills $asset_urls (old file URL => new) for the Elementor data below.
eval( '?>' . $get( 'tools/optimize.php' ) );

// 1c. Contact Form 7 (the Contact page form), installed from wordpress.org if missing.
if ( ! file_exists( WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	$api = plugins_api( 'plugin_information', [ 'slug' => 'contact-form-7', 'fields' => [ 'sections' => false ] ] );
	if ( is_wp_error( $api ) ) {
		throw new Exception( 'CF7: ' . $api->get_error_message() );
	}
	$up = new Plugin_Upgrader( new Automatic_Upgrader_Skin() );
	if ( true !== $up->install( $api->download_link ) ) {
		throw new Exception( 'CF7 install failed' );
	}
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if ( ! is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
	$act = activate_plugin( 'contact-form-7/wp-contact-form-7.php' );
	if ( is_wp_error( $act ) ) {
		throw new Exception( 'CF7: ' . $act->get_error_message() );
	}
}
if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
	include_once WP_PLUGIN_DIR . '/contact-form-7/wp-contact-form-7.php';
	if ( function_exists( 'wpcf7_init' ) ) {
		wpcf7_init(); // registers the form post type, which normally happens on 'init' (already past here)
	}
}
$ids = get_option( 'bms_build_ids', [] );
$cf  = ! empty( $ids['cf7'] ) ? wpcf7_contact_form( $ids['cf7'] ) : null;
if ( ! $cf ) {
	$cf = WPCF7_ContactForm::get_template();
}
$cf->set_title( 'BMS Contact' );
$cf->set_properties( [
	'form'     => $get( 'theme/cf7-contact-form.txt' ),
	'mail'     => [
		'active'             => true,
		'subject'            => 'Bless My Socks contact: [topic] from [your-name]',
		'sender'             => 'Bless My Socks <wordpress@' . wp_parse_url( home_url(), PHP_URL_HOST ) . '>',
		'recipient'          => get_option( 'admin_email' ),
		'body'               => "From: [your-name] <[your-email]>\nTopic: [topic]\nOrder number: [order-number]\n\n[your-message]\n\n-- \nSent from the contact form on " . home_url( '/contact/' ),
		'additional_headers' => 'Reply-To: [your-email]',
		'attachments'        => '',
		'use_html'           => false,
		'exclude_blank'      => false,
	],
	'messages' => [
		'mail_sent_ok'     => 'Thank you. Your message is on its way.',
		'mail_sent_ng'     => 'Sorry, your message could not be sent. Please email hello@blessmysocks.com.',
		'validation_error' => 'Please add your name, a valid email and a message.',
		'invalid_required' => 'Required.',
		'invalid_email'    => 'Please enter a valid email address.',
		'spam'             => 'Sorry, your message could not be sent. Please email hello@blessmysocks.com.',
	] + $cf->prop( 'messages' ),
] );
$cf->save();
$ids['cf7'] = $cf->id();
$log['cf7'] = $cf->id();
$cf7_shortcode = sprintf( '[contact-form-7 id="%s" title="BMS Contact"]', method_exists( $cf, 'hash' ) && $cf->hash() ? substr( $cf->hash(), 0, 7 ) : $cf->id() );

// Page shells first, so the menus below can link to the pages themselves (see 'page:<slug>').
foreach ( [ 'about' => 'About', 'faq' => 'FAQ', 'contact' => 'Contact' ] as $slug => $title ) {
	if ( empty( $ids[ $slug ] ) || ! get_post( $ids[ $slug ] ) ) {
		$found = get_page_by_path( $slug );
		$ids[ $slug ] = $found ? $found->ID : wp_insert_post( [ 'post_type' => 'page', 'post_title' => $title, 'post_name' => $slug, 'post_status' => 'publish' ] );
	}
}
update_option( 'bms_build_ids', $ids, false );

// 2. Menus (UAE Navigation Menu widgets in the header/footer use these).
$home = home_url( '/' );
$menu = function ( $name, $items ) {
	$m = wp_get_nav_menu_object( $name );
	if ( $m ) {
		$id = $m->term_id;
		foreach ( (array) wp_get_nav_menu_items( $id ) as $it ) {
			wp_delete_post( $it->ID, true );
		}
	} else {
		$id = wp_create_nav_menu( $name );
	}
	foreach ( $items as $i => $it ) {
		$args = [
			'menu-item-title'    => $it[0],
			'menu-item-url'      => $it[1],
			'menu-item-type'     => 'custom',
			'menu-item-status'   => 'publish',
			'menu-item-position' => $i + 1,
		];
		// 'page:<slug>' links the item to the page itself, so WordPress marks it current on that page
		// (custom links only match the exact URL, and fail with any query string).
		if ( 0 === strpos( $it[1], 'page:' ) ) {
			$page = get_page_by_path( substr( $it[1], 5 ) );
			$args = $page
				? [ 'menu-item-object-id' => $page->ID, 'menu-item-object' => 'page', 'menu-item-type' => 'post_type' ] + $args
				: [ 'menu-item-url' => home_url( '/' . substr( $it[1], 5 ) . '/' ) ] + $args;
		}
		wp_update_nav_menu_item( $id, 0, $args );
	}
	return $id;
};
$faq   = home_url( '/faq/' );
$shop  = wc_get_page_permalink( 'shop' );
$cat   = function ( $slug ) { return home_url( '/product-category/' . $slug . '/' ); };
$menus = [
	'MENU_PRIMARY' => $menu( 'BMS Primary', [ [ 'Shop', 'page:shop' ], [ 'About', 'page:about' ], [ 'FAQ', 'page:faq' ], [ 'Contact', 'page:contact' ] ] ),
	'MENU_SHOP'    => $menu( 'BMS Footer Shop', [ [ 'Socks', $shop . '?cat=socks' ], [ 'T-shirts', $shop . '?cat=tees' ], [ 'Gifts & more', $shop . '?cat=gifts' ], [ 'Shop all', 'page:shop' ] ] ),
	'MENU_HELP'    => $menu( 'BMS Footer Help', [ [ 'Size & fit', $faq . '#size' ], [ 'Materials & care', $faq . '#materials' ], [ 'Shipping', $faq . '#shipping' ], [ 'Returns & exchanges', $faq . '#returns' ], [ 'FAQ', $faq . '#faq' ], [ 'Contact', 'page:contact' ] ] ),
	'MENU_COMPANY' => $menu( 'BMS Footer Company', [ [ 'About', 'page:about' ], [ 'Contact us', 'page:contact' ], [ 'Privacy', '#' ], [ 'Terms', '#' ] ] ),
];
$log['menus'] = $menus;

// 3. Elementor Site Settings (global colours, Jost typography, lightbox look).
$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();
if ( $kit ) {
	$ks = $kit->get_settings();
	$ks['system_colors'] = [
		[ '_id' => 'primary', 'title' => 'Ink', 'color' => '#1E222A' ],
		[ '_id' => 'secondary', 'title' => 'Faith Blue', 'color' => '#3D5A99' ],
		[ '_id' => 'text', 'title' => 'Text', 'color' => '#5F636B' ],
		[ '_id' => 'accent', 'title' => 'Accent', 'color' => '#3D5A99' ],
	];
	$t = function ( $id, $title, $w ) {
		return [ '_id' => $id, 'title' => $title, 'typography_typography' => 'custom', 'typography_font_family' => 'Jost', 'typography_font_weight' => $w ];
	};
	$ks['system_typography']             = [ $t( 'primary', 'Primary', '300' ), $t( 'secondary', 'Secondary', '400' ), $t( 'text', 'Text', '400' ), $t( 'accent', 'Accent', '500' ) ];
	$ks['body_background_background']    = 'classic';
	$ks['body_background_color']         = '#F4F3F1';
	$ks['body_color']                    = '#1E222A';
	$ks['body_typography_typography']    = 'custom';
	$ks['body_typography_font_family']   = 'Jost';
	$ks['global_image_lightbox']         = 'yes';
	$ks['lightbox_color']                = 'rgba(244,243,241,0.94)';
	$ks['lightbox_ui_color']             = '#1E222A';
	$ks['lightbox_ui_color_hover']       = '#3D5A99';
	$ks['lightbox_text_color']           = '#1E222A';
	// Theme Style: replaces Hello Elementor's default pink (#c36) on every plain link and button
	// (WooCommerce cart, checkout, account pages). Editable under Site Settings → Theme Style.
	$ks['link_normal_color']                   = '#1E222A';
	$ks['link_hover_color']                    = '#3D5A99';
	$ks['button_typography_typography']        = 'custom';
	$ks['button_typography_font_family']       = 'Jost';
	$ks['button_typography_font_weight']       = '500';
	$ks['button_text_color']                   = '#FFFFFF';
	$ks['button_background_color']             = '#1E222A';
	$ks['button_border_border']                = 'none';
	$ks['button_border_radius']                = [ 'unit' => 'px', 'top' => '999', 'right' => '999', 'bottom' => '999', 'left' => '999', 'isLinked' => true ];
	$ks['button_padding']                      = [ 'unit' => 'px', 'top' => '14', 'right' => '26', 'bottom' => '14', 'left' => '26', 'isLinked' => false ];
	$ks['button_hover_text_color']             = '#FFFFFF';
	$ks['button_hover_background_color']       = '#3D5A99';
	$ks['form_field_border_color']             = '#D9D5CE';
	$ks['form_field_focus_border_color']       = '#1E222A';
	$ks['form_field_border_radius']            = [ 'unit' => 'px', 'top' => '6', 'right' => '6', 'bottom' => '6', 'left' => '6', 'isLinked' => true ];

	// Favicon. Saving the kit syncs its Site Identity → Favicon field onto WordPress's site_icon option,
	// so an empty field here would wipe the favicon. Keep both pointing at the uploaded site icon.
	$icon = get_posts( [ 'post_type' => 'attachment', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_wp_attached_file', 'meta_value' => '2026/10/bms-site-icon.png' ] );
	if ( $icon ) {
		$ks['site_favicon'] = [ 'id' => $icon[0], 'url' => wp_get_attachment_url( $icon[0] ) ];
	}
	$kit->save( [ 'settings' => $ks ] );
	if ( $icon ) {
		update_option( 'site_icon', $icon[0] );
	}
	$log['kit']  = $kit->get_id();
	$log['icon'] = $icon ? (int) get_option( 'site_icon' ) : 'site icon attachment not found';
}

// 4. Elementor documents.
$json = function ( $name ) use ( $get, $menus, $cf7_shortcode, $asset_urls ) {
	return json_decode( strtr( $get( "elementor/$name.json" ), $menus + [ 'CF7_CONTACT' => addslashes( $cf7_shortcode ) ] + $asset_urls ), true );
};
$ids  = get_option( 'bms_build_ids', [] );
$save = function ( $key, $args, $elements, $meta = [], $settings = [] ) use ( &$ids ) {
	$id = ! empty( $ids[ $key ] ) && get_post( $ids[ $key ] ) ? $ids[ $key ] : 0;
	if ( ! $id ) {
		$id = wp_insert_post( $args + [ 'post_status' => 'publish' ], true );
		if ( is_wp_error( $id ) ) {
			throw new Exception( $key . ': ' . $id->get_error_message() );
		}
		$ids[ $key ] = $id;
	}
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	update_post_meta( $id, '_elementor_edit_mode', 'builder' );
	$doc = \Elementor\Plugin::$instance->documents->get( $id, false );
	$doc->save( [ 'elements' => $elements, 'settings' => $settings ] );
	return $id;
};
$everywhere = [ 'rule' => [ 'basic-global' ], 'specific' => [] ];
$hf_meta    = [ 'ehf_target_include_locations' => $everywhere, 'ehf_target_exclude_locations' => [ 'rule' => [], 'specific' => [] ], 'ehf_target_user_roles' => [], '_elementor_template_type' => 'wp-post' ];

$log['header'] = $save( 'header', [ 'post_type' => 'elementor-hf', 'post_title' => 'BMS Header' ], $json( 'header' ), $hf_meta + [ 'ehf_template_type' => 'type_header' ] );
$log['footer'] = $save( 'footer', [ 'post_type' => 'elementor-hf', 'post_title' => 'BMS Footer' ], $json( 'footer' ), $hf_meta + [ 'ehf_template_type' => 'type_footer' ] );
$log['home']   = $save( 'home', [ 'post_type' => 'page', 'post_title' => 'Home', 'post_name' => 'home' ], $json( 'home' ),
	[ '_wp_page_template' => 'elementor_header_footer', '_elementor_template_type' => 'wp-page' ],
	[ 'hide_title' => 'yes', 'template' => 'elementor_header_footer' ] );
$log['about']  = $save( 'about', [ 'post_type' => 'page', 'post_title' => 'About', 'post_name' => 'about' ], $json( 'about' ),
	[ '_wp_page_template' => 'elementor_header_footer', '_elementor_template_type' => 'wp-page' ],
	[ 'hide_title' => 'yes', 'template' => 'elementor_header_footer' ] );
foreach ( [ 'faq' => 'FAQ', 'contact' => 'Contact' ] as $slug => $title ) {
	$log[ $slug ] = $save( $slug, [ 'post_type' => 'page', 'post_title' => $title, 'post_name' => $slug ], $json( $slug ),
		[ '_wp_page_template' => 'elementor_header_footer', '_elementor_template_type' => 'wp-page' ],
		[ 'hide_title' => 'yes', 'template' => 'elementor_header_footer' ] );
}
update_option( 'bms_build_ids', $ids, false );

update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $ids['home'] );

// 5. Regenerate Elementor CSS and purge caches.
\Elementor\Plugin::$instance->files_manager->clear_cache();
if ( function_exists( 'wp_cache_flush' ) ) {
	wp_cache_flush();
}

return $log;
