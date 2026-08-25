/**
 * Generic tab switching — pairs [data-tabs-target] nav links with
 * [data-tabs-panel] panels sharing the same value, inside any [data-tabs]
 * container. No dependency on the Settings API or any specific markup
 * beyond that data-attribute contract, so the same pattern can be reused
 * for the next admin screen that needs tabs, or adapted as a React
 * component for the block editor if a block ever needs them — see
 * lc_js_skeleton_render_settings_page() in inc/options.php for the first
 * (and so far only) use.
 *
 * Framework-free on purpose — unlike js/gallery-field.js and
 * js/repeater-field.js, this has no reason to depend on jQuery (no
 * wp.media involved), so it doesn't.
 *
 * @package lc-js-skeleton2026
 */
( function () {
	'use strict';

	document.querySelectorAll( '[data-tabs]' ).forEach( function ( tabs ) {
		var links = tabs.querySelectorAll( '[data-tabs-target]' );
		var panels = tabs.querySelectorAll( '[data-tabs-panel]' );

		links.forEach( function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				event.preventDefault();

				var target = link.getAttribute( 'data-tabs-target' );

				links.forEach( function ( otherLink ) {
					otherLink.classList.toggle( 'nav-tab-active', otherLink === link );
				} );

				panels.forEach( function ( panel ) {
					panel.hidden = panel.getAttribute( 'data-tabs-panel' ) !== target;
				} );
			} );
		} );
	} );
} )();
