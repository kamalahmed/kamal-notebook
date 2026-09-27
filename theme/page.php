<?php get_header(); ?>
<?php while ( have_posts() ) : the_post(); ?>
	<article <?php post_class( 'simple-page container' ); ?>><header><a class="back-link" href="<?php echo esc_url( kn_archive_url() ); ?>">← <?php esc_html_e( 'The notebook', 'kamal-notebook' ); ?></a><h1><?php the_title(); ?></h1></header><div class="article-content"><?php the_content(); ?></div></article>
<?php endwhile; ?>
<?php get_footer(); ?>
