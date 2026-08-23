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
