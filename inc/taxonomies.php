<?php
/**
 * Custom Taxonomies Registration
 *
 * Duplicate the register_taxonomy() call below (commented out) as a
 * starting point for a new taxonomy — nothing is registered by default.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register custom taxonomies for the theme.
 *
 * @return void
 */
function lc_js_skeleton_register_theme_taxonomies() {

	/*
	register_taxonomy(
		'service',
		array( 'case_study', 'post' ),
		array(
			'labels'             => array(
				'name'          => 'Services',
				'singular_name' => 'Service',
			),
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_ui'            => true,
			'show_in_nav_menus'  => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => true,
			'show_in_rest'       => true,
			'rewrite'            => false,
		)
	);
	*/

}
add_action( 'init', 'lc_js_skeleton_register_theme_taxonomies' );
