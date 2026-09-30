<?php
/** Interactive arithmetic example for the reading-time tutorial. */
defined( 'ABSPATH' ) || exit;

function knt_reading_lab_assets(): void {
	if ( is_singular() && has_shortcode( get_post()->post_content, 'notebook_reading_lab' ) ) {
		wp_enqueue_style( 'knt-reading-lab', knt_asset_url( 'reading-lab.css' ), array(), KNT_VERSION );
		wp_enqueue_script( 'knt-reading-lab', knt_asset_url( 'reading-lab.js' ), array(), KNT_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
add_action( 'wp_enqueue_scripts', 'knt_reading_lab_assets' );

function knt_reading_lab(): string {
	$id = wp_unique_id( 'reading-lab-' );
	ob_start();
	?>
	<section class="knt-reading-lab" data-reading-lab aria-label="Reading time calculator">
		<p class="knt-lab-label">TRY THE CALCULATION</p>
		<p>Change the word count or reading speed to see why we round up.</p>
		<div class="knt-lab-fields">
			<label for="<?php echo esc_attr( $id ); ?>words">Word count<input id="<?php echo esc_attr( $id ); ?>words" data-words type="number" min="0" max="1000000" step="1" value="450" inputmode="numeric"></label>
			<label for="<?php echo esc_attr( $id ); ?>speed">Words per minute<select id="<?php echo esc_attr( $id ); ?>speed" data-speed><option value="150">150 — slower</option><option value="200" selected>200 — tutorial default</option><option value="250">250 — faster</option></select></label>
		</div>
		<output aria-live="polite" aria-atomic="true" for="<?php echo esc_attr( $id ); ?>words <?php echo esc_attr( $id ); ?>speed">450 ÷ 200 = 2.25 → 3 minutes</output>
		<button type="button" data-reset>Reset example</button>
		<p class="knt-lab-note">An estimate for text only. This calculator runs in your browser; it does not install or execute PHP.</p>
		<noscript><p>Enable JavaScript to change the example. The formula is minutes = ceil(words ÷ words per minute).</p></noscript>
	</section>
	<?php
	return ob_get_clean();
}
add_shortcode( 'notebook_reading_lab', 'knt_reading_lab' );
