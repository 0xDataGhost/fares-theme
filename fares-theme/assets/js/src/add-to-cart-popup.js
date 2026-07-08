/**
 * "Added to cart" confirmation popup.
 *
 * WooCommerce fires the jQuery `added_to_cart` event on document.body after an
 * AJAX loop add-to-cart resolves. We read the product from the clicked card,
 * fill the footer-rendered dialog, and open it as an accessible modal.
 *
 * Single-product add-to-cart is a form submit (+ buy-now redirect), so it never
 * reaches this path — by design.
 */
( () => {
	'use strict';

	const modal = document.getElementById( 'fares-atc-modal' );
	if ( ! modal || ! window.jQuery ) {
		return;
	}

	const dialog = modal.querySelector( '[data-fares-atc-dialog]' );
	const nameEl = modal.querySelector( '[data-fares-atc-name]' );
	const thumbEl = modal.querySelector( '[data-fares-atc-thumb]' );

	// Keep in sync with --fares-duration-normal (300ms) + a small buffer.
	const CLOSE_MS = 320;

	let lastFocused = null;
	let closeTimer = null;

	const focusable = () =>
		Array.from(
			dialog.querySelectorAll(
				'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
			)
		);

	const onKeydown = ( event ) => {
		if ( event.key === 'Escape' ) {
			close();
			return;
		}
		if ( event.key !== 'Tab' ) {
			return;
		}
		const items = focusable();
		if ( ! items.length ) {
			return;
		}
		const first = items[ 0 ];
		const last = items[ items.length - 1 ];
		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	};

	function open() {
		clearTimeout( closeTimer );
		lastFocused = document.activeElement;
		modal.hidden = false;
		// Next frame so the transition runs from the hidden state.
		requestAnimationFrame( () => modal.classList.add( 'is-open' ) );
		document.addEventListener( 'keydown', onKeydown );
		focusable()[ 0 ]?.focus();
	}

	function close() {
		modal.classList.remove( 'is-open' );
		document.removeEventListener( 'keydown', onKeydown );
		// Deterministic hide after the fade — also covers prefers-reduced-motion,
		// where no transitionend would ever fire.
		clearTimeout( closeTimer );
		closeTimer = setTimeout( () => {
			modal.hidden = true;
		}, CLOSE_MS );
		lastFocused?.focus?.();
	}

	// Close on overlay click or any [data-fares-atc-close] control.
	modal.addEventListener( 'click', ( event ) => {
		if ( event.target === modal || event.target.closest( '[data-fares-atc-close]' ) ) {
			close();
		}
	} );

	// Populate from the clicked product card, then open.
	window.jQuery( document.body ).on( 'added_to_cart', ( _event, _fragments, _hash, $button ) => {
		const button = $button && $button[ 0 ];
		const card = button ? button.closest( 'li.product, .fares-card' ) : null;

		const title = card?.querySelector( '.fares-card__title' )?.textContent?.trim();
		if ( nameEl ) {
			nameEl.textContent = title || '';
		}

		const img = card?.querySelector( 'img' );
		if ( thumbEl ) {
			if ( img?.currentSrc || img?.src ) {
				thumbEl.src = img.currentSrc || img.src;
				thumbEl.alt = title || '';
				thumbEl.hidden = false;
			} else {
				thumbEl.hidden = true;
			}
		}

		open();
	} );
} )();
