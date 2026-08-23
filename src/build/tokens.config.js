'use strict';

/**
 * Single source of truth for breakpoints and the utility/grid classes the
 * generator produces. CSS custom properties can't be read inside an @media
 * condition, so breakpoint pixel values live here, not in tokens.css.
 *
 * A couple of hand-authored files (src/css/nav.css) hardcode the "lg" value
 * in a plain @media query for the same reason — keep them in sync with this
 * file if a breakpoint changes.
 */

// Empty-string key = no media query (mobile-first base). Order matters —
// generated in this order, later ones win on equal specificity.
const breakpoints = {
	'': null,
	sm: 576,
	md: 768,
	lg: 992,
	xl: 1200,
	xxl: 1400,
};

const gridColumns = 12;

// property: [className prefix, CSS property, { suffix: value } ]
const utilities = {
	display: {
		className: 'd',
		prop: 'display',
		values: { block: 'block', flex: 'flex', 'inline-flex': 'inline-flex', grid: 'grid', none: 'none' },
	},
	'flex-direction': {
		className: 'flex',
		prop: 'flex-direction',
		values: { row: 'row', column: 'column' },
	},
	// suffixes intentionally match Bootstrap's standalone names (.flex-wrap,
	// not .flex-wrap-wrap) — same className prefix as flex-direction above,
	// no collision since the suffixes themselves don't overlap.
	'flex-wrap': {
		className: 'flex',
		prop: 'flex-wrap',
		values: { wrap: 'wrap', nowrap: 'nowrap', 'wrap-reverse': 'wrap-reverse' },
		responsive: false,
	},
	'justify-content': {
		prop: 'justify-content',
		values: { start: 'flex-start', end: 'flex-end', center: 'center', between: 'space-between' },
	},
	'align-items': {
		prop: 'align-items',
		values: { start: 'flex-start', end: 'flex-end', center: 'center' },
	},
	'text-align': {
		className: 'text',
		prop: 'text-align',
		values: { start: 'left', center: 'center', end: 'right' },
	},
	// values read from tokens.css's --fw-* custom properties at generate time
	// (see readTokenValues() in generate-utilities.js) — add a --fw-300 there
	// and .fw-300 appears here with no edit needed in this file. Base only,
	// no per-breakpoint variants (fw-lg-700 etc. aren't a real-world need).
	'font-weight': {
		className: 'fw',
		prop: 'font-weight',
		tokenPrefix: 'fw-',
		responsive: false,
	},
};

// Spacing utilities (gap, margin, padding) driven off the tokens.css spacing scale.
const spacingScale = [0, 1, 2, 3, 4, 5];

module.exports = { breakpoints, gridColumns, utilities, spacingScale };
