<?php
/** Theme setup and editorial helpers. */

defined( 'ABSPATH' ) || exit;

const KN_VERSION = '2.0.0';

// Preserve existing content/meta identifiers while moving all runtime code into the theme.
// An older extension may already have loaded on the first upgrade request.
if ( ! function_exists( 'knt_register_code_block' ) ) {
	require_once __DIR__ . '/includes/notebook.php';
}
function kn_retire_legacy_tools(): void {
	$legacy = 'kamal-notebook-tools/kamal-notebook-tools.php';
	if ( in_array( $legacy, (array) get_option( 'active_plugins', array() ), true ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( $legacy );
	}
}
add_action( 'after_setup_theme', 'kn_retire_legacy_tools' );
require_once __DIR__ . '/includes/upgrades.php';

function kn_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array( 'height' => 96, 'width' => 96, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_editor_style( array( 'assets/css/editor.css', 'assets/css/lessons.css' ) );
	register_nav_menus( array( 'primary' => __( 'Primary navigation', 'kamal-notebook' ) ) );
	add_image_size( 'kn-card', 900, 640, true );
	add_image_size( 'kn-feature', 1200, 900, true );
}
add_action( 'after_setup_theme', 'kn_setup' );

function kn_assets(): void {
	wp_enqueue_style( 'kn-site', get_theme_file_uri( 'assets/css/site.css' ), array(), KN_VERSION );
	if ( is_singular() && ! is_front_page() ) {
		wp_enqueue_style( 'kn-lessons', get_theme_file_uri( 'assets/css/lessons.css' ), array( 'kn-site' ), KN_VERSION );
	}
	if ( is_page( array( 'about', 'contact' ) ) || is_page_template( array( 'page-about.php', 'page-contact.php' ) ) ) {
		wp_enqueue_style( 'kn-pages', get_theme_file_uri( 'assets/css/pages.css' ), array( 'kn-site' ), KN_VERSION );
	}
	wp_enqueue_script( 'kn-site', get_theme_file_uri( 'assets/js/site.js' ), array(), KN_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	if ( is_singular( 'post' ) ) {
		wp_enqueue_style( 'kn-series', get_theme_file_uri( 'assets/css/series.css' ), array( 'kn-lessons' ), KN_VERSION );
		wp_enqueue_script( 'kn-reader', get_theme_file_uri( 'assets/js/reader.js' ), array(), KN_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
	wp_enqueue_style( 'kn-appearance', get_theme_file_uri( 'assets/css/appearance.css' ), array( 'kn-site' ), KN_VERSION );
}
add_action( 'wp_enqueue_scripts', 'kn_assets' );

function kn_editor_outline_assets(): void {
	wp_enqueue_script( 'kn-editor-outline', get_theme_file_uri( 'assets/js/editor-outline.js' ), array( 'wp-plugins', 'wp-editor', 'wp-element', 'wp-data', 'wp-block-editor', 'wp-i18n' ), KN_VERSION, true );
	wp_add_inline_script( 'kn-editor-outline', 'window.knFeatureAvailable = ' . ( function_exists( 'knt_featured_post' ) ? 'true' : 'false' ) . ';', 'before' );
	if ( function_exists( 'knt_cover_studio_page' ) ) {
		wp_add_inline_script( 'kn-editor-outline', 'window.knCoverStudioBase = ' . wp_json_encode( admin_url( 'admin.php?page=knt-cover-studio&post=' ) ) . ';', 'before' );
	}
	wp_enqueue_style( 'kn-editor-outline', get_theme_file_uri( 'assets/css/editor-outline.css' ), array(), KN_VERSION );
}
add_action( 'enqueue_block_editor_assets', 'kn_editor_outline_assets' );

function kn_preload_fonts(): void {
	$base = get_theme_file_uri( 'assets/fonts/' );
	foreach ( array( 'fraunces.woff2', 'dm-sans.woff2' ) as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $base . $font ) );
	}
}
add_action( 'wp_head', 'kn_preload_fonts', 2 );

/** Respect the owner's Site Icon; supply a lightweight default when unset. */
function kn_default_site_icon(): void {
	if ( ! has_site_icon() && ! is_customize_preview() ) {
		printf( '<link rel="icon" type="image/svg+xml" href="%s">' . "\n", esc_url( get_theme_file_uri( 'assets/images/favicon.svg' ) ) );
	}
}
add_action( 'wp_head', 'kn_default_site_icon' );

function kn_defaults(): array {
	$defaults = array(
		'archive_layout' => 'grid',
		'hero_prefix'    => 'Stay a little',
		'hero_accent'    => 'curious.',
		'hero_deck'      => 'Notes on building with code, thinking with new tools, and paying attention to the life happening around both.',
		'about_heading'  => 'The internet is better when we think out loud.',
		'about_text'     => 'A home for experiments, explanations, and personal stories: useful enough to return to, human enough to linger with.',
		'save_enabled'   => '1',
		'share_enabled'  => '1',
		'feedback_enabled' => '1',
		'contact_email'  => '',
	);
	foreach ( array( 'knt_featured_defaults', 'knt_security_defaults' ) as $provider ) {
		if ( function_exists( $provider ) ) { $defaults = array_merge( $defaults, $provider() ); }
	}
	return $defaults;
}

function kn_option( string $key ): string {
	$options = wp_parse_args( get_option( 'kn_settings', array() ), kn_defaults() );
	return (string) ( $options[ $key ] ?? '' );
}

function kn_sanitize_settings( $input ): array {
	$defaults = kn_defaults();
	$previous = get_option( 'kn_settings', array() );
	$previous = is_array( $previous ) ? $previous : array();
	$clean    = $previous;
	$input    = is_array( $input ) ? $input : array();
	$clean['archive_layout'] = in_array( $input['archive_layout'] ?? '', array( 'grid', 'index' ), true ) ? $input['archive_layout'] : $defaults['archive_layout'];
	foreach ( array( 'hero_prefix', 'hero_accent', 'hero_deck', 'about_heading', 'about_text' ) as $key ) {
		$clean[ $key ] = sanitize_text_field( $input[ $key ] ?? $defaults[ $key ] );
	}
	foreach ( array( 'save_enabled', 'share_enabled', 'feedback_enabled' ) as $key ) {
		$clean[ $key ] = empty( $input[ $key ] ) ? '0' : '1';
	}
	$receivers = array();
	$raw_receivers = isset( $input['contact_email'] ) && is_string( $input['contact_email'] ) ? $input['contact_email'] : '';
	foreach ( preg_split( '/[,;\s]+/', $raw_receivers ) as $candidate ) {
		$email = sanitize_email( $candidate );
		if ( $email === $candidate && is_email( $email ) ) {
			$receivers[] = $email;
		}
	}
	$clean['contact_email'] = implode( ', ', array_unique( $receivers ) );
	foreach ( array( 'knt_featured_sanitize', 'knt_security_sanitize' ) as $sanitizer ) {
		if ( function_exists( $sanitizer ) ) { $clean = array_merge( $clean, $sanitizer( $input, $previous ) ); }
	}
	return $clean;
}

function kn_settings_menu(): void {
	add_menu_page( __( 'Notebook settings', 'kamal-notebook' ), __( 'Notebook', 'kamal-notebook' ), 'manage_options', 'kn-settings', 'kn_render_settings', 'dashicons-book-alt', 1 );
	add_submenu_page( 'kn-settings', __( 'Notebook settings', 'kamal-notebook' ), __( 'Settings', 'kamal-notebook' ), 'manage_options', 'kn-settings', 'kn_render_settings' );
}
add_action( 'admin_menu', 'kn_settings_menu', 9 );
require_once __DIR__ . '/includes/admin-navigation.php';

function kn_register_settings(): void {
	register_setting( 'kn_settings', 'kn_settings', array( 'type' => 'object', 'sanitize_callback' => 'kn_sanitize_settings', 'default' => kn_defaults() ) );
}
add_action( 'admin_init', 'kn_register_settings' );

require_once __DIR__ . '/includes/settings.php';

function kn_setting_field( string $key, string $label, bool $long = false ): void {
	printf( '<p><label for="kn-%1$s"><strong>%2$s</strong></label><br>', esc_attr( $key ), esc_html( $label ) );
	if ( $long ) {
		printf( '<textarea id="kn-%1$s" name="kn_settings[%1$s]" rows="3" class="large-text">%2$s</textarea>', esc_attr( $key ), esc_textarea( kn_option( $key ) ) );
	} else {
		printf( '<input id="kn-%1$s" name="kn_settings[%1$s]" type="text" class="regular-text" value="%2$s">', esc_attr( $key ), esc_attr( kn_option( $key ) ) );
	}
	echo '</p>';
}

function kn_setting_checkbox( string $key, string $label ): void {
	printf( '<p><label><input type="checkbox" name="kn_settings[%1$s]" value="1" %2$s> %3$s</label></p>', esc_attr( $key ), checked( kn_option( $key ), '1', false ), esc_html( $label ) );
}

function kn_admin_assets( string $hook ): void {
	if ( in_array( $hook, array( 'toplevel_page_kn-settings', 'appearance_page_kn-settings' ), true ) ) {
		wp_enqueue_style( 'kn-admin', get_theme_file_uri( 'assets/css/admin.css' ), array(), KN_VERSION );
		wp_enqueue_script( 'kn-admin-settings', get_theme_file_uri( 'assets/js/admin-settings.js' ), array(), KN_VERSION, true );
	}
}
add_action( 'admin_enqueue_scripts', 'kn_admin_assets' );

function kn_body_classes( array $classes ): array {
	$classes[] = 'kn-layout-' . kn_option( 'archive_layout' );
	return $classes;
}
add_filter( 'body_class', 'kn_body_classes' );

function kn_reading_minutes( int $post_id ): int {
	$words = str_word_count( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) );
	return max( 1, (int) ceil( $words / 220 ) );
}

function kn_post_topic( int $post_id ): string {
	$categories = get_the_category( $post_id );
	return $categories ? $categories[0]->name : __( 'Journal', 'kamal-notebook' );
}

function kn_story_excerpt( int $post_id, int $words = 28 ): string {
	return wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), $words, '…' );
}

