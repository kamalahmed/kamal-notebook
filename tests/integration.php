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
$assert( WP_Block_Patterns_Registry::get_instance()->is_registered( 'kamal-notebook/guided-article' ), 'Guided Article starter is missing.' );
$assert( WP_Block_Patterns_Registry::get_instance()->is_registered( 'kamal-notebook/quick-note' ), 'Quick Note starter is missing.' );

list( $html, $outline ) = kn_prepare_article( '<h2>One &amp; two</h2><h2>One &amp; two</h2><h2 class="kn-utility-heading">Short version</h2><h2 id="custom">Another</h2><h2 id="custom">Again</h2>' );
$assert( 4 === count( $outline ), 'Utility headings must stay out of the table of contents.' );
$assert( array( 'one-two', 'one-two-2', 'custom', 'custom-2' ) === array_column( $outline, 'id' ), 'Heading anchors must be stable and unique.' );
$assert( str_contains( $html, 'id="custom-2"' ), 'Duplicate custom anchors must be updated in the rendered content.' );

$assert( WP_Block_Type_Registry::get_instance()->is_registered( 'kamal-notebook/code' ), 'Notebook Code block is missing.' );
$code = knt_render_code( array( 'language' => 'javascript', 'filename' => '<script>', 'code' => '<script>alert(1)</script>' ) );
$assert( ! str_contains( $code, '<script>' ) && str_contains( $code, '&lt;script&gt;' ), 'Code output must be escaped.' );

echo "Notebook integration checks passed.\n";
