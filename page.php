<?php
/**
 * Page template.
 *
 * Most projects build page layouts from native blocks rather than the_content()
 * directly — override this per project as needed.
 *
 * @package lc-js-skeleton2026
 */

get_header();
?>

<div class="container">
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</div>

<?php
get_footer();
