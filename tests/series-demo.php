<?php
/** Run after importing the demonstration course: wp eval-file tests/series-demo.php */
defined( 'ABSPATH' ) || exit;

$assert = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};
$before_options = array( get_option( 'kn_settings' ), get_option( 'show_on_front' ), get_option( 'page_for_posts' ) );
$before_posts = get_posts( array( 'numberposts' => -1, 'post_status' => 'any' ) );
$before = array();
foreach ( $before_posts as $post ) {
	$before[ $post->ID ] = array( $post->post_title, $post->post_content, $post->post_status, get_post_meta( $post->ID ) );
}
$result = knt_import_demo_series();
$assert( ! isset( $result['error'] ), 'Course import must succeed.' );
$assert( 0 === $result['created'] && 3 === $result['skipped'], 'A repeated import must skip all three lessons.' );
$assert( $before_options === array( get_option( 'kn_settings' ), get_option( 'show_on_front' ), get_option( 'page_for_posts' ) ), 'Course import must preserve site settings.' );
foreach ( $before as $id => $snapshot ) {
	$post = get_post( $id );
	$assert( $snapshot === array( $post->post_title, $post->post_content, $post->post_status, get_post_meta( $id ) ), 'Repeated import changed an existing post.' );
}
$lessons = knt_series_lessons( $result['series_id'] );
$assert( array_column( $lessons, 'ID' ) === $result['ids'], 'Lessons must appear in course order.' );
foreach ( $lessons as $i => $lesson ) {
	$assert( str_starts_with( $lesson->post_title, 'Demonstration:' ), 'Lessons must be explicitly labeled.' );
	$context = knt_series_context( $lesson->ID );
	$assert( $context['position'] === $i + 1 && $context['total'] === 3, 'Lesson progress must match the course.' );
	$assert( ( $context['previous']['id'] ?? null ) === ( $lessons[ $i - 1 ]->ID ?? null ), 'Previous lesson must be correct.' );
	$assert( ( $context['next']['id'] ?? null ) === ( $lessons[ $i + 1 ]->ID ?? null ), 'Next lesson must be correct.' );
	$request = new WP_REST_Request( 'GET' );
	$request['id'] = $lesson->ID;
	$response = knt_series_lesson_response( $request )->get_data();
	$assert( $response['canSwap'] && count( $response['outline'] ) >= 2, 'Lessons need a swappable response and linked headings.' );
	if ( 1 === $i ) {
		$assert( str_contains( $response['content'], 'reading-list.js' ), 'Lesson two needs the rendered code example.' );
	}
}
echo "Series demo checks passed: repeat import, preserved content/settings, ordered links, REST content.\n";
