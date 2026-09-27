<?php
/** Local illustrated cover generator. The browser draws; WordPress stores the approved PNG. */

defined( 'ABSPATH' ) || exit;

function knt_cover_studio_menu(): void {
	add_submenu_page( 'edit.php', __( 'Notebook cover studio', 'kamal-notebook-tools' ), __( 'Cover studio', 'kamal-notebook-tools' ), 'edit_posts', 'knt-cover-studio', 'knt_cover_studio_page' );
}
add_action( 'admin_menu', 'knt_cover_studio_menu' );

function knt_cover_studio_assets( string $hook ): void {
	if ( 'posts_page_knt-cover-studio' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'knt-cover-studio', plugins_url( 'assets/cover-studio.css', dirname( __DIR__ ) . '/kamal-notebook-tools.php' ), array(), KNT_VERSION );
	wp_enqueue_script( 'knt-cover-studio', plugins_url( 'assets/cover-studio.js', dirname( __DIR__ ) . '/kamal-notebook-tools.php' ), array(), KNT_VERSION, true );
	wp_add_inline_script( 'knt-cover-studio', 'window.kntCoverStudio = ' . wp_json_encode( array(
		'endpoint' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce( 'knt_save_cover' ),
		'siteName' => get_bloginfo( 'name' ),
	) ) . ';', 'before' );
}
add_action( 'admin_enqueue_scripts', 'knt_cover_studio_assets' );

