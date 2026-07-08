<?php
/**
 * Slide-in category drawer ("القائمة الرئيسية").
 *
 * Opened by the header hamburger on every breakpoint. Lists top-level product
 * categories (term_order, same source as the home grid). Rendered inert
 * (hidden); JS slides it in.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! taxonomy_exists( 'product_cat' ) ) {
	return;
}

$fares_terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
		'orderby'    => 'term_order',
		'exclude'    => array_filter( array( (int) get_option( 'default_product_cat' ) ) ),
	)
);

if ( is_wp_error( $fares_terms ) || empty( $fares_terms ) ) {
	return;
}
?>
<div class="fares-drawer" id="fares-category-drawer" hidden>
	<aside class="fares-drawer__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'القائمة الرئيسية', 'fares-theme' ); ?>" data-fares-drawer-panel>
		<div class="fares-drawer__head">
			<span class="fares-drawer__title"><?php esc_html_e( 'القائمة الرئيسية', 'fares-theme' ); ?></span>
			<button type="button" class="fares-drawer__close" data-fares-menu-close aria-label="<?php esc_attr_e( 'إغلاق القائمة', 'fares-theme' ); ?>">
				<?php fares_icon( 'close' ); ?>
			</button>
		</div>
		<nav class="fares-drawer__nav" aria-label="<?php esc_attr_e( 'التصنيفات', 'fares-theme' ); ?>">
			<ul class="fares-drawer__list">
				<?php foreach ( $fares_terms as $fares_term ) : ?>
					<li class="fares-drawer__item">
						<a class="fares-drawer__link" href="<?php echo esc_url( get_term_link( $fares_term ) ); ?>">
							<?php echo esc_html( $fares_term->name ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
	</aside>
</div>
