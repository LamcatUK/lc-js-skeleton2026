/**
 * Mobile nav toggle. Wires any button with aria-controls pointing at a
 * .navbar-collapse to show/hide it and keep aria-expanded in sync — this is
 * the entire replacement for Bootstrap's Collapse component for this use case.
 */
export function initNavToggle() {
	document.querySelectorAll('.navbar-toggler[aria-controls]').forEach((toggler) => {
		const target = document.getElementById(toggler.getAttribute('aria-controls'));
		if (!target) return;

		toggler.addEventListener('click', () => {
			const isOpen = target.classList.toggle('is-open');
			toggler.setAttribute('aria-expanded', String(isOpen));
		});

		// Close after choosing a link — expected mobile nav behaviour.
		target.querySelectorAll('a').forEach((link) => {
			link.addEventListener('click', () => {
				target.classList.remove('is-open');
				toggler.setAttribute('aria-expanded', 'false');
			});
		});
	});
}
