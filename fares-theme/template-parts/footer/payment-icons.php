<?php
/**
 * Footer payment-method tiles row.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_payment_methods = array(
	'mada'       => 'mada',
	'visa-mc'    => 'Visa / Mastercard',
	'paypal'     => 'PayPal',
	'stc'        => 'STC Bank',
	'apple-pay'  => 'Apple Pay',
	'bank'       => 'Bank transfer',
	'cash'       => 'Cash payment',
);
?>
<ul class="fares-footer__payments">
	<?php foreach ( $fares_payment_methods as $fares_pm_slug => $fares_pm_label ) : ?>
		<li class="fares-payment-tile">
			<img
				src="<?php echo esc_url( FARES_THEME_URI . "/assets/images/figma/payment-{$fares_pm_slug}.png" ); ?>"
				alt="<?php echo esc_attr( $fares_pm_label ); ?>"
				width="40"
				height="24"
				loading="lazy"
			/>
		</li>
	<?php endforeach; ?>
</ul>
