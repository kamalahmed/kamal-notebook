</main>
<?php if ( '1' === kn_option( 'save_enabled' ) ) : ?>
	<dialog class="saved-dialog" id="kn-saved-dialog" aria-labelledby="kn-saved-title">
		<div class="dialog-head">
			<span class="eyebrow"><?php esc_html_e( 'YOUR READING LIST', 'kamal-notebook' ); ?></span>
			<button type="button" data-close-saved aria-label="<?php esc_attr_e( 'Close saved stories', 'kamal-notebook' ); ?>">×</button>
		</div>
		<h2 id="kn-saved-title"><?php esc_html_e( 'Saved for later.', 'kamal-notebook' ); ?></h2>
		<div data-saved-content></div>
		<p class="dialog-note"><?php esc_html_e( 'Saved in this browser only.', 'kamal-notebook' ); ?></p>
	</dialog>
<?php endif; ?>
<footer class="site-footer container">
	<div class="footer-brand">
		<?php echo esc_html( get_bloginfo( 'name' ) ); ?><em>.</em>
		<small><?php echo esc_html( get_bloginfo( 'description' ) ); ?></small>
	</div>
	<div class="footer-right">
		<a href="#main"><?php esc_html_e( 'Back to top ↑', 'kamal-notebook' ); ?></a>
		<span>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
