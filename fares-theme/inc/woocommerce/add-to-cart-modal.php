<?php
/**
 * Renders the "added to cart" confirmation dialog in the footer.
 *
 * Skipped on cart/checkout where the popup adds no value (the user is already
 * in the funnel). The dialog stays inert until the JS opens it.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print the confirmation dialog shell once, at the end of the body.
 */
function fares_render_added_to_cart_modal(): void {
	if ( is_cart() || is_checkout() ) {
		return;
	}

	get_template_part( 'template-parts/global/added-to-cart-modal' );
}
add_action( 'wp_footer', 'fares_render_added_to_cart_modal' );
