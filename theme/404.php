<?php get_header(); ?>
<div class="simple-page container"><span class="eyebrow">404 / <?php esc_html_e( 'LOST PAGE', 'kamal-notebook' ); ?></span><h1><?php esc_html_e( 'This page took another path.', 'kamal-notebook' ); ?></h1><p><?php esc_html_e( 'Head back to the notebook and find a story to read.', 'kamal-notebook' ); ?></p><a class="text-link" href="<?php echo esc_url( kn_archive_url() ); ?>"><?php esc_html_e( 'Back to the notebook ↗', 'kamal-notebook' ); ?></a></div>
<?php get_footer(); ?>
