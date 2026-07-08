<?php
/**
 * Generic product carousel (display only — products are handed in).
 *
 * Expected $args:
 *   'products'   => WC_Product[]  (required)
 *   'standalone' => bool — true when not wrapped by a [data-fares-carousel]
 *                   section (e.g. related products), so it owns the scope.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_products = $args['products'] ?? array();

if ( empty( $fares_products ) ) {
	return;
}
?>
<div class="fares-carousel"<?php echo empty( $args['standalone'] ) ? '' : ' data-fares-carousel'; ?>>
	<div class="fares-carousel__viewport">
		<ul class="products fares-carousel__track">
			<?php
			foreach ( $fares_products as $fares_product ) {
				$fares_post = get_post( $fares_product->get_id() );
				if ( ! $fares_post ) {
					continue;
				}
				setup_postdata( $GLOBALS['post'] = $fares_post ); // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments, WordPress.WP.GlobalVariablesOverride
				wc_get_template_part( 'content', 'product' );
			}
			wp_reset_postdata();
			?>
		</ul>
	</div>
</div>
