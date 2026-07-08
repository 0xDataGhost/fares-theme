<?php
/**
 * Cart page — custom two-column layout per Figma 2:7764: line-item cards
 * (thumb, title, prices, variation pills, qty stepper, remove) beside the
 * order-summary box.
 *
 * Overrides woocommerce/templates/cart/cart.php (all core hooks kept).
 *
 * @package fares-theme
 * @version 10.8.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' ); ?>

<form class="woocommerce-cart-form fares-cart" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>

	<div class="fares-cart__items">
		<?php do_action( 'woocommerce_before_cart_contents' ); ?>

		<?php
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

			if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				continue;
			}

			$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
			?>
			<div class="fares-cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
				<?php
				echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC-built link.
					'woocommerce_cart_item_remove_link',
					sprintf(
						'<a href="%s" class="remove fares-cart-item__remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
						esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
						/* translators: %s: product name. */
						esc_attr( sprintf( __( 'حذف %s من السلة', 'fares-theme' ), wp_strip_all_tags( $_product->get_name() ) ) ),
						esc_attr( $product_id ),
						esc_attr( $_product->get_sku() )
					),
					$cart_item_key
				);
				?>

				<div class="fares-cart-item__main">
					<span class="fares-cart-item__thumb">
						<?php
						$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_gallery_thumbnail' ), $cart_item, $cart_item_key );
						if ( ! $product_permalink ) {
							echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</span>

					<div class="fares-cart-item__info">
						<p class="fares-cart-item__name">
							<?php
							if ( ! $product_permalink ) {
								echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;' );
							} else {
								echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ), $cart_item, $cart_item_key ) );
							}

							do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );
							?>
						</p>

						<div class="fares-cart-item__price">
							<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<div class="fares-cart-item__meta">
							<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pills styled via CSS. ?>
						</div>
					</div>

					<div class="fares-cart-item__controls">
						<?php
						if ( $_product->is_sold_individually() ) {
							$min_quantity = 1;
							$max_quantity = 1;
						} else {
							$min_quantity = 0;
							$max_quantity = $_product->get_max_purchase_quantity();
						}

						echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'woocommerce_cart_item_quantity',
							woocommerce_quantity_input(
								array(
									'input_name'   => "cart[{$cart_item_key}][qty]",
									'input_value'  => $cart_item['quantity'],
									'max_value'    => $max_quantity,
									'min_value'    => $min_quantity,
									'product_name' => $_product->get_name(),
								),
								$_product,
								false
							),
							$cart_item_key,
							$cart_item
						);
						?>

						<p class="fares-cart-item__total">
							<span><?php esc_html_e( 'المجموع:', 'fares-theme' ); ?></span>
							<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</p>
					</div>
				</div>
			</div>
			<?php
		}
		?>

		<?php do_action( 'woocommerce_cart_contents' ); ?>

		<div class="fares-cart__actions">
			<?php if ( wc_coupons_enabled() ) : ?>
				<div class="coupon fares-cart__coupon">
					<label class="screen-reader-text" for="coupon_code"><?php esc_html_e( 'كود الخصم', 'fares-theme' ); ?></label>
					<input type="text" name="coupon_code" class="input-text" id="coupon_code" value="" placeholder="<?php esc_attr_e( 'كود الخصم', 'fares-theme' ); ?>" />
					<button type="submit" class="fares-button" name="apply_coupon" value="<?php esc_attr_e( 'تطبيق', 'fares-theme' ); ?>"><?php esc_html_e( 'تطبيق الكود', 'fares-theme' ); ?></button>
					<?php do_action( 'woocommerce_cart_coupon' ); ?>
				</div>
			<?php endif; ?>

			<button type="submit" class="fares-button--outline fares-cart__update" name="update_cart" value="<?php esc_attr_e( 'تحديث السلة', 'fares-theme' ); ?>"><?php esc_html_e( 'تحديث السلة', 'fares-theme' ); ?></button>

			<?php do_action( 'woocommerce_cart_actions' ); ?>
			<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		</div>

		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
	</div>

	<?php do_action( 'woocommerce_after_cart_table' ); ?>

	<div class="cart-collaterals fares-cart__summary">
		<?php
		/**
		 * Cart collaterals hook — renders cart totals ("ملخص الطلب").
		 *
		 * @hooked woocommerce_cross_sell_display
		 * @hooked woocommerce_cart_totals - 10
		 */
		do_action( 'woocommerce_cart_collaterals' );
		?>
	</div>
</form>

<?php do_action( 'woocommerce_after_cart' ); ?>
