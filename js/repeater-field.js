/**
 * Add/remove/reorder rows and per-row image selection for `repeater`-type
 * settings fields (see lc_js_skeleton_render_repeater_field() in
 * inc/options.php). Generic — works for any repeater's sub-fields without
 * knowing their names, since row markup (including the empty-row
 * <template>) is fully rendered server-side; this only wires interactions.
 *
 * Row reordering is a plain DOM move — submitted field order follows DOM
 * order, and PHP preserves array insertion order regardless of the actual
 * (non-sequential) row index values, so nothing needs renumbering server-
 * side. The visible 1/2/3 row-number badge is purely cosmetic though, and
 * does need renumbering here after every add/remove/move.
 *
 * @package lc-js-skeleton2026
 */
( function ( $ ) {
	'use strict';

	function renumberRows( $rowsWrap ) {
		$rowsWrap.find( '.lc-js-skeleton-settings-repeater__row' ).each( function ( i ) {
			$( this ).find( '.lc-js-skeleton-settings-repeater__number' ).text( i + 1 );
		} );
	}

	function bindImagePicker( $row ) {
		$row.find( '.lc-js-skeleton-settings-repeater__select-image' ).on( 'click', function ( event ) {
			event.preventDefault();

			var $button = $( this );
			var $wrap = $button.closest( '.lc-js-skeleton-settings-repeater__image' );

			var frame = wp.media( {
				title: $button.data( 'select-label' ),
				button: { text: 'Use this image' },
				multiple: false,
			} );

			frame.on( 'select', function () {
				var attachment = frame.state().get( 'selection' ).first().toJSON();
				var src = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;

				$wrap.find( '.lc-js-skeleton-settings-repeater__image-input' ).val( attachment.id );
				$wrap.find( 'img' ).attr( 'src', src ).show();
				$wrap.find( '.lc-js-skeleton-settings-repeater__clear-image' ).show();
			} );

			frame.open();
		} );

		$row.find( '.lc-js-skeleton-settings-repeater__clear-image' ).on( 'click', function ( event ) {
			event.preventDefault();

			var $button = $( this );
			var $wrap = $button.closest( '.lc-js-skeleton-settings-repeater__image' );

			$wrap.find( '.lc-js-skeleton-settings-repeater__image-input' ).val( '' );
			$wrap.find( 'img' ).attr( 'src', '' ).hide();
			$button.hide();
		} );
	}

	function bindRowActions( $row, $rowsWrap ) {
		$row.find( '.lc-js-skeleton-settings-repeater__remove-row' ).on( 'click', function ( event ) {
			event.preventDefault();
			$row.remove();
			renumberRows( $rowsWrap );
		} );

		$row.find( '.lc-js-skeleton-settings-repeater__move-up' ).on( 'click', function ( event ) {
			event.preventDefault();
			var $prev = $row.prev( '.lc-js-skeleton-settings-repeater__row' );
			if ( $prev.length ) {
				$row.insertBefore( $prev );
				renumberRows( $rowsWrap );
			}
		} );

		$row.find( '.lc-js-skeleton-settings-repeater__move-down' ).on( 'click', function ( event ) {
			event.preventDefault();
			var $next = $row.next( '.lc-js-skeleton-settings-repeater__row' );
			if ( $next.length ) {
				$row.insertAfter( $next );
				renumberRows( $rowsWrap );
			}
		} );

		bindImagePicker( $row );
	}

	$( function () {
		$( '.lc-js-skeleton-settings-repeater' ).each( function () {
			var $field = $( this );
			var $rowsWrap = $field.find( '.lc-js-skeleton-settings-repeater__rows' );
			var templateHtml = $field.find( 'template' )[ 0 ].innerHTML;
			var rowCounter = 0;

			$rowsWrap.find( '.lc-js-skeleton-settings-repeater__row' ).each( function () {
				bindRowActions( $( this ), $rowsWrap );
			} );

			$field.find( '.lc-js-skeleton-settings-repeater__add-row' ).on( 'click', function ( event ) {
				event.preventDefault();
				rowCounter += 1;
				var index = 'new_' + Date.now() + '_' + rowCounter;
				var $row = $( templateHtml.split( '__INDEX__' ).join( index ) );
				$rowsWrap.append( $row );
				bindRowActions( $row, $rowsWrap );
				renumberRows( $rowsWrap );
			} );
		} );
	} );
} )( jQuery );
