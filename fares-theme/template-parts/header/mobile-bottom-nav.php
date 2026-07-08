<?php
/**
 * Sticky mobile bottom navigation (Figma 2:8663) — home, categories, cart
 * (badge), login, search. Hidden ≥ 782px via CSS.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wc_get_cart_url' ) ) {
	return;
}
?>
<nav class="fares-mobile-nav" aria-label="<?php esc_attr_e( 'تنقل الجوال', 'fares-theme' ); ?>">
	<a class="fares-mobile-nav__item<?php echo is_front_page() ? ' is-active' : ''; ?>" href="<?php echo esc_url( home_url( '/' ) ); ?>">
		<?php fares_icon( 'home' ); ?>
		<span><?php esc_html_e( 'الرئيسية', 'fares-theme' ); ?></span>
	</a>

	<a class="fares-mobile-nav__item<?php echo is_shop() || is_product_taxonomy() ? ' is-active' : ''; ?>" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
		<?php fares_icon( 'categories' ); ?>
		<span><?php esc_html_e( 'التصنيفات', 'fares-theme' ); ?></span>
	</a>

	<a class="fares-mobile-nav__item<?php echo is_cart() ? ' is-active' : ''; ?>" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
		<span class="fares-mobile-nav__icon-wrap">
			<?php fares_icon( 'cart' ); ?>
			<?php fares_cart_count_badge(); ?>
		</span>
		<span><?php esc_html_e( 'السلة', 'fares-theme' ); ?></span>
	</a>

	<a class="fares-mobile-nav__item<?php echo is_account_page() ? ' is-active' : ''; ?>" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
		<?php fares_icon( 'user' ); ?>
		<span><?php esc_html_e( 'تسجيل الدخول', 'fares-theme' ); ?></span>
	</a>

	<button type="button" class="fares-mobile-nav__item" data-fares-search-toggle aria-expanded="false" aria-controls="fares-search-overlay">
		<?php fares_icon( 'search' ); ?>
		<span><?php esc_html_e( 'بحث', 'fares-theme' ); ?></span>
	</button>
</nav>

<div class="fares-search-overlay" id="fares-search-overlay" hidden>
	<form role="search" method="get" class="fares-search-overlay__form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="fares-search-input"><?php esc_html_e( 'ابحث عن منتج', 'fares-theme' ); ?></label>
		<input type="search" id="fares-search-input" name="s" placeholder="<?php esc_attr_e( 'ابحث عن منتج…', 'fares-theme' ); ?>" />
		<input type="hidden" name="post_type" value="product" />
		<button type="submit" class="fares-button"><?php esc_html_e( 'بحث', 'fares-theme' ); ?></button>
		<button type="button" class="fares-search-overlay__close" data-fares-search-close aria-label="<?php esc_attr_e( 'إغلاق البحث', 'fares-theme' ); ?>"><?php fares_icon( 'close' ); ?></button>
	</form>
</div>
