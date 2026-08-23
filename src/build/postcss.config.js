'use strict';

/*
 * postcss-nesting is mostly belt-and-braces here — the "modern evergreen
 * only" browserslist target already has native CSS nesting support, so this
 * plugin has little to flatten. Kept in case a nesting edge case differs
 * across engines; drop it if you'd rather ship zero CSS build step at all.
 */

module.exports = {
	map: {
		inline: false,
		annotation: true,
		sourcesContent: true,
	},
	plugins: {
		'postcss-import': {},
		'postcss-nesting': {},
		autoprefixer: {},
	},
};
