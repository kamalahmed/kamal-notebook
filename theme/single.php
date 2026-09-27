<?php
/** Single article with an automatic outline and inline-editable content pieces. */

get_header();
while ( have_posts() ) :
	the_post();
	$post_id = get_the_ID();
	$raw_content = get_the_content();
	$is_legacy_tutorial = str_contains( $raw_content, 'lessons-container' ) && str_contains( $raw_content, '<style>' ) && str_contains( $raw_content, '<script>' );
	$is_tutorial = $is_legacy_tutorial || kn_has_tutorial_layout( $raw_content );
	$rendered_content = apply_filters( 'the_content', $raw_content );
	list( $content, $outline ) = $is_legacy_tutorial ? array( $rendered_content, array() ) : kn_prepare_article( $rendered_content );
	if ( $is_legacy_tutorial ) {
		wp_enqueue_script( 'kn-legacy-tutorial', get_theme_file_uri( 'assets/js/legacy-tutorial.js' ), array(), KN_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
		$legacy_mobile = '<style>@media(max-width:700px){.top-nav{height:auto;min-height:48px;flex-wrap:wrap;gap:8px;padding:10px 16px}.nav-title{line-height:1.4}.lesson-header{display:block}.lesson-number{display:block;margin-bottom:8px}.lesson [style*="grid-template-columns"]{grid-template-columns:1fr!important}}</style>';
		$legacy_resize = '<script>(function(){function report(){parent.postMessage({knLegacyHeight:Math.max(document.body.scrollHeight,document.documentElement.scrollHeight)},"*")}addEventListener("load",report);if(window.ResizeObserver){new ResizeObserver(report).observe(document.body)}else{setInterval(report,500)}requestAnimationFrame(report)})();</script>';
		$legacy_doc = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head><body>' . $content . $legacy_mobile . $legacy_resize . '</body></html>';
	}
	$in_series = function_exists( 'knt_series_context' ) ? knt_series_context( $post_id ) : null;
	$related = array();
	if ( ! $in_series ) {
		$next_args = array(
			'numberposts' => 1,
			'post_type'   => 'post',
			'post_status' => 'publish',
			'exclude'     => array( $post_id ),
			'date_query' => array( array( 'before' => get_post_field( 'post_date', $post_id ), 'inclusive' => false ) ),
		);
		$categories = wp_get_post_categories( $post_id );
		if ( $categories ) {
			$related = get_posts( $next_args + array( 'category__in' => $categories ) );
		}
		if ( ! $related ) {
			$related = get_posts( $next_args );
		}
	}
	?>
	<article id="post-<?php the_ID(); ?>" data-knt-lesson-id="<?php echo esc_attr( $post_id ); ?>" <?php if ( $in_series ) : ?>data-knt-series-id="<?php echo esc_attr( $in_series['id'] ); ?>"<?php endif; ?> <?php post_class( $is_tutorial ? 'kn-article-tutorial' : '' ); ?>>
		<header class="article-hero container">
			<div class="article-hero-copy">
				<a class="back-link" href="<?php echo esc_url( kn_archive_url() . '#stories' ); ?>">
					<span aria-hidden="true">←</span> <?php esc_html_e( 'Back to the notebook', 'kamal-notebook' ); ?>
				</a>
				<div class="article-kicker">
					<span><?php echo esc_html( kn_post_topic( $post_id ) ); ?> / <?php echo esc_html( $in_series ? __( 'LESSON', 'kamal-notebook' ) : ( $is_tutorial ? __( 'TUTORIAL', 'kamal-notebook' ) : __( 'STORY', 'kamal-notebook' ) ) ); ?></span>
					<span class="kicker-rule"></span>
					<span><?php echo esc_html( kn_reading_minutes( $post_id ) ); ?> <?php esc_html_e( 'MIN READ', 'kamal-notebook' ); ?></span>
				</div>
				<h1><?php echo esc_html( get_the_title() ); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="article-standfirst"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<div class="article-byline">
					<span class="avatar-mark" aria-hidden="true">k.</span>
					<span>
						<strong><?php echo esc_html( get_the_author_meta( 'display_name' ) ); ?></strong>
						<small><?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( kn_post_topic( $post_id ) ); ?></small>
					</span>
				</div>
			</div>
			<div class="article-hero-art">
				<?php echo kn_post_image( $post_id, 'kn-feature', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped theme markup. ?>
				<span class="art-vertical"><?php esc_html_e( 'THE NOTEBOOK', 'kamal-notebook' ); ?></span>
			</div>
		</header>

		<div class="article-divider container">
			<span><?php esc_html_e( 'THE IDEA, THEN THE DETAILS', 'kamal-notebook' ); ?></span>
			<span aria-hidden="true">↓</span>
		</div>
		<div class="article-shell container <?php echo $outline ? 'has-outline' : 'no-outline'; ?><?php echo $is_legacy_tutorial ? ' is-legacy' : ''; ?>">
			<?php if ( $outline ) : ?>
				<aside class="toc-rail">
					<div class="toc-sticky">
						<div class="toc-label"><?php echo esc_html( $is_tutorial ? __( 'THE LESSONS', 'kamal-notebook' ) : __( 'ON THIS PAGE', 'kamal-notebook' ) ); ?><span><?php echo esc_html( sprintf( '%02d', count( $outline ) ) ); ?></span></div>
						<nav aria-label="<?php esc_attr_e( 'Article sections', 'kamal-notebook' ); ?>">
							<?php foreach ( $outline as $item ) : ?>
								<a class="toc-link" href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
							<?php endforeach; ?>
						</nav>
						<div class="toc-end"><span class="toc-end-mark">✳</span><span><?php echo esc_html( $is_tutorial ? __( 'Go at your own pace.', 'kamal-notebook' ) : __( 'Take your time with this one.', 'kamal-notebook' ) ); ?></span></div>
					</div>
				</aside>
			<?php endif; ?>

			<div class="article-content">
				<?php if ( $outline ) : ?>
					<details class="toc-mobile">
						<summary><?php echo esc_html( $is_tutorial ? __( 'Lessons in this tutorial', 'kamal-notebook' ) : __( 'In this piece', 'kamal-notebook' ) ); ?> <span aria-hidden="true">↓</span></summary>
						<nav aria-label="<?php esc_attr_e( 'Article sections', 'kamal-notebook' ); ?>">
							<?php foreach ( $outline as $item ) : ?>
								<a class="toc-link" href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
							<?php endforeach; ?>
						</nav>
					</details>
				<?php endif; ?>

				<div data-knt-lesson-content>
				<?php if ( $is_legacy_tutorial ) : ?>
					<iframe class="kn-legacy-frame" title="<?php esc_attr_e( 'Interactive tutorial lessons', 'kamal-notebook' ); ?>" sandbox="allow-scripts" srcdoc="<?php echo esc_attr( $legacy_doc ); ?>"></iframe>
				<?php else : ?>
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered by WordPress block content filters. ?>
				<?php endif; ?>
				</div>
				<div class="article-end"><span class="end-symbol" aria-hidden="true">✳</span><span><?php echo esc_html( $in_series ? __( 'END OF LESSON', 'kamal-notebook' ) : ( $is_tutorial ? __( 'END OF TUTORIAL', 'kamal-notebook' ) : __( 'END OF STORY', 'kamal-notebook' ) ) ); ?></span></div>

				<?php if ( '1' === kn_option( 'save_enabled' ) || '1' === kn_option( 'share_enabled' ) ) : ?>
					<div class="reader-actions" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-title="<?php echo esc_attr( get_the_title() ); ?>" data-url="<?php echo esc_url( get_permalink() ); ?>">
						<?php if ( '1' === kn_option( 'save_enabled' ) ) : ?>
							<button class="reader-button" type="button" data-save aria-pressed="false"><?php esc_html_e( 'Save for later', 'kamal-notebook' ); ?></button>
						<?php endif; ?>
						<?php if ( '1' === kn_option( 'share_enabled' ) ) : ?>
							<button class="reader-button" type="button" data-share><?php esc_html_e( 'Share this story', 'kamal-notebook' ); ?></button>
						<?php endif; ?>
					</div>
					<p class="reader-status" role="status" data-reader-status></p>
				<?php endif; ?>
				<?php do_action( 'kn_after_article_tools', $post_id ); ?>
			</div>
		</div>
	</article>

	<?php if ( $related ) : $next = $related[0]; ?>
		<section class="next-read">
			<div class="container next-inner">
				<div>
					<span class="overline"><?php esc_html_e( 'KEEP WANDERING', 'kamal-notebook' ); ?></span>
					<h2><?php echo esc_html( get_the_title( $next ) ); ?></h2>
					<p><?php echo esc_html( kn_story_excerpt( $next->ID, 25 ) ); ?></p>
					<a class="light-link" href="<?php echo esc_url( get_permalink( $next ) ); ?>"><?php esc_html_e( 'Read the next story', 'kamal-notebook' ); ?> <span aria-hidden="true">↗</span></a>
				</div>
				<span class="next-illustration" aria-hidden="true">↗</span>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
