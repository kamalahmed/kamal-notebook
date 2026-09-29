<?php
/**
 * Plugin Name: Notebook Reading Time
 * Description: Adds a text reading-time estimate above individual blog posts.
 * Version: 1.0.0
 * Requires at least: 6.6
 * Requires PHP: 8.0
 * Author: Kamal Ahmed
 * License: GPL-2.0-or-later
 * Text Domain: notebook-reading-time
 */

defined( 'ABSPATH' ) || exit;

function knr_word_count( string $html ): int {
	$text = wp_strip_all_tags( str_replace( '><', '> <', $html ) );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$count = preg_match_all( '/[\p{L}\p{N}]+(?:[’\x{0027}-][\p{L}\p{N}]+)*/u', $text );
	return false === $count ? 0 : $count;
}

function knr_minutes( int $words ): int {
	$words_per_minute = 200;
	return (int) ceil( max( 0, $words ) / $words_per_minute );
}

function knr_add_reading_time( string $content ): string {
	if ( is_admin() || is_feed() || ! is_singular( 'post' )
		|| ! in_the_loop() || ! is_main_query() || post_password_required()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return $content;
	}

	$minutes = knr_minutes( knr_word_count( $content ) );
	if ( 0 === $minutes ) {
		return $content;
	}

	/* translators: %s: estimated reading time in minutes. */
	$label = sprintf(
		_n( '%s minute read', '%s minutes read', $minutes, 'notebook-reading-time' ),
		number_format_i18n( $minutes )
	);

	return '<p class="knr-reading-time">' . esc_html( $label ) . '</p>' . $content;
}
add_filter( 'the_content', 'knr_add_reading_time', 20 );
