<?php
/**
 * Single post template.
 *
 * @package lc-js-skeleton2026
 */

get_header();
?>

<div class="container">
	<?php
	while ( have_posts() ) {
		the_post();
		?>
		<article <?php post_class(); ?>>
			<h1><?php the_title(); ?></h1>
			<p class="visually-hidden"><?php echo esc_html( get_the_date() ); ?></p>
			<?php the_content(); ?>
		</article>
		<?php
	}
	?>
</div>

<?php
get_footer();
