/**
 * Global chrome interactions: mobile search overlay toggle.
 */
( () => {
	'use strict';

	document.documentElement.classList.add( 'fares-js' );

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
