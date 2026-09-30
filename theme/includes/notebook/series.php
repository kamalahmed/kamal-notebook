<?php
/** Ordered, independently published posts grouped into a course or series. */

defined( 'ABSPATH' ) || exit;

const KNT_SERIES_TAXONOMY = 'knt_series';
const KNT_SERIES_ORDER_META = '_knt_series_order';

function knt_register_series(): void {
	register_taxonomy( KNT_SERIES_TAXONOMY, 'post', array(
		'labels' => array(
			'name'          => __( 'Series', 'kamal-notebook' ),
			'singular_name' => __( 'Series', 'kamal-notebook' ),
			'add_new_item'  => __( 'Add new series', 'kamal-notebook' ),
			'edit_item'     => __( 'Edit series', 'kamal-notebook' ),
		),
		'public'            => true,
		'show_ui'           => true,
		'show_in_rest'      => false, // One series per post is managed by the metabox below.
		'show_admin_column' => true,
		'meta_box_cb'       => false,
		'hierarchical'      => false,
		'rewrite'           => array( 'slug' => 'series' ),
	) );
	register_post_meta( 'post', KNT_SERIES_ORDER_META, array(
		'type'              => 'integer',
		'single'            => true,
		'show_in_rest'      => false,
		'auth_callback'     => static fn() => current_user_can( 'edit_posts' ),
		'sanitize_callback' => static fn( $value ) => max( 1, min( 9999, (int) $value ) ),
	) );
}
add_action( 'init', 'knt_register_series' );

function knt_series_metabox(): void {
	add_meta_box( 'knt-series', __( 'Course / series lesson', 'kamal-notebook' ), 'knt_series_metabox_content', 'post', 'side', 'default' );
}
add_action( 'add_meta_boxes', 'knt_series_metabox' );

function knt_series_metabox_content( WP_Post $post ): void {
	wp_nonce_field( 'knt_save_series', 'knt_series_nonce' );
	$terms = get_terms( array( 'taxonomy' => KNT_SERIES_TAXONOMY, 'hide_empty' => false ) );
	$current = knt_series_term( $post->ID );
	$order = (int) get_post_meta( $post->ID, KNT_SERIES_ORDER_META, true );
	?>
	<p><label for="knt-series-term"><strong><?php esc_html_e( 'Series', 'kamal-notebook' ); ?></strong></label></p>
	<select id="knt-series-term" name="knt_series_term" class="widefat">
		<option value="0"><?php esc_html_e( 'Standalone post', 'kamal-notebook' ); ?></option>
		<?php if ( ! is_wp_error( $terms ) ) : foreach ( $terms as $term ) : ?>
			<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $current ? $current->term_id : 0, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option>
		<?php endforeach; endif; ?>
	</select>
	<p class="description"><?php esc_html_e( 'Create and name series under Posts → Series. Each lesson is a separate post with its own URL.', 'kamal-notebook' ); ?></p>
	<p><label for="knt-series-order"><strong><?php esc_html_e( 'Lesson number', 'kamal-notebook' ); ?></strong></label></p>
	<input id="knt-series-order" name="knt_series_order" type="number" class="small-text" min="1" max="9999" step="1" value="<?php echo esc_attr( $order > 0 ? $order : 1 ); ?>">
	<p class="description"><?php esc_html_e( 'Lessons appear in number order. Equal numbers are ordered by publish date.', 'kamal-notebook' ); ?></p>
	<?php
}

