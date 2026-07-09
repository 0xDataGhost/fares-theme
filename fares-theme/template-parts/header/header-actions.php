<?php
/**
 * Header actions: login, cart with badge, language/currency label, search.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="fares-header-actions">
	<span class="fares-header-actions__locale">
		<?php if ( class_exists( 'TRP_Translate_Press' ) ) : ?>
			<span class="fares-header-actions__lang">
				<?php echo do_shortcode( '[language-switcher]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- TranslatePress-generated switcher markup. ?>
			</span>
		<?php else : ?>
			<?php esc_html_e( 'العربية', 'fares-theme' ); ?>
		<?php endif; ?>
		<?php if ( function_exists( 'fares_currency_switcher' ) ) : ?>
			<?php fares_currency_switcher(); ?>
		<?php endif; ?>
	</span>

	<?php if ( function_exists( 'wc_get_cart_url' ) ) : ?>
		<a class="fares-header-actions__login" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
			<?php fares_icon( 'user' ); ?>
			<?php echo esc_html( is_user_logged_in() ? __( 'حسابي', 'fares-theme' ) : __( 'تسجيل الدخول', 'fares-theme' ) ); ?>
		</a>

		<a class="fares-header-actions__cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
			<span class="screen-reader-text"><?php esc_html_e( 'السلة', 'fares-theme' ); ?></span>
			<?php fares_icon( 'cart' ); ?>
			<?php fares_cart_count_badge(); ?>
		</a>
	<?php endif; ?>
</div>
