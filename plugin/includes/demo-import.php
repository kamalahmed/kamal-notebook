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

/** Three editable lessons for trying a course without changing site settings. */
function knt_demo_series_posts(): array {
	$lessons = array(
		array(
			'key' => 'course-observe', 'title' => 'Demonstration: 1. Start with an observation', 'art' => 'art-journal.svg',
			'excerpt' => 'Turn a vague idea into a concrete question. The first of three sample lessons.',
			'opening' => 'This demonstration course follows one small exercise: making a useful reading list. Each lesson is a separate post with its own address. Use the lesson navigation below to continue.',
			'sections' => array(
				'Choose something small' => 'Imagine you have collected five articles and keep forgetting which one to read next. Begin with that specific situation instead of planning an entire knowledge system.',
				'Write the question' => 'Ask: which unread article can I finish in ten minutes? The question tells us which details the list needs: a title, a reading time, and whether the article has been read.',
				'Try it yourself' => 'Write down three articles you want to read. Give each a rough reading time. In the next lesson, we will represent one article as a small JavaScript object.',
			),
		),
		array(
			'key' => 'course-model', 'title' => 'Demonstration: 2. Make the idea concrete', 'art' => 'art-programming.svg',
			'excerpt' => 'Represent the reading list with a small JavaScript example you can inspect and copy.',
			'opening' => 'This is lesson two of the demonstration course. We are turning a reading-list question into data. The sample below runs in a browser console and does not need an account or a service.',
			'sections' => array(
				'Keep only the useful details' => 'An object groups the details of one article. A list of objects lets us ask the same question about every article. We use minutes as a number and read as a boolean so the conditions stay explicit.',
				'Filter the reading list' => 'The filter keeps articles that are unread and take ten minutes or less. Copy the example, run it in your browser console, and inspect the returned list. Only “A useful question” should remain.',
			),
			'code' => "const articles = [\n  { title: 'A useful question', minutes: 8, read: false },\n  { title: 'A longer exploration', minutes: 18, read: false },\n  { title: 'A familiar idea', minutes: 5, read: true },\n];\n\nconst nextReads = articles.filter(article =>\n  !article.read && article.minutes <= 10\n);\nconsole.log(nextReads);",
		),
		array(
			'key' => 'course-reflect', 'title' => 'Demonstration: 3. Test, notice, revise', 'art' => 'art-questions.svg',
			'excerpt' => 'Check the boundary cases, explain what changed, and choose the next small experiment.',
			'opening' => 'This final demonstration lesson closes the reading-list exercise. A useful experiment ends with a checkable observation and a next step, even when the result is small.',
			'sections' => array(
				'Check the edges' => 'Change the first article to exactly ten minutes: it should still appear. Set read to true: it should disappear. Make every article longer than ten minutes: the result should be an empty list.',
				'Read the result' => 'An empty list is a valid result. It means no article matches the question. A reading interface could explain that plainly and offer a longer time limit, rather than showing a blank space.',
				'Choose the next question' => 'Would you rather sort the matches by time or choose one at random? Pick one change and explain how you would know it works. Return to the previous lesson to try your variation.',
			),
		),
	);
	foreach ( $lessons as &$lesson ) {
		$lesson['content'] = knt_demo_article( $lesson['opening'], $lesson['sections'] );
		if ( isset( $lesson['code'] ) ) {
			$lesson['content'] .= '<!-- wp:kamal-notebook/code ' . wp_json_encode( array( 'language' => 'javascript', 'filename' => 'reading-list.js', 'code' => $lesson['code'] ) ) . ' /-->';
		}
	}
	unset( $lesson );
	// Editable blocks make a small decision diagram, with no external image dependency.
	$lessons[0]['content'] .= '<!-- wp:heading --><h2 class="wp-block-heading">The shape of the exercise</h2><!-- /wp:heading --><!-- wp:table --><figure class="wp-block-table"><table><thead><tr><th>Observe</th><th>Make</th><th>Check</th></tr></thead><tbody><tr><td>Too many saved articles</td><td>A list with reading times</td><td>Unread and ten minutes or less</td></tr></tbody></table><figcaption class="wp-element-caption">One question carried through three lessons.</figcaption></figure><!-- /wp:table -->';
	return $lessons;
}

