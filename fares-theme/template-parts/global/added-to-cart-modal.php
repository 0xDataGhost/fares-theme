<?php
/**
 * "Added to cart" confirmation dialog shell.
 *
 * Rendered once in the footer; JS fills the product name/thumbnail and toggles
 * visibility on WooCommerce's `added_to_cart` event.
 *
 * @package fares-theme
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="fares-atc-modal" id="fares-atc-modal" hidden>
	<div class="fares-atc-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="fares-atc-modal-title" data-fares-atc-dialog>
		<button type="button" class="fares-atc-modal__close" data-fares-atc-close aria-label="<?php esc_attr_e( 'إغلاق', 'fares-theme' ); ?>">
			<?php fares_icon( 'close' ); ?>
		</button>

		<span class="fares-atc-modal__check" aria-hidden="true"><?php fares_icon( 'cart' ); ?></span>

		<h2 class="fares-atc-modal__title" id="fares-atc-modal-title"><?php esc_html_e( 'تمت الإضافة إلى السلة', 'fares-theme' ); ?></h2>

		<div class="fares-atc-modal__product">
			<img class="fares-atc-modal__thumb" data-fares-atc-thumb src="" alt="" width="56" height="56" hidden />
			<span class="fares-atc-modal__name" data-fares-atc-name></span>
		</div>

		<div class="fares-atc-modal__actions">
			<a class="fares-button fares-atc-modal__action" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
				<?php esc_html_e( 'إتمام الطلب', 'fares-theme' ); ?>
			</a>
			<a class="fares-button fares-button--outline fares-atc-modal__action" href="<?php echo esc_url( wc_get_cart_url() ); ?>">
				<?php esc_html_e( 'عرض السلة', 'fares-theme' ); ?>
			</a>
			<button type="button" class="fares-button fares-atc-modal__action fares-atc-modal__action--ghost" data-fares-atc-close>
				<?php esc_html_e( 'متابعة التسوق', 'fares-theme' ); ?>
			</button>
		</div>
	</div>
</div>
