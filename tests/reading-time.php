<?php
/** Run in WordPress with wp eval-file. No persistent content is created. */
$example = dirname( __DIR__ ) . '/tutorials/reading-time/notebook-reading-time/notebook-reading-time.php';
if ( ! file_exists( $example ) ) { throw new RuntimeException( 'The downloadable tutorial plugin is missing.' ); }
require_once $example;
$cases = [
 ['<p>Hello</p><p>world</p>', 2],
 ['Hello&nbsp;world', 2],
 ['<script>one two</script><style>three four</style>Visible', 1],
 ['<p></p>', 0],
 ['It’s a well-made café.', 4],
];
foreach ( $cases as [$html, $expected] ) {
 if ( $expected !== knr_word_count( $html ) ) { throw new RuntimeException( 'Unexpected count for ' . $html ); }
}
foreach ( [0=>0, 1=>1, 200=>1, 201=>2, 400=>2, 401=>3] as $words=>$expected ) {
 if ( knr_minutes( $words ) !== $expected ) { throw new RuntimeException( 'Rounding failed: ' . $words ); }
}
if ( knr_add_reading_time( '<p>Outside the main post.</p>' ) !== '<p>Outside the main post.</p>' ) { throw new RuntimeException( 'Content outside a post was modified.' ); }
echo "Tutorial plugin: HTML, entities, Unicode words, empty text, rounding and query guard passed.\n";
$test_post = wp_insert_post( ['post_type'=>'post','post_status'=>'publish','post_title'=>'Temporary reading-time check','post_content'=>str_repeat('hello ',201)] );
$old_query = $GLOBALS['wp_query'];
$old_main = $GLOBALS['wp_the_query'];
try {
 $query = new WP_Query(['p'=>$test_post]);
 $GLOBALS['wp_query'] = $query;
 $GLOBALS['wp_the_query'] = $query;
 $query->the_post();
 $rendered = apply_filters('the_content', get_the_content());
 if ( ! str_contains($rendered,'2 minutes read') || ! str_contains($rendered,'hello') ) { throw new RuntimeException('The real post filter did not prepend the estimate and preserve content.'); }
} finally {
 $GLOBALS['wp_query']=$old_query;
 $GLOBALS['wp_the_query']=$old_main;
 wp_reset_postdata();
 wp_delete_post($test_post,true);
}
echo "Real WordPress post loop: estimate rendered and original text retained.\n";
