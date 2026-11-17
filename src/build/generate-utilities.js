'use strict';

/**
 * Generates two files that get @imported by src/css/theme.css:
 *
 *  - src/css/utilities.css — the breakpoint-suffixed utility/grid classes
 *    (d-flex, d-md-flex, col-6, col-lg-4, justify-content-between, ...).
 *    This replaces the job Bootstrap's Sass @each loops used to do — same
 *    idea, plain Node instead of a preprocessor.
 *
 *  - src/css/blocks.css — a straight concatenation of whatever's actually in
 *    src/blocks/*.css. Add a file there named after a block's slug and
 *    it's picked up automatically on the next build; no registration needed.
 *
 * Run directly (`npm run css-generate`) or as part of `npm run css`.
 */

const fs = require('fs');
const path = require('path');
const fg = require('fast-glob');
const { breakpoints, gridColumns, utilities, spacingScale } = require('./tokens.config');
const { readTokensFromFile } = require('./read-tokens');

const cssDir = path.resolve(__dirname, '../css');
const blocksStylesDir = path.resolve(__dirname, '../blocks');
const tokensFile = path.resolve(cssDir, 'tokens.css');

// Reads --{prefix}* custom properties out of tokens.css's :root blocks
// (shared reader with generate-theme-json.js) so a utility's value set stays
// in sync with the token scale with no second place to edit.
function readTokenValues(prefix) {
	const tokens = readTokensFromFile(tokensFile);
	const values = {};
	for (const key of Object.keys(tokens)) {
		if (key.startsWith(prefix)) {
			values[key.slice(prefix.length)] = `var(--${key})`;
		}
	}
	return values;
}

