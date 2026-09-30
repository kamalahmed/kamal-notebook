<?php
/** Ordered featured articles, shared by Notebook settings and the post editor. */
defined( 'ABSPATH' ) || exit;

function knt_featured_defaults(): array {
 return array( 'featured_mode' => 'single', 'featured_ids' => '' );
}

/** Normalize IDs without granting access to unpublished articles. */
function knt_featured_ids( $value, bool $check_permission = false ): array {
 $values = is_array( $value ) ? $value : explode( ',', is_scalar( $value ) ? (string) $value : '' );
 $ids = array();
 foreach ( $values as $value ) {
  if ( ! is_scalar( $value ) || ! preg_match( '/^\d+$/', trim( (string) $value ) ) ) { continue; }
  $id = absint( $value );
  $post = get_post( $id );
  if ( ! $post || 'post' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password || ( $check_permission && ! current_user_can( 'edit_post', $id ) ) ) { continue; }
  if ( ! in_array( $id, $ids, true ) ) { $ids[] = $id; }
  if ( 4 === count( $ids ) ) { break; }
 }
 return $ids;
}

function knt_featured_sanitize( $input, $previous ): array {
 $previous = is_array( $previous ) ? $previous : array();
 $out = array_merge( knt_featured_defaults(), array_intersect_key( $previous, knt_featured_defaults() ) );
 if ( isset( $input['featured_mode'] ) && in_array( $input['featured_mode'], array( 'single', 'slider' ), true ) ) { $out['featured_mode'] = $input['featured_mode']; }
 if ( array_key_exists( 'featured_ids', $input ) ) {
  $out['featured_ids'] = implode( ',', knt_featured_ids( $input['featured_ids'], ! knt_featured_syncing() ) );
 } elseif ( ! array_key_exists( 'featured_ids', $previous ) ) {
  // An unrelated settings save must not silently discard the existing editor selection.
  $out['featured_ids'] = implode( ',', knt_featured_selected_ids( $previous ) );
 }
 return $out;
}

