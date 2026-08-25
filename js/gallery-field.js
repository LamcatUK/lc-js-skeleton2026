/**
 * Media-modal picker for `gallery`-type settings fields.
 *
 * @package lc-js-skeleton2026
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $field = $( '.lc-js-skeleton-gallery-field' );
		if ( ! $field.length ) {
			return;
		}

		var $input = $field.find( 'input[type="hidden"]' );
		var $preview = $field.find( '.lc-js-skeleton-gallery-field__preview' );
		var frame = null;

		function renderPreview( attachments ) {
			$preview.empty();
			attachments.forEach( function ( attachment ) {
				var src = attachment.sizes && attachment.sizes.thumbnail
					? attachment.sizes.thumbnail.url
					: attachment.url;
				$( '<li>' )
					.append( $( '<img>' ).attr( { src: src, alt: '' } ).css( {
						width: '80px',
						height: '80px',
						objectFit: 'contain',
						background: '#fff',
						border: '1px solid #ccc',
					} ) )
					.appendTo( $preview );
			} );
		}

		$field.find( '.lc-js-skeleton-gallery-field__select' ).on( 'click', function ( event ) {
			event.preventDefault();

			if ( ! frame ) {
				frame = wp.media( {
					title: 'Select Images',
					button: { text: 'Use these images' },
					multiple: true,
				} );

				frame.on( 'select', function () {
					var attachments = frame.state().get( 'selection' ).toJSON();
					$input.val( attachments.map( function ( attachment ) {
						return attachment.id;
					} ).join( ',' ) );
					renderPreview( attachments );
				} );
			}

			frame.open();
		} );

		$field.find( '.lc-js-skeleton-gallery-field__clear' ).on( 'click', function ( event ) {
			event.preventDefault();
			$input.val( '' );
			$preview.empty();
		} );
	} );
} )( jQuery );
