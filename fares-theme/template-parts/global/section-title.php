<?php
/**
 * Section header row: title + optional "عرض الكل" link + carousel arrows.
 *
 * Expected $args:
 *   'title'    => string (required)
 *   'view_all' => string URL (optional)
 *   'arrows'   => bool (default true — targets the sibling carousel)
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_title    = $args['title'] ?? '';
$fares_view_all = $args['view_all'] ?? '';
$fares_arrows   = $args['arrows'] ?? true;

if ( '' === $fares_title ) {
	return;
}
?>
<div class="fares-section-header">
	<h2 class="fares-section-header__title"><?php echo esc_html( $fares_title ); ?></h2>
	<div class="fares-section-header__controls">
		<?php if ( '' !== $fares_view_all ) : ?>
			<a class="fares-section-header__view-all" href="<?php echo esc_url( $fares_view_all ); ?>"><?php esc_html_e( 'عرض الكل', 'fares-theme' ); ?></a>
		<?php endif; ?>
		<?php if ( $fares_arrows ) : ?>
			<button type="button" class="fares-section-header__arrow" data-carousel-prev aria-label="<?php esc_attr_e( 'السابق', 'fares-theme' ); ?>"><?php fares_icon( 'arrow-start' ); ?></button>
			<button type="button" class="fares-section-header__arrow" data-carousel-next aria-label="<?php esc_attr_e( 'التالي', 'fares-theme' ); ?>"><?php fares_icon( 'arrow-end' ); ?></button>
		<?php endif; ?>
	</div>
</div>
