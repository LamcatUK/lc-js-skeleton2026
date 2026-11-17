import { useEffect, useRef, useState } from '@wordpress/element';
import { URLInput } from '@wordpress/block-editor';

/**
 * Core's URLInput (type-ahead search of pages and posts, with ordinary text
 * entry when nothing matches), made safe to use in a block's own fields.
 *
 * Two things core's URLInput gets wrong there:
 *
 *  1. Its suggestion list is not dismissed when focus leaves the field. Type a
 *     URL by hand (nothing is selected), click elsewhere and the list stays
 *     open over the block.
 *  2. It opens the list when the value changes from outside the field (undo,
 *     redo, a row being moved), even though nobody is typing.
 *
 * URLInput's own switch for this is `disableSuggestions`, which hides the list
 * and stops it fetching. This keeps that switch on unless the field is focused
 * (or focus is inside the suggestion list, so choosing a suggestion works).
 * Leaving the field turns it back on after a short delay, and not at all while
 * the pointer is over the list, so a click on a suggestion is never lost.
 * Choosing a suggestion turns it off at once (a stored path like /au/ is not a
 * complete URL to core, so it would search again and reopen the list), and
 * typing turns it off again.
 *
 * It also stores a same-site URL as a root-relative path (/uk/page/), so links
 * survive the content moving between environments. Other URLs are kept as typed.
 *
 * @param {Object}   props
 * @param {string}   props.value
 * @param {Function} props.onChange   ( url ) => void
 * @param {string}   [props.placeholder]
 * @param {string}   [props.className]
 */

const SUGGESTIONS = '.block-editor-url-input__suggestions';

export function toSiteRelative( url ) {
	if ( ! url ) {
		return '';
	}

	try {
		const parsed = new URL( url, window.location.origin );

		if ( parsed.origin === window.location.origin && /^(https?:)?\/\//.test( url ) ) {
			return parsed.pathname + parsed.search + parsed.hash;
		}
	} catch ( e ) {
		// Not a parseable URL: keep what was typed.
	}

	return url;
}

export default function SiteUrlInput( { value, onChange, placeholder, className } ) {
	const [ active, setActive ] = useState( false );
	const timer = useRef( null );
	const justChose = useRef( false );

	useEffect( () => () => window.clearTimeout( timer.current ), [] );

	return (
		<div
			className={ className }
			onFocus={ () => {
				window.clearTimeout( timer.current );

				// Core puts focus back in the field after a suggestion is chosen:
				// that must not turn the suggestions on again.
				if ( ! justChose.current ) {
					setActive( true );
				}
			} }
			onInput={ () => {
				justChose.current = false;
				setActive( true );
			} }
			onBlur={ ( event ) => {
				const next = event.relatedTarget;

				// Focus moving into the suggestion list is part of choosing one.
				if ( next && next.closest && next.closest( SUGGESTIONS ) ) {
					return;
				}

				window.clearTimeout( timer.current );
				timer.current = window.setTimeout( () => {
					if ( ! document.querySelector( `${ SUGGESTIONS }:hover` ) ) {
						setActive( false );
					}
				}, 150 );
			} }
		>
			<URLInput
				value={ value || '' }
				onChange={ ( url, post ) => {
					// A second argument means a suggestion was chosen, not text typed.
					if ( post ) {
						justChose.current = true;
						setActive( false );
					}

					onChange( toSiteRelative( url ) );
				} }
				placeholder={ placeholder }
				disableSuggestions={ ! active }
				__nextHasNoMarginBottom
			/>
		</div>
	);
}
