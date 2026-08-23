</main>

<footer id="footer">
	<div class="container">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'footer',
				'menu_class'     => 'navbar-nav',
				'container'      => false,
				'fallback_cb'    => false,
			)
		);
		?>
	</div>
	<div id="colophon">
		<div class="container">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
