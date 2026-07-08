<?php
/**
 * Home: payment-methods promo banner ("عشانك الدفع صار أسهل").
 * The two Figma banner instances share one image fill — rendered twice per
 * the design.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_banner = FARES_THEME_URI . '/assets/images/figma/payment-promo-banner.jpg';
?>
<section class="fares-promo fares-container" aria-label="<?php esc_attr_e( 'طرق الدفع', 'fares-theme' ); ?>">
	<img class="fares-promo__banner" src="<?php echo esc_url( $fares_banner ); ?>" alt="<?php esc_attr_e( 'عشانك الدفع صار أسهل — Apple Pay, mada, PayPal, Mastercard, G Pay', 'fares-theme' ); ?>" width="1272" height="1041" loading="lazy" />
	<img class="fares-promo__banner" src="<?php echo esc_url( $fares_banner ); ?>" alt="" loading="lazy" />
</section>
