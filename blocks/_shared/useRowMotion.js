import { useCallback, useEffect, useLayoutEffect, useRef, useState } from '@wordpress/element';

/**
 * Shared row behaviour for repeater-style fields such as RepeaterField: add,
 * move and remove rows, and make a move obvious.
 *
 *  - Stable keys: each row keeps an identity as it moves, so React moves its
 *    DOM node instead of rewriting rows in place.
 *  - Slide: the two swapped rows animate past each other (FLIP), skipped when
 *    the visitor prefers reduced motion.
 *  - Highlight: the moved row gets .is-moved for a moment (CSS fades it).
 *  - Focus: stays on the same up/down button of the moved row, so pressing it
 *    again keeps moving the same row.
 *
 * The row element needs ref={ setRowRef( keys[ index ] ) }, key={ keys[ index ] },
 * the class is-moved when keys[ index ] === movedKey, and its move buttons
 * data-move="up" / data-move="down".
 *
 * @param {Object[]} rows     Current rows.
 * @param {Function} onChange ( rows ) => void
 * @return {Object} keys, setRowRef, movedKey, addRow( emptyRow ), moveRow( index, direction ), removeRow( index ).
 */
let counter = 0;
const newKey = () => `row-${ ++counter }`;
const HIGHLIGHT_MS = 1200;
const SLIDE_MS = 220;

export default function useRowMotion( rows, onChange ) {
	const list = rows || [];
	const keysRef = useRef( [] );
	const elementsRef = useRef( new Map() );
	const beforeRef = useRef( null );
	const [ moved, setMoved ] = useState( null );

	// Rows can change from outside (undo, redo): keep one key per row.
	while ( keysRef.current.length < list.length ) {
		keysRef.current.push( newKey() );
	}
	keysRef.current.length = list.length;

	const setRowRef = useCallback(
		( key ) => ( element ) => {
			if ( element ) {
				elementsRef.current.set( key, element );
			} else {
				elementsRef.current.delete( key );
			}
		},
		[]
	);

	// Slide: compare each row's position before and after a move.
	useLayoutEffect( () => {
		const before = beforeRef.current;

		if ( ! before ) {
			return;
		}

		beforeRef.current = null;

		if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			return;
		}

		keysRef.current.forEach( ( key ) => {
			const element = elementsRef.current.get( key );
			const oldTop = before.get( key );

			if ( ! element || undefined === oldTop ) {
				return;
			}

			const delta = oldTop - element.getBoundingClientRect().top;

			if ( delta ) {
				element.animate( [ { transform: `translateY(${ delta }px)` }, { transform: 'none' } ], {
					duration: SLIDE_MS,
					easing: 'ease-out',
				} );
			}
		} );
	}, [ list ] );

	// Highlight for a moment, and put focus back on the button that was used.
	useEffect( () => {
		if ( ! moved ) {
			return undefined;
		}

		const element = elementsRef.current.get( moved.key );

		if ( element ) {
			const button =
				element.querySelector( `[data-move="${ moved.direction }"]:not([disabled])` ) ||
				element.querySelector( '[data-move]:not([disabled])' );

			if ( button ) {
				button.focus( { preventScroll: true } );
			}
		}

		const timer = window.setTimeout( () => setMoved( null ), HIGHLIGHT_MS );

		return () => window.clearTimeout( timer );
	}, [ moved ] );

	const addRow = ( emptyRow ) => {
		onChange( [ ...list, { ...emptyRow } ] );
	};

	const moveRow = ( index, direction ) => {
		const target = index + direction;

		if ( target < 0 || target >= list.length ) {
			return;
		}

		const keys = keysRef.current;
		const positions = new Map();

		keys.forEach( ( key ) => {
			const element = elementsRef.current.get( key );

			if ( element ) {
				positions.set( key, element.getBoundingClientRect().top );
			}
		} );
		beforeRef.current = positions;

		const movedKey = keys[ index ];
		[ keys[ index ], keys[ target ] ] = [ keys[ target ], keys[ index ] ];

		const next = list.slice();
		[ next[ index ], next[ target ] ] = [ next[ target ], next[ index ] ];

		setMoved( { key: movedKey, direction: direction < 0 ? 'up' : 'down' } );
		onChange( next );
	};

	const removeRow = ( index ) => {
		keysRef.current.splice( index, 1 );
		onChange( list.filter( ( _row, i ) => i !== index ) );
	};

	return {
		keys: keysRef.current,
		setRowRef,
		movedKey: moved ? moved.key : null,
		addRow,
		moveRow,
		removeRow,
	};
}
