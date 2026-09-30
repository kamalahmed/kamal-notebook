<?php
/** CAPTCHA and atomic, expiring contact-form budgets. */
defined( 'ABSPATH' ) || exit;

function knt_security_defaults(): array {
	return array(
		'contact_captcha' => 'off', 'contact_turnstile_sitekey' => '', 'contact_recaptcha_sitekey' => '',
		'contact_attempt_limit' => 20, 'contact_attempt_window' => 10,
		'contact_ip_limit' => 5, 'contact_email_limit' => 3, 'contact_global_limit' => 20,
	);
}

function knt_security_sanitize( $input, $previous ): array {
	$input = is_array( $input ) ? $input : array();
	$values = array_merge( knt_security_defaults(), array_intersect_key( (array) $previous, knt_security_defaults() ) );
	foreach ( array( 'contact_captcha' => array( 'off', 'turnstile', 'recaptcha' ) ) as $key => $allowed ) {
		if ( isset( $input[ $key ] ) && in_array( $input[ $key ], $allowed, true ) ) { $values[ $key ] = $input[ $key ]; }
	}
	foreach ( array( 'contact_attempt_limit' => array( 1, 200 ), 'contact_attempt_window' => array( 1, 60 ), 'contact_ip_limit' => array( 1, 100 ), 'contact_email_limit' => array( 1, 50 ), 'contact_global_limit' => array( 1, 500 ) ) as $key => $range ) {
		if ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) && is_numeric( $input[ $key ] ) ) { $values[ $key ] = max( $range[0], min( $range[1], (int) $input[ $key ] ) ); }
	}
	foreach ( array( 'turnstile', 'recaptcha' ) as $provider ) {
		$key = 'contact_' . $provider . '_sitekey';
		if ( isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) {
			$values[ $key ] = substr( preg_replace( '/[^a-zA-Z0-9_-]/', '', $input[ $key ] ), 0, 100 );
		}
		// Separate, non-autoloaded options keep secrets out of settings and HTML.
		$key = 'contact_' . $provider . '_secret';
		if ( current_user_can( 'manage_options' ) && isset( $input[ $key ] ) && is_string( $input[ $key ] ) ) {
			$secret = trim( $input[ $key ] );
			if ( preg_match( '/^[a-zA-Z0-9_-]{10,200}$/', $secret ) ) { update_option( 'knt_' . $key, $secret, false ); }
		}
	}
	return $values;
}

function knt_contact_security_settings(): array {
	$settings = get_option( 'kn_settings', array() );
	// Also bound programmatic option changes at read time; never save a secret here.
	return knt_security_sanitize( array_intersect_key( (array) $settings, knt_security_defaults() ), knt_security_defaults() );
}

/** Read theme-owned credentials only on the server. */
function knt_contact_captcha_credentials(): array {
	$settings = knt_contact_security_settings();
	if ( 'recaptcha' === $settings['contact_captcha'] ) {
		return array( 'sitekey' => $settings['contact_recaptcha_sitekey'], 'secret' => (string) get_option( 'knt_contact_recaptcha_secret', '' ) );
	}
	return array( 'sitekey' => $settings['contact_turnstile_sitekey'], 'secret' => (string) get_option( 'knt_contact_turnstile_secret', '' ) );
}

