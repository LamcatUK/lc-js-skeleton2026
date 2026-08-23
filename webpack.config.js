/**
 * Extends the default @wordpress/scripts webpack config so every block under
 * blocks/{slug}/src/index.js compiles to its own blocks/{slug}/build/index.js
 * — matching each block.json's "editorScript": "file:./build/index.js" —
 * instead of wp-scripts' single top-level build/ default.
 */

const path = require( 'path' );
const fastGlob = require( 'fast-glob' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const entry = fastGlob.sync( 'blocks/*/src/index.js' ).reduce( ( entries, entryPath ) => {
	const blockSlug = entryPath.split( '/' )[ 1 ];
	entries[ blockSlug ] = path.resolve( process.cwd(), entryPath );
	return entries;
}, {} );

module.exports = {
	...defaultConfig,
	entry,
	output: {
		...defaultConfig.output,
		path: path.resolve( process.cwd(), 'blocks' ),
		filename: '[name]/build/index.js',
		// The default config cleans output.path before every build. Since
		// output.path here is the shared blocks/ directory (not a dedicated
		// build/ dir), that would wipe every block's block.json/render.php/src
		// alongside the compiled output — disable it rather than risk that.
		clean: false,
	},
};
