<?php
/**
 * Footer important-links menu ("روابط مهمة").
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! has_nav_menu( 'footer-links' ) ) {
	return;
}
?>
<nav class="fares-footer__links" aria-label="<?php esc_attr_e( 'روابط مهمة', 'fares-theme' ); ?>">
	<h2 class="fares-footer__heading"><?php esc_html_e( 'روابط مهمة', 'fares-theme' ); ?></h2>
	<?php
	wp_nav_menu(
		array(
			'theme_location' => 'footer-links',
			'container'      => false,
			'menu_class'     => 'fares-footer__menu',
			'depth'          => 1,
		)
	);
	?>
</nav>
