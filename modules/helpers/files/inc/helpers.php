<?php
/**
 * Project-specific helpers.
 *
 * Reusable functions that only make sense for this project (unlike
 * inc/utilities.php, which stays project-agnostic). Add to this file rather
 * than scattering one-off functions across templates.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Excerpt derived from the post's own content, ignoring the manual excerpt
 * field entirely.
 *
 * Unlike get_the_excerpt() (which prefers a hand-written excerpt that may be
 * stale, empty, or auto-filled by an SEO plugin), this always reads
 * post_content: block delimiters, shortcodes and tags are stripped, whitespace
 * collapsed, and the remainder trimmed to $length words. Returns '' when the
 * post has no readable text (e.g. an empty placeholder), so callers can hide
 * the excerpt element altogether.
 *
 * @param int|WP_Post|null $post   Post to excerpt. Defaults to the loop post.
 * @param int               $length Words to keep.
 * @return string Plain-text excerpt (unescaped — escape at output).
 */
function lc_js_skeleton_content_excerpt( $post = null, $length = 30 ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	$text = strip_shortcodes( $post->post_content );
	$text = excerpt_remove_blocks( $text );
	$text = wp_strip_all_tags( $text );
	$text = trim( preg_replace( '/\s+/', ' ', $text ) );

	if ( '' === $text ) {
		return '';
	}

	return wp_trim_words( $text, absint( $length ), '…' );
}