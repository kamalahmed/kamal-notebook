<?php
/** A clearly labeled, repeatable starter site for fresh Notebook installs. */

defined( 'ABSPATH' ) || exit;

function knt_demo_article( string $opening, array $sections, bool $with_code = false ): string {
	$content = '<!-- wp:paragraph --><p>' . esc_html( $opening ) . '</p><!-- /wp:paragraph -->';
	foreach ( $sections as $heading => $paragraph ) {
		$content .= '<!-- wp:heading --><h2 class="wp-block-heading">' . esc_html( $heading ) . '</h2><!-- /wp:heading -->';
		$content .= '<!-- wp:paragraph --><p>' . esc_html( $paragraph ) . '</p><!-- /wp:paragraph -->';
	}
	if ( $with_code ) {
		$content .= '<!-- wp:kamal-notebook/code ' . wp_json_encode( array(
			'language' => 'javascript',
			'filename' => 'example.js',
			'code' => "const questions = ['What changed?', 'What remains unclear?'];\nquestions.forEach(console.log);",
		) ) . ' /-->';
	}
	return $content;
}

function knt_demo_posts(): array {
	return array(
		array(
			'key' => 'working-notebook',
			'title' => 'Demonstration: What I keep in a working notebook',
			'excerpt' => 'A sample personal note showing how small observations can become useful stories.',
			'category' => 'Life',
			'art' => 'art-journal.svg',
			'content' => knt_demo_article( 'This is demonstration content. Replace it with your own observation, question, and point of view.', array(
				'Begin with the moment' => 'A useful notebook entry starts with a detail a reader can picture. Describe what you noticed before explaining what it means.',
				'Keep the question open' => 'Show the thought that followed. A short story does not need to resolve every question to feel complete.',
			) ),
		),
		array(
			'key' => 'better-questions',
			'title' => 'Demonstration: A small guide to better questions',
			'excerpt' => 'An example of a practical guide with clear sections and a short exercise.',
			'category' => 'New Tools',
			'art' => 'art-questions.svg',
			'content' => knt_demo_article( 'This demonstration article shows how a short guide can move from a problem to a practical next step.', array(
				'Name the problem' => 'Tell the reader what makes the task difficult in ordinary terms. A concrete example is more helpful than a broad promise.',
				'Try a smaller question' => 'Ask what information would change the decision. Then give the reader a manageable way to test it.',
			) ),
		),
		array(
			'key' => 'design-for-reading',
			'title' => 'Demonstration: Design a page worth reading',
			'excerpt' => 'A sample editorial piece using the theme’s generous type and section rhythm.',
			'category' => 'Design',
			'art' => 'art-tech.svg',
			'content' => knt_demo_article( 'This is sample content for seeing the Notebook article layout. Replace every example with your own work before publishing a real site.', array(
				'Start with a clear line' => 'Let the title, opening, and illustration make the subject easy to recognize. Use the first section to give the reader their bearings.',
				'Give ideas room' => 'Use headings, short paragraphs, images, and captions to make a long article comfortable to follow.',
			) ),
		),
		array(
			'key' => 'plugin-boundary',
			'title' => 'Demonstration: Where should a WordPress plugin boundary be?',
			'excerpt' => 'A technical sample with an editable, highlighted JavaScript example.',
			'category' => 'Programming',
			'art' => 'art-programming.svg',
			'content' => knt_demo_article( 'This demonstration walks through a small software design question. It is an example of the writing format, not a report of a real project.', array(
				'The question' => 'Consider which behavior a site should keep when its appearance changes. Write the decision in a sentence before looking at code.',
				'An example in code' => 'Add the Notebook Code block for real code. Choose a language and filename in the block toolbar; the reader sees syntax colors and a copy control.',
			), true ),
		),
		array(
			'key' => 'first-hour',
			'title' => 'Demonstration: The first hour with a new tool',
			'excerpt' => 'A sample featured essay showing the opening, outline, and closing of a Notebook story.',
			'category' => 'Technology',
			'art' => 'art-ai.svg',
			'content' => knt_demo_article( 'This is demonstration content for the featured story. It shows the visual structure of an article and makes no claim about a real test or outcome.', array(
				'The first question' => 'Begin by saying what you wanted to understand. Keep the first example narrow enough that another person can follow it.',
				'What to look for' => 'Separate what you observed from what you expected. A screenshot or short code example can help the reader inspect the same detail.',
				'Your next step' => 'Close with one practical question that a reader could explore in their own setting.',
			) ),
		),
	);
}

function knt_demo_imported_id( string $key ): int {
	$posts = get_posts( array(
		'post_type' => 'post',
		'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ),
		'posts_per_page' => 1,
		'fields' => 'ids',
		'meta_key' => '_knt_demo_key',
		'meta_value' => $key,
	) );
	return $posts ? (int) $posts[0] : 0;
}

