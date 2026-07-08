<?php
/**
 * Account icon in the leading header cluster (mobile). Sits beside the menu
 * toggle; desktop hides it in favour of the labelled login pill in the actions
 * row. Hidden ≥ 782px via CSS.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_page_permalink' ) ) {
	return;
}
?>
<a class="fares-header__account" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
	<span class="screen-reader-text"><?php esc_html_e( 'حسابي', 'fares-theme' ); ?></span>
	<?php fares_icon( 'user' ); ?>
</a>
