<?php
/** Theme tools have one visible home in WordPress navigation. */
defined( 'ABSPATH' ) || exit;

function kn_admin_toolbar( WP_Admin_Bar $bar ): void {
 if ( ! current_user_can( 'edit_posts' ) ) { return; }
 $can_manage = current_user_can( 'manage_options' );
 $bar->add_node( array(
  'id' => 'kn-notebook',
  'title' => '<span class="ab-icon dashicons dashicons-book-alt" aria-hidden="true"></span><span class="kn-toolbar-label">' . esc_html__( 'Notebook', 'kamal-notebook' ) . '</span>',
  'href' => admin_url( $can_manage ? 'admin.php?page=kn-settings' : 'edit.php?page=knt-cover-studio' ),
  'meta' => array( 'title' => __( 'Notebook settings and tools', 'kamal-notebook' ) ),
 ) );
 if ( $can_manage ) {
  $bar->add_node( array( 'parent' => 'kn-notebook', 'id' => 'kn-notebook-settings', 'title' => __( 'Settings', 'kamal-notebook' ), 'href' => admin_url( 'admin.php?page=kn-settings' ) ) );
  $bar->add_node( array( 'parent' => 'kn-notebook', 'id' => 'kn-notebook-demo', 'title' => __( 'Import demo', 'kamal-notebook' ), 'href' => admin_url( 'admin.php?page=knt-demo-import' ) ) );
 }
 $bar->add_node( array( 'parent' => 'kn-notebook', 'id' => 'kn-notebook-covers', 'title' => __( 'Cover studio', 'kamal-notebook' ), 'href' => admin_url( 'admin.php?page=knt-cover-studio' ) ) );
}
add_action( 'admin_bar_menu', 'kn_admin_toolbar', 45 );

function kn_toolbar_styles(): void {
 if ( ! is_admin_bar_showing() || ! current_user_can( 'edit_posts' ) ) { return; }
 echo '<style>@media(max-width:782px){#wpadminbar #wp-admin-bar-kn-notebook{display:block}#wpadminbar #wp-admin-bar-kn-notebook>.ab-item{min-width:46px}#wpadminbar .kn-toolbar-label{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}}</style>';
}
add_action( 'wp_head', 'kn_toolbar_styles' );
add_action( 'admin_head', 'kn_toolbar_styles' );

/** Keep bookmarked links from previous theme versions usable. */
function kn_redirect_settings_bookmarks(): void {
 global $pagenow;
 if ( 'themes.php' !== $pagenow || ! isset( $_GET['page'] ) || ! is_string( $_GET['page'] ) ) { return; }
 $page = sanitize_key( wp_unslash( $_GET['page'] ) );
 if ( in_array( $page, array( 'kn-settings', 'knt-demo-import' ), true ) && current_user_can( 'manage_options' ) ) {
  wp_safe_redirect( admin_url( 'admin.php?page=' . $page ) );
  exit;
 }
}
add_action( 'admin_init', 'kn_redirect_settings_bookmarks' );
