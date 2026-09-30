<?php
/** One-time data handoffs; the theme never loads another extension's runtime. */
defined( 'ABSPATH' ) || exit;

function kn_migrate_contact_credentials(): void {
 if ( get_option( 'kn_contact_credentials_v2' ) ) { return; }
 $settings = (array) get_option( 'kn_settings', array() );
 if ( 'existing' === ( $settings['contact_captcha_source'] ?? '' ) ) {
  // Read a retired storage format once, so an upgrade does not lose saved keys.
  // This works even when the originating extension has been removed entirely.
  $legacy = (array) get_option( 'wpcf7', array() );
  $keys = $legacy['turnstile'] ?? array();
  if ( is_array( $keys ) && 1 === count( $keys ) ) {
   $sitekey = array_key_first( $keys );
   $secret = $keys[ $sitekey ];
   if ( is_string( $sitekey ) && is_string( $secret ) && preg_match( '/^[a-zA-Z0-9_-]{10,100}$/', $sitekey ) && preg_match( '/^[a-zA-Z0-9_-]{10,200}$/', $secret ) ) {
    $settings['contact_turnstile_sitekey'] = $sitekey;
    update_option( 'knt_contact_turnstile_secret', $secret, false );
   }
  }
 }
 unset( $settings['contact_captcha_source'] );
 // Preserve absence on fresh sites; importer can still detect a clean setup.
 if ( get_option( 'kn_settings', null ) !== null ) { update_option( 'kn_settings', $settings ); }
 update_option( 'kn_contact_credentials_v2', '1', false );
}
add_action( 'after_setup_theme', 'kn_migrate_contact_credentials', 5 );
