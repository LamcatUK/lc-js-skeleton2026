<?php
/**
 * Fixed site hardening.
 *
 * Comments, gravatars, tags and emojis are always off here (no options
 * UI), alongside security response headers, head cleanup, XML-RPC
 * lockdown, generic login errors and author/REST user-enumeration blocks.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Disable comments everywhere: support removed from all post types,
 * existing threads closed and hidden, admin UI (menu, admin bar, list
 * columns, discussion settings) removed or redirected away from.
 *
 * @return void
 */
function lc_js_skeleton_disable_comments() {
	add_action( 'init', 'lc_js_skeleton_disable_comments_post_type_support' );

	add_filter( 'comments_open', '__return_false', 20, 2 );
	add_filter( 'pings_open', '__return_false', 20, 2 );
	add_filter( 'comments_array', '__return_empty_array', 10, 2 );

	add_action( 'admin_menu', 'lc_js_skeleton_remove_comments_menu' );
	add_action( 'admin_init', 'lc_js_skeleton_redirect_comment_pages' );
	add_action( 'admin_bar_menu', 'lc_js_skeleton_remove_comments_admin_bar', 999 );
	add_action( 'admin_init', 'lc_js_skeleton_hide_discussion_settings' );
	add_action( 'admin_menu', 'lc_js_skeleton_remove_discussion_menu' );

	add_filter( 'manage_posts_columns', 'lc_js_skeleton_remove_comments_column' );
	add_filter( 'manage_pages_columns', 'lc_js_skeleton_remove_comments_column' );
}
lc_js_skeleton_disable_comments();

/**
 * Strip comments/trackbacks support from every post type.
 *
 * @return void
 */
function lc_js_skeleton_disable_comments_post_type_support() {
	foreach ( get_post_types() as $post_type ) {
		if ( post_type_supports( $post_type, 'comments' ) ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}
}

/**
 * Drop the comments column from the posts/pages list tables.
 *
 * @param array $columns List-table columns.
 * @return array
 */
function lc_js_skeleton_remove_comments_column( $columns ) {
	unset( $columns['comments'] );
	return $columns;
}

/**
 * Remove the comments admin menu page.
 *
 * @return void
 */
function lc_js_skeleton_remove_comments_menu() {
	remove_menu_page( 'edit-comments.php' );
}

/**
 * Remove the Discussion submenu from Settings.
 *
 * @return void
 */
function lc_js_skeleton_remove_discussion_menu() {
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
}

/**
 * Redirect the comments list screen to the dashboard.
 *
 * @return void
 */
function lc_js_skeleton_redirect_comment_pages() {
	global $pagenow;

	if ( 'edit-comments.php' === $pagenow ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}

/**
 * Remove comments from the admin bar.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar instance.
 * @return void
 */
function lc_js_skeleton_remove_comments_admin_bar( $wp_admin_bar ) {
	$wp_admin_bar->remove_node( 'comments' );
}

/**
 * Hide the discussion settings screen behind a redirect (belt and braces
 * alongside the removed submenu above).
 *
 * @return void
 */
function lc_js_skeleton_hide_discussion_settings() {
	add_action( 'admin_head', 'lc_js_skeleton_hide_discussion_settings_css' );
}

/**
 * Blank the discussion settings screen and bounce to the dashboard.
 *
 * @return void
 */
function lc_js_skeleton_hide_discussion_settings_css() {
	global $pagenow;

	if ( 'options-discussion.php' === $pagenow ) {
		echo '<style>body { display: none; }</style>';
		echo '<script>window.location.href = "' . esc_url( admin_url() ) . '";</script>';
	}
}

/**
 * Disable gravatars: option forced off, profile copy blanked, every
 * avatar request answered with an empty string.
 *
 * @return void
 */
function lc_js_skeleton_disable_gravatars() {
	add_filter( 'pre_option_show_avatars', '__return_zero' );
	add_filter( 'user_profile_picture_description', '__return_empty_string' );
	add_filter( 'get_avatar', 'lc_js_skeleton_disable_gravatar', 10, 5 );
}
lc_js_skeleton_disable_gravatars();

/**
 * Answer every avatar request with nothing.
 *
 * Params are required by the filter signature but unused — the point is
 * to return an empty string unconditionally.
 *
 * @param string $avatar         Avatar HTML.
 * @param mixed  $id_or_email    User ID or email.
 * @param int    $size           Avatar size.
 * @param string $default_avatar Default avatar URL.
 * @param string $alt            Alt text.
 * @return string
 */
function lc_js_skeleton_disable_gravatar( $avatar, $id_or_email, $size, $default_avatar, $alt ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	return '';
}

/**
 * Disable tags: unregistered from posts, UI hidden, submenu and editor
 * metabox removed.
 *
 * @return void
 */
function lc_js_skeleton_disable_tags() {
	add_action( 'init', 'lc_js_skeleton_unregister_tags', 999 );
	add_action( 'admin_menu', 'lc_js_skeleton_remove_tags_menu' );
	add_action( 'add_meta_boxes', 'lc_js_skeleton_remove_tags_metabox', 999 );
}
lc_js_skeleton_disable_tags();

/**
 * Detach post_tag from posts and hide every scrap of its UI, without
 * deleting the taxonomy itself.
 *
 * @return void
 */
function lc_js_skeleton_unregister_tags() {
	unregister_taxonomy_for_object_type( 'post_tag', 'post' );

	global $wp_taxonomies;
	if ( isset( $wp_taxonomies['post_tag'] ) ) {
		$wp_taxonomies['post_tag']->show_ui            = false;
		$wp_taxonomies['post_tag']->show_in_menu       = false;
		$wp_taxonomies['post_tag']->show_in_nav_menus  = false;
		$wp_taxonomies['post_tag']->show_tagcloud      = false;
		$wp_taxonomies['post_tag']->show_in_quick_edit = false;
		$wp_taxonomies['post_tag']->show_admin_column  = false;
	}
}

/**
 * Remove the Tags submenu from Posts.
 *
 * @return void
 */
function lc_js_skeleton_remove_tags_menu() {
	remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=post_tag' );
}

/**
 * Remove the Tags metabox from the post editor.
 *
 * @return void
 */
function lc_js_skeleton_remove_tags_metabox() {
	remove_meta_box( 'tagsdiv-post_tag', 'post', 'side' );
}

/**
 * Disable emojis: detection scripts/styles gone from frontend and admin,
 * feed/mail staticization off, TinyMCE plugin and CDN URL removed.
 * Runs at file load — everything it touches fires later, so no hook
 * needed (the plugin's plugins_loaded@1 + init@1 double wiring
 * collapses to this single call).
 *
 * @return void
 */
function lc_js_skeleton_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'lc_js_skeleton_disable_emojis_tinymce' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
lc_js_skeleton_disable_emojis();

/**
 * Strip the emoji plugin from TinyMCE.
 *
 * @param array $plugins TinyMCE plugins.
 * @return array
 */
function lc_js_skeleton_disable_emojis_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	}
	return $plugins;
}

