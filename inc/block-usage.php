<?php
/**
 * Block usage shortcode for debugging/QA — [block_usage_table].
 *
 * Lists every block file in /blocks against the published pages/posts that
 * actually use it, so you can tell at a glance whether a block is safe to
 * remove with rm_block.sh.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders a table of all blocks and the pages/posts that use them.
 *
 * @return string HTML table of block usage.
 */
function lc_js_skeleton_block_usage_table_shortcode() {
	$blocks_dir  = get_stylesheet_directory() . '/blocks/';
	$block_files = glob( $blocks_dir . '*/block.json' );

	if ( ! $block_files ) {
		return '<p>No blocks found.</p>';
	}

	// Block names are the directory name under blocks/ — matches the
	// "name" registered from each block.json (theme-namespace/{slug}).
	$block_names = array();
	foreach ( $block_files as $file ) {
		$block_names[] = basename( dirname( $file ) );
	}

	$posts = get_posts(
		array(
			'post_type'      => array( 'page', 'post' ),
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		)
	);

	$usage_map  = array_fill_keys( $block_names, array() );
	$theme_slug = wp_get_theme()->get( 'TextDomain' );

	foreach ( $posts as $post ) {
		preg_match_all( '/<!-- wp:' . preg_quote( $theme_slug, '/' ) . '\/([a-z0-9-]+)\s/', $post->post_content, $matches );

		if ( empty( $matches[1] ) ) {
			continue;
		}

		foreach ( array_unique( $matches[1] ) as $found_block ) {
			if ( isset( $usage_map[ $found_block ] ) ) {
				$usage_map[ $found_block ][] = $post;
			}
		}
	}

	// Inline-styled on purpose — this is a standalone QA utility that should
	// look reasonable on any project regardless of whether it has opted
	// into src/css/tables.css.
	ob_start();
	?>
	<div style="padding: 2rem;">
	<table style="width: 100%; border-collapse: collapse;">
		<thead>
			<tr style="border-bottom: 2px solid #ccc;">
				<th style="text-align: left; padding: 8px; font-weight: bold;">Block Name</th>
				<th style="text-align: left; padding: 8px; font-weight: bold;">Used In</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $usage_map as $block_name => $posts_using_block ) : ?>
				<tr style="border-bottom: 1px solid #eee;">
					<td style="padding: 8px; vertical-align: top;"><?php echo esc_html( $block_name ); ?></td>
					<td style="padding: 8px;">
						<?php if ( empty( $posts_using_block ) ) : ?>
							<em style="color: #999;">Not used</em>
						<?php else : ?>
							<ul style="margin: 0; padding-left: 20px;">
								<?php foreach ( $posts_using_block as $post ) : ?>
									<li>
										<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>" target="_blank">
											<?php echo esc_html( $post->post_title ); ?>
										</a>
										<span style="color: #999; font-size: 0.9em;">(<?php echo esc_html( ucfirst( $post->post_type ) ); ?>)</span>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'block_usage_table', 'lc_js_skeleton_block_usage_table_shortcode' );
