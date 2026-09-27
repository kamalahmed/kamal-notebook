<?php
/**
 * Contact form handling for the theme's Contact page.
 *
 * Bootstrap with: require_once __DIR__ . '/includes/contact.php';
 * The theme stores comma- or newline-separated receivers in
 * kn_settings[contact_email]. An empty setting disables the form.
 */

defined( 'ABSPATH' ) || exit;

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

function knt_handle_contact_submission(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_die( esc_html__( 'This request is unavailable.', 'kamal-notebook-tools' ), '', array( 'response' => 405 ) );
	}

	$page_id = absint( knt_contact_post_field( 'contact_page_id' ) );
	if ( ! knt_contact_page_is_valid( $page_id ) ) {
		wp_die( esc_html__( 'This contact page is unavailable.', 'kamal-notebook-tools' ), '', array( 'response' => 404 ) );
	}
	if ( ! knt_contact_ready() ) {
		knt_contact_redirect( $page_id, 'unavailable' );
	}
	$nonce = knt_contact_post_field( 'knt_contact_nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'knt_contact_' . $page_id ) ) {
		knt_contact_redirect( $page_id, 'nonce' );
	}

	// A filled field is treated as delivered, without sending mail to anyone.
	if ( '' !== trim( knt_contact_post_field( 'website' ) ?? '' ) ) {
		knt_contact_redirect( $page_id, 'sent' );
	}

	$raw_name = knt_contact_post_field( 'contact_name' );
	$raw_email = knt_contact_post_field( 'contact_email' );
	$raw_message = knt_contact_post_field( 'contact_message' );
	if ( null === $raw_name || null === $raw_email || null === $raw_message ) {
		knt_contact_redirect( $page_id, 'invalid' );
	}
	$name = trim( sanitize_text_field( $raw_name ) );
	$email = trim( $raw_email );
	$message = trim( sanitize_textarea_field( $raw_message ) );
	$name_length = function_exists( 'mb_strlen' ) ? mb_strlen( $name, 'UTF-8' ) : strlen( $name );
	$message_length = function_exists( 'mb_strlen' ) ? mb_strlen( $message, 'UTF-8' ) : strlen( $message );
	if ( '' === $name || $name_length > 100 || '' === $message || $message_length > 5000 || strlen( $email ) > 254 || ! is_email( $email ) ) {
		knt_contact_redirect( $page_id, 'invalid' );
	}

	// Hash addresses and IPs before using them as transient keys; store no form text.
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
	$ip = filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	$identity = '' !== $ip ? $ip : $email;
	$rate_key = 'knt_contact_rate_' . substr( hash_hmac( 'sha256', $identity, wp_salt( 'auth' ) ), 0, 32 );
	$email_key = 'knt_contact_email_' . substr( hash_hmac( 'sha256', strtolower( $email ), wp_salt( 'auth' ) ), 0, 32 );
	if ( (int) get_transient( $rate_key ) >= 5 || (int) get_transient( $email_key ) >= 3 ) {
		knt_contact_redirect( $page_id, 'limited' );
	}

	$duplicate_key = 'knt_contact_dup_' . substr( hash_hmac( 'sha256', strtolower( $email ) . "\n" . $message, wp_salt( 'auth' ) ), 0, 32 );
	if ( get_transient( $duplicate_key ) ) {
		knt_contact_redirect( $page_id, 'sent' );
	}

	set_transient( $rate_key, (int) get_transient( $rate_key ) + 1, HOUR_IN_SECONDS );
	set_transient( $email_key, (int) get_transient( $email_key ) + 1, HOUR_IN_SECONDS );
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
		knt_contact_redirect( $page_id, 'failed' );
	}

	set_transient( $duplicate_key, 1, HOUR_IN_SECONDS );
	knt_contact_redirect( $page_id, 'sent' );
}
add_action( 'admin_post_nopriv_knt_contact', 'knt_handle_contact_submission' );
add_action( 'admin_post_knt_contact', 'knt_handle_contact_submission' );
