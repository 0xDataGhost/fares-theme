/**
 * Cart page UX: auto-submit the update when a quantity changes (the
 * "تحديث السلة" button stays as a no-JS fallback).
 */
let timer;

document.addEventListener( 'change', ( event ) => {
	if ( ! event.target.matches( '.woocommerce-cart-form .qty' ) ) {
		return;
	}

	clearTimeout( timer );
	timer = setTimeout( () => {
		document.querySelector( '.woocommerce-cart-form [name="update_cart"]' )?.click();
	}, 400 );
} );
