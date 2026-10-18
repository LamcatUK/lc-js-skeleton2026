<?php
/**
 * LC JS Skeleton 2026 — theme functions.
 *
 * Standalone theme, no parent theme. See style.css header for the
 * "BS-flavored naming, not Bootstrap" note and browser support baseline.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

// Disable the Theme and Plugin file editors in wp-admin. Core omits both
// menu items (and blocks direct access to theme-editor.php/plugin-editor.php)
// when this is set; defining it here rather than wp-config.php keeps the
// hardening with the theme, and the guard respects a wp-config.php value.
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

define( 'LC_SKELETON_DIR', get_template_directory() );

require_once LC_SKELETON_DIR . '/inc/setup.php';
require_once LC_SKELETON_DIR . '/inc/enqueue.php';
require_once LC_SKELETON_DIR . '/inc/class-lc-skeleton-nav-walker.php';
require_once LC_SKELETON_DIR . '/inc/blocks.php';
require_once LC_SKELETON_DIR . '/inc/editor.php';
require_once LC_SKELETON_DIR . '/inc/options.php';
require_once LC_SKELETON_DIR . '/inc/social-icons.php';
require_once LC_SKELETON_DIR . '/inc/head-tags.php';
require_once LC_SKELETON_DIR . '/inc/block-usage.php';
require_once LC_SKELETON_DIR . '/inc/utilities.php';
require_once LC_SKELETON_DIR . '/inc/posttypes.php';
require_once LC_SKELETON_DIR . '/inc/taxonomies.php';
require_once LC_SKELETON_DIR . '/inc/security.php';
