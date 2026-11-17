/**
 * Share buttons ([data-share]) — the icon-only share control on single posts.
 *
 * Uses the Web Share API where the browser offers it (mobile), otherwise
 * copies the article URL to the clipboard with a brief "Copied" confirmation.
 * No share-provider links are rendered: nothing to style per network, no
 * third-party endpoints, and it degrades to a plain copy on desktop.
 */
export function initShareButtons() {
	const buttons = document.querySelectorAll( '[data-share]' );

	buttons.forEach( ( button ) => {
		button.addEventListener( 'click', async () => {
			const url =
				button.getAttribute( 'data-share-url' ) ||
				window.location.href;
			const title =
				button.getAttribute( 'data-share-title' ) || document.title;

			if ( navigator.share ) {
				try {
					await navigator.share( { title, url } );
				} catch ( err ) {
					// User dismissed the sheet — not an error.
				}
				return;
			}

			let copied = false;

			if ( navigator.clipboard ) {
				try {
					await navigator.clipboard.writeText( url );
					copied = true;
				} catch ( err ) {
					copied = false;
				}
			}

			if ( ! copied ) {
				const input = document.createElement( 'textarea' );
				input.value = url;
				input.setAttribute( 'readonly', '' );
				input.style.position = 'absolute';
				input.style.left = '-9999px';
				document.body.appendChild( input );
				input.select();
				try {
					copied = document.execCommand( 'copy' );
				} catch ( err ) {
					copied = false;
				}
				document.body.removeChild( input );
			}

			if ( ! copied ) {
				return;
			}

			const originalLabel = button.getAttribute( 'aria-label' );
			button.classList.add( 'is-copied' );
			button.setAttribute( 'aria-label', 'Link copied to clipboard' );
			window.setTimeout( () => {
				button.classList.remove( 'is-copied' );
				if ( originalLabel ) {
					button.setAttribute( 'aria-label', originalLabel );
				}
			}, 2000 );
		} );
	} );
}
