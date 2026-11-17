/**
 * Click-to-play video facades: thumbnail + play button swap for the real
 * player on click. The iframe carries data-src only (same lazy pattern as
 * dialog.js), so nothing is fetched until asked for; thumbnail and button
 * hide while the frame unhides.
 */
export function initVideoFacades() {
	document.querySelectorAll( '[data-video-facade]' ).forEach( ( root ) => {
		const play = root.querySelector( '[data-video-play]' );
		const frame = root.querySelector( '[data-video-frame]' );

		if ( ! play || ! frame ) {
			return;
		}

		play.addEventListener( 'click', () => {
			const iframe = frame.querySelector( 'iframe[data-src]' );

			if ( iframe && ! iframe.getAttribute( 'src' ) ) {
				iframe.setAttribute( 'src', iframe.getAttribute( 'data-src' ) );
			}

			frame.hidden = false;
			play.hidden = true;

			const thumbnail = root.querySelector( '[data-video-thumbnail]' );

			if ( thumbnail ) {
				thumbnail.hidden = true;
			}
		}, { once: true } );
	} );
}