function kn_post_image( int $post_id, string $size = 'kn-card', bool $eager = false ): string {
	if ( has_post_thumbnail( $post_id ) ) {
		// Article covers can contain type: use the original proportions, including older uploads.
		$size = 'kn-feature' === $size ? 'full' : $size;
		return get_the_post_thumbnail( $post_id, $size, array( 'loading' => $eager ? 'eager' : 'lazy', 'decoding' => 'async', 'fetchpriority' => $eager ? 'high' : 'auto' ) );
	}
	$art = array( 'art-programming.svg', 'art-ai.svg', 'art-tech.svg', 'art-journal.svg', 'art-questions.svg' );
	$demo_art = (string) get_post_meta( $post_id, '_kn_demo_art', true );
	$file = in_array( $demo_art, $art, true ) ? $demo_art : $art[ $post_id % count( $art ) ];
	$src = get_theme_file_uri( 'assets/images/' . $file );
	return sprintf( '<img src="%s" alt="" width="640" height="470" loading="%s" decoding="async"%s>', esc_url( $src ), $eager ? 'eager' : 'lazy', $eager ? ' fetchpriority="high"' : '' );
}

/** Recognize the tutorial starter by its block class, not by words in prose. */
function kn_has_tutorial_layout( string $content ): bool {
	$has_tutorial = static function ( array $blocks ) use ( &$has_tutorial ): bool {
		foreach ( $blocks as $block ) {
			$classes = preg_split( '/\s+/', (string) ( $block['attrs']['className'] ?? '' ) );
			if ( in_array( 'kn-tutorial', $classes, true ) || $has_tutorial( $block['innerBlocks'] ?? array() ) ) {
				return true;
			}
		}
		return false;
	};
	return $has_tutorial( parse_blocks( $content ) );
}

