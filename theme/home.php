<?php
/** Journal homepage and archive. */

$topic = '';
if ( is_category() ) {
	$topic = get_queried_object()->slug;
} elseif ( isset( $_GET['topic'] ) ) {
	$topic = sanitize_title( wp_unslash( $_GET['topic'] ) );
}

$series_term = is_tax( 'knt_series' ) && function_exists( 'knt_series_lessons' ) ? get_queried_object() : null;
$search      = get_search_query();
$paged       = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$filtered    = $series_term || '' !== $topic || '' !== $search;
// Keep one archive result set across pagination; filtered views include every matching post.
$featured    = $filtered || ! function_exists( 'knt_featured_posts' ) ? array() : knt_featured_posts();
$leads       = $paged > 1 ? array() : $featured;
$archive_url = kn_archive_url();
$categories  = get_categories( array( 'hide_empty' => true ) );
// Match the main taxonomy query so valid course pages are never treated as 404s.
$per_page = max( 1, (int) get_query_var( 'posts_per_page', get_option( 'posts_per_page' ) ) );
$story_query = array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => $per_page,
	'paged'               => $paged,
	'category_name'       => $topic,
	's'                   => $search,
	'post__not_in'        => array_column( $featured, 'ID' ),
	'ignore_sticky_posts' => true,
);
if ( $series_term ) {
	$story_query['post__in'] = array_column( knt_series_lessons( $series_term->term_id ), 'ID' ) ?: array( 0 );
	$story_query['orderby'] = 'post__in';
	$story_query['category_name'] = '';
	$story_query['s'] = '';
}
$stories = new WP_Query( $story_query );

