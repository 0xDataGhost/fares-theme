<?php
/**
 * Footer contact column ("تواصل معنا") + social icons.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_channels         = fares_contact_channels();
$fares_contact_email    = $fares_channels['email'];
$fares_contact_telegram = $fares_channels['telegram'];
$fares_social_instagram = $fares_channels['instagram'];
$fares_social_x         = $fares_channels['x'];
$fares_social_youtube   = $fares_channels['youtube'];
?>
<div class="fares-footer__contact">
	<h2 class="fares-footer__heading"><?php esc_html_e( 'تواصل معنا', 'fares-theme' ); ?></h2>

	<div class="fares-footer__contact-row">
		<a class="fares-footer__icon-box" href="<?php echo esc_url( 'mailto:' . $fares_contact_email ); ?>">
			<?php fares_icon( 'email', __( 'البريد الإلكتروني', 'fares-theme' ) ); ?>
		</a>
		<span dir="ltr"><?php echo esc_html( $fares_contact_email ); ?></span>
	</div>

	<div class="fares-footer__contact-row">
		<a class="fares-footer__icon-box" href="<?php echo esc_url( $fares_contact_telegram ); ?>" rel="noopener">
			<?php fares_icon( 'telegram', __( 'تيليجرام', 'fares-theme' ) ); ?>
		</a>
		<span dir="ltr"><?php echo esc_html( $fares_contact_telegram ); ?></span>
	</div>

	<div class="fares-footer__social">
		<a class="fares-footer__icon-box" href="<?php echo esc_url( $fares_social_instagram ); ?>" target="_blank" rel="noopener noreferrer"><?php fares_icon( 'instagram', 'Instagram' ); ?></a>
		<a class="fares-footer__icon-box" href="<?php echo esc_url( $fares_social_x ); ?>" target="_blank" rel="noopener noreferrer"><?php fares_icon( 'x', 'X' ); ?></a>
		<a class="fares-footer__icon-box" href="<?php echo esc_url( $fares_social_youtube ); ?>" target="_blank" rel="noopener noreferrer"><?php fares_icon( 'youtube', 'YouTube' ); ?></a>
	</div>
</div>
