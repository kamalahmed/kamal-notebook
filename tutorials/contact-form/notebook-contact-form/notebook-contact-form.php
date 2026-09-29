<?php
/**
 * Plugin Name: Notebook Contact Form
 * Description: A small contact form using a shortcode and WordPress mail.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * Author: Kamal Ahmed
 * License: GPL-2.0-or-later
 */
defined( 'ABSPATH' ) || exit;

function kncf_rate_key(): string {
	$address = (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' );
	return 'kncf_' . hash_hmac( 'sha256', $address, wp_salt( 'nonce' ) );
}

function kncf_process( array $input ): string {
	foreach ( [ 'kncf_nonce', 'contact_name', 'contact_email', 'contact_message', 'website' ] as $key ) {
		if ( ! isset( $input[ $key ] ) || ! is_string( $input[ $key ] ) ) {
			return 'invalid';
		}
	}
	if ( ! wp_verify_nonce( $input['kncf_nonce'], 'kncf_contact' ) ) {
		return 'expired';
	}
	if ( '' !== trim( $input['website'] ) ) {
		return 'invalid';
	}
	$name = sanitize_text_field( $input['contact_name'] );
	$email = trim( $input['contact_email'] );
	$message = sanitize_textarea_field( $input['contact_message'] );
	if ( '' === trim( $name ) || '' === trim( $message )
		|| strlen( $input['contact_name'] ) > 100
		|| strlen( $input['contact_email'] ) > 254
		|| strlen( $input['contact_message'] ) > 5000
		|| preg_match( '/[\r\n]/', $email ) || ! is_email( $email ) ) {
		return 'invalid';
	}
	if ( get_transient( kncf_rate_key() ) ) {
		return 'limited';
	}
	$recipient = get_option( 'admin_email' );
	if ( ! is_email( $recipient ) ) {
		return 'failed';
	}
	set_transient( kncf_rate_key(), 1, MINUTE_IN_SECONDS );
	$body = "Name: {$name}\nEmail: {$email}\n\n{$message}";
	$sent = wp_mail(
		$recipient,
		'New website contact message',
		$body,
		[ 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . sanitize_email( $email ) ]
	);
	return $sent ? 'sent' : 'failed';
}

function kncf_handle(): void {
	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		wp_die( 'Method not allowed.', '', [ 'response' => 405 ] );
	}
	$input = wp_unslash( $_POST );
	$page_id = isset( $input['page_id'] ) && is_string( $input['page_id'] ) ? absint( $input['page_id'] ) : 0;
	$page = get_post( $page_id );
	if ( ! $page || 'publish' !== $page->post_status || ! has_shortcode( $page->post_content, 'notebook_contact' ) ) {
		wp_die( 'Contact page not found.', '', [ 'response' => 400 ] );
	}
	$status = kncf_process( $input );
	wp_safe_redirect( add_query_arg( 'contact_status', $status, get_permalink( $page_id ) ) . '#notebook-contact', 303 );
	exit;
}
add_action( 'admin_post_kncf_send', 'kncf_handle' );
add_action( 'admin_post_nopriv_kncf_send', 'kncf_handle' );

function kncf_no_cache(): void {
	if ( is_singular() && has_shortcode( get_post()->post_content, 'notebook_contact' ) ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
	}
}
add_action( 'template_redirect', 'kncf_no_cache' );

function kncf_form(): string {
	$notices = [
		'sent' => 'Your message was accepted for sending. Thank you.',
		'invalid' => 'Please check your details and try again.',
		'expired' => 'The form expired. Refresh this page and try again.',
		'limited' => 'Please wait one minute before sending another message.',
		'failed' => 'The message could not be sent. Please try again later.',
	];
	$status = isset( $_GET['contact_status'] ) && is_string( $_GET['contact_status'] ) ? sanitize_key( wp_unslash( $_GET['contact_status'] ) ) : '';
	ob_start();
	?>
	<div id="notebook-contact">
		<?php if ( isset( $notices[ $status ] ) ) : ?>
			<p role="status"><?php echo esc_html( $notices[ $status ] ); ?></p>
		<?php endif; ?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="kncf_send">
			<input type="hidden" name="page_id" value="<?php echo esc_attr( get_queried_object_id() ); ?>">
			<?php wp_nonce_field( 'kncf_contact', 'kncf_nonce' ); ?>
			<p><label for="kncf-name">Name (required)</label><br><input id="kncf-name" name="contact_name" autocomplete="name" maxlength="100" required></p>
			<p><label for="kncf-email">Email (required)</label><br><input id="kncf-email" name="contact_email" type="email" autocomplete="email" maxlength="254" required></p>
			<p><label for="kncf-message">Message (required)</label><br><textarea id="kncf-message" name="contact_message" rows="6" maxlength="5000" required></textarea></p>
			<div aria-hidden="true" style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden"><label for="kncf-website">Leave this empty</label><input id="kncf-website" name="website" tabindex="-1" autocomplete="off"></div>
			<p>Your name, email and message will be emailed to the site owner so they can reply.</p>
			<button type="submit">Send message</button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'notebook_contact', 'kncf_form' );
