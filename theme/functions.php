<?php
/** Theme setup and editorial helpers. */

defined( 'ABSPATH' ) || exit;

const KN_VERSION = '1.0.0';

function kn_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array( 'height' => 96, 'width' => 96, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_editor_style( 'assets/css/editor.css' );
	register_nav_menus( array( 'primary' => __( 'Primary navigation', 'kamal-notebook' ) ) );
	add_image_size( 'kn-card', 900, 640, true );
	add_image_size( 'kn-feature', 1200, 900, true );
}
add_action( 'after_setup_theme', 'kn_setup' );

function kn_assets(): void {
	wp_enqueue_style( 'kn-site', get_theme_file_uri( 'assets/css/site.css' ), array(), KN_VERSION );
	wp_enqueue_script( 'kn-site', get_theme_file_uri( 'assets/js/site.js' ), array(), KN_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	if ( is_singular( 'post' ) ) {
		wp_enqueue_script( 'kn-reader', get_theme_file_uri( 'assets/js/reader.js' ), array(), KN_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
}
add_action( 'wp_enqueue_scripts', 'kn_assets' );

function kn_preload_fonts(): void {
	$base = get_theme_file_uri( 'assets/fonts/' );
	foreach ( array( 'fraunces.woff2', 'dm-sans.woff2' ) as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( $base . $font ) );
	}
}
add_action( 'wp_head', 'kn_preload_fonts', 2 );

function kn_defaults(): array {
	return array(
		'archive_layout' => 'grid',
		'hero_prefix'    => 'Stay a little',
		'hero_accent'    => 'curious.',
		'hero_deck'      => 'Notes on building with code, thinking with new tools, and paying attention to the life happening around both.',
		'about_heading'  => 'The internet is better when we think out loud.',
		'about_text'     => 'A home for experiments, explanations, and personal stories: useful enough to return to, human enough to linger with.',
		'save_enabled'   => '1',
		'share_enabled'  => '1',
		'feedback_enabled' => '1',
	);
}

function kn_option( string $key ): string {
	$options = wp_parse_args( get_option( 'kn_settings', array() ), kn_defaults() );
	return (string) ( $options[ $key ] ?? '' );
}

function kn_sanitize_settings( $input ): array {
	$defaults = kn_defaults();
	$clean    = array();
	$input    = is_array( $input ) ? $input : array();
	$clean['archive_layout'] = in_array( $input['archive_layout'] ?? '', array( 'grid', 'index' ), true ) ? $input['archive_layout'] : $defaults['archive_layout'];
	foreach ( array( 'hero_prefix', 'hero_accent', 'hero_deck', 'about_heading', 'about_text' ) as $key ) {
		$clean[ $key ] = sanitize_text_field( $input[ $key ] ?? $defaults[ $key ] );
	}
	foreach ( array( 'save_enabled', 'share_enabled', 'feedback_enabled' ) as $key ) {
		$clean[ $key ] = empty( $input[ $key ] ) ? '0' : '1';
	}
	return $clean;
}

function kn_settings_menu(): void {
	add_theme_page( __( 'Notebook settings', 'kamal-notebook' ), __( 'Notebook settings', 'kamal-notebook' ), 'manage_options', 'kn-settings', 'kn_render_settings' );
}
add_action( 'admin_menu', 'kn_settings_menu' );

function kn_register_settings(): void {
	register_setting( 'kn_settings', 'kn_settings', array( 'type' => 'object', 'sanitize_callback' => 'kn_sanitize_settings', 'default' => kn_defaults() ) );
}
add_action( 'admin_init', 'kn_register_settings' );

function kn_render_settings(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap kn-admin">
		<h1><?php esc_html_e( 'Notebook settings', 'kamal-notebook' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Choose the archive view and edit the short introductions. Write articles in Posts using the Guided Article or Quick Note starter.', 'kamal-notebook' ); ?></p>
		<form method="post" action="options.php">
			<?php settings_fields( 'kn_settings' ); ?>
			<h2><?php esc_html_e( 'Blog listing', 'kamal-notebook' ); ?></h2>
			<div class="kn-layout-choices">
				<label>
					<input type="radio" name="kn_settings[archive_layout]" value="grid" <?php checked( kn_option( 'archive_layout' ), 'grid' ); ?>>
					<span class="kn-layout-preview kn-preview-grid"><i></i><i></i><i></i></span>
					<strong><?php esc_html_e( 'Illustrated grid', 'kamal-notebook' ); ?></strong>
					<small><?php esc_html_e( 'The original prototype.', 'kamal-notebook' ); ?></small>
				</label>
				<label>
					<input type="radio" name="kn_settings[archive_layout]" value="index" <?php checked( kn_option( 'archive_layout' ), 'index' ); ?>>
					<span class="kn-layout-preview kn-preview-index"><i></i><i></i><i></i></span>
					<strong><?php esc_html_e( 'Numbered index', 'kamal-notebook' ); ?></strong>
					<small><?php esc_html_e( 'The second prototype listing, in the original palette.', 'kamal-notebook' ); ?></small>
				</label>
			</div>
			<h2><?php esc_html_e( 'Home introduction', 'kamal-notebook' ); ?></h2>
			<?php kn_setting_field( 'hero_prefix', __( 'Headline', 'kamal-notebook' ) ); ?>
			<?php kn_setting_field( 'hero_accent', __( 'Accented word or phrase', 'kamal-notebook' ) ); ?>
			<?php kn_setting_field( 'hero_deck', __( 'Introduction', 'kamal-notebook' ), true ); ?>
			<h2><?php esc_html_e( 'About band', 'kamal-notebook' ); ?></h2>
			<?php kn_setting_field( 'about_heading', __( 'Heading', 'kamal-notebook' ) ); ?>
			<?php kn_setting_field( 'about_text', __( 'Short description', 'kamal-notebook' ), true ); ?>
			<h2><?php esc_html_e( 'Reader tools', 'kamal-notebook' ); ?></h2>
			<p><?php esc_html_e( 'Choose which actions appear below an article. Feedback requires the Notebook Tools plugin.', 'kamal-notebook' ); ?></p>
			<?php kn_setting_checkbox( 'save_enabled', __( 'Save stories in the reader’s browser', 'kamal-notebook' ) ); ?>
			<?php kn_setting_checkbox( 'share_enabled', __( 'Share via device menu or copy link', 'kamal-notebook' ) ); ?>
			<?php kn_setting_checkbox( 'feedback_enabled', __( 'Private “Was this useful?” feedback', 'kamal-notebook' ) ); ?>
			<?php submit_button( __( 'Save notebook settings', 'kamal-notebook' ) ); ?>
		</form>
		<div class="kn-admin-help">
			<h2><?php esc_html_e( 'A simple way to write', 'kamal-notebook' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Open Posts → Add New and choose Guided Article or Quick Note.', 'kamal-notebook' ); ?></li>
				<li><?php esc_html_e( 'Replace the visible draft prompts, add sections from the pattern inserter, and set a short excerpt.', 'kamal-notebook' ); ?></li>
				<li><?php esc_html_e( 'Use Heading 2 for sections. The article table of contents builds itself.', 'kamal-notebook' ); ?></li>
				<li><?php esc_html_e( 'Use the Notebook Code block for highlighted code and a copy button.', 'kamal-notebook' ); ?></li>
			</ol>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>"><?php esc_html_e( 'Write a new post', 'kamal-notebook' ); ?></a>
		</div>
	</div>
	<?php
}

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
	if ( 'appearance_page_kn-settings' === $hook ) {
		wp_enqueue_style( 'kn-admin', get_theme_file_uri( 'assets/css/admin.css' ), array(), KN_VERSION );
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

function kn_post_image( int $post_id, string $size = 'kn-card' ): string {
	if ( has_post_thumbnail( $post_id ) ) {
		return get_the_post_thumbnail( $post_id, $size, array( 'loading' => 'lazy', 'decoding' => 'async' ) );
	}
	$art = array( 'art-programming.svg', 'art-ai.svg', 'art-tech.svg', 'art-journal.svg', 'art-questions.svg' );
	$src = get_theme_file_uri( 'assets/images/' . $art[ $post_id % count( $art ) ] );
	return sprintf( '<img src="%s" alt="" width="640" height="470" loading="lazy" decoding="async">', esc_url( $src ) );
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
	printf( '<ul><li><a href="%s">%s</a></li><li><a href="%s">%s</a></li></ul>', esc_url( kn_archive_url() ), esc_html__( 'The notebook', 'kamal-notebook' ), esc_url( kn_archive_url() . '#stories' ), esc_html__( 'Explore stories', 'kamal-notebook' ) );
}
