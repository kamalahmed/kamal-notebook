<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#f6f2e9">
	<?php
	$description = is_singular() && has_excerpt()
		? get_the_excerpt()
		: ( is_front_page() || is_home() ? kn_option( 'hero_deck' ) : get_bloginfo( 'description' ) );
	if ( $description ) :
		?>
		<meta name="description" content="<?php echo esc_attr( wp_trim_words( wp_strip_all_tags( $description ), 32, '…' ) ); ?>">
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="reading-progress" id="reading-progress" aria-hidden="true"></div>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'kamal-notebook' ); ?></a>
<div class="topline">
	<div class="container topline-inner">
		<span><?php esc_html_e( 'Personal notes on making things', 'kamal-notebook' ); ?></span>
		<span><?php esc_html_e( 'Programming ✳ Technology ✳ Life', 'kamal-notebook' ); ?></span>
	</div>
</div>
<header class="site-header container">
	<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php if ( has_custom_logo() ) : ?>
			<span class="brand-logo"><?php echo wp_get_attachment_image( get_theme_mod( 'custom_logo' ), 'thumbnail', false, array( 'alt' => '' ) ); ?></span>
		<?php else : ?>
			<span class="brand-mark" aria-hidden="true">k<span>.</span></span>
		<?php endif; ?>
		<span class="brand-text">
			<?php echo esc_html( get_bloginfo( 'name' ) ); ?>
			<small><?php esc_html_e( 'THE NOTEBOOK', 'kamal-notebook' ); ?></small>
		</span>
	</a>
	<nav class="main-nav" aria-label="<?php esc_attr_e( 'Main navigation', 'kamal-notebook' ); ?>">
		<?php wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'fallback_cb' => 'kn_menu_fallback', 'depth' => 1 ) ); ?>
	</nav>
	<?php if ( '1' === kn_option( 'save_enabled' ) ) : ?>
		<button class="saved-trigger" type="button" data-open-saved>
			<?php esc_html_e( 'Saved', 'kamal-notebook' ); ?>
			<span class="saved-count" data-saved-count>0</span>
		</button>
	<?php endif; ?>
</header>
<main id="main">
