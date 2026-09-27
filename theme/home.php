<?php
/** Journal homepage and archive. */

$topic = '';
if ( is_category() ) {
	$topic = get_queried_object()->slug;
} elseif ( isset( $_GET['topic'] ) ) {
	$topic = sanitize_title( wp_unslash( $_GET['topic'] ) );
}

$search      = get_search_query();
$paged       = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
$filtered    = '' !== $topic || '' !== $search;
$featured    = $filtered || $paged > 1 ? array() : get_posts( array(
	'numberposts'        => 1,
	'post_type'          => 'post',
	'post_status'        => 'publish',
	'ignore_sticky_posts' => false,
) );
$lead        = $featured ? $featured[0] : null;
$archive_url = kn_archive_url();
$categories  = get_categories( array( 'hide_empty' => true ) );
$stories     = new WP_Query( array(
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => 9,
	'paged'               => $paged,
	'category_name'       => $topic,
	's'                   => $search,
	'post__not_in'        => $lead ? array( $lead->ID ) : array(),
	'ignore_sticky_posts' => true,
) );

get_header();
?>
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

	<?php if ( $lead ) : ?>
		<a class="lead-story" href="<?php echo esc_url( get_permalink( $lead ) ); ?>">
			<div class="lead-art">
				<?php if ( has_post_thumbnail( $lead ) ) : ?>
					<?php echo get_the_post_thumbnail( $lead, 'kn-feature', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ) ); ?>
				<?php else : ?>
					<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/feature.svg' ) ); ?>" alt="" width="920" height="720" fetchpriority="high">
				<?php endif; ?>
				<span class="art-sticker"><?php esc_html_e( 'START HERE', 'kamal-notebook' ); ?> <span aria-hidden="true">↗</span></span>
			</div>
			<div class="lead-meta">
				<span><?php esc_html_e( 'Featured essay', 'kamal-notebook' ); ?> <span aria-hidden="true">/</span> <?php echo esc_html( kn_post_topic( $lead->ID ) ); ?></span>
				<span><?php echo esc_html( kn_reading_minutes( $lead->ID ) ); ?> <?php esc_html_e( 'min read', 'kamal-notebook' ); ?></span>
			</div>
			<div class="lead-text">
				<h2><?php echo esc_html( get_the_title( $lead ) ); ?></h2>
				<p><?php echo esc_html( kn_story_excerpt( $lead->ID, 24 ) ); ?></p>
				<span class="lead-arrow" aria-hidden="true">↗</span>
			</div>
		</a>
	<?php else : ?>
		<div class="lead-story lead-placeholder">
			<div class="lead-art"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/feature.svg' ) ); ?>" alt="" width="920" height="720"></div>
			<div class="lead-meta"><span><?php esc_html_e( 'The notebook', 'kamal-notebook' ); ?></span><span>✳</span></div>
			<div class="lead-text">
				<h2><?php esc_html_e( 'A space for what comes next.', 'kamal-notebook' ); ?></h2>
				<p><?php esc_html_e( 'Stories will appear here as they are published.', 'kamal-notebook' ); ?></p>
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

<section id="stories" class="stories-section container" aria-labelledby="stories-title">
	<div class="section-heading">
		<div><div class="eyebrow section-eyebrow">01 / THE ARCHIVE</div><h2 id="stories-title">From the <em>notebook</em></h2></div>
		<p><?php esc_html_e( 'Some pieces are practical, some are personal. All begin with a question.', 'kamal-notebook' ); ?></p>
	</div>
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

	<p class="result-count">
		<?php printf( esc_html( _n( '%s story', '%s stories', $stories->found_posts, 'kamal-notebook' ) ), esc_html( number_format_i18n( $stories->found_posts ) ) ); ?>
	</p>
	<div class="story-grid" id="story-grid">
		<?php while ( $stories->have_posts() ) : $stories->the_post(); $post_id = get_the_ID(); ?>
			<article class="story-card">
				<span class="row-number" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $stories->current_post + 1 + ( $paged - 1 ) * 9 ) ); ?></span>
				<a class="story-visual" href="<?php echo esc_url( get_permalink() ); ?>" tabindex="-1" aria-hidden="true"><?php echo kn_post_image( $post_id ); ?></a>
				<div class="story-body">
					<div class="story-meta">
						<span><?php echo esc_html( kn_post_topic( $post_id ) ); ?></span>
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
			<?php echo paginate_links( array(
				'total'     => $stories->max_num_pages,
				'current'   => $paged,
				'prev_text' => '← ' . __( 'Previous', 'kamal-notebook' ),
				'next_text' => __( 'Next', 'kamal-notebook' ) . ' →',
			) ); ?>
		</nav>
	<?php endif; ?>
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
