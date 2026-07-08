<?php
/**
 * Header actions: login, cart with badge, language/currency labels.
 *
 * Fully styled in Phase 2; functional skeleton for now.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="fares-header-actions">
	<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
		<a class="fares-header-actions__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
			<span class="screen-reader-text"><?php esc_html_e( 'السلة', 'fares-theme' ); ?></span>
			<?php fares_cart_count_badge(); ?>
		</a>
		<a class="fares-header-actions__login" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
			<?php esc_html_e( 'تسجيل الدخول', 'fares-theme' ); ?>
		</a>
	<?php endif; ?>
	<span class="fares-header-actions__locale"><?php esc_html_e( 'العربية', 'fares-theme' ); ?> | <?php esc_html_e( 'ج.م', 'fares-theme' ); ?></span>
</div>
