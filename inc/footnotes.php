<?php
/**
 * Footnotes — [Footnote]...[/Footnote] tags become numbered, linked
 * footnotes with a single running counter per page request.
 *
 * Same tag and shortcode names throughout ([Footnote] tags,
 * [lcp_footnotes] list shortcode), same single-counter semantics, same
 * main-loop-only guard. The `lcp_footnotes_*` filter/helper names are
 * kept as-is so existing overrides keep firing.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'LC_JS_Skeleton_Footnotes' ) ) {

	/**
	 * Collects [Footnote] tags into a numbered list for the page request.
	 */
	class LC_JS_Skeleton_Footnotes {

		/**
		 * Footnotes collected so far during the current page request.
		 *
		 * @var stdClass[]
		 */
		private $footnotes = array();

		/**
		 * Running counter, shared across the whole page request.
		 *
		 * @var int
		 */
		private $index = 1;

		/**
		 * Whether the footnote list has already been output.
		 *
		 * @var bool
		 */
		private $rendered = false;

		/**
		 * Wire up hooks.
		 *
		 * Runs before WordPress's default `do_shortcode` (priority 11) so
		 * that a [lcp_footnotes] shortcode placed in the same content as
		 * [Footnote] tags sees the fully-populated footnote list when it
		 * renders.
		 */
		public function __construct() {
			add_filter( 'the_content', array( $this, 'process' ), 8 );
			add_shortcode( 'lcp_footnotes', array( $this, 'shortcode_render' ) );
		}

		/**
		 * Extract [Footnote] tags from a string, replacing each with a link,
		 * then append the rendered footnote list directly to the end of that
		 * same content — unless the content already places the list manually
		 * via the [lcp_footnotes] shortcode.
		 *
		 * Public so blocks can run it over content that doesn't pass through
		 * the `the_content` filter — see lc_js_skeleton_footnotes_process().
		 *
		 * @param string $content Content string, possibly containing [Footnote] tags.
		 * @return string Content with tags replaced by footnote links.
		 */
		public function process( $content ) {
			if ( false === stripos( $content, '[footnote]' ) ) {
				return $content;
			}

			// Only the main loop feeds the single running counter. Anything
			// else that runs content through the_content (SEO/schema/TOC
			// pre-passes outside the loop, related-post loops) must not
			// consume indices or trigger the list: otherwise numbering
			// restarts mid-page and the list lands in discarded output —
			// i.e. visible footnotes starting at [4] with no list in the DOM.
			if ( ! in_the_loop() || ! is_main_query() ) {
				return $content;
			}

			$has_shortcode = has_shortcode( $content, 'lcp_footnotes' );

			$content = preg_replace_callback(
				'/\[Footnote\](.*?)\[\/Footnote\]/is',
				array( $this, 'replace_callback' ),
				$content
			);

			if ( ! $has_shortcode && $this->has_footnotes() && ! $this->rendered ) {
				$this->rendered = true;
				$content       .= $this->render_html();
			}

			return $content;
		}

		/**
		 * Regex callback: stores the footnote and returns its link markup.
		 *
		 * @param array $matches Regex matches; $matches[1] is the footnote text.
		 * @return string HTML link to the stored footnote.
		 */
		private function replace_callback( $matches ) {
			$footnote          = new stdClass();
			$footnote->index   = $this->index++;
			$footnote->content = $matches[1];

			$this->footnotes[] = $footnote;

			return $this->link_to_footnote( $footnote );
		}

		/**
		 * Build the bracketed link markup pointing at a footnote's list entry.
		 *
		 * @param stdClass $footnote Footnote object.
		 * @return string HTML link.
		 */
		private function link_to_footnote( $footnote ) {
			return sprintf(
				'<a href="#footnote-%1$d" id="footnote-ref-%1$d" class="footnote-link"><sup>[%1$d]</sup></a>',
				(int) $footnote->index
			);
		}

		/**
		 * Whether any footnotes have been collected so far this request.
		 *
		 * @return bool
		 */
		public function has_footnotes() {
			return ! empty( $this->footnotes );
		}

		/**
		 * Shortcode handler: [lcp_footnotes] — renders the footnote list at
		 * the point it's placed instead of the default end-of-content
		 * position, and marks it as rendered so it isn't output twice.
		 *
		 * @return string Footnotes list HTML, or empty string if none collected
		 *                yet, or if the list was already rendered elsewhere.
		 */
		public function shortcode_render() {
			if ( $this->rendered || ! $this->has_footnotes() ) {
				return '';
			}

			$this->rendered = true;
			return $this->render_html();
		}

		/**
		 * Build the footnote list markup, wrapped in a container div.
		 *
		 * The wrapper defaults to plain `lcp-footnotes-box` — no
		 * width-constraint class: the list inherits its width from wherever
		 * the content renders (e.g. single.php's article column), and a
		 * nested `.container` would trip the theme's nested-container
		 * padding reset. Swap a class in via the `lcp_footnotes_wrapper_class`
		 * filter (space-separated) instead of overriding the CSS.
		 *
		 * @return string Footnote list HTML.
		 */
		private function render_html() {
			$wrapper_class = apply_filters( 'lcp_footnotes_wrapper_class', 'lcp-footnotes-box' );

			ob_start();
			?>
			<div class="<?php echo esc_attr( $wrapper_class ); ?>">
				<ol class="lcp-footnotes-list">
				<?php foreach ( $this->footnotes as $footnote ) { ?>
					<li id="footnote-<?php echo esc_attr( $footnote->index ); ?>">
						<?php echo wp_kses_post( $footnote->content ); ?>
						<a href="#footnote-ref-<?php echo esc_attr( $footnote->index ); ?>" class="lcp-footnote-backlink" aria-label="<?php esc_attr_e( 'Back to content', 'lc-js-skeleton2026' ); ?>">&#8617;</a>
					</li>
				<?php } ?>
				</ol>
			</div>
			<?php
			return ob_get_clean();
		}
	}
}

// The plugin owns everything while it's still active (same behaviour,
 // zero drift) — the theme takes over automatically once it's deleted.
// A bare function_exists() guard on the alias alone wouldn't be enough:
// both copies would still hook the_content and fight over the
// [lcp_footnotes] shortcode in the meantime.
if ( ! class_exists( 'CBFootnotes' ) && class_exists( 'LC_JS_Skeleton_Footnotes' ) && ! isset( $GLOBALS['lc_js_skeleton_footnotes_instance'] ) ) {
	$GLOBALS['lc_js_skeleton_footnotes_instance'] = new LC_JS_Skeleton_Footnotes();
}

/**
 * Process arbitrary content strings (e.g. block attributes) that don't
 * pass through the `the_content` filter.
 *
 * @param string $content Content string, possibly containing [Footnote] tags.
 * @return string Content with tags replaced by footnote links.
 */
function lc_js_skeleton_footnotes_process( $content ) {
	if ( ! isset( $GLOBALS['lc_js_skeleton_footnotes_instance'] ) ) {
		return $content;
	}
	return $GLOBALS['lc_js_skeleton_footnotes_instance']->process( $content );
}

/**
 * Legacy alias — same helper under the plugin's old name, so anything
 * calling lcp_footnotes_process() keeps working after the plugin is gone.
 *
 * @param string $content Content string, possibly containing [Footnote] tags.
 * @return string Content with tags replaced by footnote links.
 */
if ( ! function_exists( 'lcp_footnotes_process' ) ) {
	function lcp_footnotes_process( $content ) {
		return lc_js_skeleton_footnotes_process( $content );
	}
}
