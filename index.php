<?php
/**
 * Fallback template.
 *
 * @package lc-js-skeleton2026
 */

get_header();
?>

<div class="container">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			?>
			<article <?php post_class(); ?>>
				<h1><?php the_title(); ?></h1>
				<?php the_content(); ?>
			</article>
			<?php
		}
	} else {
		?>
		<p><?php esc_html_e( 'Nothing found.', 'lc-js-skeleton2026' ); ?></p>
		<?php
	}
	?>
</div>

<?php
get_footer();
