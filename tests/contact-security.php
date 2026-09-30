<?php
/** Run locally: wp eval-file tests/contact-security.php. Never sends mail. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit; }
$GLOBALS['knt_security_checks'] = 0;
function knt_security_test_assert( $condition, $message ) {
	
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	$GLOBALS['knt_security_checks']++;
}
knt_security_test_assert( function_exists( 'knt_contact_verify_captcha' ), 'Contact CAPTCHA verification must exist.' );
if ( 'local' !== wp_get_environment_type() ) { throw new RuntimeException( 'Local environment only.' ); }
global $wpdb;
$original_prefix = $wpdb->prefix;
$wpdb->prefix .= 'security_test_' . substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 12 ) . '_';
$wpdb->query( "CREATE TABLE {$wpdb->prefix}knt_contact_limits LIKE {$original_prefix}knt_contact_limits" );
$old = get_option( 'kn_settings' );
$old_secret = get_option( 'knt_contact_turnstile_secret', null );
$run = 'test-' . wp_generate_uuid4();
$keys = array();
$mail_count = 0;
$http_count = 0;
$mock = 'success';
$mail_filter = static function () use ( &$mail_count ) { $mail_count++; return true; };
$http_filter = static function ( $pre, $request, $url ) use ( &$http_count, &$mock ) {
	if ( 'https://challenges.cloudflare.com/turnstile/v0/siteverify' !== $url ) { return $pre; }
	$http_count++;
	if ( 'network' === $mock ) { return new WP_Error( 'http_request_failed', 'Mock timeout' ); }
	$body = array( 'success' => true, 'hostname' => wp_parse_url( home_url(), PHP_URL_HOST ), 'action' => 'notebook_contact' );
	if ( 'reject' === $mock ) { $body['success'] = false; $body['error-codes'] = array( 'timeout-or-duplicate' ); }
	if ( 'host' === $mock ) { $body['hostname'] = 'evil.example'; }
	if ( 'action' === $mock ) { $body['action'] = 'another_form'; }
	return array( 'response' => array( 'code' => 'status' === $mock ? 503 : 200 ), 'body' => 'json' === $mock ? '<html>error</html>' : wp_json_encode( $body ) );
};
add_filter( 'pre_wp_mail', $mail_filter );
add_filter( 'pre_http_request', $http_filter, 10, 3 );
try {
	$settings = array_merge( is_array( $old ) ? $old : array(), knt_security_defaults(), array( 'contact_captcha' => 'turnstile', 'contact_captcha_source' => 'custom', 'contact_turnstile_sitekey' => 'test-public-key' ) );
	update_option( 'kn_settings', $settings );
	update_option( 'knt_contact_turnstile_secret', 'test-secret', false );
	foreach ( array( 'success', 'reject', 'host', 'action', 'network', 'json', 'status' ) as $case ) {
		$mock = $case;
		$result = knt_contact_verify_captcha( $run . '-' . $case );
		knt_security_test_assert( ( true === $result ) === ( 'success' === $case ), 'CAPTCHA case: ' . $case );
	}
	$mock = 'success';
	$before = $http_count;
	foreach ( array( '', str_repeat( 'x', 2049 ), $run . '-success' ) as $invalid ) {
		knt_security_test_assert( is_wp_error( knt_contact_verify_captcha( $invalid ) ), 'Missing, oversized or replay token rejected.' );
	}
	knt_security_test_assert( $before === $http_count, 'Invalid and replay tokens must not call Siteverify.' );
	$bounded = knt_security_sanitize( array( 'contact_ip_limit' => '0', 'contact_global_limit' => '999999', 'contact_captcha' => 'oops', 'contact_turnstile_secret' => '' ), $settings );
	knt_security_test_assert( 1 === $bounded['contact_ip_limit'] && 500 === $bounded['contact_global_limit'], 'Limits bounded.' );
	knt_security_test_assert( 'turnstile' === $bounded['contact_captcha'], 'Invalid mode preserves prior configuration.' );
	knt_security_test_assert( 'test-secret' === get_option( 'knt_contact_turnstile_secret' ), 'Blank secret preserves existing secret.' );
	knt_security_test_assert( ! isset( $bounded['contact_turnstile_secret'] ), 'Secret excluded from public settings.' );
	ob_start(); knt_security_settings_fields(); $html = ob_get_clean();
	knt_security_test_assert( false === strpos( $html, 'test-secret' ), 'Secret never rendered.' );
	$key = knt_contact_security_key( 'test', $run ); $keys[] = $key;
	knt_security_test_assert( true === knt_contact_reserve( $key, 2, 60 ), 'First slot.' );
	knt_security_test_assert( true === knt_contact_reserve( $key, 2, 60 ), 'Second slot.' );
	knt_security_test_assert( is_wp_error( knt_contact_reserve( $key, 2, 60 ) ), 'Limit enforced.' );
	global $wpdb;
	$wpdb->update( $wpdb->prefix . 'knt_contact_limits', array( 'expires' => time() - 1 ), array( 'bucket' => $key ) );
	knt_security_test_assert( true === knt_contact_reserve( $key, 2, 60 ), 'Expired window resets.' );
	$_SERVER['REMOTE_ADDR'] = '2001:db8::1';
	$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.7';
	knt_security_test_assert( '2001:db8::1' === knt_contact_remote_ip(), 'Forwarded IP ignored.' );
	// Unique identities ensure real form tests cannot consume ordinary visitor quotas.
	$_SERVER['REMOTE_ADDR'] = '192.0.2.' . random_int( 1, 254 );
	$settings['contact_email'] = 'receiver@example.invalid';
	update_option( 'kn_settings', $settings );
	$page = get_page_by_path( 'contact' );
	knt_security_test_assert( $page && knt_contact_page_is_valid( $page->ID ), 'Local Contact page fixture exists.' );
	$_POST = array( 'knt_contact_nonce' => wp_create_nonce( 'knt_contact_' . $page->ID ), 'website' => '', 'contact_name' => 'Security test', 'contact_email' => $run . '@example.invalid', 'contact_message' => $run, 'cf-turnstile-response' => $run . '-form-success' );
	knt_security_test_assert( 'sent' === knt_contact_process( $page->ID ), 'Valid form reaches intercepted mail.' );
	knt_security_test_assert( 1 === $mail_count, 'Exactly one intercepted email.' );
	$_POST['cf-turnstile-response'] = $run . '-form-duplicate';
	knt_security_test_assert( 'limited' === knt_contact_process( $page->ID ), 'Duplicate never sends twice.' );
	$_POST['cf-turnstile-response'] = '';
	knt_security_test_assert( 'captcha' === knt_contact_process( $page->ID ), 'Missing token cannot send.' );
	$_POST['website'] = 'bot';
	knt_security_test_assert( 'invalid' === knt_contact_process( $page->ID ), 'Honeypot never pretends to send.' );
	$_POST['website'] = ''; $_POST['knt_contact_nonce'] = 'bad';
	knt_security_test_assert( 'nonce' === knt_contact_process( $page->ID ), 'Invalid nonce cannot send.' );
	knt_security_test_assert( 1 === $mail_count, 'Blocked forms never send mail.' );
	// Exhausted IP attempt budgets must block before any remote verification.
	$_POST['knt_contact_nonce'] = wp_create_nonce( 'knt_contact_' . $page->ID );
	$_POST['cf-turnstile-response'] = $run . '-blocked-before-remote';
	$attempt_key = knt_contact_security_key( 'attempt', knt_contact_remote_ip() );
	$wpdb->update( $wpdb->prefix . 'knt_contact_limits', array( 'hits' => 20 ), array( 'bucket' => $attempt_key ) );
	$before = $http_count;
	knt_security_test_assert( 'limited' === knt_contact_process( $page->ID ), 'Exhausted attempt quota blocks form.' );
	knt_security_test_assert( $before === $http_count, 'Attempt quota precedes remote calls.' );
	delete_option( 'knt_contact_turnstile_secret' );
	knt_security_test_assert( 'security' === knt_contact_verify_captcha( $run . '-unconfigured' )->get_error_code(), 'Missing credentials fail closed.' );
	update_option( 'knt_contact_turnstile_secret', 'test-secret', false );
	// The shared CF7 script handle avoids loading Cloudflare twice.
	wp_enqueue_script( 'cloudflare-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
	ob_start(); knt_contact_security_fields(); $widget = ob_get_clean();
	$turnstile_scripts = array_filter( wp_scripts()->queue, static function ( $handle ) { return false !== strpos( (string) wp_scripts()->registered[$handle]->src, 'challenges.cloudflare.com/turnstile/v0/api.js' ); } );
	knt_security_test_assert( 1 === count( $turnstile_scripts ), 'One Cloudflare loader when CF7 already enqueued it.' );
	knt_security_test_assert( false !== strpos( $widget, 'data-action="notebook_contact"' ), 'Widget action matches server.' );
	WP_CLI::success( $GLOBALS['knt_security_checks'] . " contact security assertions passed; all mail intercepted, all Siteverify calls mocked." );
} finally {
	update_option( 'kn_settings', $old );
	if ( null === $old_secret ) { delete_option( 'knt_contact_turnstile_secret' ); } else { update_option( 'knt_contact_turnstile_secret', $old_secret, false ); }
	remove_filter( 'pre_wp_mail', $mail_filter ); remove_filter( 'pre_http_request', $http_filter, 10 );
	$wpdb->query( "DROP TABLE {$wpdb->prefix}knt_contact_limits" );
	$wpdb->prefix = $original_prefix;
}
