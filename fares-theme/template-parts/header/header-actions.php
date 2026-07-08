<?php
/**
 * Header actions: login, cart with badge, language/currency label, search.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="fares-header-actions">
	<span class="fares-header-actions__locale"><?php esc_html_e( 'العربية', 'fares-theme' ); ?> | <?php esc_html_e( 'ج.م', 'fares-theme' ); ?></span>

	<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
		<a class="fares-header-actions__login" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
			<?php fares_icon( 'user' ); ?>
			<?php esc_html_e( 'تسجيل الدخول', 'fares-theme' ); ?>
		</a>

		<a class="fares-header-actions__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
			<span class="screen-reader-text"><?php esc_html_e( 'السلة', 'fares-theme' ); ?></span>
			<?php fares_icon( 'cart' ); ?>
			<?php fares_cart_count_badge(); ?>
		</a>
	<?php endif; ?>
</div>
