<?php
/**
 * Generic homepage product section: optional decorative banner image,
 * section header row (title / view-all / arrows), product carousel.
 *
 * Expected $args:
 *   'title'    => string        (required)
 *   'products' => WC_Product[]  (required)
 *   'view_all' => string URL    (optional)
 *   'banner'   => string        (optional image file inside assets/images/figma/)
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_title    = $args['title'] ?? '';
$fares_products = $args['products'] ?? array();
$fares_banner   = $args['banner'] ?? '';

if ( '' === $fares_title || empty( $fares_products ) ) {
	return;
}

$fares_banner_path = '' !== $fares_banner ? FARES_THEME_DIR . '/assets/images/figma/' . $fares_banner : '';
?>
<section class="fares-home-section fares-container" data-fares-carousel>
	<?php if ( '' !== $fares_banner_path && file_exists( $fares_banner_path ) ) : ?>
		<img
			class="fares-home-section__banner"
			src="<?php echo esc_url( FARES_THEME_URI . '/assets/images/figma/' . $fares_banner ); ?>"
			alt=""
			loading="lazy"
		/>
	<?php endif; ?>

	<?php
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
