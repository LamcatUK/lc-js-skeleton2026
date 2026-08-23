<?php
/**
 * Register native blocks.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register every block under blocks/ automatically — each one is a
 * directory containing its own block.json, so nothing needs registering by
 * hand here when add_block.sh scaffolds a new one.
 *
 * @return void
 */
function lc_js_skeleton_register_blocks() {
	foreach ( glob( get_template_directory() . '/blocks/*/block.json' ) as $block_json ) {
		register_block_type( dirname( $block_json ) );
	}
}
add_action( 'init', 'lc_js_skeleton_register_blocks' );

/**
 * Give plain-text core blocks a render_callback that wraps their output in
 * .container. Most page width in this theme comes from blocks building
 * their own .container internally — a bare core/paragraph or core/heading
 * dropped into the_content() would otherwise render edge-to-edge with no
 * container padding.
 *
 * @param array  $args Block type args.
 * @param string $name Block type name.
 * @return array
 */
function lc_js_skeleton_core_block_type_args( $args, $name ) {
	$wrapped_blocks = array( 'core/paragraph', 'core/heading', 'core/list', 'core/separator' );

	if ( in_array( $name, $wrapped_blocks, true ) ) {
		$args['render_callback'] = 'lc_js_skeleton_wrap_block_in_container';
	}

	return $args;
}
add_filter( 'register_block_type_args', 'lc_js_skeleton_core_block_type_args', 10, 2 );

/**
 * Render callback that wraps a core block's content in .container.
 *
 * @param array  $attributes Block attributes — unused, required by the render_callback signature.
 * @param string $content    Rendered block content.
 * @return string
 */
function lc_js_skeleton_wrap_block_in_container( $attributes, $content ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	return '<div class="container">' . $content . '</div>';
}

/**
 * Register the category that this theme's own native blocks (blocks/*) are
 * registered into — see add_block.sh, which scaffolds new blocks with
 * "category": "{text domain}" so they land here rather than in a core
 * bucket like "layout".
 *
 * Named after the theme itself (text domain as slug, theme Name as title)
 * rather than a generic "theme" slug — WordPress core already registers a
 * category with that exact slug for legacy widget blocks, and a project
 * built from this skeleton may well end up disallowing that whole category
 * as noise, which would silently hide every block registered under a
 * colliding "theme" slug too.
 *
 * @param array $categories Existing block categories.
 * @return array
 */
function lc_js_skeleton_register_theme_block_category( $categories ) {
	$theme = wp_get_theme();
	$slug  = $theme->get( 'TextDomain' );

	foreach ( $categories as $category ) {
		if ( isset( $category['slug'] ) && $slug === $category['slug'] ) {
			return $categories;
		}
	}

	array_unshift(
		$categories,
		array(
			'slug'  => $slug,
			'title' => $theme->get( 'Name' ),
			'icon'  => null,
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'lc_js_skeleton_register_theme_block_category' );
