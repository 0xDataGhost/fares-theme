<?php
/**
 * Footer trust column — business-platform verification + license + tax number.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="fares-footer__trust">
	<div class="fares-footer__badge">
		<span class="fares-footer__badge-tile">
			<img src="<?php echo esc_url( FARES_THEME_URI . '/assets/images/figma/badge-saudi-business-platform.png' ); ?>" alt="" width="40" height="40" loading="lazy" />
		</span>
		<span><?php esc_html_e( 'موثّق في منصة الأعمال', 'fares-theme' ); ?></span>
	</div>

	<div class="fares-footer__badge">
		<img src="<?php echo esc_url( FARES_THEME_URI . '/assets/images/figma/badge-freelance-certificate.png' ); ?>" alt="" width="64" height="32" loading="lazy" />
		<div class="fares-footer__meta">
			<span><?php esc_html_e( 'وثيقة العمل الحر', 'fares-theme' ); ?></span>
			<b dir="ltr">FL-128116989</b>
		</div>
	</div>

	<div class="fares-footer__meta">
		<span><?php esc_html_e( 'الرقم الضريبى', 'fares-theme' ); ?></span>
		<b dir="ltr">312478563400003</b>
	</div>
</div>
