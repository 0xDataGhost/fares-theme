/**
 * Global chrome interactions: mobile search overlay + category drawer.
 */
( () => {
	'use strict';

	document.documentElement.classList.add( 'fares-js' );

	const FOCUSABLE =
		'a[href], button:not([disabled]), input, [tabindex]:not([tabindex="-1"])';

	/**
	 * Trap Tab focus within a container while it is open.
	 *
	 * @param {KeyboardEvent} event     Keydown event.
	 * @param {HTMLElement}   container Element to keep focus inside.
	 */
	const trapFocus = ( event, container ) => {
		if ( event.key !== 'Tab' ) {
			return;
		}
		const items = Array.from( container.querySelectorAll( FOCUSABLE ) );
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

	/* --- Mobile search overlay (instant show/hide) --------------------- */
	( () => {
		const overlay = document.getElementById( 'fares-search-overlay' );
		const toggle = document.querySelector( '[data-fares-search-toggle]' );
		if ( ! overlay || ! toggle ) {
			return;
		}

		const open = () => {
			overlay.hidden = false;
			toggle.setAttribute( 'aria-expanded', 'true' );
			overlay.querySelector( 'input[type="search"]' )?.focus();
		};
		const close = () => {
			overlay.hidden = true;
			toggle.setAttribute( 'aria-expanded', 'false' );
			toggle.focus();
		};

		toggle.addEventListener( 'click', () => ( overlay.hidden ? open() : close() ) );
		overlay.querySelector( '[data-fares-search-close]' )?.addEventListener( 'click', close );
		overlay.addEventListener( 'click', ( e ) => {
			if ( e.target === overlay ) {
				close();
			}
		} );
		document.addEventListener( 'keydown', ( e ) => {
			if ( 'Escape' === e.key && ! overlay.hidden ) {
				close();
			}
		} );
	} )();

	/* --- Category drawer (slide in/out) -------------------------------- */
	( () => {
		const drawer = document.getElementById( 'fares-category-drawer' );
		const toggle = document.querySelector( '[data-fares-menu-toggle]' );
		if ( ! drawer || ! toggle ) {
			return;
		}

		// Keep in sync with --fares-duration-normal (300ms) + a small buffer.
		const CLOSE_MS = 320;
		const panel = drawer.querySelector( '[data-fares-drawer-panel]' );
		let closeTimer = null;
		let lastFocused = null;

		const onKeydown = ( event ) => {
			if ( event.key === 'Escape' ) {
				close();
			} else {
				trapFocus( event, panel || drawer );
			}
		};

		const open = () => {
			clearTimeout( closeTimer );
			lastFocused = document.activeElement;
			drawer.hidden = false;
			requestAnimationFrame( () => drawer.classList.add( 'is-open' ) );
			toggle.setAttribute( 'aria-expanded', 'true' );
			document.addEventListener( 'keydown', onKeydown );
			drawer.querySelector( '[data-fares-menu-close]' )?.focus();
		};

		const close = () => {
			drawer.classList.remove( 'is-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			document.removeEventListener( 'keydown', onKeydown );
			clearTimeout( closeTimer );
			closeTimer = setTimeout( () => {
				drawer.hidden = true;
			}, CLOSE_MS );
			lastFocused?.focus?.();
		};

		toggle.addEventListener( 'click', () => ( drawer.hidden ? open() : close() ) );
		drawer.querySelector( '[data-fares-menu-close]' )?.addEventListener( 'click', close );
		drawer.addEventListener( 'click', ( e ) => {
			if ( e.target === drawer ) {
				close();
			}
		} );
	} )();
} )();