function knt_import_demo_series(): array {
	$result = array( 'created' => 0, 'skipped' => 0, 'ids' => array() );
	$term = term_exists( 'demonstration-small-learning-project', KNT_SERIES_TAXONOMY );
	if ( ! $term ) {
		$term = wp_insert_term( 'Demonstration: A small learning project', KNT_SERIES_TAXONOMY, array(
			'slug' => 'demonstration-small-learning-project',
			'description' => 'Three demonstration lessons: observe a small problem, make an example, and check the result. Each lesson is an editable post.',
		) );
	}
	if ( is_wp_error( $term ) ) {
		return array_merge( $result, array( 'error' => $term->get_error_message() ) );
	}
	$term_id = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	foreach ( knt_demo_series_posts() as $index => $lesson ) {
		$existing = knt_demo_imported_id( $lesson['key'] );
		if ( $existing ) {
			$result['ids'][] = $existing;
			++$result['skipped'];
			continue;
		}
		$post_id = wp_insert_post( array(
			'post_type' => 'post', 'post_status' => 'publish',
			'post_title' => $lesson['title'], 'post_name' => 'demonstration-' . $lesson['key'],
			'post_excerpt' => $lesson['excerpt'], 'post_content' => $lesson['content'],
			'meta_input' => array( '_knt_demo_key' => $lesson['key'], '_kn_demo_art' => $lesson['art'], KNT_SERIES_ORDER_META => $index + 1 ),
		), true );
		if ( is_wp_error( $post_id ) ) {
			return array_merge( $result, array( 'error' => $post_id->get_error_message() ) );
		}
		$assigned = wp_set_object_terms( $post_id, array( $term_id ), KNT_SERIES_TAXONOMY );
		if ( is_wp_error( $assigned ) ) {
			// Remove only the post just created so a later import can retry cleanly.
			wp_delete_post( $post_id, true );
			return array_merge( $result, array( 'error' => $assigned->get_error_message() ) );
		}
		$result['ids'][] = $post_id;
		++$result['created'];
	}
	$result['series_id'] = $term_id;
	return $result;
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

/** Add editable utility pages only when their slugs are free. */
function knt_demo_add_pages(): void {
	$pages = array(
		'about' => array( 'title' => 'About', 'content' => '<!-- wp:paragraph --><p>This is demonstration content for your About page. Tell readers who you are and what this notebook covers. Replace this text with your own story before publishing a real site.</p><!-- /wp:paragraph -->' ),
		'contact' => array( 'title' => 'Contact', 'content' => '<!-- wp:paragraph --><p>This is a demonstration Contact page. Set receiver addresses under Appearance → Notebook settings to enable the form below.</p><!-- /wp:paragraph -->' ),
	);
	foreach ( $pages as $slug => $page ) {
		if ( get_page_by_path( $slug ) ) {
			continue;
		}
		wp_insert_post( array(
			'post_type' => 'page',
			'post_status' => 'publish',
			'post_name' => $slug,
			'post_title' => $page['title'],
			'post_content' => $page['content'],
			'page_template' => 'page-' . $slug . '.php',
			'meta_input' => array( '_knt_demo_page' => 1 ),
		) );
	}
}

function knt_import_demo(): array {
	$created = 0;
	$skipped = 0;
	$stories = knt_demo_posts();
	$feature_demo = function_exists( 'knt_featured_post' ) && ! knt_featured_post();
	knt_demo_add_pages();
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
			'meta_input' => array( '_knt_demo_key' => $story['key'], '_kn_demo_art' => $story['art'], '_knt_featured' => $feature_demo && 'first-hour' === $story['key'] ? '1' : '0' ),
		), true );
		if ( is_wp_error( $post_id ) ) {
			return array( 'created' => $created, 'skipped' => $skipped, 'error' => $post_id->get_error_message() );
		}
		++$created;
	}
	$series = knt_import_demo_series();
	$created += $series['created'];
	$skipped += $series['skipped'];
	if ( isset( $series['error'] ) ) {
		return array( 'created' => $created, 'skipped' => $skipped, 'error' => $series['error'] );
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
		<p><?php esc_html_e( 'This adds five clearly labeled demonstration stories and a three-lesson demonstration course with editable blocks, category tabs, the original home introduction, and the illustrated grid. If no article is featured yet, one demo post is marked deliberately; you can change that in any post editor.', 'kamal-notebook-tools' ); ?></p>
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
