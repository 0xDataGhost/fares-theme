<?php
/**
 * Footer trust column — business-platform verification + license + tax number.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="fares-footer__trust">
	<div class="fares-footer__brand">
		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a class="fares-footer__brand-name" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php bloginfo( 'name' ); ?>
			</a>
		<?php endif; ?>
	</div>

	<a class="fares-footer__badge" href="<?php echo esc_url( 'https://eauthenticate.saudibusiness.gov.sa/certificate-details/0000261241' ); ?>" target="_blank" rel="noopener noreferrer">
		<span class="fares-footer__badge-tile">
			<img src="<?php echo esc_url( FARES_THEME_URI . '/assets/images/figma/badge-saudi-business-platform.png' ); ?>" alt="" width="40" height="40" loading="lazy" />
		</span>
		<span><?php esc_html_e( 'موثّق في منصة الأعمال', 'fares-theme' ); ?></span>
	</a>

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