/**
 * Suppress noisy core dashboard widgets: WordPress Events and News,
 * At a Glance and Quick Draft. Same remove_meta_box approach the old
 * blog-options plugin used — only these three, nothing else.
 *
 * @return void
 */
function lc_js_skeleton_suppress_dashboard_widgets() {
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
}
add_action( 'wp_dashboard_setup', 'lc_js_skeleton_suppress_dashboard_widgets' );

/**
 * Send fixed security response headers on every response.
 *
 * HSTS only over actual HTTPS (over plain HTTP it would be a no-op at
 * best); no `preload` token, matching the plugin's default-off — only
 * submit to hstspreload.org once every subdomain is confirmed HTTPS-only,
 * as removal takes months to propagate. COOP uses
 * same-origin-allow-popups (not the stricter same-origin) so popup-based
 * flows like OAuth keep working; XFO SAMEORIGIN still allows the site to
 * frame itself.
 *
 * @return void
 */
function lc_js_skeleton_send_security_headers() {
	if ( headers_sent() ) {
		return;
	}

	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}

	header( 'Cross-Origin-Opener-Policy: same-origin-allow-popups' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
}
add_action( 'send_headers', 'lc_js_skeleton_send_security_headers' );

/**
 * Strip version and discovery tags from wp_head: generator, RSD, WLW
 * manifest, shortlink and pingback link. Runs at file load — everything
 * it touches fires later, so no hook needed.
 *
 * @return void
 */
function lc_js_skeleton_head_cleanup() {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'pingback_link' );
	add_filter( 'wp_headers', 'lc_js_skeleton_remove_pingback_header' );
}
lc_js_skeleton_head_cleanup();

/**
 * Drop the X-Pingback response header (pairs with the pingback_link
 * removal above).
 *
 * @param array $headers Response headers.
 * @return array
 */
function lc_js_skeleton_remove_pingback_header( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
}

/**
 * Disable XML-RPC: closes the pingback/trackback abuse vector. Comments
 * and pings are already forced off above, so nothing legitimate needs it.
 *
 * @return void
 */
function lc_js_skeleton_disable_xmlrpc() {
	add_filter( 'xmlrpc_enabled', '__return_false' );
}
lc_js_skeleton_disable_xmlrpc();

/**
 * Return a generic login error so failed logins never reveal whether
 * the username or the password was wrong.
 *
 * @return string
 */
function lc_js_skeleton_generic_login_errors() {
	return __( 'Invalid username or password.', 'lc-js-skeleton2026' );
}
add_filter( 'login_errors', 'lc_js_skeleton_generic_login_errors' );

/**
 * Block ?author=N user enumeration scans: author archives and plain
 * author query vars redirect home instead of revealing usernames.
 *
 * @return void
 */
function lc_js_skeleton_block_author_enumeration() {
	if ( is_author() || isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- value never used, presence alone triggers the redirect.
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'lc_js_skeleton_block_author_enumeration' );

/**
 * Hide user routes (/wp/v2/users*) from the REST index for logged-out
 * visitors so usernames cannot be harvested in bulk. Logged-in requests
 * are untouched.
 *
 * @param array $endpoints Registered REST routes.
 * @return array
 */
function lc_js_skeleton_restrict_rest_users( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}

	foreach ( array_keys( $endpoints ) as $route ) {
		if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
			unset( $endpoints[ $route ] );
		}
	}

	return $endpoints;
}
add_filter( 'rest_endpoints', 'lc_js_skeleton_restrict_rest_users' );