/** Add stable IDs to rendered H2 headings and return the linked outline. */
function kn_prepare_article( string $html ): array {
	$outline = array();
	$used    = array();
	$html    = preg_replace_callback(
		'~<h2\b([^>]*)>(.*?)</h2>~is',
		static function ( array $match ) use ( &$outline, &$used ): string {
			if ( str_contains( $match[1], 'kn-utility-heading' ) ) {
				return $match[0];
			}
			$title = trim( html_entity_decode( wp_strip_all_tags( $match[2] ), ENT_QUOTES, 'UTF-8' ) );
			if ( '' === $title ) {
				return $match[0];
			}
			$attrs = $match[1];
			$has_id = preg_match( '~\bid\s*=\s*["\']([^"\']+)["\']~i', $attrs, $found );
			$base   = sanitize_title( $has_id ? $found[1] : $title ) ?: 'section';
			$id     = $base;
			$next   = 2;
			while ( isset( $used[ $id ] ) ) {
				$id = $base . '-' . $next++;
			}
			if ( $has_id ) {
				$attrs = preg_replace( '~\bid\s*=\s*["\'][^"\']+["\']~i', 'id="' . esc_attr( $id ) . '"', $attrs, 1 );
			} else {
				$attrs .= ' id="' . esc_attr( $id ) . '"';
			}
			$used[ $id ] = true;
			$outline[]   = array( 'id' => $id, 'title' => $title );
			return '<h2' . $attrs . '>' . $match[2] . '</h2>';
		},
		$html
	);
	return array( $html, $outline );
}

function kn_register_pattern_category(): void {
	register_block_pattern_category( 'kn-writing', array( 'label' => __( 'Notebook writing pieces', 'kamal-notebook' ) ) );
}
add_action( 'init', 'kn_register_pattern_category' );

function kn_archive_url(): string {
	$page = (int) get_option( 'page_for_posts' );
	return $page ? get_permalink( $page ) : home_url( '/' );
}

function kn_menu_fallback(): void {
	echo '<ul>';
	printf( '<li><a href="%s">%s</a></li>', esc_url( kn_archive_url() . '#stories' ), esc_html__( 'Explore stories', 'kamal-notebook' ) );
	foreach ( array( 'about' => __( 'About', 'kamal-notebook' ), 'contact' => __( 'Contact', 'kamal-notebook' ) ) as $slug => $label ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			printf( '<li><a href="%s">%s</a></li>', esc_url( get_permalink( $page ) ), esc_html( $label ) );
		}
	}
	echo '</ul>';
}
