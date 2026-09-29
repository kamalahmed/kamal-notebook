<?php
/** A browser-only practice form. It never sends mail or stores visitor input. */
defined( 'ABSPATH' ) || exit;

function knt_contact_lab_assets(): void {
	if ( is_singular() && has_shortcode( get_post()->post_content, 'notebook_contact_lab' ) ) {
		wp_enqueue_style( 'knt-contact-lab', plugins_url( 'assets/contact-lab.css', dirname( __FILE__ ) ), array(), KNT_VERSION );
		wp_enqueue_script( 'knt-contact-lab', plugins_url( 'assets/contact-lab.js', dirname( __FILE__ ) ), array(), KNT_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
add_action( 'wp_enqueue_scripts', 'knt_contact_lab_assets' );

function knt_contact_lab(): string {
	$id = wp_unique_id( 'contact-lab-' );
	ob_start();
	?>
	<section class="knt-contact-lab" data-contact-lab aria-label="Practice contact form">
		<p class="knt-lab-label">PRACTICE FORM · NOTHING IS SENT</p>
		<p>Try an empty field or an invalid email, then complete the form. Use made-up details: this example only checks your input in this browser.</p>
		<div class="knt-lab-fields">
			<label for="<?php echo esc_attr( $id ); ?>name">Practice name<input id="<?php echo esc_attr( $id ); ?>name" data-name maxlength="100" autocomplete="off" required></label>
			<label for="<?php echo esc_attr( $id ); ?>email">Practice email<input id="<?php echo esc_attr( $id ); ?>email" data-email type="email" maxlength="254" autocomplete="off" required></label>
			<label class="knt-lab-message" for="<?php echo esc_attr( $id ); ?>message">Practice message<textarea id="<?php echo esc_attr( $id ); ?>message" data-message rows="4" maxlength="5000" required></textarea></label>
		</div>
		<div class="knt-lab-actions"><button type="button" data-check>Check this form</button><button type="button" data-example>Fill an example</button><button type="button" data-reset>Reset</button></div>
		<p data-result role="status" aria-live="polite">Ready to try. No message has been sent.</p>
		<noscript><p>Enable JavaScript to try the practice form. The WordPress plugin below works without JavaScript.</p></noscript>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'notebook_contact_lab', 'knt_contact_lab' );
