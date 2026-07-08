<?php
/**
 * Home hero — full-bleed art-directed banner (Figma 1:2050).
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="fares-hero" aria-label="<?php esc_attr_e( 'أهلا بك في المتجر', 'fares-theme' ); ?>">
	<img
		class="fares-hero__image"
		src="<?php echo esc_url( FARES_THEME_URI . '/assets/images/figma/hero-banner.png' ); ?>"
		alt=""
		width="1100"
		height="900"
		fetchpriority="high"
	/>
</section>
