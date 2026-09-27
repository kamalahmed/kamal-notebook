<?php
/** Single article with an automatic outline and inline-editable content pieces. */

get_header();
while ( have_posts() ) :
	the_post();
	$post_id = get_the_ID();
	list( $content, $outline ) = kn_prepare_article( apply_filters( 'the_content', get_the_content() ) );
	$related = get_posts( array(
		'numberposts' => 1,
		'post_type'   => 'post',
		'post_status' => 'publish',
		'exclude'     => array( $post_id ),
		'category__in' => wp_get_post_categories( $post_id ),
	) );
	if ( ! $related ) {
		$related = get_posts( array( 'numberposts' => 1, 'post_type' => 'post', 'post_status' => 'publish', 'exclude' => array( $post_id ) ) );
	}
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<header class="article-hero container">
			<div class="article-hero-copy">
				<a class="back-link" href="<?php echo esc_url( kn_archive_url() . '#stories' ); ?>">
					<span aria-hidden="true">←</span> <?php esc_html_e( 'Back to the notebook', 'kamal-notebook' ); ?>
				</a>
				<div class="article-kicker">
					<span><?php echo esc_html( kn_post_topic( $post_id ) ); ?> / <?php esc_html_e( 'STORY', 'kamal-notebook' ); ?></span>
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
				<?php if ( has_post_thumbnail() ) : ?>
					<?php echo get_the_post_thumbnail( $post_id, 'kn-feature', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'decoding' => 'async' ) ); ?>
				<?php else : ?>
					<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/feature.svg' ) ); ?>" alt="" width="920" height="720" fetchpriority="high">
				<?php endif; ?>
				<span class="art-vertical"><?php esc_html_e( 'THE NOTEBOOK', 'kamal-notebook' ); ?></span>
			</div>
		</header>

		<div class="article-divider container">
			<span><?php esc_html_e( 'THE IDEA, THEN THE DETAILS', 'kamal-notebook' ); ?></span>
			<span aria-hidden="true">↓</span>
		</div>
		<div class="article-shell container <?php echo $outline ? 'has-outline' : 'no-outline'; ?>">
			<?php if ( $outline ) : ?>
				<aside class="toc-rail">
					<div class="toc-sticky">
						<div class="toc-label"><?php esc_html_e( 'ON THIS PAGE', 'kamal-notebook' ); ?><span><?php echo esc_html( sprintf( '%02d', count( $outline ) ) ); ?></span></div>
						<nav aria-label="<?php esc_attr_e( 'Article sections', 'kamal-notebook' ); ?>">
							<?php foreach ( $outline as $item ) : ?>
								<a class="toc-link" href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
							<?php endforeach; ?>
						</nav>
						<div class="toc-end"><span class="toc-end-mark">✳</span><span><?php esc_html_e( 'Take your time with this one.', 'kamal-notebook' ); ?></span></div>
					</div>
				</aside>
			<?php endif; ?>

			<div class="article-content">
				<?php if ( $outline ) : ?>
					<details class="toc-mobile">
						<summary><?php esc_html_e( 'In this piece', 'kamal-notebook' ); ?> <span aria-hidden="true">↓</span></summary>
						<nav aria-label="<?php esc_attr_e( 'Article sections', 'kamal-notebook' ); ?>">
							<?php foreach ( $outline as $item ) : ?>
								<a class="toc-link" href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
							<?php endforeach; ?>
						</nav>
					</details>
				<?php endif; ?>

				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered by WordPress block content filters. ?>
				<div class="article-end"><span class="end-symbol" aria-hidden="true">✳</span><span><?php esc_html_e( 'END OF STORY', 'kamal-notebook' ); ?></span></div>

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
