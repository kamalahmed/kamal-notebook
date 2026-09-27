<?php
/**
 * Plugin Name: Kamal Notebook Tools
 * Description: An editable code block and simple reader feedback for Kamal Notebook.
 * Version: 1.3.0
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * Author: Kamal Ahmed
 * Text Domain: kamal-notebook-tools
 */

defined( 'ABSPATH' ) || exit;

const KNT_VERSION = '1.3.0';
require_once __DIR__ . '/includes/demo-import.php';
require_once __DIR__ . '/includes/featured.php';
require_once __DIR__ . '/includes/contact.php';
require_once __DIR__ . '/includes/series.php';
require_once __DIR__ . '/includes/cover-studio.php';

function knt_register_code_block(): void {
	wp_register_script( 'knt-codemirror-python', plugins_url( 'assets/python-mode.js', __FILE__ ), array( 'wp-codemirror' ), KNT_VERSION, true );
	wp_register_script( 'knt-code-editor', plugins_url( 'assets/code-editor.js', __FILE__ ), array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-i18n', 'code-editor', 'knt-codemirror-python' ), KNT_VERSION, true );
	wp_register_style( 'knt-code-editor', plugins_url( 'assets/code-editor.css', __FILE__ ), array(), KNT_VERSION );
	wp_register_script( 'knt-code-view', plugins_url( 'assets/code-view.js', __FILE__ ), array(), KNT_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	register_block_type( __DIR__ . '/blocks/code', array( 'render_callback' => 'knt_render_code' ) );
}
add_action( 'init', 'knt_register_code_block' );

function knt_register_code_pattern(): void {
	$example = array(
		'language' => 'javascript',
		'filename' => 'example.js',
		'code'     => "const greeting = 'Hello, notebook';\nconsole.log(greeting);",
	);
	register_block_pattern( 'kamal-notebook/highlighted-code', array(
		'title'       => __( 'Highlighted code example', 'kamal-notebook-tools' ),
		'description' => __( 'A working, editable code sample with language colors and a copy button.', 'kamal-notebook-tools' ),
		'categories'  => array( 'kn-writing', 'text' ),
		'keywords'    => array( 'code', 'highlight', 'syntax', 'tutorial' ),
		'content'     => '<!-- wp:kamal-notebook/code ' . wp_json_encode( $example ) . ' /-->',
	) );
}
add_action( 'init', 'knt_register_code_pattern', 20 );

function knt_editor_assets(): void {
	wp_enqueue_code_editor( array( 'type' => 'text/javascript', 'codemirror' => array( 'lint' => false, 'lineNumbers' => true, 'lineWrapping' => false ) ) );
}
add_action( 'enqueue_block_editor_assets', 'knt_editor_assets' );

function knt_canvas_styles(): void {
	if ( is_admin() ) {
		wp_enqueue_style( 'wp-codemirror' );
	}
}
add_action( 'enqueue_block_assets', 'knt_canvas_styles' );

function knt_render_code( array $attributes ): string {
	$allowed = array( 'javascript', 'css', 'markup', 'json', 'php', 'bash', 'python', 'plain' );
	$language = in_array( $attributes['language'] ?? '', $allowed, true ) ? $attributes['language'] : 'plain';
	$filename = sanitize_text_field( $attributes['filename'] ?? '' );
	$code = (string) ( $attributes['code'] ?? '' );
	if ( '' === trim( $code ) ) {
		return '';
	}
	wp_enqueue_script( 'knt-code-view' );
	return sprintf(
		'<div class="kn-code"><div class="kn-code-head"><span>%s</span><button type="button" data-copy-code>%s</button></div><pre tabindex="0"><code class="language-%s">%s</code></pre></div>',
		esc_html( $filename ?: strtoupper( $language ) ),
		esc_html__( 'Copy code', 'kamal-notebook-tools' ),
		esc_attr( $language ),
		esc_html( $code )
	);
}

function knt_feedback_route(): void {
	register_rest_route( 'kamal-notebook/v1', '/feedback', array(
		'methods'             => 'POST',
		'callback'            => 'knt_submit_feedback',
		'permission_callback' => '__return_true',
		'args'                => array(
			'post_id' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
			'rating'  => array( 'required' => true, 'type' => 'string', 'enum' => array( 'useful', 'not_yet' ) ),
		),
	) );
}
add_action( 'rest_api_init', 'knt_feedback_route' );

function knt_submit_feedback( WP_REST_Request $request ) {
	$post_id = (int) $request['post_id'];
	$rating = $request['rating'];
	if ( 'post' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
		return new WP_Error( 'invalid_post', __( 'This story is unavailable.', 'kamal-notebook-tools' ), array( 'status' => 404 ) );
	}
	if ( ! empty( $request['website'] ) ) {
		return new WP_Error( 'invalid_feedback', __( 'Please try again.', 'kamal-notebook-tools' ), array( 'status' => 400 ) );
	}
	$visitor = isset( $_COOKIE['knt_visitor'] ) && preg_match( '/^[a-f0-9]{32}$/', $_COOKIE['knt_visitor'] )
		? $_COOKIE['knt_visitor']
		: bin2hex( random_bytes( 16 ) );
	if ( ! isset( $_COOKIE['knt_visitor'] ) ) {
		setcookie( 'knt_visitor', $visitor, array( 'expires' => time() + YEAR_IN_SECONDS, 'path' => COOKIEPATH ?: '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
	}
	$vote_key = 'knt_vote_' . substr( hash_hmac( 'sha256', $post_id . ':' . $visitor, wp_salt() ), 0, 32 );
	$previous = get_transient( $vote_key );
	if ( $previous === $rating ) {
		return rest_ensure_response( array( 'rating' => $rating, 'message' => __( 'Your response is already recorded.', 'kamal-notebook-tools' ) ) );
	}
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$rate_key = 'knt_rate_' . substr( hash_hmac( 'sha256', $ip, wp_salt() ), 0, 32 );
	$rate = (int) get_transient( $rate_key );
	if ( $rate >= 25 ) {
		return new WP_Error( 'feedback_limit', __( 'Feedback is temporarily unavailable. Please try later.', 'kamal-notebook-tools' ), array( 'status' => 429 ) );
	}
	set_transient( $rate_key, $rate + 1, DAY_IN_SECONDS );
	if ( $previous ) {
		$old_key = '_knt_feedback_' . $previous;
		update_post_meta( $post_id, $old_key, max( 0, (int) get_post_meta( $post_id, $old_key, true ) - 1 ) );
	}
	$new_key = '_knt_feedback_' . $rating;
	update_post_meta( $post_id, $new_key, (int) get_post_meta( $post_id, $new_key, true ) + 1 );
	set_transient( $vote_key, $rating, YEAR_IN_SECONDS );
	return rest_ensure_response( array( 'rating' => $rating, 'message' => __( 'Thank you. Your response will help shape future notes.', 'kamal-notebook-tools' ) ) );
}

function knt_render_feedback( int $post_id ): void {
	if ( function_exists( 'kn_option' ) && '1' !== kn_option( 'feedback_enabled' ) ) {
		return;
	}
	wp_enqueue_script( 'knt-feedback', plugins_url( 'assets/feedback.js', __FILE__ ), array(), KNT_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	$config = array( 'endpoint' => esc_url_raw( rest_url( 'kamal-notebook/v1/feedback' ) ) );
	wp_add_inline_script( 'knt-feedback', 'window.kntFeedback = ' . wp_json_encode( $config ) . ';', 'before' );
	?>
	<section class="feedback" data-feedback data-post-id="<?php echo esc_attr( $post_id ); ?>" aria-labelledby="feedback-title">
		<h2 id="feedback-title"><?php esc_html_e( 'Was this useful?', 'kamal-notebook-tools' ); ?></h2>
		<div class="feedback-buttons">
			<button class="feedback-button" type="button" data-rating="useful" aria-pressed="false"><?php esc_html_e( 'Yes, useful', 'kamal-notebook-tools' ); ?></button>
			<button class="feedback-button" type="button" data-rating="not_yet" aria-pressed="false"><?php esc_html_e( 'Not yet', 'kamal-notebook-tools' ); ?></button>
		</div>
		<p class="feedback-status" role="status" data-feedback-status></p>
		<small><?php esc_html_e( 'Private feedback to the writer. You can change your answer.', 'kamal-notebook-tools' ); ?></small>
	</section>
	<?php
}
add_action( 'kn_after_article_tools', 'knt_render_feedback' );

function knt_feedback_meta_box(): void {
	add_meta_box( 'knt-feedback', __( 'Reader feedback', 'kamal-notebook-tools' ), 'knt_feedback_meta_box_content', 'post', 'side' );
}
add_action( 'add_meta_boxes', 'knt_feedback_meta_box' );

function knt_feedback_meta_box_content( WP_Post $post ): void {
	printf( '<p><strong>%s:</strong> %d</p><p><strong>%s:</strong> %d</p><p>%s</p>', esc_html__( 'Useful', 'kamal-notebook-tools' ), (int) get_post_meta( $post->ID, '_knt_feedback_useful', true ), esc_html__( 'Not yet', 'kamal-notebook-tools' ), (int) get_post_meta( $post->ID, '_knt_feedback_not_yet', true ), esc_html__( 'Anonymous responses only. Counts are never shown publicly.', 'kamal-notebook-tools' ) );
}