function knt_import_demo(): array {
	$created = 0;
	$skipped = 0;
	$stories = knt_demo_posts();
	foreach ( $stories as $index => $story ) {
		if ( knt_demo_imported_id( $story['key'] ) ) {
			++$skipped;
			continue;
		}
		$term = term_exists( $story['category'], 'category' );
		if ( ! $term ) {
			$term = wp_insert_term( $story['category'], 'category' );
		}
		if ( is_wp_error( $term ) ) {
			return array( 'created' => $created, 'skipped' => $skipped, 'error' => $term->get_error_message() );
		}
		$post_id = wp_insert_post( array(
			'post_type' => 'post',
			'post_status' => 'publish',
			'post_title' => $story['title'],
			'post_excerpt' => $story['excerpt'],
			'post_content' => $story['content'],
			'post_category' => array( (int) ( is_array( $term ) ? $term['term_id'] : $term ) ),
			'post_date' => wp_date( 'Y-m-d H:i:s', time() - ( count( $stories ) - $index ) * DAY_IN_SECONDS ),
			'meta_input' => array( '_knt_demo_key' => $story['key'], '_kn_demo_art' => $story['art'] ),
		), true );
		if ( is_wp_error( $post_id ) ) {
			return array( 'created' => $created, 'skipped' => $skipped, 'error' => $post_id->get_error_message() );
		}
		++$created;
	}
	if ( ! get_option( 'knt_demo_setup_applied' ) ) {
		update_option( 'knt_demo_previous_options', array(
			'kn_settings' => get_option( 'kn_settings', false ),
			'show_on_front' => get_option( 'show_on_front' ),
			'page_for_posts' => get_option( 'page_for_posts' ),
		) );
		if ( function_exists( 'kn_defaults' ) ) {
			update_option( 'kn_settings', kn_defaults() );
		}
		update_option( 'show_on_front', 'posts' );
		update_option( 'page_for_posts', 0 );
		update_option( 'knt_demo_setup_applied', 1 );
	}
	return array( 'created' => $created, 'skipped' => $skipped );
}

function knt_demo_menu(): void {
	if ( get_stylesheet() === 'kamal-notebook' ) {
		add_theme_page( __( 'Import Notebook demo', 'kamal-notebook-tools' ), __( 'Import Notebook demo', 'kamal-notebook-tools' ), 'manage_options', 'knt-demo-import', 'knt_demo_page' );
	}
}
add_action( 'admin_menu', 'knt_demo_menu' );

function knt_demo_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$published = (int) wp_count_posts( 'post' )->publish;
	?>
	<div class="wrap kn-admin">
		<h1><?php esc_html_e( 'Import the Notebook demo', 'kamal-notebook-tools' ); ?></h1>
		<?php if ( isset( $_GET['knt_created'] ) ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( sprintf( __( '%1$d demo stories added; %2$d already present.', 'kamal-notebook-tools' ), absint( $_GET['knt_created'] ), absint( $_GET['knt_skipped'] ?? 0 ) ) ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['knt_error'] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['knt_error'] ) ) ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'This adds five clearly labeled demonstration posts with editable blocks, category tabs, the original home introduction, and the illustrated grid. On a fresh site, the newest demo post becomes the featured story.', 'kamal-notebook-tools' ); ?></p>
		<p><?php esc_html_e( 'The first import also sets the homepage to latest posts and resets Notebook settings to the prototype defaults. Later imports leave your settings alone. Your site name, logo, existing posts, and media stay as they are. The closest match is an otherwise empty WordPress site.', 'kamal-notebook-tools' ); ?></p>
		<?php if ( $published ) : ?>
			<p><strong><?php echo esc_html( sprintf( _n( 'This site already has %d published post. Demo posts will appear alongside it.', 'This site already has %d published posts. Demo posts will appear alongside them.', $published, 'kamal-notebook-tools' ), $published ) ); ?></strong></p>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="knt_import_demo">
			<?php wp_nonce_field( 'knt_import_demo' ); ?>
			<?php if ( $published ) : ?>
				<p><label><input type="checkbox" name="knt_existing_ok" value="1" required> <?php esc_html_e( 'I understand the demo will join my existing posts.', 'kamal-notebook-tools' ); ?></label></p>
			<?php endif; ?>
			<?php submit_button( __( 'Import demo', 'kamal-notebook-tools' ) ); ?>
		</form>
	</div>
	<?php
}

function knt_demo_import_action(): void {
	if ( ! current_user_can( 'manage_options' ) || get_stylesheet() !== 'kamal-notebook' ) {
		wp_die( esc_html__( 'You cannot import this demo.', 'kamal-notebook-tools' ), '', array( 'response' => 403 ) );
	}
	check_admin_referer( 'knt_import_demo' );
	if ( (int) wp_count_posts( 'post' )->publish && empty( $_POST['knt_existing_ok'] ) ) {
		wp_die( esc_html__( 'Confirm that existing posts may appear beside the demo.', 'kamal-notebook-tools' ), '', array( 'response' => 400 ) );
	}
	$result = knt_import_demo();
	$url = admin_url( 'themes.php?page=knt-demo-import' );
	$url = isset( $result['error'] )
		? add_query_arg( 'knt_error', $result['error'], $url )
		: add_query_arg( array( 'knt_created' => $result['created'], 'knt_skipped' => $result['skipped'] ), $url );
	wp_safe_redirect( $url );
	exit;
}
add_action( 'admin_post_knt_import_demo', 'knt_demo_import_action' );
