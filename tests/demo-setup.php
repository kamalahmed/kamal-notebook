<?php
/**
 * Transactional regression checks, for a disposable database only.
 * KNT_DEMO_TESTS=1 wp eval-file tests/demo-setup.php --skip-plugins --path=...
 */
defined( 'ABSPATH' ) || exit;
if ( '1' !== getenv( 'KNT_DEMO_TESTS' ) ) {
	throw new RuntimeException( 'Use a disposable database and explicitly set KNT_DEMO_TESTS=1.' );
}
global $wpdb;
$assert = static function ( $ok, $message ) {
	if ( ! $ok ) {
		throw new RuntimeException( $message );
	}
};
$wpdb->query( 'START TRANSACTION' );
try {
	// Hide prior demo markers and page paths inside this transaction to test a fresh import.
	foreach ( array_merge( knt_demo_posts(), knt_demo_series_posts() ) as $story ) {
		$id = knt_demo_imported_id( $story['key'] );
		if ( $id ) {
			update_post_meta( $id, '_knt_demo_key', 'kn-test-preserved-' . $id );
		}
	}
	foreach ( array( 'home', 'writing', 'about', 'contact' ) as $slug ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			wp_update_post( array( 'ID' => $page->ID, 'post_name' => 'kn-test-preserved-' . $page->ID ) );
		}
	}
	delete_option( 'kn_settings' );
	delete_option( 'knt_demo_setup_applied' );
	delete_option( 'knt_demo_previous_options' );
	update_option( 'show_on_front', 'posts' );
	update_option( 'page_on_front', 0 );
	update_option( 'page_for_posts', 0 );
	// A deliberate empty selection excludes any existing editorial feature.
	update_option( 'kn_settings', array( 'featured_ids' => '' ) );
	// Match the real admin-post request, including registered setting sanitizers.
	kn_register_settings();
	$administrators = get_users( array( 'role' => 'administrator', 'fields' => 'ID', 'number' => 1 ) );
	$assert( ! empty( $administrators ), 'The disposable site needs an administrator.' );
	wp_set_current_user( (int) $administrators[0] );
	$first = knt_import_demo();
	$assert( ! isset( $first['error'] ), 'Import must complete.' );
	$assert( 8 === $first['created'] && 4 === $first['pages_created'], 'A fresh import needs eight stories and four pages.' );
	$settings = get_option( 'kn_settings' );
	$assert( '1' === $settings['save_enabled'] && '1' === $settings['share_enabled'] && '1' === $settings['feedback_enabled'], 'Admin import must retain fresh reader-tool defaults.' );
	$assert( false !== stripos( $settings['hero_deck'], 'demonstration' ), 'Fresh introduction must identify the demo.' );
	$assert( false !== stripos( $settings['about_text'], 'demonstration' ), 'Fresh about band must identify the demo.' );
	$assert( array( 'featured_ids' => '' ) === get_option( 'knt_demo_previous_options' )['kn_settings'], 'Previous settings must be captured before featured hooks mutate them.' );
	$assert( knt_demo_imported_id( 'first-hour' ) === knt_featured_post()->ID, 'Fresh homepage must feature the sample lead.' );
	$assert( 'page' === get_option( 'show_on_front' ), 'Import must configure a static homepage.' );
	$home = (int) get_option( 'page_on_front' );
	$writing = (int) get_option( 'page_for_posts' );
	$assert( $home && $writing && $home !== $writing, 'Home and Writing must be assigned separately.' );
	$assert( 'publish' === get_post_status( $home ) && 'publish' === get_post_status( $writing ), 'Assigned pages must be published.' );
	foreach ( array_merge( knt_demo_posts(), knt_demo_series_posts() ) as $story ) {
		$id = knt_demo_imported_id( $story['key'] );
		$post = get_post( $id );
		$assert( 0 === strpos( $post->post_title, 'Demonstration:' ), 'Every sample story must be labeled.' );
		$assert( false !== stripos( $post->post_content, 'demonstration' ) || false !== stripos( $post->post_content, 'sample' ), 'Article body must identify sample content.' );
		$assert( has_category( $story['category'], $id ), 'Every sample needs its intended category.' );
		$assert( file_exists( get_theme_file_path( 'assets/images/' . $story['art'] ) ), 'Demo artwork must be bundled.' );
	}
	$about = get_page_by_path( 'about' );
	$contact = get_page_by_path( 'contact' );
	$assert( 3 === substr_count( $about->post_content, '<!-- wp:heading -->' ), 'About sample must show editable editorial sections.' );
	$assert( false !== stripos( $contact->post_content, 'demonstration' ), 'Contact sample must be labeled.' );
	// User edits, featured choice, contact settings, and deliberate trash must survive repeats.
	$settings['contact_email'] = 'owner@example.com';
	$settings['hero_prefix'] = 'Keep my introduction';
	$settings['featured_ids'] = (string) knt_demo_imported_id( 'working-notebook' );
	update_option( 'kn_settings', $settings );
	wp_update_post( array( 'ID' => $about->ID, 'post_content' => 'My existing biography.' ) );
	$removed = knt_demo_imported_id( 'better-questions' );
	wp_update_post( array( 'ID' => $removed, 'post_status' => 'trash' ) );
	$second = knt_import_demo();
	$assert( 0 === $second['created'] && 8 === $second['skipped'] && 0 === $second['pages_created'], 'Repeat import must not duplicate posts or pages, including trash.' );
	$assert( $settings === get_option( 'kn_settings' ), 'Repeat import must preserve customized settings and featured choice.' );
	$assert( 'My existing biography.' === get_post_field( 'post_content', $about->ID ), 'Existing biography must survive.' );
	$assert( 'trash' === get_post_status( $removed ), 'Importer must not restore deliberately removed examples.' );
	$assert( $home === (int) get_option( 'page_on_front' ) && $writing === (int) get_option( 'page_for_posts' ), 'Repeat import must reuse pages.' );
	// Legacy installs may still keep their featured selection only in post meta.
	remove_filter( 'sanitize_option_kn_settings', 'kn_sanitize_settings' );
	update_option( 'kn_settings', array( 'contact_email' => 'owner@example.com' ) );
	kn_register_settings();
	$legacy_feature = knt_featured_post()->ID;
	knt_import_demo();
	$assert( $legacy_feature === knt_featured_post()->ID, 'Import must preserve legacy post-meta featured choices.' );
	update_option( 'show_on_front', 'posts' );
	knt_import_demo( false );
	$assert( 'posts' === get_option( 'show_on_front' ), 'Opting out must preserve Reading settings.' );
	wp_update_post( array( 'ID' => $home, 'post_status' => 'draft' ) );
	update_option( 'page_on_front', 0 );
	$blocked = knt_demo_add_pages();
	$assert( isset( $blocked['error'] ), 'An unpublished page collision must report failure.' );
	$assert( 'draft' === get_post_status( $home ), 'Importer must not publish existing private work.' );
	echo "Demo setup passed: fresh content, labels, art, feature, snapshot, settings/content/trash preservation, repeat import, reading opt-out, and draft collision.\n";
} finally {
	$wpdb->query( 'ROLLBACK' );
	wp_cache_flush();
}
