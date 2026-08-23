<?php
/**
 * Site-Wide Settings page — plain Settings API, one array option
 * (lc_js_skeleton_site_settings). Replaces the old ACF options page; read
 * values elsewhere in the theme with lc_js_skeleton_get_setting( $key ).
 *
 * The Icons tab (SVG upload straight into img/icons/) from the ACF version
 * of this page is deliberately not ported here — deferred to a future
 * plugin rather than rebuilt as part of dropping ACF.
 *
 * @package lc-js-skeleton2026
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option name for the single serialized settings array.
 *
 * @var string
 */
define( 'LC_JS_SKELETON_SETTINGS_OPTION', 'lc_js_skeleton_site_settings' );

/**
 * Read one Site-Wide Settings value.
 *
 * @param string $key     Setting key, e.g. 'ga_property'.
 * @param string $default Fallback if the key isn't set.
 * @return string
 */
function lc_js_skeleton_get_setting( $key, $default = '' ) {
	$settings = get_option( LC_JS_SKELETON_SETTINGS_OPTION, array() );
	return isset( $settings[ $key ] ) && '' !== $settings[ $key ] ? $settings[ $key ] : $default;
}

/**
 * Register the settings page, section, and fields.
 *
 * @return void
 */
function lc_js_skeleton_register_settings_page() {
	add_menu_page(
		'Site-Wide Settings',
		'Site-Wide Settings',
		'edit_posts',
		'theme-general-settings',
		'lc_js_skeleton_render_settings_page',
		'dashicons-admin-generic',
		80
	);

	register_setting( 'lc_js_skeleton_settings', LC_JS_SKELETON_SETTINGS_OPTION );

	add_settings_section( 'lc_js_skeleton_general', 'General', '__return_false', 'theme-general-settings' );
	add_settings_section( 'lc_js_skeleton_social', 'Social', '__return_false', 'theme-general-settings' );
	add_settings_section( 'lc_js_skeleton_tracking', 'Tracking & Verification', '__return_false', 'theme-general-settings' );

	$fields = array(
		'email'                     => array(
			'label'   => 'Email',
			'type'    => 'email',
			'section' => 'lc_js_skeleton_general',
		),
		'phone'                     => array(
			'label'   => 'Phone',
			'type'    => 'text',
			'section' => 'lc_js_skeleton_general',
		),
		'facebook_url'              => array(
			'label'       => 'Facebook URL',
			'type'        => 'url',
			'section'     => 'lc_js_skeleton_social',
			'placeholder' => 'https://facebook.com/...',
			'description' => 'Leave blank to hide this icon from [social_icons].',
		),
		'instagram_url'             => array(
			'label'       => 'Instagram URL',
			'type'        => 'url',
			'section'     => 'lc_js_skeleton_social',
			'placeholder' => 'https://instagram.com/...',
			'description' => 'Leave blank to hide this icon from [social_icons].',
		),
		'ga_property'               => array(
			'label'       => 'GA Property',
			'type'        => 'text',
			'section'     => 'lc_js_skeleton_tracking',
			'placeholder' => 'G-XXXXXXX',
			'description' => 'Google Analytics measurement ID. Only fires for logged-out visitors.',
		),
		'gtm_property'              => array(
			'label'       => 'GTM Property',
			'type'        => 'text',
			'section'     => 'lc_js_skeleton_tracking',
			'placeholder' => 'GTM-XXXXXXX',
			'description' => 'Google Tag Manager container ID. Only fires for logged-out visitors.',
		),
		'google_site_verification'  => array(
			'label'       => 'Google Site Verification',
			'type'        => 'text',
			'section'     => 'lc_js_skeleton_tracking',
			'description' => 'Content value of the google-site-verification meta tag.',
		),
		'bing_site_verification'    => array(
			'label'       => 'Bing Site Verification',
			'type'        => 'text',
			'section'     => 'lc_js_skeleton_tracking',
			'description' => 'Content value of the msvalidate.01 meta tag.',
		),
	);

	foreach ( $fields as $key => $field ) {
		add_settings_field(
			$key,
			$field['label'],
			'lc_js_skeleton_render_settings_field',
			'theme-general-settings',
			$field['section'],
			array_merge( $field, array( 'key' => $key ) )
		);
	}
}
add_action( 'admin_menu', 'lc_js_skeleton_register_settings_page' );

/**
 * Render a single text/email/url settings field.
 *
 * @param array $args Field args: key, type, placeholder, description.
 * @return void
 */
function lc_js_skeleton_render_settings_field( $args ) {
	$value = lc_js_skeleton_get_setting( $args['key'] );
	?>
	<input
		type="<?php echo esc_attr( $args['type'] ); ?>"
		id="<?php echo esc_attr( $args['key'] ); ?>"
		name="<?php echo esc_attr( LC_JS_SKELETON_SETTINGS_OPTION ); ?>[<?php echo esc_attr( $args['key'] ); ?>]"
		value="<?php echo esc_attr( $value ); ?>"
		placeholder="<?php echo esc_attr( $args['placeholder'] ?? '' ); ?>"
		class="regular-text"
	>
	<?php if ( ! empty( $args['description'] ) ) : ?>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Settings page HTML.
 *
 * @return void
 */
function lc_js_skeleton_render_settings_page() {
	?>
	<div class="wrap">
		<h1>Site-Wide Settings</h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'lc_js_skeleton_settings' );
			do_settings_sections( 'theme-general-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