if ( ! $series_term ) {
	// Queue after the base assets, so their dependency does not move site.css
	// ahead of WordPress global styles and override the appearance palette.
	add_action( 'wp_enqueue_scripts', static function () {
		wp_enqueue_style( 'kn-featured', get_theme_file_uri( 'assets/css/featured.css' ), array( 'kn-site' ), KN_VERSION );
		wp_enqueue_script( 'kn-featured', get_theme_file_uri( 'assets/js/featured.js' ), array(), KN_VERSION, true );
	}, 20 );
}
add_action( 'wp_enqueue_scripts', static function () {
	wp_enqueue_style( 'kn-archive', get_theme_file_uri( 'assets/css/archive.css' ), array( 'kn-site' ), KN_VERSION );
	wp_enqueue_script( 'kn-archive', get_theme_file_uri( 'assets/js/archive.js' ), array(), KN_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
}, 20 );
get_header();
?>
<?php if ( $series_term ) : ?>
<section class="container series-intro" aria-labelledby="intro-title">
	<div class="eyebrow"><?php esc_html_e( 'A COURSE IN THE NOTEBOOK', 'kamal-notebook' ); ?></div>
	<h1 id="intro-title"><?php echo esc_html( $series_term->name ); ?></h1>
	<?php if ( $series_term->description ) : ?><p class="intro-deck"><?php echo esc_html( $series_term->description ); ?></p><?php endif; ?>
	<a class="text-link" href="#stories"><?php esc_html_e( 'Explore the lessons', 'kamal-notebook' ); ?> <span aria-hidden="true">↘</span></a>
</section>
<?php else : ?>
<section class="intro container" aria-labelledby="intro-title">
	<div class="intro-copy">
		<div class="eyebrow">
			<span class="eyebrow-line"></span>
			<?php esc_html_e( 'A personal field journal', 'kamal-notebook' ); ?>
			<span class="issue-no"><?php esc_html_e( '№ 001 / ongoing', 'kamal-notebook' ); ?></span>
		</div>
		<h1 id="intro-title">
			<?php echo esc_html( kn_option( 'hero_prefix' ) ); ?>
			<em><?php echo esc_html( kn_option( 'hero_accent' ) ); ?></em>
		</h1>
		<p class="intro-deck"><?php echo esc_html( kn_option( 'hero_deck' ) ); ?></p>
		<div class="intro-bottom">
			<a class="text-link" href="#stories">
				<?php esc_html_e( 'Wander through the notebook', 'kamal-notebook' ); ?>
				<span aria-hidden="true">↘</span>
			</a>
			<span class="margin-scribble" aria-hidden="true">read slowly<br>↗</span>
		</div>
	</div>

	<?php if ( $leads ) : $slide_count = count( $leads ); ?>
	<div class="kn-featured" <?php if ( $slide_count > 1 ) : ?>data-kn-featured role="region" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Featured articles', 'kamal-notebook' ); ?>"<?php endif; ?>>
	<div class="kn-featured-slides" id="kn-featured-slides">
	<?php foreach ( $leads as $slide_index => $lead ) : ?>
	<div class="kn-featured-slide<?php echo 0 === $slide_index ? ' is-active' : ''; ?>" <?php if ( $slide_count > 1 ) : ?>role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( sprintf( __( '%1$d of %2$d', 'kamal-notebook' ), $slide_index + 1, $slide_count ) ); ?>"<?php endif; ?> <?php echo 0 === $slide_index ? '' : 'aria-hidden="true" inert'; ?>>
		<a class="lead-story" href="<?php echo esc_url( get_permalink( $lead ) ); ?>">
			<div class="lead-art">
				<?php if ( get_post_meta( $lead->ID, '_kn_demo_art', true ) && ! has_post_thumbnail( $lead ) ) : ?>
					<?php echo kn_post_image( $lead->ID, 'kn-feature', 0 === $slide_index ); ?>
				<?php elseif ( has_post_thumbnail( $lead ) ) : ?>
					<?php echo get_the_post_thumbnail( $lead, 'kn-feature', array( 'loading' => 0 === $slide_index ? 'eager' : 'lazy', 'fetchpriority' => 0 === $slide_index ? 'high' : 'low', 'decoding' => 'async' ) ); ?>
				<?php else : ?>
					<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/feature.svg' ) ); ?>" alt="" width="920" height="720" fetchpriority="high">
				<?php endif; ?>
				<span class="art-sticker"><?php esc_html_e( 'START HERE', 'kamal-notebook' ); ?> <span aria-hidden="true">↗</span></span>
			</div>
			<div class="lead-meta">
				<span><?php esc_html_e( 'Featured article', 'kamal-notebook' ); ?> <span aria-hidden="true">/</span> <?php echo esc_html( kn_post_topic( $lead->ID ) ); ?></span>
				<span><?php echo esc_html( kn_reading_minutes( $lead->ID ) ); ?> <?php esc_html_e( 'min read', 'kamal-notebook' ); ?></span>
			</div>
			<div class="lead-text">
				<h2><?php echo esc_html( get_the_title( $lead ) ); ?></h2>
				<p><?php echo esc_html( kn_story_excerpt( $lead->ID, 24 ) ); ?></p>
				<span class="lead-arrow" aria-hidden="true">↗</span>
			</div>
		</a>
	</div>
	<?php endforeach; ?>
	</div>
	<?php if ( $slide_count > 1 ) : ?>
	<div class="kn-featured-controls" hidden>
		<span class="kn-featured-caption"><?php esc_html_e( 'IN THE SPOTLIGHT', 'kamal-notebook' ); ?></span>
		<button type="button" data-featured-prev aria-controls="kn-featured-slides" aria-label="<?php esc_attr_e( 'Previous featured article', 'kamal-notebook' ); ?>"><span aria-hidden="true">←</span></button>
		<span class="kn-featured-count" aria-hidden="true"><span data-featured-current>1</span> / <?php echo esc_html( $slide_count ); ?></span>
		<button type="button" data-featured-next aria-controls="kn-featured-slides" aria-label="<?php esc_attr_e( 'Next featured article', 'kamal-notebook' ); ?>"><span aria-hidden="true">→</span></button>
		<span class="sr-only" data-featured-status aria-live="polite" aria-atomic="true"></span>
	</div>
	<?php endif; ?>
	</div>
	<?php else : ?>
		<div class="lead-story lead-placeholder">
			<div class="lead-art"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/feature.svg' ) ); ?>" alt="" width="920" height="720" fetchpriority="high"></div>
			<div class="lead-meta"><span><?php esc_html_e( 'The notebook', 'kamal-notebook' ); ?></span><span>✳</span></div>
			<div class="lead-text">
				<h2><?php esc_html_e( 'A space for what comes next.', 'kamal-notebook' ); ?></h2>
				<p><?php esc_html_e( 'Explore the stories below.', 'kamal-notebook' ); ?></p>
			</div>
		</div>
	<?php endif; ?>
</section>

<div class="ribbon" aria-hidden="true">
	<div class="container ribbon-inner">
		<span>Ideas worth trying</span><span class="ribbon-flower">✳</span>
		<span>Things worth questioning</span><span class="ribbon-flower">✳</span>
		<span>Stories worth keeping</span>
	</div>
</div>

<?php endif; ?>
<section id="stories" data-kn-archive data-loading="<?php esc_attr_e( 'Loading stories…', 'kamal-notebook' ); ?>" data-error="<?php esc_attr_e( 'Stories could not be loaded. Try again or open this view.', 'kamal-notebook' ); ?>" class="stories-section container" aria-labelledby="stories-title">
	<div class="section-heading">
		<?php if ( $series_term ) : ?>
		<div><div class="eyebrow section-eyebrow"><?php esc_html_e( 'STEP BY STEP', 'kamal-notebook' ); ?></div><h2 id="stories-title"><?php esc_html_e( 'The lessons', 'kamal-notebook' ); ?></h2></div>
		<p><?php esc_html_e( 'Start at the beginning or return to a lesson. Each one has its own place in the course.', 'kamal-notebook' ); ?></p>
		<?php else : ?>
		<div><div class="eyebrow section-eyebrow">01 / THE ARCHIVE</div><h2 id="stories-title">From the <em>notebook</em></h2></div>
		<p><?php esc_html_e( 'Some pieces are practical, some are personal. All begin with a question.', 'kamal-notebook' ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( ! $series_term ) : ?>
	<div class="explore-bar">
		<nav class="topic-tabs" aria-label="<?php esc_attr_e( 'Filter by topic', 'kamal-notebook' ); ?>">
			<a class="topic-tab <?php echo $topic ? '' : 'is-active'; ?>" href="<?php echo esc_url( $archive_url . '#stories' ); ?>" <?php echo $topic ? '' : 'aria-current="page"'; ?>>
				<?php esc_html_e( 'Everything', 'kamal-notebook' ); ?>
			</a>
			<?php foreach ( $categories as $category ) : ?>
				<a class="topic-tab <?php echo $topic === $category->slug ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'topic', $category->slug, $archive_url ) . '#stories' ); ?>" <?php echo $topic === $category->slug ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( $category->name ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<form class="search-field" role="search" action="<?php echo esc_url( $archive_url ); ?>" method="get">
			<span class="search-icon" aria-hidden="true">⌕</span>
			<label class="sr-only" for="story-search"><?php esc_html_e( 'Search stories', 'kamal-notebook' ); ?></label>
			<input id="story-search" type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search the notebook', 'kamal-notebook' ); ?>">
			<button type="submit" class="sr-only"><?php esc_html_e( 'Search', 'kamal-notebook' ); ?></button>
		</form>
	</div>

	<?php endif; ?>
	<p class="archive-status" role="status" aria-live="polite" aria-atomic="true"></p>
	<div class="archive-results" tabindex="-1">
	<p class="result-count">
		<?php printf( esc_html( $series_term ? _n( '%s lesson', '%s lessons', $stories->found_posts, 'kamal-notebook' ) : _n( '%s story', '%s stories', $stories->found_posts, 'kamal-notebook' ) ), esc_html( number_format_i18n( $stories->found_posts ) ) ); ?>
	</p>
	<div class="story-grid" id="story-grid">
		<?php while ( $stories->have_posts() ) : $stories->the_post(); $post_id = get_the_ID(); ?>
			<article class="story-card">
				<span class="row-number" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $stories->current_post + 1 + ( $paged - 1 ) * $per_page ) ); ?></span>
				<a class="story-visual" href="<?php echo esc_url( get_permalink() ); ?>" tabindex="-1" aria-hidden="true"><?php echo kn_post_image( $post_id ); ?></a>
				<div class="story-body">
					<div class="story-meta">
						<span><?php echo esc_html( $series_term ? sprintf( __( 'Lesson %d', 'kamal-notebook' ), $stories->current_post + 1 + ( $paged - 1 ) * $per_page ) : kn_post_topic( $post_id ) ); ?></span>
						<span><?php echo esc_html( kn_reading_minutes( $post_id ) ); ?> <?php esc_html_e( 'min read', 'kamal-notebook' ); ?></span>
					</div>
					<h3><a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?> <span aria-hidden="true">↗</span></a></h3>
					<p><?php echo esc_html( kn_story_excerpt( $post_id ) ); ?></p>
				</div>
			</article>
		<?php endwhile; wp_reset_postdata(); ?>
	</div>

	<?php if ( 0 === $stories->post_count ) : ?>
		<div class="empty-state">
			<span aria-hidden="true">✳</span>
			<h3><?php esc_html_e( 'No notes found yet.', 'kamal-notebook' ); ?></h3>
			<p><?php esc_html_e( 'Try another search or browse all stories.', 'kamal-notebook' ); ?></p>
			<a href="<?php echo esc_url( $archive_url . '#stories' ); ?>"><?php esc_html_e( 'Show everything ↗', 'kamal-notebook' ); ?></a>
		</div>
	<?php endif; ?>

	<?php if ( $stories->max_num_pages > 1 ) : ?>
		<nav class="pagination" aria-label="<?php esc_attr_e( 'Story pages', 'kamal-notebook' ); ?>">
			<?php
			$pagination = array(
				'total'     => $stories->max_num_pages,
				'current'   => $paged,
				'prev_text' => '← ' . __( 'Previous', 'kamal-notebook' ),
				'next_text' => __( 'Next', 'kamal-notebook' ) . ' →',
			);
			if ( is_front_page() && 'page' === get_option( 'show_on_front' ) ) {
				$pagination['base'] = str_replace( '999999999', '%#%', add_query_arg( 'paged', 999999999, $archive_url ) );
				$pagination['format'] = '';
			}
			echo paginate_links( $pagination ); ?>
		</nav>
	<?php endif; ?>
	</div>
</section>

<section class="about-band" aria-labelledby="about-title">
	<div class="container about-inner">
		<div class="about-emblem" aria-hidden="true"><span>k.</span></div>
		<div>
			<div class="eyebrow"><?php esc_html_e( 'A NOTE FROM THE MARGIN', 'kamal-notebook' ); ?></div>
			<h2 id="about-title"><?php echo esc_html( kn_option( 'about_heading' ) ); ?></h2>
			<p><?php echo esc_html( kn_option( 'about_text' ) ); ?></p>
		</div>
	</div>
</section>
<?php get_footer(); ?>
