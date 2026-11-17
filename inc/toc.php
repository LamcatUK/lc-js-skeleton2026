<?php
/**
 * Table of contents for single posts.
 *
 * [lc_toc] shortcode listing every h2 in the post, linked to it. Heading IDs are injected into the
 * rendered content by filter — never written to the database, and derived
 * from the same parse as the list itself so the two can never disagree.
 *
 * Both halves read markup without running it through the_content (the ToC
 * expands blocks via do_blocks only; the injector works on the filtered
 * string it is given), so this never trips the footnote counter or recurses.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Collect h2s from an HTML string, in document order.
 *
 * Skipped headings (empty text) are omitted everywhere, so the returned
 * list doubles as the injector's source of truth. Manual ids are kept and
 * join the dedup pool; generated ids come from sanitize_title_with_dashes
 * with -2, -3 suffixes on collision.
 *
 * @param string $html Markup to scan.
 * @return array[] Each: match (nth h2 overall), id, text, auto (bool).
 */
function lc_js_skeleton_collect_h2s( $html ) {
	$entries = array();

	if ( ! preg_match_all( '/<h2\b([^>]*)>(.*?)<\/h2>/is', $html, $matches, PREG_SET_ORDER ) ) {
		return $entries;
	}

	$used_ids = array();
	foreach ( $matches as $match ) {
		if ( preg_match( '/\sid\s*=\s*(["\'])(.*?)\1/i', $match[1], $id_match ) ) {
			$used_ids[] = html_entity_decode( $id_match[2], ENT_QUOTES, 'UTF-8' );
		}
	}

	$auto_n = 0;
	foreach ( $matches as $k => $match ) {
		$text = trim( wp_strip_all_tags( $match[2] ) );

		if ( '' === $text ) {
			continue;
		}

		$display = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );

		if ( preg_match( '/\sid\s*=\s*(["\'])(.*?)\1/i', $match[1], $id_match ) ) {
			$entries[] = array(
				'match' => $k,
				'id'    => html_entity_decode( $id_match[2], ENT_QUOTES, 'UTF-8' ),
				'text'  => $display,
				'auto'  => false,
			);
			continue;
		}

		$base = sanitize_title_with_dashes( $display );

		if ( '' === $base ) {
			++$auto_n;
			$base = 'section-' . $auto_n;
		}

		$slug   = $base;
		$suffix = 2;
		while ( in_array( $slug, $used_ids, true ) ) {
			$slug = $base . '-' . $suffix;
			++$suffix;
		}
		$used_ids[] = $slug;

		$entries[] = array(
			'match' => $k,
			'id'    => $slug,
			'text'  => $display,
			'auto'  => true,
		);
	}

	return $entries;
}

/**
 * Add missing ids to h2s in rendered content, from the same parse the ToC
 * uses — every auto id the list links to is guaranteed present.
 *
 * @param string $content Filtered post content.
 * @return string Content with h2 ids filled in.
 */
function lc_js_skeleton_auto_heading_ids( $content ) {
	if ( false === stripos( $content, '<h2' ) ) {
		return $content;
	}

	$by_match = array();
	foreach ( lc_js_skeleton_collect_h2s( $content ) as $entry ) {
		if ( $entry['auto'] ) {
			$by_match[ $entry['match'] ] = $entry['id'];
		}
	}

	if ( ! $by_match ) {
		return $content;
	}

	$k = -1;

	return preg_replace_callback(
		'/<h2\b([^>]*)>(.*?)<\/h2>/is',
		static function ( $match ) use ( &$k, $by_match ) {
			++$k;

			if ( ! isset( $by_match[ $k ] ) ) {
				return $match[0];
			}

			return '<h2 id="' . esc_attr( $by_match[ $k ] ) . '"' . $match[1] . '>' . $match[2] . '</h2>';
		},
		$content
	);
}
add_filter( 'the_content', 'lc_js_skeleton_auto_heading_ids', 20 );

/**
 * Render the table of contents for a post's h2s. Empty string when none.
 *
 * Parses the block-rendered content (do_blocks), not the raw post_content,
 * so headings emitted by dynamic blocks (e.g. FAQ section titles) are
 * included. Shortcodes are not expanded by do_blocks, so an in-content
 * [lc_toc] can neither recurse nor pollute the list — and no the_content
 * filters run here, keeping the footnote counter untouched.
 *
 * @param int|WP_Post|null $post Post to index. Defaults to the loop post.
 * @return string ToC nav HTML (already escaped).
 */
function lc_js_skeleton_toc( $post = null ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	$items = '';
	foreach ( lc_js_skeleton_collect_h2s( do_blocks( $post->post_content ) ) as $entry ) {
		$items .= '<li class="lc-toc__item"><a class="lc-toc__link" href="#' . esc_attr( $entry['id'] ) . '">' . esc_html( $entry['text'] ) . '</a></li>';
	}

	if ( '' === $items ) {
		return '';
	}

	return '<nav class="lc-toc" aria-label="' . esc_attr__( 'Table of contents', 'lc-js-skeleton2026' ) . '"><p class="lc-toc__title text-label">' . esc_html__( 'Contents', 'lc-js-skeleton2026' ) . '</p><ol class="lc-toc__list">' . $items . '</ol></nav>';
}
add_shortcode( 'lc_toc', 'lc_js_skeleton_toc' );
