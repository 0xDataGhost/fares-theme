<?php
/**
 * Home hero — full-bleed art-directed banner (Figma 1:2050).
 *
 * Image + optional link are client-editable via the Customizer ("البانر
 * الرئيسي"). Falls back to the bundled theme image when nothing is set.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_hero_id   = (int) get_theme_mod( FARES_HERO_IMAGE_MOD, 0 );
$fares_hero_link = get_theme_mod( FARES_HERO_LINK_MOD, '' );

if ( $fares_hero_id ) {
	// Customizer image — alt comes from the media library; eager + high
	// priority since the hero is the LCP element.
	$fares_hero_img = wp_get_attachment_image(
		$fares_hero_id,
		'full',
		false,
		array(
			'class'         => 'fares-hero__image',
			'loading'       => 'eager',
			'fetchpriority' => 'high',
		)
	);
} else {
	$fares_hero_img = sprintf(
		'<img class="fares-hero__image" src="%s" alt="" width="1100" height="900" fetchpriority="high" />',
		esc_url( FARES_THEME_URI . '/assets/images/figma/hero-banner.jpg' )
	);
}
?>
<section class="fares-hero" aria-label="<?php esc_attr_e( 'أهلا بك في المتجر', 'fares-theme' ); ?>">
	<?php if ( $fares_hero_link ) : ?>
		<a class="fares-hero__link" href="<?php echo esc_url( $fares_hero_link ); ?>">
			<?php echo $fares_hero_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image / sprintf output already escaped. ?>
		</a>
	<?php else : ?>
		<?php echo $fares_hero_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- see above. ?>
	<?php endif; ?>
</section>
