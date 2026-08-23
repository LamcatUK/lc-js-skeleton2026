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
 */
export function initDialogs() {
	document.querySelectorAll('[data-dialog-target]').forEach((trigger) => {
		const dialog = document.getElementById(trigger.getAttribute('data-dialog-target'));
		if (!(dialog instanceof HTMLDialogElement)) return;

		trigger.addEventListener('click', () => dialog.showModal());

		dialog.querySelectorAll('[data-dialog-close]').forEach((closeBtn) => {
			closeBtn.addEventListener('click', () => dialog.close());
		});

		// Click on the backdrop (the dialog element itself, outside its content) closes it.
		dialog.addEventListener('click', (event) => {
			if (event.target === dialog) dialog.close();
		});
	});
}
