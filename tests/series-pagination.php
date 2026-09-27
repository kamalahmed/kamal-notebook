<?php
$ids = array();
$term = wp_insert_term( 'Pagination fixture', KNT_SERIES_TAXONOMY, array( 'slug' => 'series-pagination-fixture-' . time() ) );
if ( is_wp_error( $term ) ) { throw new RuntimeException( $term->get_error_message() ); }
$term_id = (int) $term['term_id'];
try {
 $per_page = (int) get_option( 'posts_per_page' );
 for ( $i = 1; $i <= $per_page; $i++ ) {
  $id = wp_insert_post( array( 'post_title' => 'Pagination fixture ' . $i, 'post_status' => 'publish', 'meta_input' => array( KNT_SERIES_ORDER_META => $i ) ) );
  $ids[] = $id;
  wp_set_object_terms( $id, array( $term_id ), KNT_SERIES_TAXONOMY );
 }
 $url = get_term_link( $term_id, KNT_SERIES_TAXONOMY );
 $response = wp_remote_get( $url );
 $html = wp_remote_retrieve_body( $response );
 if ( 200 !== wp_remote_retrieve_response_code( $response ) || $per_page !== substr_count( $html, 'class="story-card"' ) || str_contains( $html, 'class="pagination"' ) ) {
  throw new RuntimeException( 'Exactly one page of lessons must fit on the first page.' );
 }
 $id = wp_insert_post( array( 'post_title' => 'Pagination final fixture', 'post_status' => 'publish', 'meta_input' => array( KNT_SERIES_ORDER_META => $per_page + 1 ) ) );
 $ids[] = $id; wp_set_object_terms( $id, array( $term_id ), KNT_SERIES_TAXONOMY );
 $response = wp_remote_get( trailingslashit( $url ) . 'page/2/' );
 $html = wp_remote_retrieve_body( $response );
 if ( 200 !== wp_remote_retrieve_response_code( $response ) || 1 !== substr_count( $html, 'class="story-card"' ) || ! str_contains( $html, 'Pagination final fixture' ) || ! str_contains( $html, 'Lesson ' . ( $per_page + 1 ) ) ) {
  throw new RuntimeException( 'The next lesson must render on page two with its correct number.' );
 }
 echo "Series pagination passed at {$per_page} and " . ( $per_page + 1 ) . " lessons.\n";
} finally {
 foreach ( $ids as $id ) { wp_delete_post( $id, true ); }
 wp_delete_term( $term_id, KNT_SERIES_TAXONOMY );
}
