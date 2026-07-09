<?php
/**
 * Generic homepage product section: optional decorative banner image,
 * section header row (title / view-all / arrows), product carousel.
 *
 * Expected $args:
 *   'title'       => string        (required)
 *   'products'    => WC_Product[]  (required)
 *   'view_all'    => string URL    (optional)
 *   'banner'      => string        (optional image file inside assets/images/figma/)
 *   'banner_id'   => int           (optional media-library attachment ID; wins over 'banner')
 *   'banner_link' => string URL    (optional; makes the banner clickable)
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_title       = $args['title'] ?? '';
$fares_products    = $args['products'] ?? array();
$fares_banner      = $args['banner'] ?? '';
$fares_banner_id   = absint( $args['banner_id'] ?? 0 );
$fares_banner_link = $args['banner_link'] ?? '';

if ( '' === $fares_title || empty( $fares_products ) ) {
	return;
}

$fares_banner_path = '' !== $fares_banner ? FARES_THEME_DIR . '/assets/images/figma/' . $fares_banner : '';

$fares_banner_html = '';
if ( $fares_banner_id ) {
	$fares_banner_html = wp_get_attachment_image(
		$fares_banner_id,
		'full',
		false,
		array(
			'class'   => 'fares-home-section__banner',
			'alt'     => '',
			'loading' => 'lazy',
		)
	);
} elseif ( '' !== $fares_banner_path && file_exists( $fares_banner_path ) ) {
	$fares_banner_html = sprintf(
		'<img class="fares-home-section__banner" src="%s" alt="" loading="lazy" />',
		esc_url( FARES_THEME_URI . '/assets/images/figma/' . $fares_banner )
	);
}
?>
<section class="fares-home-section fares-container" data-fares-carousel>
	<?php
	if ( '' !== $fares_banner_html ) {
		if ( '' !== $fares_banner_link ) {
			printf(
				'<a href="%s">%s</a>',
				esc_url( $fares_banner_link ),
				$fares_banner_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via wp_get_attachment_image / esc_url above.
			);
		} else {
			echo $fares_banner_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via wp_get_attachment_image / esc_url above.
		}
	}

	get_template_part(
		'template-parts/global/section-title',
		null,
		array(
			'title'    => $fares_title,
			'view_all' => $args['view_all'] ?? '',
		)
	);

	get_template_part(
		'template-parts/global/product-carousel',
		null,
		array( 'products' => $fares_products )
	);
	?>
</section>
