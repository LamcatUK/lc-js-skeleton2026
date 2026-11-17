/**
 * Native <dialog> wiring — replaces Bootstrap's Modal component entirely.
 * showModal()/close() do the heavy lifting (focus trap, Escape-to-close,
 * ::backdrop); this just connects trigger/close buttons to a target dialog.
 *
 * Markup:
 *   <button data-dialog-target="my-dialog">Open</button>
 *   <dialog id="my-dialog">
 *     <button data-dialog-close>Close</button>
 *     ...
 *   </dialog>
 *
 * A dialog containing <iframe data-src="..."> (click-to-play video embeds)
 * only loads the iframe when the dialog opens — nothing is fetched or
 * tracked until asked for — and unloads it again on close, so background
 * playback stops the moment the modal closes.
 */
export function initDialogs() {
	document.querySelectorAll('[data-dialog-target]').forEach((trigger) => {
		const dialog = document.getElementById(trigger.getAttribute('data-dialog-target'));
		if (!(dialog instanceof HTMLDialogElement)) return;

		trigger.addEventListener('click', () => {
			dialog.querySelectorAll('iframe[data-src]').forEach((frame) => {
				if (!frame.getAttribute('src')) frame.setAttribute('src', frame.getAttribute('data-src'));
			});
			dialog.showModal();
		});

		dialog.querySelectorAll('[data-dialog-close]').forEach((closeBtn) => {
			closeBtn.addEventListener('click', () => dialog.close());
		});

		// Click on the backdrop (the dialog element itself, outside its content) closes it.
		dialog.addEventListener('click', (event) => {
			if (event.target === dialog) dialog.close();
		});

		// Unload lazy iframes on close — removes the video from the page so
		// playback (and tracking) stops, ready to load fresh next open.
		dialog.addEventListener('close', () => {
			dialog.querySelectorAll('iframe[data-src]').forEach((frame) => {
				frame.removeAttribute('src');
			});
		});
	});
}