function knt_security_settings_fields(): void {
	$s = knt_contact_security_settings();
	$credentials = knt_contact_captcha_credentials();
	?>
	<div class="kn-setting kn-setting-card" data-kn-setting="captcha turnstile cloudflare google recaptcha bot spam security">
		<label for="kn-contact-captcha"><?php esc_html_e( 'Contact CAPTCHA', 'kamal-notebook' ); ?></label>
		<select id="kn-contact-captcha" name="kn_settings[contact_captcha]">
			<option value="off" <?php selected( $s['contact_captcha'], 'off' ); ?>><?php esc_html_e( 'Built-in protection (no external service)', 'kamal-notebook' ); ?></option>
			<option value="turnstile" <?php selected( $s['contact_captcha'], 'turnstile' ); ?>><?php esc_html_e( 'Cloudflare Turnstile', 'kamal-notebook' ); ?></option>
			<option value="recaptcha" <?php selected( $s['contact_captcha'], 'recaptcha' ); ?>><?php esc_html_e( 'Google reCAPTCHA v2 (checkbox)', 'kamal-notebook' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Choose built-in protection or add an optional CAPTCHA provider. When a provider is selected, failed or unavailable verification blocks sending.', 'kamal-notebook' ); ?></p>
	</div>
	<div class="kn-setting kn-setting-card" data-kn-setting="built in local bot spam honeypot duplicate rate privacy security">
		<strong><?php esc_html_e( 'Built-in protection is always on', 'kamal-notebook' ); ?></strong>
		<p class="description"><?php esc_html_e( 'The theme checks a hidden honeypot, validates submissions, suppresses duplicates, and limits attempts by IP, email, and across the site. This is the default and needs no external account, script, or service. Optional CAPTCHA adds a human-verification step; built-in checks alone cannot stop every sophisticated bot.', 'kamal-notebook' ); ?></p>
		<?php if ( 'off' !== $s['contact_captcha'] ) : ?><p class="description"><?php echo esc_html( '' !== $credentials['sitekey'] && '' !== $credentials['secret'] ? __( 'Keys are configured for the saved provider.', 'kamal-notebook' ) : __( 'The saved provider needs both a site key and a secret key before messages can be sent.', 'kamal-notebook' ) ); ?></p><?php endif; ?>
	</div>
	<div class="kn-setting kn-setting-card" data-kn-setting="captcha turnstile custom site key hostname domain">
		<label for="kn-contact-sitekey"><?php esc_html_e( 'Turnstile site key', 'kamal-notebook' ); ?></label>
		<input id="kn-contact-sitekey" name="kn_settings[contact_turnstile_sitekey]" value="<?php echo esc_attr( $s['contact_turnstile_sitekey'] ); ?>" type="text" autocomplete="off" maxlength="100">
		<p class="description"><?php esc_html_e( 'Your Cloudflare widget must allow this site’s hostname. Used only when Turnstile is selected.', 'kamal-notebook' ); ?></p>
	</div>
	<div class="kn-setting kn-setting-card" data-kn-setting="captcha turnstile custom secret key password">
		<label for="kn-contact-secret"><?php esc_html_e( 'Turnstile secret key', 'kamal-notebook' ); ?></label>
		<input id="kn-contact-secret" name="kn_settings[contact_turnstile_secret]" value="" type="password" autocomplete="new-password" maxlength="200">
		<p class="description"><?php esc_html_e( 'Leave blank to keep the stored secret. The stored value is never displayed.', 'kamal-notebook' ); ?></p>
	</div>
	<div class="kn-setting kn-setting-card" data-kn-setting="captcha cloudflare turnstile setup documentation">
		<strong><?php esc_html_e( 'Set up Cloudflare Turnstile', 'kamal-notebook' ); ?></strong>
		<p class="description"><?php esc_html_e( 'Create a Managed widget for your site hostname, then enter its site and secret keys above. Select Turnstile, save, and test your Contact page. Cloudflare DNS is not required.', 'kamal-notebook' ); ?></p>
		<a href="https://developers.cloudflare.com/turnstile/get-started/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Turnstile setup documentation ↗', 'kamal-notebook' ); ?></a>
	</div>
	<div class="kn-setting kn-setting-card" data-kn-setting="captcha google recaptcha site key setup">
		<label for="kn-recaptcha-sitekey"><?php esc_html_e( 'Google reCAPTCHA v2 site key', 'kamal-notebook' ); ?></label>
		<input id="kn-recaptcha-sitekey" name="kn_settings[contact_recaptcha_sitekey]" value="<?php echo esc_attr( $s['contact_recaptcha_sitekey'] ); ?>" type="text" autocomplete="off" maxlength="100">
		<p class="description"><?php esc_html_e( 'Use a v2 “I’m not a robot” checkbox key for this hostname. Google uses its own separate keys; v3 keys are not compatible.', 'kamal-notebook' ); ?></p>
		<a href="https://developers.google.com/recaptcha/docs/display" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google reCAPTCHA documentation ↗', 'kamal-notebook' ); ?></a>
	</div>
	<div class="kn-setting kn-setting-card" data-kn-setting="captcha google recaptcha secret key password">
		<label for="kn-recaptcha-secret"><?php esc_html_e( 'Google reCAPTCHA v2 secret key', 'kamal-notebook' ); ?></label>
		<input id="kn-recaptcha-secret" name="kn_settings[contact_recaptcha_secret]" value="" type="password" autocomplete="new-password" maxlength="200">
		<p class="description"><?php esc_html_e( 'Leave blank to keep the stored secret. The stored value is never displayed.', 'kamal-notebook' ); ?></p>
	</div>
	<?php
	$fields = array(
		'contact_attempt_limit' => array( __( 'Attempts per IP', 'kamal-notebook' ), 200, __( 'Includes rejected submissions; checked before contacting the CAPTCHA provider.', 'kamal-notebook' ) ),
		'contact_attempt_window' => array( __( 'Attempt window (minutes)', 'kamal-notebook' ), 60, __( 'A window begins at the first attempt and resets after this duration.', 'kamal-notebook' ) ),
		'contact_ip_limit' => array( __( 'Send attempts per IP per hour', 'kamal-notebook' ), 100, __( 'Shared networks share this budget.', 'kamal-notebook' ) ),
		'contact_email_limit' => array( __( 'Send attempts per email per hour', 'kamal-notebook' ), 50, __( 'Stops the same sender address from flooding the inbox.', 'kamal-notebook' ) ),
		'contact_global_limit' => array( __( 'Send attempts across the site per hour', 'kamal-notebook' ), 500, __( 'Overall contact-mail ceiling, including unsuccessful mail attempts. Rate protection remains active with CAPTCHA off.', 'kamal-notebook' ) ),
	);
	foreach ( $fields as $key => $field ) {
		printf( '<div class="kn-setting kn-setting-card" data-kn-setting="%1$s flood rate limit security"><label for="kn-%2$s">%3$s</label><input id="kn-%2$s" name="kn_settings[%2$s]" type="number" min="1" max="%4$d" step="1" value="%5$d"><p class="description">%6$s</p></div>', esc_attr( $field[0] ), esc_attr( $key ), esc_html( $field[0] ), $field[1], $s[ $key ], esc_html( $field[2] ) );
	}
}

/** Render only the selected optional provider in the built-in contact form. */
function knt_contact_security_fields(): void {
	$provider = knt_contact_security_settings()['contact_captcha'];
	if ( 'off' === $provider ) { return; }
	$credentials = knt_contact_captcha_credentials();
	if ( '' === $credentials['sitekey'] || '' === $credentials['secret'] ) {
		echo '<p role="status">' . esc_html__( 'Contact verification is temporarily unavailable. Please try again later.', 'kamal-notebook' ) . '</p>';
		return;
	}
	if ( 'recaptcha' === $provider ) {
		wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=explicit', array(), null, array( 'strategy' => 'defer', 'in_footer' => true ) );
		wp_enqueue_script( 'knt-recaptcha', knt_asset_url( 'recaptcha.js' ), array( 'google-recaptcha' ), KNT_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
		printf( '<div data-knt-recaptcha data-sitekey="%s" data-error="%s" data-expired="%s"></div><p data-knt-captcha-status role="status" aria-live="polite"></p>', esc_attr( $credentials['sitekey'] ), esc_attr__( 'Verification could not load. Please reload the page and try again.', 'kamal-notebook' ), esc_attr__( 'Verification expired. Please complete the checkbox again.', 'kamal-notebook' ) );
	} else {
		wp_enqueue_script( 'cloudflare-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, array( 'strategy' => 'defer', 'in_footer' => true ) );
		printf( '<div class="cf-turnstile" data-sitekey="%s" data-action="notebook_contact" data-size="flexible" data-theme="auto"></div>', esc_attr( $credentials['sitekey'] ) );
	}
	echo '<noscript><p>' . esc_html__( 'Enable JavaScript to complete contact verification.', 'kamal-notebook' ) . '</p></noscript>';
}

/** Create the rate-limit table on first use, including theme upgrades. */
function knt_contact_security_install(): void {
	if ( '1' === get_option( 'knt_contact_security_schema' ) ) { return; }
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table = $wpdb->prefix . 'knt_contact_limits';
	dbDelta( "CREATE TABLE $table (
		bucket char(64) NOT NULL,
		hits bigint unsigned NOT NULL DEFAULT 0,
		expires bigint unsigned NOT NULL,
		PRIMARY KEY  (bucket),
		KEY expires (expires)
	) " . $wpdb->get_charset_collate() . ';' );
	if ( $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) { update_option( 'knt_contact_security_schema', '1', false ); }
}
add_action( 'init', 'knt_contact_security_install', 1 );

function knt_contact_security_cleanup(): void {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}knt_contact_limits WHERE expires <= %d LIMIT 10000", time() ) );
}
add_action( 'knt_contact_security_cleanup', 'knt_contact_security_cleanup' );
function knt_contact_security_schedule(): void {
	if ( ! wp_next_scheduled( 'knt_contact_security_cleanup' ) ) { wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'knt_contact_security_cleanup' ); }
}
add_action( 'init', 'knt_contact_security_schedule', 2 );

function knt_contact_security_key( string $scope, string $identity ): string {
	return hash_hmac( 'sha256', $scope . ':' . $identity, wp_salt( 'auth' ) );
}

function knt_contact_remote_ip(): string {
	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	if ( ! is_string( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) { return ''; }
	// Canonical IPv6 spelling prevents alternate representations getting new budgets.
	return inet_ntop( inet_pton( $ip ) );
}

/** Each accepted slot is claimed in one conditional UPDATE, safe across PHP workers. */
function knt_contact_reserve( string $key, int $limit, int $duration ) {
	global $wpdb;
	$table = $wpdb->prefix . 'knt_contact_limits';
	$now = time();
	$insert = $wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $table (bucket,hits,expires) VALUES (%s,0,%d)", $key, $now + $duration ) );
	if ( false === $insert ) { return new WP_Error( 'security' ); }
	$result = $wpdb->query( $wpdb->prepare( "UPDATE $table SET hits = IF(expires <= %d, 1, hits + 1), expires = IF(expires <= %d, %d, expires) WHERE bucket = %s AND (expires <= %d OR hits < %d)", $now, $now, $now + $duration, $key, $now, $limit ) );
	return 1 === $result ? true : new WP_Error( false === $result ? 'security' : 'limited' );
}

function knt_contact_release( string $key ): void {
	global $wpdb;
	$wpdb->delete( $wpdb->prefix . 'knt_contact_limits', array( 'bucket' => $key ), array( '%s' ) );
}

function knt_contact_verify_captcha( string $token ) {
	$provider = knt_contact_security_settings()['contact_captcha'];
	if ( 'off' === $provider ) { return true; }
	$credentials = knt_contact_captcha_credentials();
	if ( '' === $credentials['sitekey'] || '' === $credentials['secret'] ) { return new WP_Error( 'security' ); }
	if ( '' === trim( $token ) || strlen( $token ) > 2048 ) { return new WP_Error( 'captcha' ); }
	// Reserve before Siteverify so simultaneous replay cannot start two remote calls.
	$reserved = knt_contact_reserve( knt_contact_security_key( 'token', $provider . ':' . $token ), 1, 5 * MINUTE_IN_SECONDS );
	if ( is_wp_error( $reserved ) ) { return new WP_Error( 'security' === $reserved->get_error_code() ? 'security' : 'captcha' ); }
	$endpoint = 'recaptcha' === $provider ? 'https://www.google.com/recaptcha/api/siteverify' : 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
	$response = wp_remote_post( $endpoint, array(
		'timeout' => 8, 'redirection' => 0, 'limit_response_size' => 16384,
		'body' => array( 'secret' => $credentials['secret'], 'response' => $token, 'remoteip' => knt_contact_remote_ip() ),
	) );
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return new WP_Error( 'security' ); }
	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! is_array( $body ) || true !== ( $body['success'] ?? false ) || ! is_string( $host ) || '' === $host || $host !== ( $body['hostname'] ?? '' ) || ( 'turnstile' === $provider && 'notebook_contact' !== ( $body['action'] ?? '' ) ) ) { return new WP_Error( 'captcha' ); }
	return true;
}
