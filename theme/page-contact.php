<?php
/**
 * Template Name: Contact the Notebook
 *
 * The Notebook Tools plugin processes the form; the theme supplies its view.
 */

get_header();
while ( have_posts() ) :
	the_post();
	$page_id = get_the_ID();
	$ready = function_exists( 'knt_contact_ready' ) && knt_contact_ready();
	$status = isset( $_GET['kn_contact'] ) && is_string( $_GET['kn_contact'] ) ? sanitize_key( wp_unslash( $_GET['kn_contact'] ) ) : '';
	$notices = array(
		'sent'        => __( 'Your note was accepted for sending. Thank you for writing.', 'kamal-notebook' ),
		'invalid'     => __( 'Please check your name, email, and message, then try again.', 'kamal-notebook' ),
		'nonce'       => __( 'This form expired. Please refresh the page and try again.', 'kamal-notebook' ),
		'limited'     => __( 'Please wait a while before sending another note.', 'kamal-notebook' ),
		'failed'      => __( 'The message could not be sent. Please try again later.', 'kamal-notebook' ),
		'captcha'     => __( 'Please complete the human verification and try again.', 'kamal-notebook' ),
		'security'    => __( 'Human verification is temporarily unavailable. Please try again later.', 'kamal-notebook' ),
		'unavailable' => __( 'The contact form is unavailable right now.', 'kamal-notebook' ),
	);
	?>
	<article <?php post_class( 'kn-editorial-page kn-contact-page' ); ?>>
		<header class="kn-page-hero kn-page-hero-compact container">
			<div class="kn-page-hero-copy">
				<h1><?php the_title(); ?></h1>
				<p class="kn-page-deck"><?php esc_html_e( 'Questions about a tutorial, a project, or working together? Send me a message.', 'kamal-notebook' ); ?></p>
			</div>
		</header>
		<div class="kn-page-rule" aria-hidden="true"><span></span><span></span><span></span></div>
		<div class="kn-page-body container">
			<div class="kn-page-content">
				<?php if ( trim( get_the_content() ) ) : ?><div class="article-content kn-contact-intro"><?php the_content(); ?></div><?php endif; ?>
				<div id="contact-response">
					<?php if ( isset( $notices[ $status ] ) && ( $ready || 'unavailable' === $status ) ) : ?>
						<p class="kn-contact-notice <?php echo 'sent' === $status ? 'is-success' : 'is-error'; ?>" role="<?php echo 'sent' === $status ? 'status' : 'alert'; ?>"><?php echo esc_html( $notices[ $status ] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $ready ) : ?>
					<form id="contact-form" class="kn-contact-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
						<input type="hidden" name="action" value="knt_contact">
						<input type="hidden" name="contact_page_id" value="<?php echo esc_attr( $page_id ); ?>">
						<?php wp_nonce_field( 'knt_contact_' . $page_id, 'knt_contact_nonce' ); ?>
						<div class="kn-contact-trap" aria-hidden="true"><label for="kn-contact-website"><?php esc_html_e( 'Leave this field empty', 'kamal-notebook' ); ?></label><input id="kn-contact-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
						<div class="kn-contact-fields">
							<p class="kn-contact-field"><label for="kn-contact-name"><?php esc_html_e( 'Your name', 'kamal-notebook' ); ?> <span aria-hidden="true">*</span></label><input id="kn-contact-name" type="text" name="contact_name" autocomplete="name" maxlength="100" required></p>
							<p class="kn-contact-field"><label for="kn-contact-email"><?php esc_html_e( 'Email address', 'kamal-notebook' ); ?> <span aria-hidden="true">*</span></label><input id="kn-contact-email" type="email" name="contact_email" autocomplete="email" maxlength="254" required></p>
						</div>
						<p class="kn-contact-field kn-contact-message"><label for="kn-contact-message"><?php esc_html_e( 'Your message', 'kamal-notebook' ); ?> <span aria-hidden="true">*</span></label><textarea id="kn-contact-message" name="contact_message" rows="8" maxlength="5000" required></textarea></p>
						<?php if ( function_exists( 'knt_contact_security_fields' ) ) { knt_contact_security_fields(); } ?>
						<div class="kn-contact-submit"><button type="submit"><?php esc_html_e( 'Send your note', 'kamal-notebook' ); ?> <span aria-hidden="true">↗</span></button><p><?php esc_html_e( 'Your email address is included so I can reply.', 'kamal-notebook' ); ?></p></div>
					</form>
				<?php elseif ( 'unavailable' !== $status ) : ?>
					<p class="kn-contact-unavailable"><?php esc_html_e( 'The contact form is unavailable right now.', 'kamal-notebook' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
endwhile;
get_footer();
