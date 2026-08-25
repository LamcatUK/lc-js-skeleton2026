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
 * Tweak block registration for non-theme blocks.
 *
 * Plain-text core blocks get a render_callback that wraps their output in
 * .container. Most page width in this theme comes from blocks building
 * their own .container internally — a bare core/paragraph or core/heading
 * dropped into the_content() would otherwise render edge-to-edge with no
 * container padding.
 *
 * Any remaining non-theme blocks are regrouped into a single Gutenberg
 * inserter category so the editor does not keep WordPress's default bucket
 * names around.
 *
 * @param array  $args Block type args.
 * @param string $name Block type name.
 * @return array
 */
function lc_js_skeleton_core_block_type_args( $args, $name ) {
	$GLOBALS['lc_js_skeleton_original_block_categories'][ $name ] = isset( $args['category'] ) ? $args['category'] : '';

	$theme_namespace = wp_get_theme()->get( 'TextDomain' ) . '/';
	$wrapped_blocks = array( 'core/paragraph', 'core/heading', 'core/list', 'core/separator' );

	if ( 0 !== strpos( $name, $theme_namespace ) ) {
		$args['category'] = 'gutenberg';
	}

	if ( in_array( $name, $wrapped_blocks, true ) ) {
		$args['render_callback'] = 'lc_js_skeleton_wrap_block_in_container';
	}

	return $args;
}
add_filter( 'register_block_type_args', 'lc_js_skeleton_core_block_type_args', 10, 2 );

/**
 * Register a single Gutenberg category for non-theme blocks.
 *
 * @param array $categories Existing block categories.
 * @return array
 */
function lc_js_skeleton_register_gutenberg_block_category( $categories ) {
	foreach ( $categories as $category ) {
		if ( isset( $category['slug'] ) && 'gutenberg' === $category['slug'] ) {
			return $categories;
		}
	}

	array_unshift(
		$categories,
		array(
			'slug'  => 'gutenberg',
			'title' => 'Gutenberg',
			'icon'  => null,
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'lc_js_skeleton_register_gutenberg_block_category' );

/**
 * Register the category that this theme's own native blocks (blocks/*) are
 * registered into — see add_block.sh, which scaffolds new blocks with
 * "category": "{text domain}" so they land here rather than in a core
 * bucket like "layout".
 *
 * Named after the theme itself (text domain as slug, theme Name as title)
 * rather than a generic "theme" slug — WordPress core already registers a
 * category with that exact slug for legacy widget blocks, and this theme
 * disallows that whole category as noise (see
 * lc_js_skeleton_get_disallowed_gutenberg_blocks()), which silently hides
 * every block registered under a colliding "theme" slug too.
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

/**
 * Return disallowed Gutenberg blocks for this theme.
 *
 * Keeping the removals under one heading makes future pruning easier than
 * mirroring WordPress's own category layout.
 *
 * @return array<string, array<string>>
 */
function lc_js_skeleton_get_disallowed_gutenberg_blocks() {
	return array(
		'categories'        => array(
			'theme',
			'embed',
		),
		'blocks'            => array(
			'core/embed',
			'core/details',
			'core/math',
			'core/preformatted',
			'core/verse',
			'core/audio',
			'core/cover',
			'core/file',
			'core/gallery',
			'core/icon',
			'core/media-text',
			'core/playlist',
			'core/video',
		),
		'prefixes'          => array(
			'core-embed/',
			'yoast/*',
			'yoast-seo/*',
		),
		'widget_exceptions' => array( 'core/html' ),
	);
}

/**
 * Hide unwanted core block groups from the inserter.
 *
 * Those blocks (site title/logo, navigation, query loop, template-part,
 * post-content, comments pieces, embeds, most widgets, Yoast blocks, etc.)
 * come from WordPress core or plugins, not this theme. This skeleton will
 * use its own project-specific theme blocks instead, so leave the generic
 * core theme/embed/widget blocks and Yoast blocks unavailable by default,
 * while still allowing Custom HTML.
 *
 * @param bool|array $allowed_block_types Current allowed block types.
 * @return bool|array
 */
function lc_js_skeleton_disallow_unwanted_core_blocks( $allowed_block_types ) {
	$registered_blocks       = WP_Block_Type_Registry::get_instance()->get_all_registered();
	$disallowed_gutenberg    = lc_js_skeleton_get_disallowed_gutenberg_blocks();
	$disallowed_blocks = array_keys(
		array_filter(
			$registered_blocks,
			static function ( $block_type, $block_name ) use ( $disallowed_gutenberg ) {
				$original_categories    = isset( $GLOBALS['lc_js_skeleton_original_block_categories'] ) ? $GLOBALS['lc_js_skeleton_original_block_categories'] : array();
				$original_category      = isset( $original_categories[ $block_name ] ) ? $original_categories[ $block_name ] : ( isset( $block_type->category ) ? $block_type->category : '' );
				$is_disallowed_category = in_array( $original_category, $disallowed_gutenberg['categories'], true );
				$is_widget_block        = 'widgets' === $original_category && ! in_array( $block_name, $disallowed_gutenberg['widget_exceptions'], true );
				$is_listed_block        = in_array( $block_name, $disallowed_gutenberg['blocks'], true );
				$has_disallowed_prefix  = false;

				foreach ( $disallowed_gutenberg['prefixes'] as $prefix ) {
					$normalized_prefix = rtrim( $prefix, '*' );

					if ( 0 === strpos( $block_name, $normalized_prefix ) ) {
						$has_disallowed_prefix = true;
						break;
					}
				}

				return $is_disallowed_category
					|| $is_widget_block
					|| $is_listed_block
					|| $has_disallowed_prefix;
			}
			,
			ARRAY_FILTER_USE_BOTH
		)
	);

	if ( true === $allowed_block_types ) {
		return array_values( array_diff( array_keys( $registered_blocks ), $disallowed_blocks ) );
	}

	if ( is_array( $allowed_block_types ) ) {
		return array_values( array_diff( $allowed_block_types, $disallowed_blocks ) );
	}

	return $allowed_block_types;
}
add_filter( 'allowed_block_types_all', 'lc_js_skeleton_disallow_unwanted_core_blocks' );

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
