<?php
/**
 * Banner-only homepage band (dashboard-managed "بانر فقط" section).
 *
 * Expected $args:
 *   'title'     => string (accessible label; optional)
 *   'banner_id' => int    (media-library attachment ID; required)
 *   'link'      => string URL (optional; makes the banner clickable)
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_banner_id = absint( $args['banner_id'] ?? 0 );
$fares_link      = $args['link'] ?? '';
$fares_label     = $args['title'] ?? '';

if ( ! $fares_banner_id ) {
	return;
}

$fares_banner_html = wp_get_attachment_image(
	$fares_banner_id,
	'full',
	false,
	array(
		'class'   => 'fares-promo__banner',
		'loading' => 'lazy',
	)
);
?>
<section class="fares-promo fares-container"<?php echo '' !== $fares_label ? ' aria-label="' . esc_attr( $fares_label ) . '"' : ''; ?>>
	<?php
	if ( '' !== $fares_link ) {
		printf(
			'<a href="%s">%s</a>',
			esc_url( $fares_link ),
			$fares_banner_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via wp_get_attachment_image / esc_url above.
		);
	} else {
		echo $fares_banner_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built via wp_get_attachment_image above.
	}
	?>
</section>
