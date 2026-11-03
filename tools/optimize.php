<?php
/**
 * Lighter media: the design's PNG/JPG images become WebP copies made from the same pixels, and the cloud video
 * a smaller encode of the same clip (same 1920x1080 frame, no audio track, which was never played).
 *
 * Included by tools/deploy.php (uses its $B and $log). Each attachment keeps its ID, so Elementor widgets and
 * products pick up the new file; $asset_urls maps old file URLs to new ones for settings that store a URL.
 * The original files stay on disk, so any old link keeps working. Idempotent.
 */

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/misc.php'; // insert_with_markers()

$up         = wp_get_upload_dir();
$asset_urls = [];
$converted  = 0;
$webp_subs  = wp_image_editor_supports( [ 'mime_type' => 'image/webp' ] );

$find = function ( $stem ) {
	// '/bms-<stem>.' matches the attachment whether it is still .png/.jpg or already .webp.
	return get_posts( [
		'post_type'      => 'attachment',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => [ [ 'key' => '_wp_attached_file', 'value' => '/bms-' . $stem . '.', 'compare' => 'LIKE' ] ], // phpcs:ignore WordPress.DB.SlowDBQuery
	] );
};

$list = wp_remote_retrieve_body( wp_remote_get( $B . 'assets/webp/manifest.txt', [ 'timeout' => 30 ] ) );
foreach ( array_filter( array_map( 'trim', explode( "\n", $list ) ) ) as $stem ) {
	$ids = $find( $stem );
	if ( ! $ids ) {
		continue; // not in the media library (e.g. a design file the site does not use)
	}
	$id  = (int) $ids[0];
	$rel = get_post_meta( $id, '_wp_attached_file', true );
	if ( '.webp' !== substr( $rel, -5 ) ) {
		$body = wp_remote_retrieve_body( wp_remote_get( $B . 'assets/webp/' . $stem . '.webp', [ 'timeout' => 60 ] ) );
		if ( strlen( $body ) < 100 ) {
			throw new Exception( 'webp fetch failed: ' . $stem );
		}
		$new_rel = dirname( $rel ) . '/bms-' . $stem . '.webp';
		file_put_contents( $up['basedir'] . '/' . $new_rel, $body );
		update_post_meta( $id, '_bms_orig_file', $rel );
		update_attached_file( $id, $up['basedir'] . '/' . $new_rel );
		wp_update_post( [ 'ID' => $id, 'post_mime_type' => 'image/webp' ] );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['basedir'] . '/' . $new_rel ) );
		$rel = $new_rel;
		$converted++;
	}
	$orig = get_post_meta( $id, '_bms_orig_file', true );
	if ( $orig ) {
		$asset_urls[ $up['baseurl'] . '/' . $orig ] = $up['baseurl'] . '/' . $rel;
	}
}

// Cloud video: the lighter encode as its own attachment; the original stays in the library.
$video = get_posts( [
	'post_type'      => 'attachment',
	'posts_per_page' => 1,
	'fields'         => 'ids',
	'meta_query'     => [ [ 'key' => '_wp_attached_file', 'value' => '/bms-hero-video-opt.mp4', 'compare' => 'LIKE' ] ], // phpcs:ignore WordPress.DB.SlowDBQuery
] );
if ( ! $video ) {
	$tmp = download_url( $B . 'assets/hero-video-opt.mp4', 120 );
	if ( is_wp_error( $tmp ) ) {
		throw new Exception( 'video: ' . $tmp->get_error_message() );
	}
	$vid = media_handle_sideload( [ 'name' => 'bms-hero-video-opt.mp4', 'tmp_name' => $tmp ], 0 );
	if ( is_wp_error( $vid ) ) {
		throw new Exception( 'video: ' . $vid->get_error_message() );
	}
	$video = [ $vid ];
}
$old_video = get_posts( [
	'post_type'      => 'attachment',
	'posts_per_page' => 1,
	'fields'         => 'ids',
	'meta_query'     => [ [ 'key' => '_wp_attached_file', 'value' => '/bms-hero-video.mp4', 'compare' => 'LIKE' ] ], // phpcs:ignore WordPress.DB.SlowDBQuery
] );
if ( $old_video ) {
	$asset_urls[ wp_get_attachment_url( $old_video[0] ) ] = wp_get_attachment_url( $video[0] );
}

// Long browser caching for media (the video had none). Guarded, so a server without the module ignores it.
if ( preg_match( '/apache|litespeed/i', $_SERVER['SERVER_SOFTWARE'] ?? '' ) && is_writable( ABSPATH . '.htaccess' ) ) {
	insert_with_markers( ABSPATH . '.htaccess', 'BMS media caching', [
		'<IfModule mod_expires.c>',
		'ExpiresActive On',
		'ExpiresByType video/mp4 "access plus 6 months"',
		'ExpiresByType image/webp "access plus 6 months"',
		'ExpiresByType image/png "access plus 6 months"',
		'ExpiresByType image/jpeg "access plus 6 months"',
		'</IfModule>',
	] );
	$log['htaccess'] = 'expires added';
}

$log['optimize'] = [ 'converted' => $converted, 'mapped' => count( $asset_urls ), 'webp_subsizes' => $webp_subs, 'server' => $_SERVER['SERVER_SOFTWARE'] ?? '' ];
