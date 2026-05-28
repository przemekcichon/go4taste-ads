/**
 * Carousel dot navigation for the inline banner block (mobile).
 *
 * @package Go4Taste_Ads
 * @since   1.2.0
 */
( function () {
	function initCarousels() {
		const carousels = document.querySelectorAll(
			'.g4t-banner-inline--mobile-carousel'
		);

		carousels.forEach( ( banner ) => {
			const track = banner.querySelector( '.g4t-ads-inline-products' );
			const dots = banner.querySelectorAll( '.g4t-carousel-dot' );

			if ( ! track || dots.length < 1 ) {
				return;
			}

			const items = track.querySelectorAll( '.g4t-ads-content' );
			if ( items.length < 2 ) {
				return;
			}

			/* --- Scroll → update active dot (throttled) --- */
			let ticking = false;

			function updateActiveDot() {
				const scrollLeft = track.scrollLeft;
				const trackWidth = track.scrollWidth - track.clientWidth;

				let activeIndex = 0;
				if ( trackWidth > 0 ) {
					activeIndex = Math.round(
						( scrollLeft / trackWidth ) * ( items.length - 1 )
					);
				}
				activeIndex = Math.max(
					0,
					Math.min( activeIndex, items.length - 1 )
				);

				dots.forEach( ( dot, i ) => {
					dot.classList.toggle(
						'g4t-carousel-dot--active',
						i === activeIndex
					);
				} );
			}

			track.addEventListener( 'scroll', function () {
				if ( ! ticking ) {
					requestAnimationFrame( function () {
						updateActiveDot();
						ticking = false;
					} );
					ticking = true;
				}
			} );

			/* --- Dot click → scroll to item --- */
			dots.forEach( ( dot ) => {
				dot.addEventListener( 'click', function () {
					const index = parseInt( dot.dataset.index, 10 );
					if ( isNaN( index ) || ! items[ index ] ) {
						return;
					}
					items[ index ].scrollIntoView( {
						behavior: 'smooth',
						block: 'nearest',
						inline: 'center',
					} );
				} );
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initCarousels );
	} else {
		initCarousels();
	}
} )();
