<?php
/**
 * Standing head-output requirements for every project on this theme: font
 * preloads, GA/GTM (logged-out visitors only, so the team's own traffic
 * doesn't skew analytics), and search-engine verification meta tags.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Preload every font file in /fonts. Add files there and they're picked up
 * automatically on the next request — no registration step, same pattern as
 * the block CSS glob.
 *
 * @return void
 */
function lc_js_skeleton_preload_fonts() {
	$fonts_dir = get_stylesheet_directory() . '/fonts';
	$fonts_url = get_stylesheet_directory_uri() . '/fonts';

	foreach ( glob( $fonts_dir . '/*.woff2' ) as $font_path ) {
		printf(
			'<link rel="preload" href="%s/%s" as="font" type="font/woff2" crossorigin>' . "\n",
			esc_url( $fonts_url ),
			esc_attr( basename( $font_path ) )
		);
	}
}
add_action( 'wp_head', 'lc_js_skeleton_preload_fonts', 1 );

/**
 * GA/GTM tags and search-engine verification meta — all read from the
 * Site-Wide Settings options page. GA/GTM only fire for logged-out
 * visitors.
 *
 * @return void
 */
function lc_js_skeleton_head_tags() {
	if ( ! is_user_logged_in() ) {
		$ga_property = lc_js_skeleton_get_setting( 'ga_property' );
		if ( $ga_property ) {
			?>
			<!-- Google Analytics -->
			<script async src="https://www.googletagmanager.com/gtag/js?id=<?= esc_attr( $ga_property ); // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- third-party vendor snippet with a dynamic id, not a local file to enqueue; matches Google's own integration instructions ?>"></script>
			<script>
				window.dataLayer = window.dataLayer || [];
				function gtag(){ dataLayer.push(arguments); }
				gtag('js', new Date());
				gtag('config', '<?= esc_js( $ga_property ); ?>');
			</script>
			<?php
		}

		$gtm_property = lc_js_skeleton_get_setting( 'gtm_property' );
		if ( $gtm_property ) {
			?>
			<!-- Google Tag Manager -->
			<script>
				(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?= esc_js( $gtm_property ); ?>');
			</script>
			<!-- End Google Tag Manager -->
			<?php
		}
	}

	$google_verification = lc_js_skeleton_get_setting( 'google_site_verification' );
	if ( $google_verification ) {
		printf( '<meta name="google-site-verification" content="%s" />' . "\n", esc_attr( $google_verification ) );
	}

	$bing_verification = lc_js_skeleton_get_setting( 'bing_site_verification' );
	if ( $bing_verification ) {
		printf( '<meta name="msvalidate.01" content="%s" />' . "\n", esc_attr( $bing_verification ) );
	}
}
add_action( 'wp_head', 'lc_js_skeleton_head_tags', 1 );

/**
 * GTM noscript fallback — placed right after <body> opens via wp_body_open,
 * which is exactly where Google's own documentation says it belongs (not
 * buried in the footer).
 *
 * @return void
 */
function lc_js_skeleton_gtm_noscript() {
	if ( is_user_logged_in() ) {
		return;
	}

	$gtm_property = lc_js_skeleton_get_setting( 'gtm_property' );
	if ( ! $gtm_property ) {
		return;
	}
	?>
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= esc_attr( $gtm_property ); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
	<?php
}
add_action( 'wp_body_open', 'lc_js_skeleton_gtm_noscript' );

/**
 * Print one of the three raw script slots from the Site-Wide Settings
 * "Scripts" tab.
 *
 * Deliberately unescaped: the whole point of these fields is to paste a
 * vendor's snippet verbatim — <script> tags, inline JS, <noscript> pixels —
 * and have it reach the page unchanged. Escaping or running it through
 * wp_kses() would defeat the feature entirely. The settings page is the trust
 * boundary here, not this function.
 *
 * @param string $key Setting key: custom_head, custom_body_open, custom_body_close.
 * @return void
 */
function lc_js_skeleton_print_custom_scripts( $key ) {
	// Default-on, matching GA/GTM — see the checkbox renderer for why '0' and
	// not '' is the unticked value.
	if ( lc_js_skeleton_get_setting( 'custom_scripts_logged_out_only', '1' ) && is_user_logged_in() ) {
		return;
	}

	$markup = lc_js_skeleton_get_setting( $key );
	if ( ! $markup ) {
		return;
	}

	printf( "\n<!-- %s -->\n%s\n", esc_html( $key ), $markup ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- pasted third-party snippets, printed verbatim by design; see docblock.
}

/**
 * Wire each script slot to its hook. The head and footer slots run late
 * (priority 99) so anything the theme manages itself — GA/GTM, verification
 * metas, font preloads — is already on the page first, and a pasted snippet
 * that depends on dataLayer existing finds it there.
 *
 * @return void
 */
function lc_js_skeleton_custom_head_scripts() {
	lc_js_skeleton_print_custom_scripts( 'custom_head' );
}
add_action( 'wp_head', 'lc_js_skeleton_custom_head_scripts', 99 );

/**
 * Body-open slot — runs after the GTM noscript fallback above (default
 * priority 10 on the same hook, registered earlier).
 *
 * @return void
 */
function lc_js_skeleton_custom_body_open_scripts() {
	lc_js_skeleton_print_custom_scripts( 'custom_body_open' );
}
add_action( 'wp_body_open', 'lc_js_skeleton_custom_body_open_scripts', 20 );

/**
 * Body-close slot.
 *
 * @return void
 */
function lc_js_skeleton_custom_body_close_scripts() {
	lc_js_skeleton_print_custom_scripts( 'custom_body_close' );
}
add_action( 'wp_footer', 'lc_js_skeleton_custom_body_close_scripts', 99 );
