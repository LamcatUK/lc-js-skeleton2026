/**
 * Smooth scroll via Lenis, synced with GSAP ScrollTrigger through a single
 * gsap-ticker-driven loop — this sync is the whole point: Lenis on its own
 * rAF loop leaves ScrollTrigger reading native scroll positions that no
 * longer match what's on screen, desyncing every scroll-driven trigger on
 * the page. Self-guarding: no-ops if the Lenis global isn't present;
 * reduced-motion visitors get native scrolling instead of a hijacked one.
 */
export function initLenis() {
	if (typeof window.Lenis === 'undefined') return;

	if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

	const lenis = new window.Lenis({
		lerp: 0.1,
	});

	if (typeof window.gsap !== 'undefined' && typeof window.ScrollTrigger !== 'undefined') {
		try {
			window.gsap.registerPlugin(window.ScrollTrigger);
			lenis.on('scroll', window.ScrollTrigger.update);
			window.gsap.ticker.add((time) => {
				lenis.raf(time * 1000);
			});
			window.gsap.ticker.lagSmoothing(0);
			return;
		} catch (error) {
			// Fall through to a standalone rAF loop below.
		}
	}

	function raf(time) {
		lenis.raf(time);
		requestAnimationFrame(raf);
	}
	requestAnimationFrame(raf);
}
