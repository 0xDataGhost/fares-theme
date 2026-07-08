<?php
/**
 * Footer contact column ("تواصل معنا") + social icons.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

$fares_contact_email    = get_option( 'admin_email' );
$fares_contact_telegram = 'https://t.me/Sho9_store';
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
		<a class="fares-footer__icon-box" href="#" rel="noopener"><?php fares_icon( 'instagram', 'Instagram' ); ?></a>
		<a class="fares-footer__icon-box" href="#" rel="noopener"><?php fares_icon( 'x', 'X' ); ?></a>
		<a class="fares-footer__icon-box" href="#" rel="noopener"><?php fares_icon( 'youtube', 'YouTube' ); ?></a>
	</div>
</div>
