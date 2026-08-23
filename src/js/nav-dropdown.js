/**
 * Click-to-open nav dropdowns. Each dropdown-toggle button shows/hides its
 * linked .dropdown-menu and keeps aria-expanded in sync. Clicking elsewhere,
 * or pressing Escape, closes whatever is open — this is the entire
 * replacement for hover-based submenus.
 */
export function initNavDropdowns() {
	const toggles = document.querySelectorAll('.dropdown-toggle[aria-controls]');

	function close(toggle) {
		const menu = document.getElementById(toggle.getAttribute('aria-controls'));
		if (!menu) return;
		menu.classList.remove('is-open');
		toggle.setAttribute('aria-expanded', 'false');
	}

	function closeAllExcept(except) {
		toggles.forEach((toggle) => {
			if (toggle !== except) close(toggle);
		});
	}

	toggles.forEach((toggle) => {
		const menu = document.getElementById(toggle.getAttribute('aria-controls'));
		if (!menu) return;

		toggle.addEventListener('click', (event) => {
			event.stopPropagation();
			const isOpen = menu.classList.toggle('is-open');
			toggle.setAttribute('aria-expanded', String(isOpen));
			closeAllExcept(toggle);
		});
	});

	document.addEventListener('click', (event) => {
		if (event.target.closest('.dropdown-menu')) return;
		closeAllExcept();
	});

	document.addEventListener('keydown', (event) => {
		if (event.key !== 'Escape') return;
		const openToggle = Array.from(toggles).find((toggle) => toggle.getAttribute('aria-expanded') === 'true');
		closeAllExcept();
		if (openToggle) openToggle.focus();
	});
}