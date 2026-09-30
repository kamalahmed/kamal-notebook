<?php
/** Worker for contact-security-concurrency.py. Local test harness only. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || 'local' !== wp_get_environment_type() ) { throw new RuntimeException( 'Local WP-CLI only.' ); }
list( $run, $scenario, $worker, $start ) = $args;
if ( ! preg_match( '/^[a-f0-9]{12}$/', $run ) ) { throw new RuntimeException( 'Invalid test namespace.' ); }
global $wpdb;
$wpdb->prefix .= 'security_test_' . $run . '_';
$settings = array_merge( knt_security_defaults(), array( 'contact_email' => 'intercepted@example.invalid', 'contact_captcha' => 'off' ) );
if ( 'global' === $scenario ) { $settings['contact_global_limit'] = 4; }
if ( 'attempt' === $scenario ) {
	$settings['contact_captcha'] = 'turnstile'; $settings['contact_captcha_source'] = 'custom'; $settings['contact_turnstile_sitekey'] = 'test-sitekey'; $settings['contact_attempt_limit'] = 4;
}
add_filter( 'pre_option_kn_settings', static function () use ( $settings ) { return $settings; } );
add_filter( 'pre_option_knt_contact_turnstile_secret', static function () { return 'test-secret'; } );
$mail = 0; $remote = 0;
add_filter( 'pre_wp_mail', static function () use ( &$mail ) { $mail++; return true; } );
add_filter( 'pre_http_request', static function () use ( &$remote ) { $remote++; return array( 'response' => array( 'code' => 200 ), 'body' => '{"success":false}' ); }, 10, 3 );
$_SERVER['REMOTE_ADDR'] = in_array( $scenario, array( 'ip', 'attempt' ), true ) ? '192.0.2.1' : '192.0.2.' . ( 1 + (int) $worker );
$page = get_page_by_path( 'contact' );
$email = in_array( $scenario, array( 'duplicate', 'email' ), true ) ? 'same@example.invalid' : 'reader' . $worker . '@example.invalid';
$message = 'duplicate' === $scenario ? 'Identical normalized message.' : 'Message ' . $worker;
$_POST = array( 'knt_contact_nonce' => wp_create_nonce( 'knt_contact_' . $page->ID ), 'website' => '', 'contact_name' => 'Concurrent test', 'contact_email' => $email, 'contact_message' => $message, 'cf-turnstile-response' => 'token-' . $worker );
while ( microtime( true ) < (float) $start ) { usleep( 1000 ); }
$status = knt_contact_process( $page->ID );
echo wp_json_encode( array( 'status' => $status, 'mail' => $mail, 'remote' => $remote ) );