function knt_save_series( int $post_id ): void {
	if ( ! isset( $_POST['knt_series_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['knt_series_nonce'] ) ), 'knt_save_series' ) ) {
		return;
	}
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$term_id = isset( $_POST['knt_series_term'] ) ? absint( wp_unslash( $_POST['knt_series_term'] ) ) : 0;
	if ( $term_id && ! term_exists( $term_id, KNT_SERIES_TAXONOMY ) ) {
		return;
	}
	if ( ! $term_id ) {
		wp_set_object_terms( $post_id, array(), KNT_SERIES_TAXONOMY );
		delete_post_meta( $post_id, KNT_SERIES_ORDER_META );
		return;
	}
	$order = isset( $_POST['knt_series_order'] ) ? (int) wp_unslash( $_POST['knt_series_order'] ) : 1;
	$order = max( 1, min( 9999, $order ) );
	wp_set_object_terms( $post_id, array( $term_id ), KNT_SERIES_TAXONOMY );
	update_post_meta( $post_id, KNT_SERIES_ORDER_META, $order );
}
add_action( 'save_post_post', 'knt_save_series' );

function knt_series_term( int $post_id ): ?WP_Term {
	$terms = wp_get_post_terms( $post_id, KNT_SERIES_TAXONOMY, array( 'orderby' => 'term_id', 'order' => 'ASC' ) );
	return is_wp_error( $terms ) || ! $terms ? null : $terms[0];
}

/** Published lessons only; missing order values sort after numbered lessons. */
function knt_series_lessons( int $term_id ): array {
	$posts = get_posts( array(
		'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => -1,
		'tax_query' => array( array( 'taxonomy' => KNT_SERIES_TAXONOMY, 'field' => 'term_id', 'terms' => $term_id ) ),
		'orderby' => 'date', 'order' => 'ASC', 'suppress_filters' => false,
	) );
	usort( $posts, static function ( WP_Post $a, WP_Post $b ): int {
		$a_order = (int) get_post_meta( $a->ID, KNT_SERIES_ORDER_META, true ) ?: PHP_INT_MAX;
		$b_order = (int) get_post_meta( $b->ID, KNT_SERIES_ORDER_META, true ) ?: PHP_INT_MAX;
		return ( $a_order <=> $b_order ) ?: ( strcmp( $a->post_date_gmt, $b->post_date_gmt ) ?: ( $a->ID <=> $b->ID ) );
	} );
	return $posts;
}

function knt_series_context( int $post_id ): ?array {
	$term = knt_series_term( $post_id );
	if ( ! $term || 'publish' !== get_post_status( $post_id ) ) {
		return null;
	}
	$lessons = knt_series_lessons( $term->term_id );
	$index = null;
	foreach ( $lessons as $i => $lesson ) {
		if ( $lesson->ID === $post_id ) {
			$index = $i;
			break;
		}
	}
	if ( null === $index ) {
		return null;
	}
	$link = static fn( ?WP_Post $post ) => $post ? array( 'id' => $post->ID, 'title' => get_the_title( $post ), 'url' => get_permalink( $post ) ) : null;
	$archive_url = get_term_link( $term );
	return array(
		'id' => $term->term_id,
		'name' => $term->name,
		'url' => ! is_wp_error( $archive_url ) ? $archive_url : '',
		'position' => $index + 1,
		'total' => count( $lessons ),
		'previous' => $link( $lessons[ $index - 1 ] ?? null ),
		'next' => $link( $lessons[ $index + 1 ] ?? null ),
	);
}

function knt_render_series_navigation( int $post_id ): void {
	$series = knt_series_context( $post_id );
	if ( ! $series ) {
		return;
	}
	?>
	<nav class="knt-series-nav" data-knt-series-nav aria-label="<?php esc_attr_e( 'Course lessons', 'kamal-notebook' ); ?>">
		<div class="knt-series-head"><span><?php esc_html_e( 'A COURSE IN THE NOTEBOOK', 'kamal-notebook' ); ?></span><strong><?php echo esc_html( $series['name'] ); ?></strong><small><?php echo esc_html( sprintf( __( 'Lesson %1$d of %2$d', 'kamal-notebook' ), $series['position'], $series['total'] ) ); ?></small></div>
		<div class="knt-series-links">
			<?php foreach ( array( 'previous' => __( 'Previous lesson', 'kamal-notebook' ), 'next' => __( 'Next lesson', 'kamal-notebook' ) ) as $direction => $label ) : ?>
				<?php if ( $series[ $direction ] ) : ?>
					<a data-knt-lesson-link data-knt-lesson-id="<?php echo esc_attr( $series[ $direction ]['id'] ); ?>" href="<?php echo esc_url( $series[ $direction ]['url'] ); ?>"><span><?php echo esc_html( $label ); ?></span><strong><?php echo esc_html( $series[ $direction ]['title'] ); ?></strong><span aria-hidden="true"><?php echo 'previous' === $direction ? '←' : '→'; ?></span></a>
				<?php endif; ?>
			<?php endforeach; ?>
			</div>
		<span class="screen-reader-text" data-knt-series-status role="status" aria-live="polite"></span>
	</nav>
	<?php
}
add_action( 'kn_after_article_tools', 'knt_render_series_navigation', 5 );

function knt_series_assets(): void {
	if ( ! is_singular( 'post' ) || ! knt_series_context( get_queried_object_id() ) ) {
		return;
	}
	// Later lessons can contain a Notebook Code block even when the first one does not.
	wp_enqueue_script( 'knt-code-view' );
	wp_enqueue_script( 'knt-series', knt_asset_url( 'series.js' ), array(), KNT_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_add_inline_script( 'knt-series', 'window.kntSeries = ' . wp_json_encode( array( 'endpoint' => esc_url_raw( rest_url( 'kamal-notebook/v1/series-lesson/' ) ), 'siteTitle' => get_bloginfo( 'name' ) ) ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'knt_series_assets' );

function knt_series_route(): void {
	register_rest_route( 'kamal-notebook/v1', '/series-lesson/(?P<id>\d+)', array(
		'methods' => 'GET',
		'callback' => 'knt_series_lesson_response',
		'permission_callback' => '__return_true',
		'args' => array( 'id' => array( 'type' => 'integer', 'minimum' => 1, 'required' => true ) ),
	) );
}
add_action( 'rest_api_init', 'knt_series_route' );

function knt_series_lesson_response( WP_REST_Request $request ) {
	$post_id = (int) $request['id'];
	$lesson = get_post( $post_id );
	$series = knt_series_context( $post_id );
	if ( ! $lesson || 'post' !== $lesson->post_type || ! $series ) {
		return new WP_Error( 'knt_lesson_missing', __( 'This lesson is unavailable.', 'kamal-notebook' ), array( 'status' => 404 ) );
	}
	$raw = $lesson->post_content;
	$legacy = str_contains( $raw, 'lessons-container' ) && str_contains( $raw, '<style>' ) && str_contains( $raw, '<script>' );
	$requires_page_load = $legacy || (bool) preg_match( '~<(?:script|iframe|form)\b~i', $raw );
	$previous = $GLOBALS['post'] ?? null;
	$GLOBALS['post'] = $lesson;
	setup_postdata( $lesson );
	$rendered = apply_filters( 'the_content', $raw );
	$requires_page_load = $requires_page_load || (bool) preg_match( '~<(?:script|iframe|form)\b~i', $rendered );
	$outline = array();
	if ( function_exists( 'kn_prepare_article' ) ) {
		list( $rendered, $outline ) = kn_prepare_article( $rendered );
	}
	if ( $previous instanceof WP_Post ) {
		$GLOBALS['post'] = $previous;
		setup_postdata( $previous );
	} else {
		unset( $GLOBALS['post'] );
	}
	$rendered = wp_kses_post( $rendered );
	$thumbnail = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail_url( $post_id, 'kn-feature' ) : '';
	if ( function_exists( 'kn_post_image' ) ) {
		$image_tag = new WP_HTML_Tag_Processor( kn_post_image( $post_id, 'kn-feature', true ) );
		if ( $image_tag->next_tag( 'IMG' ) ) {
			$thumbnail = (string) $image_tag->get_attribute( 'src' );
		}
	}
	$topic = function_exists( 'kn_post_topic' ) ? kn_post_topic( $post_id ) : __( 'Journal', 'kamal-notebook' );
	$response = rest_ensure_response( array(
		'id' => $post_id,
		'url' => get_permalink( $post_id ),
		'title' => get_the_title( $post_id ),
		'excerpt' => has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : '',
		'content' => $rendered,
		'outline' => array_values( $outline ),
		'hero' => array(
			'image' => $thumbnail,
			'topic' => $topic,
			'kind' => __( 'LESSON', 'kamal-notebook' ),
			'readingMinutes' => function_exists( 'kn_reading_minutes' ) ? kn_reading_minutes( $post_id ) : 1,
			'author' => get_the_author_meta( 'display_name', (int) $lesson->post_author ),
			'date' => get_the_date( '', $lesson ),
		),
		'series' => $series,
		'canSwap' => ! $requires_page_load,
	) );
	$response->header( 'Cache-Control', 'public, max-age=60' );
	return $response;
}
