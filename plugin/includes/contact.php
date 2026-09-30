<?php
/**
 * Contact form handling for the theme's Contact page.
 *
 * Bootstrap with: require_once __DIR__ . '/includes/contact.php';
 * The theme stores comma- or newline-separated receivers in
 * kn_settings[contact_email]. An empty setting disables the form.
 */

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/contact-security.php';

/** Return only valid addresses from the theme setting; never guess a receiver. */
function knt_contact_recipients(): array {
	$settings = get_option( 'kn_settings', array() );
	$configured = is_array( $settings ) ? ( $settings['contact_email'] ?? '' ) : '';
	if ( ! is_string( $configured ) || '' === trim( $configured ) ) {
		return array();
	}

	$addresses = array();
	foreach ( preg_split( '/[,;\s]+/', $configured ) as $candidate ) {
		if ( '' === $candidate ) {
			continue;
		}
		$email = sanitize_email( $candidate );
		if ( $email === $candidate && is_email( $email ) ) {
			$addresses[] = $email;
		}
	}
	return array_values( array_unique( $addresses ) );
}

function knt_contact_ready(): bool {
	return array() !== knt_contact_recipients();
}

/** A scalar POST field after WordPress magic-quote removal, or null. */
function knt_contact_post_field( string $key ): ?string {
	return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : null;
}

function knt_contact_redirect( int $page_id, string $status ): void {
	$url = add_query_arg( 'kn_contact', $status, get_permalink( $page_id ) );
	wp_safe_redirect( $url . '#contact-response', 303 );
	exit;
}

/** Accept only published pages that actually use the Contact template. */
function knt_contact_page_is_valid( int $page_id ): bool {
	if ( 'page' !== get_post_type( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
		return false;
	}
	return 'page-contact.php' === get_page_template_slug( $page_id ) || 'contact' === get_post_field( 'post_name', $page_id );
}

/** Keep page caches from serving an expired contact-form nonce. */
function knt_contact_disable_page_cache(): void {
	if ( ! is_page() || ! knt_contact_page_is_valid( get_queried_object_id() ) ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'Notebook contact form requires a fresh nonce.' );
}
add_action( 'template_redirect', 'knt_contact_disable_page_cache', 0 );

function knt_handle_contact_submission(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_die( esc_html__( 'This request is unavailable.', 'kamal-notebook-tools' ), '', array( 'response' => 405 ) );
	}

	$page_id = absint( knt_contact_post_field( 'contact_page_id' ) );
	if ( ! knt_contact_page_is_valid( $page_id ) ) {
		wp_die( esc_html__( 'This contact page is unavailable.', 'kamal-notebook-tools' ), '', array( 'response' => 404 ) );
	}
	knt_contact_redirect( $page_id, knt_contact_process( $page_id ) );
}

/** Process a validated contact page; returns a generic public status. */
function knt_contact_process( int $page_id ): string {
	if ( ! knt_contact_ready() ) {
		return 'unavailable';
	}
	$settings = knt_contact_security_settings();
	$ip = knt_contact_remote_ip();
	$attempt = knt_contact_reserve( knt_contact_security_key( 'attempt', $ip ), $settings['contact_attempt_limit'], $settings['contact_attempt_window'] * MINUTE_IN_SECONDS );
	if ( is_wp_error( $attempt ) ) { return $attempt->get_error_code(); }
	$nonce = knt_contact_post_field( 'knt_contact_nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'knt_contact_' . $page_id ) ) {
		return 'nonce';
	}

	// Reject the honeypot without claiming delivery.
	if ( '' !== trim( knt_contact_post_field( 'website' ) ?? '' ) ) {
		return 'invalid';
	}

	$raw_name = knt_contact_post_field( 'contact_name' );
	$raw_email = knt_contact_post_field( 'contact_email' );
	$raw_message = knt_contact_post_field( 'contact_message' );
	if ( null === $raw_name || null === $raw_email || null === $raw_message ) {
		return 'invalid';
	}
	$name = trim( sanitize_text_field( $raw_name ) );
	$email = trim( $raw_email );
	// Browsers submit CRLF; normalize API retries to the same duplicate identity.
	$message = trim( sanitize_textarea_field( str_replace( array( "\r\n", "\r" ), "\n", $raw_message ) ) );
	$name_length = function_exists( 'mb_strlen' ) ? mb_strlen( $name, 'UTF-8' ) : strlen( $name );
	$message_length = function_exists( 'mb_strlen' ) ? mb_strlen( $message, 'UTF-8' ) : strlen( $message );
	if ( '' === $name || $name_length > 100 || '' === $message || $message_length > 5000 || strlen( $email ) > 254 || ! is_email( $email ) ) {
		return 'invalid';
	}

	$token_field = 'recaptcha' === knt_contact_security_settings()['contact_captcha'] ? 'g-recaptcha-response' : 'cf-turnstile-response';
	$captcha = knt_contact_verify_captcha( knt_contact_post_field( $token_field ) ?? '' );
	if ( is_wp_error( $captcha ) ) { return $captcha->get_error_code(); }
	$duplicate_key = knt_contact_security_key( 'duplicate', strtolower( $email ) . "\n" . $message );
	$duplicate = knt_contact_reserve( $duplicate_key, 1, HOUR_IN_SECONDS );
	if ( is_wp_error( $duplicate ) ) { return $duplicate->get_error_code(); }
	// Conservative budgets count reserved mail attempts, including transport failures.
	foreach ( array( array( 'send-ip', $ip, $settings['contact_ip_limit'] ), array( 'send-email', strtolower( $email ), $settings['contact_email_limit'] ), array( 'send-global', 'all', $settings['contact_global_limit'] ) ) as $budget ) {
		$result = knt_contact_reserve( knt_contact_security_key( $budget[0], $budget[1] ), $budget[2], HOUR_IN_SECONDS );
		if ( is_wp_error( $result ) ) {
			knt_contact_release( $duplicate_key );
			return $result->get_error_code();
		}
	}
	$body = sprintf(
		"A message from the notebook contact page\n\nName: %s\nEmail: %s\n\n%s\n\nPage: %s\n",
		$name,
		$email,
		$message,
		get_permalink( $page_id )
	);
	$sent = wp_mail(
		knt_contact_recipients(),
		__( 'A note from the notebook contact page', 'kamal-notebook-tools' ),
		$body,
		array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $email )
	);
	if ( ! $sent ) {
		knt_contact_release( $duplicate_key );
		return 'failed';
	}

	return 'sent';
}
add_action( 'admin_post_nopriv_knt_contact', 'knt_handle_contact_submission' );
add_action( 'admin_post_knt_contact', 'knt_handle_contact_submission' );
