import { __ } from '@wordpress/i18n';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { useInstanceId } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { useEffect, useMemo, useRef, useState } from '@wordpress/element';

/**
 * Wraps a block's edit.js output in a collapsible section with a title bar
 * and open/closed toggle, persisting the toggle state per block-instance
 * (keyed by the block's position path within the post plus the current
 * URL) in localStorage — so a page with many blocks stays scannable in the
 * editor instead of every block's full field set staying expanded at once.
 *
 * @param {Object}   props
 * @param {Object}   props.blockProps       Result of useBlockProps().
 * @param {string}   props.clientId         The block's clientId, for deriving its position path.
 * @param {string}   [props.classPrefix]    Class/prefix root, matches editor.css.
 * @param {string}   [props.textDomain]     i18n text domain for the toggle's aria-label.
 * @param {string}   [props.storageNamespace] Extra localStorage key segment, in case two shells need independent state on the same path.
 * @param {string}   props.title            Block name shown in the title bar.
 * @param {boolean}  [props.defaultOpen]    Initial state before localStorage is read.
 * @param {*}        props.children         The block's own field controls.
 */
export default function EditorBlockShell( {
	blockProps,
	clientId,
	classPrefix = 'lc-js-skeleton',
	textDomain = 'lc-js-skeleton2026',
	storageNamespace = 'block',
	title,
	children,
	defaultOpen = true,
} ) {
	const [ isOpen, setIsOpen ] = useState( defaultOpen );
	const contentRef = useRef();
	const instanceId = useInstanceId( EditorBlockShell );
	const contentId = `${ classPrefix }-editor-block-content-${ instanceId }`;
	const blockPath = useSelect(
		( select ) => {
			if ( ! clientId ) {
				return '';
			}

			const { getBlockIndex, getBlockRootClientId } = select( blockEditorStore );
			const path = [];
			let currentId = clientId;

			while ( currentId ) {
				const parentId = getBlockRootClientId( currentId ) || '';
				path.unshift( String( getBlockIndex( currentId, parentId ) ) );
				currentId = parentId || null;
			}

			return path.join( '.' );
		},
		[ clientId ]
	);
	const storageKey = useMemo( () => {
		if ( ! clientId || ! blockPath || typeof window === 'undefined' ) {
			return '';
		}

		return [ classPrefix, 'editor-block-state', storageNamespace, window.location.pathname, window.location.search, blockPath ].join(
			':'
		);
	}, [ blockPath, classPrefix, clientId, storageNamespace ] );

	useEffect( () => {
		if ( ! storageKey || typeof window === 'undefined' ) {
			return;
		}

		const storedValue = window.localStorage.getItem( storageKey );

		if ( storedValue === 'closed' ) {
			setIsOpen( false );
		} else if ( storedValue === 'open' ) {
			setIsOpen( true );
		}
	}, [ storageKey ] );

	useEffect( () => {
		if ( ! storageKey || typeof window === 'undefined' ) {
			return;
		}

		window.localStorage.setItem( storageKey, isOpen ? 'open' : 'closed' );
	}, [ isOpen, storageKey ] );

	// Confirmed live as a real, reproducible bug, not a theory: every field in
	// a block's own fields-form UI (TextControl, RichText, the repeater's own
	// inputs) renders inside the block's own canvas output, which sits inside
	// Gutenberg's WritingFlow component — the same wrapper that manages
	// block-to-block multi-selection. Pressing Shift+Arrow with the cursor
	// mid-text (not at a boundary — ruled out as WordPress's own intentional
	// "extend past the edge" behaviour) handed focus to WritingFlow's own
	// handler instead of the input doing its own text selection.
	//
	// A React onKeyDown prop + event.stopPropagation() here does NOT fix it:
	// per Gutenberg's own source
	// (packages/block-editor/src/components/writing-flow/use-arrow-nav.js),
	// WritingFlow intercepts with a plain native `node.addEventListener(
	// 'keydown', onKeyDown )` on its own wrapper element — not a React
	// synthetic handler. That native listener fires during real DOM bubble
	// propagation, which reaches it before React's own internal delegated
	// dispatch (which is what actually invokes a React onKeyDown prop) ever
	// gets to run — so stopping propagation inside React's synthetic system is
	// always too late. Only a real addEventListener on a descendant node (this
	// one) intercepts during native bubbling before it reaches WritingFlow's
	// own ancestor listener.
	useEffect( () => {
		const node = contentRef.current;

		if ( ! node ) {
			return;
		}

		function stopShiftArrowFromReachingWritingFlow( event ) {
			if ( event.shiftKey && [ 'ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight' ].includes( event.key ) ) {
				event.stopPropagation();
			}
		}

		node.addEventListener( 'keydown', stopShiftArrowFromReachingWritingFlow );

		return () => {
			node.removeEventListener( 'keydown', stopShiftArrowFromReachingWritingFlow );
		};
	}, [] );

	return (
		<div { ...blockProps }>
			<div className={ `${ classPrefix }-editor-block__title` }>
				<span>{ title }</span>
				<button
					type="button"
					className={ `${ classPrefix }-editor-block__toggle` }
					onClick={ () => setIsOpen( ( open ) => ! open ) }
					aria-expanded={ isOpen }
					aria-controls={ contentId }
					aria-label={ isOpen ? __( 'Hide block fields', textDomain ) : __( 'Show block fields', textDomain ) }
				>
					<span aria-hidden="true">{ isOpen ? '−' : '+' }</span>
				</button>
			</div>
			<div id={ contentId } ref={ contentRef } className={ `${ classPrefix }-editor-block__content` } hidden={ ! isOpen }>
				{ children }
			</div>
		</div>
	);
}
