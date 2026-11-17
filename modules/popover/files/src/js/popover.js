/**
 * Bridges for span popover triggers.
 *
 * Popover triggers are <span>s, not <button>s — a button won't flow inline
 * mid-sentence (it stays an atomic, centred box), while a span wraps with
 * the surrounding text. Two gaps come with that, both bridged here and
 * nothing else — Esc/outside-click/× dismiss stays fully native:
 *
 * - Click: only <button> invokers fire popovertarget natively, so span
 *   clicks toggle the popover by hand. Native <button> triggers (e.g. the
 *   × close buttons) are left to the platform.
 * - Keyboard: buttons get Enter/Space free, spans don't — a focused span
 *   trigger synthesises a click, which flows through the bridge above.
 */
export function initPopovers() {
	document.addEventListener( 'click', ( event ) => {
		const trigger = event.target instanceof Element ? event.target.closest( '[popovertarget]' ) : null;

		if ( ! trigger || 'BUTTON' === trigger.tagName ) {
			return;
		}

		const popover = document.getElementById( trigger.getAttribute( 'popovertarget' ) );

		if ( popover instanceof HTMLElement && 'function' === typeof popover.togglePopover ) {
			popover.togglePopover();
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Enter' !== event.key && ' ' !== event.key ) {
			return;
		}

		const trigger = event.target instanceof Element ? event.target.closest( '[popovertarget]' ) : null;

		if ( ! trigger || 'BUTTON' === trigger.tagName ) {
			return;
		}

		event.preventDefault();
		trigger.click();
	} );
}
