import { __ } from '@wordpress/i18n';
import { registerFormatType, toggleFormat } from '@wordpress/rich-text';
import { RichTextToolbarButton } from '@wordpress/block-editor';

/**
 * "Lede" inline format — a toolbar button in the RichText selection
 * popover (same place Bold/Italic/Link live), not a block attribute. A
 * block-level control can only size a whole RichText field, never one
 * paragraph within it — so "first paragraph bigger" needs per-selection
 * granularity, which is exactly what a format expresses.
 *
 * Applies the theme's own .text-body-l-medium class to the selection, so
 * it renders identically in the editor (theme.min.css loads there) and on
 * the frontend with no extra CSS anywhere.
 *
 * Registered once, globally, via enqueue_block_editor_assets (see
 * inc/editor.php) rather than per-block. Not auto-registered by
 * inc/blocks.php's blocks/*block.json glob (this folder has no
 * block.json — it's a format, not a block).
 */
const FORMAT_NAME = 'lc-js-skeleton2026/lede';

registerFormatType( FORMAT_NAME, {
	title: __( 'Lede', 'lc-js-skeleton2026' ),
	tagName: 'span',
	className: 'text-body-l-medium',
	edit( { isActive, value, onChange } ) {
		return (
			<RichTextToolbarButton
				icon="editor-textcolor"
				title={ __( 'Lede', 'lc-js-skeleton2026' ) }
				onClick={ () => onChange( toggleFormat( value, { type: FORMAT_NAME } ) ) }
				isActive={ isActive }
			/>
		);
	},
} );
