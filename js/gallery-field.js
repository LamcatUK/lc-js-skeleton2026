/**
 * Media-modal picker for `gallery`-type settings fields.
 *
 * @package lc-js-skeleton2026
 */
( function ( $ ) {
	'use strict';

	$( function () {
		// Scoped per field: the settings page can hold several gallery
		// fields at once (e.g. Example Gallery + a second gallery), and
		// unscoped $input/$preview sets span all of them — a dragend in one
		// list then writes every list's IDs concatenated into every input,
		// doubling all entries on save. Each closure below sees only its
		// own field, so drags (and picker frames) can't leak across lists.
		$( '.lc-js-skeleton-gallery-field' ).each( function () {
			var $field = $( this );
			var $input = $field.find( 'input[type="hidden"]' );
			var $preview = $field.find( '.lc-js-skeleton-gallery-field__preview' );
			var frame = null;

		function syncInputFromPreview() {
			var ids = $preview.find( 'li' ).map( function () {
				return $( this ).data( 'id' );
			} ).get();
			$input.val( ids.join( ',' ) );
		}

		function renderPreview( attachments ) {
			$preview.empty();
			attachments.forEach( function ( attachment ) {
				var src = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;
				$( '<li>' )
					.attr( { draggable: 'true', 'data-id': attachment.id } )
					.css( { cursor: 'grab' } )
					.append( $( '<img>' ).attr( { src: src, alt: '' } ).css( {
						width: '80px',
						height: '80px',
						objectFit: 'contain',
						background: '#fff',
						border: '1px solid #ccc',
						pointerEvents: 'none',
					} ) )
					.appendTo( $preview );
			} );
			bindDragReorder();
		}

		/**
		 * Plain HTML5 drag-and-drop — no sortable library, matches this
		 * field's existing no-dependencies approach. Rebound after every
		 * renderPreview() since that replaces all <li> elements; existing
		 * (page-load) thumbnails are bound once below.
		 */
		function bindDragReorder() {
			var $items = $preview.find( 'li' );
			var $dragging = null;

			$items.off( 'dragstart dragend dragover drop' );

			$items.on( 'dragstart', function ( event ) {
				$dragging = $( this );
				$dragging.css( 'opacity', '0.4' );
				event.originalEvent.dataTransfer.effectAllowed = 'move';
			} );

			$items.on( 'dragend', function () {
				if ( $dragging ) {
					$dragging.css( 'opacity', '' );
				}
				$dragging = null;
				syncInputFromPreview();
			} );

			$items.on( 'dragover', function ( event ) {
				event.preventDefault();
				if ( ! $dragging || $dragging.is( this ) ) {
					return;
				}

				var isAfter = event.originalEvent.offsetX > $( this ).outerWidth() / 2;
				if ( isAfter ) {
					$dragging.insertAfter( this );
				} else {
					$dragging.insertBefore( this );
				}
			} );

			$items.on( 'drop', function ( event ) {
				event.preventDefault();
			} );
		}

		bindDragReorder(); // wire up thumbnails already rendered server-side on page load

		$field.find( '.lc-js-skeleton-gallery-field__select' ).on( 'click', function ( event ) {
			event.preventDefault();

			var ids = ( $input.val() || '' ).split( ',' ).filter( Boolean );
			var shortcode = ids.length ? '[gallery ids="' + ids.join( ',' ) + '"]' : '[gallery]';

			// wp.media.gallery.edit() opens core's native "Edit Gallery" frame —
			// the same UI as double-clicking a Gallery block: hover-to-remove
			// on each image, drag reorder, and an "Add to Gallery" panel.
			frame = wp.media.gallery.edit( shortcode );

			frame.state( 'gallery-edit' ).on( 'update', function ( selection ) {
				var attachments = selection.toJSON();
				$input.val( attachments.map( function ( attachment ) {
					return attachment.id;
				} ).join( ',' ) );
				renderPreview( attachments );
				frame.close();
			} );

			frame.open();
		} );

		$field.find( '.lc-js-skeleton-gallery-field__clear' ).on( 'click', function ( event ) {
			event.preventDefault();
			$input.val( '' );
			$preview.empty();
		} );
		} );
	} );
} )( jQuery );