function knt_featured_selected_ids( ?array $settings = null ): array {
 $settings = $settings ?? (array) get_option( 'kn_settings', array() );
 if ( array_key_exists( 'featured_ids', $settings ) ) { return knt_featured_ids( $settings['featured_ids'] ); }
 return knt_featured_ids( get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_knt_featured', 'meta_value' => '1', 'has_password' => false ) ) );
}

function knt_featured_posts(): array {
 $settings = (array) get_option( 'kn_settings', array() );
 $ids = knt_featured_selected_ids( $settings );
 if ( 'slider' !== ( $settings['featured_mode'] ?? 'single' ) ) { $ids = array_slice( $ids, 0, 1 ); }
 return array_map( 'get_post', $ids );
}

function knt_featured_post(): ?WP_Post {
 return knt_featured_posts()[0] ?? null;
}

function knt_register_featured_meta(): void {
 register_post_meta( 'post', '_knt_featured', array( 'type' => 'boolean', 'single' => true, 'default' => false, 'show_in_rest' => true, 'auth_callback' => static fn( $allowed, $key, $post_id ) => current_user_can( 'edit_post', $post_id ), 'sanitize_callback' => 'rest_sanitize_boolean' ) );
}
add_action( 'init', 'knt_register_featured_meta' );

/** Recursion guard shared by option and metadata synchronization. */
function knt_featured_syncing( ?bool $value = null ): bool {
 static $syncing = false;
 if ( null !== $value ) { $syncing = $value; }
 return $syncing;
}

function knt_featured_sync_flags( $old, $new ): void {
 if ( knt_featured_syncing() || ! is_array( $new ) || ! array_key_exists( 'featured_ids', $new ) ) { return; }
 knt_featured_syncing( true );
 try {
  $ids = knt_featured_selected_ids( $new );
  $flagged = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_knt_featured', 'meta_value' => '1' ) );
  foreach ( array_diff( $flagged, $ids ) as $id ) { update_post_meta( $id, '_knt_featured', false ); }
  foreach ( $ids as $id ) { update_post_meta( $id, '_knt_featured', true ); }
 } finally { knt_featured_syncing( false ); }
}
add_action( 'update_option_kn_settings', 'knt_featured_sync_flags', 10, 2 );
add_action( 'add_option_kn_settings', static function ( $name, $value ) { knt_featured_sync_flags( array(), $value ); }, 10, 2 );

/** Checking promotes a post to first place; unchecking removes only that post. */
function knt_sync_featured_meta( $meta_id, $post_id, $meta_key, $meta_value ): void {
 if ( '_knt_featured' !== $meta_key || knt_featured_syncing() || 'post' !== get_post_type( $post_id ) ) { return; }
 // Keep a draft/scheduled post's checkbox until publication. It cannot be
 // placed in the public selection yet; unchecking still removes any old ID.
 if ( rest_sanitize_boolean( $meta_value ) && 'publish' !== get_post_status( $post_id ) ) { return; }
 $settings = (array) get_option( 'kn_settings', array() );
 $ids = knt_featured_selected_ids( $settings );
 $ids = array_values( array_diff( $ids, array( (int) $post_id ) ) );
 if ( rest_sanitize_boolean( $meta_value ) && knt_featured_ids( array( $post_id ) ) ) { array_unshift( $ids, (int) $post_id ); }
 $settings['featured_ids'] = implode( ',', array_slice( $ids, 0, 4 ) );
 // The post meta capability already authorizes this edit. Preserve the other owners'
 // central selections if the option sanitizer runs during this editor request.
 knt_featured_syncing( true );
 try { update_option( 'kn_settings', $settings ); } finally { knt_featured_syncing( false ); }
 knt_featured_sync_flags( array(), (array) get_option( 'kn_settings', array() ) );
}
add_action( 'added_post_meta', 'knt_sync_featured_meta', 10, 4 );
add_action( 'updated_post_meta', 'knt_sync_featured_meta', 10, 4 );
add_action( 'deleted_post_meta', static function ( $meta_ids, $post_id, $key ) { knt_sync_featured_meta( $meta_ids, $post_id, $key, false ); }, 10, 3 );

/** Honor a previously saved draft or scheduled article selection on publication. */
function knt_featured_published( $new_status, $old_status, $post ): void {
 if ( 'publish' === $new_status && 'publish' !== $old_status && 'post' === $post->post_type && rest_sanitize_boolean( get_post_meta( $post->ID, '_knt_featured', true ) ) ) {
  knt_sync_featured_meta( 0, $post->ID, '_knt_featured', true );
 }
}
add_action( 'transition_post_status', 'knt_featured_published', 10, 3 );

function knt_featured_settings_fields(): void {
 $settings = (array) get_option( 'kn_settings', array() );
 $selected = knt_featured_selected_ids( $settings );
 $posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'has_password' => false ) );
 ?>
 <div data-kn-setting="featured homepage slider display single">
  <label for="kn-featured-mode"><strong><?php esc_html_e( 'Homepage display', 'kamal-notebook' ); ?></strong></label>
  <select id="kn-featured-mode" name="kn_settings[featured_mode]">
   <option value="single" <?php selected( $settings['featured_mode'] ?? 'single', 'single' ); ?>><?php esc_html_e( 'Single featured article', 'kamal-notebook' ); ?></option>
   <option value="slider" <?php selected( $settings['featured_mode'] ?? 'single', 'slider' ); ?>><?php esc_html_e( 'Featured article slider', 'kamal-notebook' ); ?></option>
  </select>
  <p class="description"><?php esc_html_e( 'Single shows the first selected article. The slider shows up to four, with reader-controlled previous and next buttons.', 'kamal-notebook' ); ?></p>
 </div>
 <?php for ( $index = 0; $index < 4; $index++ ) : ?>
 <div data-kn-setting="featured homepage article order selection">
  <label for="kn-featured-<?php echo esc_attr( $index ); ?>"><strong><?php printf( esc_html__( 'Featured article %d', 'kamal-notebook' ), $index + 1 ); ?></strong></label>
  <select id="kn-featured-<?php echo esc_attr( $index ); ?>" name="kn_settings[featured_ids][]">
   <option value=""><?php esc_html_e( 'None', 'kamal-notebook' ); ?></option>
   <?php foreach ( $posts as $post ) : if ( ! current_user_can( 'edit_post', $post->ID ) ) { continue; } ?>
    <option value="<?php echo esc_attr( $post->ID ); ?>" <?php selected( $selected[ $index ] ?? 0, $post->ID ); ?>><?php echo esc_html( $post->post_title ?: __( '(Untitled)', 'kamal-notebook' ) ); ?></option>
   <?php endforeach; ?>
  </select>
 </div>
 <?php endfor; ?>
 <p class="description"><?php esc_html_e( 'Articles appear in this order. Only published, public posts are available. Checking Featured in a post editor moves that article into the first position.', 'kamal-notebook' ); ?></p>
 <?php
}
