<?php
/** Run with wp eval-file; fixtures and settings are restored even on failure. */
if ( ! function_exists( 'knt_featured_sanitize' ) ) { throw new RuntimeException( 'Featured settings helpers are missing.' ); }
$previous = get_option( 'kn_settings', array() );
$old_user = get_current_user_id();
$old_per_page = get_option( 'posts_per_page' );
$admin = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( $admin[0] );
$ids = array();
$sanitize_during_editor = static function ( $value ) { return array_merge( $value, knt_featured_sanitize( $value, get_option( 'kn_settings', array() ) ) ); };
$flags = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_knt_featured', 'meta_value' => '1' ) );
$assert = static function ( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } };
try {
 foreach ( array( 'publish', 'publish', 'publish', 'publish', 'publish', 'draft', 'private' ) as $status ) {
  $ids[] = wp_insert_post( array( 'post_title' => 'Featured fixture ' . count( $ids ), 'post_type' => 'post', 'post_status' => $status ) );
 }
 $clean = knt_featured_sanitize( array( 'featured_mode' => 'slider', 'featured_ids' => implode( ',', array( $ids[1], $ids[0], $ids[1], $ids[5], $ids[6], $ids[2], $ids[3], $ids[4], 99999999 ) ) ), $previous );
 $assert( $clean['featured_ids'] === implode( ',', array( $ids[1], $ids[0], $ids[2], $ids[3] ) ), 'Selection must preserve order, deduplicate, cap at four and reject nonpublic posts.' );
 $assert( knt_featured_sanitize( array(), $clean ) === $clean, 'Partial settings updates must retain the current selection.' );
 $assert( knt_featured_sanitize( array( 'featured_ids' => array(), 'featured_mode' => 'bogus' ), $clean )['featured_mode'] === 'slider', 'Invalid mode must preserve the prior mode.' );
 update_option( 'kn_settings', array_merge( $previous, $clean ) );
 $assert( array_column( knt_featured_posts(), 'ID' ) === array( $ids[1], $ids[0], $ids[2], $ids[3] ), 'Slider retrieval must respect configured order.' );
 $assert( (bool) get_post_meta( $ids[2], '_knt_featured', true ), 'Centrally selected posts must be checked in the editor.' );
 // Simulate the registered option sanitizer during an editor save by a user
 // without edit permission for the previously selected articles. Meta writes
 // are already authorized by WordPress before this internal hook runs.
 add_filter( 'sanitize_option_kn_settings', $sanitize_during_editor );
 wp_set_current_user( 0 );
 update_post_meta( $ids[4], '_knt_featured', true );
 wp_set_current_user( $admin[0] );
 remove_filter( 'sanitize_option_kn_settings', $sanitize_during_editor );
 $assert( array_column( knt_featured_posts(), 'ID' ) === array( $ids[4], $ids[1], $ids[0], $ids[2] ), 'Editor checking must move the new article first and keep three existing choices.' );
 update_post_meta( $ids[1], '_knt_featured', false );
 $assert( array_column( knt_featured_posts(), 'ID' ) === array( $ids[4], $ids[0], $ids[2] ), 'Editor unchecking must remove only that article.' );
 // Read actual homepage pagination: every non-featured article must occur once.
 update_option( 'posts_per_page', 2 );
 $expected_posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'post__not_in' => array( $ids[4], $ids[0], $ids[2] ) ) );
 $expected_urls = array_map( 'get_permalink', $expected_posts );
 $seen_urls = array();
 for ( $page = 1; $page <= (int) ceil( count( $expected_urls ) / 2 ); $page++ ) {
  $response = wp_remote_get( add_query_arg( 'paged', $page, home_url( '/' ) ) );
  $assert( 200 === wp_remote_retrieve_response_code( $response ), 'Every archive page must return HTTP 200.' );
  $html = wp_remote_retrieve_body( $response );
  preg_match_all( '~<h3><a href="([^"]+)"~', $html, $matches );
  $seen_urls = array_merge( $seen_urls, array_map( 'html_entity_decode', $matches[1] ) );
  if ( $page > 1 ) { $assert( false === strpos( $html, 'data-kn-featured' ), 'Later archive pages must not repeat the featured slider.' ); }
 }
 sort( $expected_urls ); sort( $seen_urls );
 $assert( $seen_urls === $expected_urls, 'Archive pagination must neither repeat nor skip articles when featured items are excluded.' );
 update_option( 'posts_per_page', $old_per_page );
 $settings = get_option( 'kn_settings' ); $settings['featured_mode'] = 'single'; update_option( 'kn_settings', $settings );
 $assert( array_column( knt_featured_posts(), 'ID' ) === array( $ids[4] ), 'Single mode must show just the first selection.' );
 // A status change must never leak a now-private selected article.
 wp_update_post( array( 'ID' => $ids[4], 'post_status' => 'private' ) );
 $assert( array_column( knt_featured_posts(), 'ID' ) === array( $ids[0] ), 'Private posts must be filtered even after initial configuration.' );
 $settings['featured_ids'] = ''; update_option( 'kn_settings', $settings );
 $assert( array() === knt_featured_posts(), 'Explicit empty selection must not revive legacy metadata.' );
 // Draft selection is an editor intent, retained until the post becomes public.
 update_post_meta( $ids[5], '_knt_featured', true );
 $assert( (bool) get_post_meta( $ids[5], '_knt_featured', true ), 'Saving a draft must retain its featured checkbox.' );
 $assert( array() === knt_featured_posts(), 'A featured draft must never appear publicly.' );
 $settings['featured_mode'] = 'slider'; update_option( 'kn_settings', $settings );
 $assert( (bool) get_post_meta( $ids[5], '_knt_featured', true ), 'An unrelated settings save must preserve draft featured intent.' );
 wp_update_post( array( 'ID' => $ids[5], 'post_status' => 'publish' ) );
 $assert( array_column( knt_featured_posts(), 'ID' ) === array( $ids[5] ), 'Publishing a checked draft must promote it to the featured selection.' );
 // Unchecking while back in draft must also remove a prior central selection.
 wp_update_post( array( 'ID' => $ids[5], 'post_status' => 'draft' ) );
 update_post_meta( $ids[5], '_knt_featured', false );
 wp_update_post( array( 'ID' => $ids[5], 'post_status' => 'publish' ) );
 $assert( array() === knt_featured_posts(), 'Republishing an unchecked draft must not revive its former selection.' );
 $legacy = $settings; unset( $legacy['featured_ids'] );
 knt_featured_syncing( true );
 update_post_meta( $ids[0], '_knt_featured', true );
 update_option( 'kn_settings', $legacy );
 knt_featured_syncing( false );
 $assert( knt_featured_post()->ID === $ids[0], 'Existing installations must retain their legacy featured article before a settings save.' );
 $assert( knt_featured_sanitize( array(), $legacy )['featured_ids'] === (string) $ids[0], 'An unrelated first settings save must preserve legacy selection.' );
 wp_set_current_user( 0 );
 $assert( '' === knt_featured_sanitize( array( 'featured_ids' => (string) $ids[0] ), array() )['featured_ids'], 'Selection sanitizer must reject posts the user cannot edit.' );
 echo "Featured settings passed: ordering, duplicate/visibility/capability guards, partial saves, editor synchronization, complete archive pagination, single mode, clearing, draft publication and legacy migration.\n";
} finally {
 remove_filter( 'sanitize_option_kn_settings', $sanitize_during_editor );
 update_option( 'posts_per_page', $old_per_page );
 wp_set_current_user( $admin[0] );
 foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
 update_option( 'kn_settings', $previous );
 foreach ( $flags as $id ) { update_post_meta( $id, '_knt_featured', true ); }
 update_option( 'kn_settings', $previous );
 wp_set_current_user( $old_user );
}
