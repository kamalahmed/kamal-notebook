<?php
/** Run with: wp eval-file tests/integration.php --path=/path/to/wordpress */

defined( 'ABSPATH' ) || exit;

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};

$assert( 'grid' === kn_sanitize_settings( array( 'archive_layout' => 'invalid' ) )['archive_layout'], 'Invalid layouts must fall back to the grid.' );
$assert( 'index' === kn_sanitize_settings( array( 'archive_layout' => 'index' ) )['archive_layout'], 'The numbered layout must be available.' );
$assert( 'one@example.com, two@example.com' === kn_sanitize_settings( array( 'contact_email' => "one@example.com, invalid, two@example.com\none@example.com" ) )['contact_email'], 'Contact receivers must be valid and unique.' );
$assert( ! knt_contact_ready() || count( knt_contact_recipients() ) > 0, 'Contact form must require a configured receiver.' );
$assert( taxonomy_exists( 'knt_series' ), 'Series taxonomy is missing.' );
$assert( isset( get_registered_meta_keys( 'post', 'post' )['_knt_featured'] ), 'Featured article setting is missing.' );
$assert( file_exists( get_theme_file_path( 'page-about.php' ) ) && file_exists( get_theme_file_path( 'page-contact.php' ) ), 'Editorial page templates are missing.' );
$assert( WP_Block_Patterns_Registry::get_instance()->is_registered( 'kamal-notebook/guided-article' ), 'Guided Article starter is missing.' );
$assert( WP_Block_Patterns_Registry::get_instance()->is_registered( 'kamal-notebook/quick-note' ), 'Quick Note starter is missing.' );
$assert( WP_Block_Patterns_Registry::get_instance()->is_registered( 'kamal-notebook/tutorial-course' ), 'Tutorial course starter is missing.' );
$assert( WP_Block_Patterns_Registry::get_instance()->is_registered( 'kamal-notebook/lesson-step' ), 'Lesson step pattern is missing.' );
$assert( WP_Block_Patterns_Registry::get_instance()->is_registered( 'kamal-notebook/visual-walkthrough' ), 'Visual walkthrough pattern is missing.' );
$tutorial_pattern = WP_Block_Patterns_Registry::get_instance()->get_registered( 'kamal-notebook/tutorial-course' );
list( $tutorial_html, $tutorial_outline ) = kn_prepare_article( do_blocks( $tutorial_pattern['content'] ) );
$assert( 3 === count( $tutorial_outline ), 'Tutorial starter must yield three navigable lessons.' );
$assert( str_contains( $tutorial_html, 'kn-visual-walkthrough' ), 'Tutorial starter needs an editable visual example.' );
$assert( kn_has_tutorial_layout( $tutorial_pattern['content'] ), 'Tutorial starter must be recognized as a tutorial.' );
$assert( ! kn_has_tutorial_layout( '<!-- wp:paragraph --><p>Write kn-tutorial in a sentence.</p><!-- /wp:paragraph -->' ), 'Ordinary prose must not be labeled as a tutorial.' );

list( $html, $outline ) = kn_prepare_article( '<h2>One &amp; two</h2><h2>One &amp; two</h2><h2 class="kn-utility-heading">Short version</h2><h2 id="custom">Another</h2><h2 id="custom">Again</h2>' );
$assert( 4 === count( $outline ), 'Utility headings must stay out of the table of contents.' );
$assert( array( 'one-two', 'one-two-2', 'custom', 'custom-2' ) === array_column( $outline, 'id' ), 'Heading anchors must be stable and unique.' );
$assert( str_contains( $html, 'id="custom-2"' ), 'Duplicate custom anchors must be updated in the rendered content.' );

$assert( WP_Block_Type_Registry::get_instance()->is_registered( 'kamal-notebook/code' ), 'Notebook Code block is missing.' );
$assert( WP_Block_Patterns_Registry::get_instance()->is_registered( 'kamal-notebook/highlighted-code' ), 'Highlighted code example pattern is missing.' );
$code = knt_render_code( array( 'language' => 'javascript', 'filename' => '<script>', 'code' => '<script>alert(1)</script>' ) );
$assert( ! str_contains( $code, '<script>' ) && str_contains( $code, '&lt;script&gt;' ), 'Code output must be escaped.' );

$demo = knt_demo_posts();
$assert( count( $demo ) >= 3, 'Demo importer needs enough stories to show the prototype layout.' );
$assert( count( array_unique( array_column( $demo, 'key' ) ) ) === count( $demo ), 'Demo story keys must be unique for repeat imports.' );
foreach ( $demo as $story ) {
	$assert( str_starts_with( $story['title'], 'Demonstration:' ), 'Demo stories must be explicitly labeled.' );
	$assert( ! empty( parse_blocks( $story['content'] ) ), 'Demo stories must be editable block content.' );
}

echo "Notebook integration checks passed.\n";
