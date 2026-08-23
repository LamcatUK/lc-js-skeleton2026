<?php
/**
 * [social_icons] shortcode — one <a> + get_icon() per platform that has a
 * URL filled in on Site-Wide Settings' Social tab (inc/options.php's
 * options page). Add another platform by adding a matching
 * img/icons/{slug}.svg, a {slug}_url field there, and an entry in
 * $platforms below.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

add_shortcode(
	'social_icons',
	function ( $atts ) {
		$atts = shortcode_atts( array( 'class' => '' ), $atts, 'social_icons' );

		$platforms = array(
			'facebook'  => 'Facebook',
			'instagram' => 'Instagram',
		);

		$links = '';
		foreach ( $platforms as $slug => $label ) {
			$url = lc_js_skeleton_get_setting( $slug . '_url' );
			if ( ! $url ) {
				continue;
			}
			$links .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="nofollow noopener noreferrer" aria-label="' . esc_attr( $label ) . '">' . get_icon( $slug ) . '</a>';
		}

		if ( ! $links ) {
			return '';
		}

		$classes = trim( 'social-icons ' . sanitize_html_class( $atts['class'] ) );

		return '<div class="' . esc_attr( $classes ) . '">' . $links . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $links built from esc_url()/esc_attr() above plus get_icon()'s already-sanitised theme SVGs.
	}
);
