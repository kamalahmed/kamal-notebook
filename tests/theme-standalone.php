<?php
/** Run with wp eval-file --skip-plugins: all Notebook capabilities belong to the theme. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit; }
foreach ( array( 'knt_import_demo', 'knt_cover_studio_page', 'knt_contact_process', 'knt_contact_verify_captcha', 'knt_series_lessons', 'knt_render_code', 'knt_submit_feedback' ) as $capability ) {
 if ( ! function_exists( $capability ) ) { throw new RuntimeException( 'Theme-only capability missing: ' . $capability ); }
 $file=(new ReflectionFunction($capability))->getFileName();
 if ( ! str_starts_with( realpath($file), realpath(get_template_directory()).DIRECTORY_SEPARATOR ) ) { throw new RuntimeException('Capability loaded outside theme: '.$capability); }
}
if ('off' !== knt_security_defaults()['contact_captcha']) { throw new RuntimeException('Fresh installs must use built-in protection by default.'); }
if ( ! WP_Block_Type_Registry::get_instance()->is_registered('kamal-notebook/code') || !taxonomy_exists('knt_series') ) {throw new RuntimeException('Theme code block and courses must register without plugins.');}
echo "Theme-only importer, covers, contact, protection, courses, code, and feedback available.\n";