function generateUtilities() {
	const rulesByBreakpoint = {};
	for (const bp of Object.keys(breakpoints)) {
		rulesByBreakpoint[bp] = [];
	}

	// display / flex-direction / justify-content / align-items / text-align / font-weight
	for (const [key, def] of Object.entries(utilities)) {
		const base = def.className || key;
		const values = def.tokenPrefix ? readTokenValues(def.tokenPrefix) : def.values;
		const bps = def.responsive === false ? [''] : Object.keys(breakpoints);
		for (const bp of bps) {
			for (const [suffix, value] of Object.entries(values)) {
				const className = bp ? `${base}-${bp}-${suffix}` : `${base}-${suffix}`;
				rulesByBreakpoint[bp].push(`.${className} { ${def.prop}: ${value}; }`);
			}
		}
	}

	// gap-{n} — responsive, driven off the spacing scale in tokens.config.js.
	//
	// Also publishes the requested size as --gap-size. That's purely so .row
	// can read back "what gap was asked for" and clamp the horizontal half of
	// it — a 12-track grid overflows if 11 column gaps exceed its own width.
	// See the --gap-size / column-gap pair in src/css/layout.css for the whole
	// story; nothing else consumes this property, and `gap` stays the thing
	// actually doing the work for every non-.row (flex, .grid) use.
	for (const bp of Object.keys(breakpoints)) {
		for (const n of spacingScale) {
			const className = bp ? `gap-${bp}-${n}` : `gap-${n}`;
			rulesByBreakpoint[bp].push(
				`.${className} { --gap-size: var(--space-${n}); gap: var(--space-${n}); }`
			);
		}
	}

	// col-{n} / col-{bp}-{n} — grid column span, base + every breakpoint.
	//
	// Deliberately the longhand grid-column-end (not the `grid-column: span
	// N` shorthand) so this can compose with offset-* below. A single bare
	// value in the grid-column SHORTHAND is assigned to grid-column-START,
	// not -end — so col-N and offset-N, as separate classes on the same
	// element, would both resolve to grid-column-start and the later one in
	// the cascade would silently wipe the other out (col-6 offset-3 rendered
	// 1 track wide). Setting col-N's span on -end only, and offset-N's line
	// number on -start only, makes them independent longhands that compose.
	for (const bp of Object.keys(breakpoints)) {
		for (let n = 1; n <= gridColumns; n++) {
			const className = bp ? `col-${bp}-${n}` : `col-${n}`;
			rulesByBreakpoint[bp].push(`.${className} { grid-column-end: span ${n}; }`);
		}
	}

	// offset-{n} / offset-{bp}-{n} — push a .row column over by n tracks, via
	// an explicit grid-column-start line number (1-indexed — line n+1 leaves n
	// empty tracks before the item). Composes with col-N above because that
	// sets grid-column-end only. n goes to gridColumns - 1: offsetting by the
	// full column count would push a column past the end of its own row.
	for (const bp of Object.keys(breakpoints)) {
		for (let n = 1; n < gridColumns; n++) {
			const className = bp ? `offset-${bp}-${n}` : `offset-${n}`;
			rulesByBreakpoint[bp].push(`.${className} { grid-column-start: ${n + 1}; }`);
		}
	}

	// margin / padding — responsive, same per-breakpoint pattern as gap-*/col-*
	// s/e (start/end) are physical, not logical — left/right, matching this
	// file's existing text-align start/end convention above, not RTL-aware
	// margin-inline-start/end.
	const spacingSides = {
		'': ['top', 'right', 'bottom', 'left'],
		t: ['top'],
		b: ['bottom'],
		s: ['left'],
		e: ['right'],
		x: ['left', 'right'],
		y: ['top', 'bottom'],
	};
	for (const bp of Object.keys(breakpoints)) {
		for (const n of spacingScale) {
			for (const [sideSuffix, sides] of Object.entries(spacingSides)) {
				for (const [abbr, prop] of [['m', 'margin'], ['p', 'padding']]) {
					const className = bp ? `${abbr}${sideSuffix}-${bp}-${n}` : `${abbr}${sideSuffix}-${n}`;
					const decls = sides.map((side) => `${prop}-${side}: var(--space-${n});`).join(' ');
					rulesByBreakpoint[bp].push(`.${className} { ${decls} }`);
				}
			}
		}

		// margin-only "auto" (m-auto, mx-auto, ...) — padding has no auto
		// value in CSS, so this isn't part of the shared loop above.
		for (const [sideSuffix, sides] of Object.entries(spacingSides)) {
			const className = bp ? `m${sideSuffix}-${bp}-auto` : `m${sideSuffix}-auto`;
			const decls = sides.map((side) => `margin-${side}: auto;`).join(' ');
			rulesByBreakpoint[bp].push(`.${className} { ${decls} }`);
		}
	}

	let output = '/* Generated by src/build/generate-utilities.js — do not hand-edit. */\n\n';
	for (const [bp, px] of Object.entries(breakpoints)) {
		const rules = rulesByBreakpoint[bp].join('\n');
		if (!bp) {
			output += rules + '\n\n';
		} else {
			output += `@media (min-width: ${px}px) {\n${rules
				.split('\n')
				.map((l) => '\t' + l)
				.join('\n')}\n}\n\n`;
		}
	}

	fs.writeFileSync(path.join(cssDir, 'utilities.css'), output);
	console.log('Generated src/css/utilities.css');
}

function generateBlocksCss() {
	fs.mkdirSync(blocksStylesDir, { recursive: true });
	const files = fg.sync('*.css', { cwd: blocksStylesDir }).sort();

	let output = '/* Generated by src/build/generate-utilities.js — do not hand-edit.\n';
	output += '   Concatenation of src/blocks/*.css, alphabetical by filename. */\n\n';
	for (const file of files) {
		output += `/* --- src/blocks/${file} --- */\n`;
		output += fs.readFileSync(path.join(blocksStylesDir, file), 'utf8').trimEnd() + '\n\n';
	}

	fs.writeFileSync(path.join(cssDir, 'blocks.css'), output);
	console.log(`Generated src/css/blocks.css (${files.length} block file(s))`);
}

generateUtilities();
generateBlocksCss();
