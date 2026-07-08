<?php
/**
 * Header hamburger button — opens the category drawer (desktop + mobile).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<button
	type="button"
	class="fares-header__menu-toggle"
	data-fares-menu-toggle
	aria-controls="fares-category-drawer"
	aria-expanded="false"
	aria-label="<?php esc_attr_e( 'القائمة الرئيسية', 'fares-theme' ); ?>"
>
	<?php fares_icon( 'menu' ); ?>
</button>
