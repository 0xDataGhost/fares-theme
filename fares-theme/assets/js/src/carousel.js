/**
 * Product/testimonial carousels — Embla with first-class RTL.
 * One code path: any element with [data-fares-carousel] gets a carousel;
 * optional sibling buttons [data-carousel-prev] / [data-carousel-next].
 */
import EmblaCarousel from 'embla-carousel';

const init = () => {
	document.querySelectorAll( '[data-fares-carousel]' ).forEach( ( root ) => {
		const viewport = root.querySelector( '.fares-carousel__viewport' );
		if ( ! viewport ) {
			return;
		}

		const embla = EmblaCarousel( viewport, {
			direction: document.documentElement.dir === 'rtl' ? 'rtl' : 'ltr',
			align: 'start',
			containScroll: 'trimSnaps',
			dragFree: true,
		} );

		const prev = root.querySelector( '[data-carousel-prev]' );
		const next = root.querySelector( '[data-carousel-next]' );

		const sync = () => {
			if ( prev ) {
				prev.disabled = ! embla.canScrollPrev();
			}
			if ( next ) {
				next.disabled = ! embla.canScrollNext();
			}
		};

		prev?.addEventListener( 'click', () => embla.scrollPrev() );
		next?.addEventListener( 'click', () => embla.scrollNext() );
		embla.on( 'select', sync );
		embla.on( 'init', sync );
		sync();
	} );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', init );
} else {
	init();
}
