/**
 * Product interactions: quantity stepper +/− buttons.
 * Buttons are rendered by PHP hooks around Woo's quantity input.
 */
const step = ( input, dir ) => {
	const min = parseFloat( input.min ) || 1;
	const max = parseFloat( input.max ) || Infinity;
	const stepBy = parseFloat( input.step ) || 1;
	const current = parseFloat( input.value ) || min;
	const next = Math.min( max, Math.max( min, current + dir * stepBy ) );

	if ( next !== current ) {
		input.value = next;
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}
};

document.addEventListener( 'click', ( event ) => {
	const button = event.target.closest( '[data-fares-qty]' );
	if ( ! button ) {
		return;
	}

	const input = button.closest( '.quantity' )?.querySelector( '.qty' );
	if ( input && ! input.readOnly ) {
		step( input, button.dataset.faresQty === 'up' ? 1 : -1 );
	}
} );
