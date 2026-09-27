<?php
/** An explicit featured story selection, editable with each post. */

defined( 'ABSPATH' ) || exit;

function knt_register_featured_meta(): void {
	register_post_meta( 'post', '_knt_featured', array(
		'type'              => 'boolean',
		'single'            => true,
		'default'           => false,
		'show_in_rest'      => true,
		'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
		'sanitize_callback' => 'rest_sanitize_boolean',
	) );
}
add_action( 'init', 'knt_register_featured_meta' );

/** When a post is marked featured, clear the flag from every other post. */
function knt_keep_one_featured( $meta_id, $post_id, $meta_key, $meta_value ): void {
	if ( '_knt_featured' !== $meta_key || ! rest_sanitize_boolean( $meta_value ) ) {
		return;
	}
	$others = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ),
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'post__not_in'   => array( (int) $post_id ),
		'meta_key'       => '_knt_featured',
		'meta_value'     => '1',
	) );
	foreach ( $others as $other_id ) {
		delete_post_meta( $other_id, '_knt_featured' );
	}
}
add_action( 'added_post_meta', 'knt_keep_one_featured', 10, 4 );
add_action( 'updated_post_meta', 'knt_keep_one_featured', 10, 4 );

function knt_featured_post(): ?WP_Post {
	$posts = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_key'       => '_knt_featured',
		'meta_value'     => '1',
	) );
	return $posts ? $posts[0] : null;
}