function knt_cover_studio_page(): void {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}
	$selected = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ), 'numberposts' => 100, 'orderby' => 'modified', 'order' => 'DESC' ) );
	$selected_post = $selected && current_user_can( 'edit_post', $selected ) ? get_post( $selected ) : null;
	?>
	<div class="wrap knt-cover-studio">
		<div class="knt-cover-heading"><span><?php esc_html_e( 'THE NOTEBOOK / IMAGE MAKING', 'kamal-notebook-tools' ); ?></span><h1><?php esc_html_e( 'Cover studio', 'kamal-notebook-tools' ); ?></h1><p><?php esc_html_e( 'Compose an illustrated cover from the notebook palette. Preview a variation, then save it to the Media Library and set it as this post’s Featured image.', 'kamal-notebook-tools' ); ?></p></div>
		<div class="knt-cover-grid">
			<form id="knt-cover-form" class="knt-cover-controls">
				<label for="knt-cover-post"><?php esc_html_e( 'Post', 'kamal-notebook-tools' ); ?></label>
				<select id="knt-cover-post" required>
					<option value=""><?php esc_html_e( 'Choose a post', 'kamal-notebook-tools' ); ?></option>
					<?php if ( $selected_post && 'post' === $selected_post->post_type && ! in_array( $selected, wp_list_pluck( $posts, 'ID' ), true ) ) : ?>
						<option value="<?php echo esc_attr( $selected ); ?>" data-title="<?php echo esc_attr( get_the_title( $selected ) ); ?>" data-recipe="<?php echo esc_attr( get_post_meta( get_post_thumbnail_id( $selected ), '_knt_cover_recipe', true ) ); ?>" selected><?php echo esc_html( get_the_title( $selected ) ); ?></option>
					<?php endif; ?>
					<?php foreach ( $posts as $post ) : if ( ! current_user_can( 'edit_post', $post->ID ) ) { continue; } ?>
						<option value="<?php echo esc_attr( $post->ID ); ?>" data-title="<?php echo esc_attr( get_the_title( $post ) ); ?>" data-recipe="<?php echo esc_attr( get_post_meta( get_post_thumbnail_id( $post->ID ), '_knt_cover_recipe', true ) ); ?>" <?php selected( $selected, $post->ID ); ?>><?php echo esc_html( get_the_title( $post ) ?: __( '(Untitled)', 'kamal-notebook-tools' ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<label for="knt-cover-title"><?php esc_html_e( 'Cover title', 'kamal-notebook-tools' ); ?></label>
				<input id="knt-cover-title" type="text" maxlength="100" required value="<?php echo esc_attr( $selected_post && 'post' === $selected_post->post_type ? get_the_title( $selected ) : '' ); ?>">
				<label for="knt-cover-tags"><?php esc_html_e( 'Design tags', 'kamal-notebook-tools' ); ?></label>
				<input id="knt-cover-tags" type="text" maxlength="100" placeholder="<?php esc_attr_e( 'e.g. code, learning, notebook', 'kamal-notebook-tools' ); ?>">
				<p class="description"><?php esc_html_e( 'Up to three words or short phrases appear on the cover and influence the variation.', 'kamal-notebook-tools' ); ?></p>
				<div class="knt-cover-pair"><div><label for="knt-cover-palette"><?php esc_html_e( 'Palette', 'kamal-notebook-tools' ); ?></label><select id="knt-cover-palette"><option value="forest"><?php esc_html_e( 'Forest', 'kamal-notebook-tools' ); ?></option><option value="sage"><?php esc_html_e( 'Sage', 'kamal-notebook-tools' ); ?></option><option value="night"><?php esc_html_e( 'Night', 'kamal-notebook-tools' ); ?></option></select></div><div><label for="knt-cover-motif"><?php esc_html_e( 'Illustration', 'kamal-notebook-tools' ); ?></label><select id="knt-cover-motif"><option value="orbit"><?php esc_html_e( 'Orbit', 'kamal-notebook-tools' ); ?></option><option value="pages"><?php esc_html_e( 'Pages', 'kamal-notebook-tools' ); ?></option><option value="path"><?php esc_html_e( 'Path', 'kamal-notebook-tools' ); ?></option><option value="botanical"><?php esc_html_e( 'Botanical', 'kamal-notebook-tools' ); ?></option><option value="weave"><?php esc_html_e( 'Weave', 'kamal-notebook-tools' ); ?></option><option value="horizon"><?php esc_html_e( 'Horizon', 'kamal-notebook-tools' ); ?></option><option value="windows"><?php esc_html_e( 'Windows', 'kamal-notebook-tools' ); ?></option></select></div></div>
			<div class="knt-cover-buttons"><button type="button" class="button" id="knt-cover-variation"><?php esc_html_e( 'New variation', 'kamal-notebook-tools' ); ?></button><button type="submit" class="button button-primary" id="knt-cover-save"><?php esc_html_e( 'Save as Featured image', 'kamal-notebook-tools' ); ?></button></div>
			<p id="knt-cover-status" role="status" aria-live="polite"></p>
			<p class="description"><?php esc_html_e( 'Images are generated in your browser with no API key or monthly limit. This makes graphic covers, not AI photographs.', 'kamal-notebook-tools' ); ?></p>
			</form>
			<div class="knt-cover-preview"><canvas id="knt-cover-canvas" width="1200" height="900" role="img" aria-label="<?php esc_attr_e( 'Generated cover preview', 'kamal-notebook-tools' ); ?>"></canvas><span>1200 × 900 PNG · <?php esc_html_e( 'Preview', 'kamal-notebook-tools' ); ?></span></div>
		</div>
	</div>
	<?php
}

function knt_save_cover(): void {
	check_ajax_referer( 'knt_save_cover', 'nonce' );
	$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
	if ( 'post' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Choose a post you can edit.', 'kamal-notebook-tools' ) ), 403 );
	}
	if ( empty( $_FILES['cover']['tmp_name'] ) || (int) ( $_FILES['cover']['size'] ?? 0 ) > 4 * MB_IN_BYTES || ! is_uploaded_file( $_FILES['cover']['tmp_name'] ) ) {
		wp_send_json_error( array( 'message' => __( 'The cover file is missing or too large.', 'kamal-notebook-tools' ) ), 400 );
	}
	$path = $_FILES['cover']['tmp_name'];
	$bytes = file_get_contents( $path );
	$size = $bytes ? getimagesizefromstring( $bytes ) : false;
	if ( ! $size || 1200 !== $size[0] || 900 !== $size[1] || 'image/png' !== $size['mime'] ) {
		wp_send_json_error( array( 'message' => __( 'The cover must be a 1200 by 900 PNG.', 'kamal-notebook-tools' ) ), 400 );
	}
	$filename = 'notebook-cover-' . $post_id . '-' . wp_date( 'Ymd-His' ) . '.png';
	$upload = wp_upload_bits( $filename, null, $bytes );
	if ( $upload['error'] ) {
		wp_send_json_error( array( 'message' => __( 'WordPress could not save this image.', 'kamal-notebook-tools' ) ), 500 );
	}
	$attachment_id = wp_insert_attachment( array(
		'post_mime_type' => 'image/png',
		'post_title' => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
		'post_status' => 'inherit',
	), $upload['file'], $post_id, true );
	if ( is_wp_error( $attachment_id ) ) {
		wp_delete_file( $upload['file'] );
		wp_send_json_error( array( 'message' => __( 'WordPress could not add this image to the Media Library.', 'kamal-notebook-tools' ) ), 500 );
	}
	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ) );
	set_post_thumbnail( $post_id, $attachment_id );
	update_post_meta( $attachment_id, '_knt_cover_recipe', wp_json_encode( array(
		'title' => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
		'tags' => sanitize_text_field( wp_unslash( $_POST['tags'] ?? '' ) ),
		'palette' => sanitize_key( wp_unslash( $_POST['palette'] ?? '' ) ),
		'motif' => sanitize_key( wp_unslash( $_POST['motif'] ?? '' ) ),
		'seed' => absint( $_POST['seed'] ?? 0 ),
		'version' => 1,
	) ) );
	wp_send_json_success( array( 'url' => wp_get_attachment_url( $attachment_id ), 'editUrl' => get_edit_post_link( $post_id, 'raw' ) ) );
}
add_action( 'wp_ajax_knt_save_cover', 'knt_save_cover' );
