<?php
/**
 * Template Name: About the Notebook
 *
 * The editor owns the biography. This template supplies only the layout.
 */

get_header();
while ( have_posts() ) :
	the_post();
	$intro = has_excerpt() ? get_the_excerpt() : kn_option( 'about_heading' );
	?>
	<article <?php post_class( 'kn-editorial-page kn-about-page' ); ?>>
		<header class="kn-page-hero container">
			<div class="kn-page-hero-label"><span class="eyebrow"><?php esc_html_e( 'A note from the margin', 'kamal-notebook' ); ?></span><span class="kn-page-hero-mark" aria-hidden="true">✳</span></div>
			<div class="kn-page-hero-copy">
				<h1><?php the_title(); ?></h1>
				<?php if ( $intro ) : ?><p class="kn-page-deck"><?php echo esc_html( $intro ); ?></p><?php endif; ?>
			</div>
		</header>
		<div class="kn-page-rule" aria-hidden="true"><span></span><span></span><span></span></div>
		<div class="kn-page-body container">
			<aside class="kn-page-aside" aria-label="<?php esc_attr_e( 'Page context', 'kamal-notebook' ); ?>">
				<span class="eyebrow">01 / <?php esc_html_e( 'This space', 'kamal-notebook' ); ?></span>
				<span class="kn-aside-glyph" aria-hidden="true">k.</span>
			</aside>
			<div class="kn-page-content article-content">
				<?php if ( trim( get_the_content() ) ) : ?>
					<?php the_content(); ?>
				<?php elseif ( kn_option( 'about_text' ) ) : ?>
					<p><?php echo esc_html( kn_option( 'about_text' ) ); ?></p>
				<?php endif; ?>
				<a class="kn-page-next" href="<?php echo esc_url( kn_archive_url() ); ?>"><span><?php esc_html_e( 'Read the notebook', 'kamal-notebook' ); ?></span><span aria-hidden="true">↗</span></a>
			</div>
		</div>
	</article>
	<?php
endwhile;
get_footer();
