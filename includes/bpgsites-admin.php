<?php
/**
 * BP Group Sites Admin class.
 *
 * Handles admin screen functionality.
 *
 * @package BP_Group_Sites
 * @since 0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BP Group Sites Admin class.
 *
 * A class that encapsulates admin functionality.
 *
 * @since 0.1
 */
class BP_Group_Sites_Admin {

	/**
	 * Plugin options array.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $bpgsites_options The plugin options array.
	 */
	public $bpgsites_options = [];

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Get options array, if it exists.
		$this->bpgsites_options = bpgsites_site_option_get( 'bpgsites_options', [] );

	}

	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// If on back end.
		if ( is_admin() ) {

			// Add menu to Network Settings submenu.
			add_action( 'network_admin_menu', [ $this, 'add_admin_menu' ], 30 );

		}

	}

	/**
	 * Actions to perform on plugin activation.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// Kick out if we are re-activating.
		if ( bpgsites_site_option_exists( 'bpgsites_installed', 'false' ) === 'true' ) {
			return;
		}

		// Get defaults.
		$defaults = $this->get_defaults();

		// Default public comment visibility to "off".
		$this->option_set( 'bpgsites_public', $defaults['public'] );

		// Default name changes to "off".
		$this->option_set( 'bpgsites_overrides', $defaults['overrides'] );

		// Default plugin name to "Group Sites".
		$this->option_set( 'bpgsites_overrides_title', $defaults['title'] );

		// Default singular to "Group Site".
		$this->option_set( 'bpgsites_overrides_name', $defaults['name'] );

		// Default plural to "Group Sites".
		$this->option_set( 'bpgsites_overrides_plural', $defaults['plural'] );

		// Default button to "Visit Group Site".
		$this->option_set( 'bpgsites_overrides_button', $defaults['button'] );

		// Default slug to "group-sites".
		$this->option_set( 'bpgsites_overrides_slug', $defaults['slug'] );

		// Default list of group sites to an empty array.
		$this->option_set( 'bpgsites_groupsites', $defaults['groupsites'] );

		// Save options array.
		$this->options_save();

		// Set installed flag.
		bpgsites_site_option_set( 'bpgsites_installed', 'true' );

	}

	/**
	 * Actions to perform on plugin deactivation (NOT deletion).
	 *
	 * @since 0.1
	 */
	public function deactivate() {

		// We'll delete our options in 'uninstall.php'
		// But for testing let's delete them here.
		delete_site_option( 'bpgsites_options' );
		delete_site_option( 'bpgsites_installed' );
		delete_site_option( 'bpgsites_auth_groups' );

	}

	/**
	 * Add an admin page for this plugin.
	 *
	 * @since 0.1
	 */
	public function add_admin_menu() {

		// We must be network admin.
		if ( ! is_super_admin() ) {
			return false;
		}

		// Try and update options.
		$saved = $this->options_update();

		// Always add the admin page to the Settings menu.
		$page = add_submenu_page(
			'settings.php',
			__( 'BP Group Sites', 'bp-group-sites' ),
			__( 'BP Group Sites', 'bp-group-sites' ),
			'manage_options',
			'bpgsites_admin_page',
			[ $this, 'network_admin_form' ]
		);

		/*
		 * Add styles only on our admin page.
		 * @see http://codex.wordpress.org/Function_Reference/wp_enqueue_script#Load_scripts_only_on_plugin_pages
		 */
		add_action( 'admin_print_styles-' . $page, [ $this, 'add_admin_styles' ] );

	}

	/**
	 * Enqueue any styles and scripts needed by our admin page.
	 *
	 * @since 0.1
	 */
	public function add_admin_styles() {

		// Add admin css.
		wp_enqueue_style(
			'bpgsites-admin-style',
			BPGSITES_URL . 'assets/css/bpgsites-admin.css',
			null,
			BPGSITES_VERSION,
			'all' // Media.
		);

	}

	/**
	 * Update options based on content of form.
	 *
	 * @since 0.1
	 */
	public function options_update() {

		// Kick out if the form was not submitted.
		if ( ! isset( $_POST['bpgsites_submit'] ) ) {
			return;
		}

		// Check that we trust the source of the data.
		check_admin_referer( 'bpgsites_admin_action', 'bpgsites_nonce' );

		// Debugging switch for admins and network admins - if set, triggers do_debug() below.
		if ( is_super_admin() && isset( $_POST['bpgsites_debug'] ) ) {
			$settings_debug = absint( $_POST['bpgsites_debug'] );
			$debug = $settings_debug ? 1 : 0;
			if ( $debug ) {
				$this->do_debug();
			}
			return;
		}

		// Init vars.
		$bpgsites_public = 0;
		$bpgsites_overrides = 0;
		$bpgsites_overrides_title = '';
		$bpgsites_overrides_name = '';
		$bpgsites_overrides_plural = '';
		$bpgsites_overrides_button = '';

		// Okay, we're through - get variables.
		extract( $_POST );

		// Get defaults.
		$defaults = $this->get_defaults();

		// Set public comments visibility on/off option.
		$bpgsites_public = absint( $bpgsites_public );
		$this->option_set( 'bpgsites_public', ( $bpgsites_public ? 1 : 0 ) );

		// Set name change on/off option.
		$bpgsites_overrides = absint( $bpgsites_overrides );
		$this->option_set( 'bpgsites_overrides', ( $bpgsites_overrides ? 1 : 0 ) );

		// Get plugin title option.
		$bpgsites_overrides_title = esc_sql( $bpgsites_overrides_title );

		// Revert to default if we didn't get one.
		if ( $bpgsites_overrides_title == '' ) {
			$bpgsites_overrides_title = $defaults['title'];
		}

		// Set title option.
		$this->option_set( 'bpgsites_overrides_title', $bpgsites_overrides_title );

		// Get name option.
		$bpgsites_overrides_name = esc_sql( $bpgsites_overrides_name );

		// Revert to default if we didn't get one.
		if ( $bpgsites_overrides_name == '' ) {
			$bpgsites_overrides_name = $defaults['name'];
		}

		// Set name option.
		$this->option_set( 'bpgsites_overrides_name', $bpgsites_overrides_name );

		// Get plural option.
		$bpgsites_overrides_plural = esc_sql( $bpgsites_overrides_plural );

		// Revert to default if we didn't get one.
		if ( $bpgsites_overrides_plural == '' ) {
			$bpgsites_overrides_plural = $defaults['plural'];
		}

		// Set plural option.
		$this->option_set( 'bpgsites_overrides_plural', $bpgsites_overrides_plural );

		// Get button option.
		$bpgsites_overrides_button = esc_sql( $bpgsites_overrides_button );

		// Revert to default if we didn't get one.
		if ( $bpgsites_overrides_button == '' ) {
			$bpgsites_overrides_button = $defaults['button'];
		}

		// Set button option.
		$this->option_set( 'bpgsites_overrides_button', $bpgsites_overrides_button );

		// Set slug option.
		$bpgsites_overrides_slug = sanitize_title( $bpgsites_overrides_plural );
		$this->option_set( 'bpgsites_overrides_slug', $bpgsites_overrides_slug );

		// Save.
		$this->options_save();

	}

	/**
	 * Save array as site option.
	 *
	 * @since 0.1
	 *
	 * @return bool Success or failure.
	 */
	public function options_save() {

		// Save array as site option.
		return bpgsites_site_option_set( 'bpgsites_options', $this->bpgsites_options );

	}

	/**
	 * Return a value for a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option.
	 * @return bool Whether or not the option exists.
	 */
	public function option_exists( $option_name = '' ) {

		// Test for null.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_exists()', 'bp-group-sites' ) );
		}

		// Get existence of option in array.
		return array_key_exists( $option_name, $this->bpgsites_options );

	}

	/**
	 * Return a value for a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed $default The default value if the option does not exist.
	 * @return mixed The option or the default.
	 */
	public function option_get( $option_name = '', $default = false ) {

		// Test for null.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_get()', 'bp-group-sites' ) );
		}

		// Get option.
		return ( array_key_exists( $option_name, $this->bpgsites_options ) ) ? $this->bpgsites_options[ $option_name ] : $default;

	}

	/**
	 * Sets a value for a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed $value The value of the option.
	 */
	public function option_set( $option_name = '', $value = '' ) {

		// Test for null.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_set()', 'bp-group-sites' ) );
		}

		// Test for other than string.
		if ( ! is_string( $option_name ) ) {
			die( __( 'You must supply the option as a string to option_set()', 'bp-group-sites' ) );
		}

		// Set option.
		$this->bpgsites_options[ $option_name ] = $value;

	}

	/**
	 * Deletes a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option.
	 */
	public function option_delete( $option_name = '' ) {

		// Test for null.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_delete()', 'bp-group-sites' ) );
		}

		// Unset option.
		unset( $this->bpgsites_options[ $option_name ] );

	}

	/**
	 * General debugging utility.
	 *
	 * @since 0.1
	 */
	public function do_debug() {

	}

	/**
	 * Show our admin page.
	 *
	 * @since 0.1
	 */
	public function network_admin_form() {

		// Only allow network admins through.
		if ( is_super_admin() == false ) {
			wp_die( __( 'You do not have permission to access this page.', 'bp-group-sites' ) );
		}

		// Show message.
		if ( isset( $_GET['updated'] ) ) {
			echo '<div id="message" class="updated"><p>' . __( 'Options saved.', 'bp-group-sites' ) . '</p></div>';
		}

		// Sanitise admin page url.
		$url = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$url_array = explode( '&', $url );
		if ( is_array( $url_array ) ) {
			$url = $url_array[0];
		}

		// Get defaults.
		$defaults = $this->get_defaults();

		// Init public comments checkbox.
		$bpgsites_public = '';
		if ( $this->option_get( 'bpgsites_public' ) == '1' ) {
			$bpgsites_public = ' checked="checked"';
		}

		// Init name change checkbox.
		$bpgsites_overrides = '';
		if ( $this->option_get( 'bpgsites_overrides' ) == '1' ) {
			$bpgsites_overrides = ' checked="checked"';
		}

		// Init plugin title.
		$bpgsites_overrides_title = $this->option_get( 'bpgsites_overrides_title' );
		if ( $bpgsites_overrides_title == '' ) {
			$bpgsites_overrides_title = esc_attr( $defaults['title'] );
		}

		// Init name.
		$bpgsites_overrides_name = $this->option_get( 'bpgsites_overrides_name' );
		if ( $bpgsites_overrides_name == '' ) {
			$bpgsites_overrides_name = esc_attr( $defaults['name'] );
		}

		// Init plural.
		$bpgsites_overrides_plural = $this->option_get( 'bpgsites_overrides_plural' );
		if ( $bpgsites_overrides_plural == '' ) {
			$bpgsites_overrides_plural = esc_attr( $defaults['plural'] );
		}

		// Init button.
		$bpgsites_overrides_button = $this->option_get( 'bpgsites_overrides_button' );
		if ( $bpgsites_overrides_button == '' ) {
			$bpgsites_overrides_button = esc_attr( $defaults['button'] );
		}

		// Open admin page.
		echo '
		<div class="wrap" id="bpgsites_admin_wrapper">

		<div class="icon32" id="icon-options-general"><br/></div>

		<h2>' . __( 'BP Group Sites Settings', 'bp-group-sites' ) . '</h2>

		<form method="post" action="' . htmlentities( $url . '&updated=true' ) . '">

		' . wp_nonce_field( 'bpgsites_admin_action', 'bpgsites_nonce', true, false ) . '
		' . wp_referer_field( false ) . "\n\n";

		// Show multisite options.
		echo '
		<div id="bpgsites_admin_options">

		<h3>' . __( 'BP Group Sites Settings', 'bp-group-sites' ) . '</h3>

		<p>' . __( 'Configure how BP Group Sites behaves.', 'bp-group-sites' ) . '</p>' . "\n\n";

		// Add global options.
		echo '
		<h4>' . __( 'Global Options', 'bp-group-sites' ) . '</h4>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><label for="bpgsites_public">' . __( 'Should comments in public groups be visible to readers who are not members of those groups?', 'bp-group-sites' ) . '</label></th>
				<td><input id="bpgsites_public" name="bpgsites_public" value="1" type="checkbox"' . $bpgsites_public . ' /></td>
			</tr>

		</table>' . "\n\n";

		// Add global options.
		echo '
		<h4>' . __( 'Naming Options', 'bp-group-sites' ) . '</h4>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><label for="bpgsites_overrides">' . __( 'Enable name changes?', 'bp-group-sites' ) . '</label></th>
				<td><input id="bpgsites_overrides" name="bpgsites_overrides" value="1" type="checkbox"' . $bpgsites_overrides . ' /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bpgsites_overrides_title">' . __( 'Component Title', 'bp-group-sites' ) . '</label></th>
				<td><input id="bpgsites_overrides_title" name="bpgsites_overrides_title" value="' . $bpgsites_overrides_title . '" type="text" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bpgsites_overrides_name">' . __( 'Singular name for a Group Site', 'bp-group-sites' ) . '</label></th>
				<td><input id="bpgsites_overrides_name" name="bpgsites_overrides_name" value="' . $bpgsites_overrides_name . '" type="text" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bpgsites_overrides_plural">' . __( 'Plural name for Group Sites', 'bp-group-sites' ) . '</label></th>
				<td><input id="bpgsites_overrides_plural" name="bpgsites_overrides_plural" value="' . $bpgsites_overrides_plural . '" type="text" /></td>
			</tr>

			<tr valign="top">
				<th scope="row"><label for="bpgsites_overrides_button">' . __( 'Visit Group Site button text', 'bp-group-sites' ) . '</label></th>
				<td><input id="bpgsites_overrides_button" name="bpgsites_overrides_button" value="' . $bpgsites_overrides_button . '" type="text" /></td>
			</tr>

		</table>' . "\n\n";

		if ( is_super_admin() ) {

			// Show debugger.
			echo '
			<hr>
			<h3>' . __( 'Developer Testing', 'bp-group-sites' ) . '</h3>

			<table class="form-table">

				<tr>
					<th scope="row">' . __( 'Debug', 'bp-group-sites' ) . '</th>
					<td>
						<input type="checkbox" class="settings-checkbox" name="bpgsites_debug" id="bpgsites_debug" value="1" />
						<label class="bpgsites_settings_label" for="bpgsites_debug">' . __( 'Check this to trigger do_debug() . ', 'bp-group-sites' ) . '</label>
					</td>
				</tr>

			</table>' . "\n\n";

		}

		// Close form.
		echo '</div>' . "\n\n";

		// Close admin form.
		echo '
		<p class="submit">
			<input type="submit" name="bpgsites_submit" value="' . __( 'Save Changes', 'bp-group-sites' ) . '" class="button-primary" />
		</p>

		</form>

		</div>
		' . "\n\n\n\n";

	}

	/**
	 * Get default values for this plugin.
	 *
	 * @since 0.1
	 *
	 * @return array The default values for this plugin.
	 */
	public function get_defaults() {

		// Init return.
		$defaults = [];

		// Default visibility of public group comments to off.
		$defaults['public'] = 0;

		// Default to off.
		$defaults['overrides'] = 0;

		// Default plugin title to "Group Sites".
		$defaults['title'] = __( 'Group Sites', 'bp-group-sites' );

		// Default singular to "Group Site".
		$defaults['name'] = __( 'Group Site', 'bp-group-sites' );

		// Default plural to "Group Sites".
		$defaults['plural'] = __( 'Group Sites', 'bp-group-sites' );

		// Default button to "Visit Group Site".
		$defaults['button'] = __( 'Visit Group Site', 'bp-group-sites' );

		// Default slug to "group-sites".
		$defaults['slug'] = 'group-sites';

		// Default list of group sites to empty.
		$defaults['groupsites'] = [];

		// --<
		return $defaults;

	}

}

// =============================================================================
// Primary filters for overrides.
// =============================================================================

/**
 * Override group extension title.
 *
 * @since 0.1
 *
 * @param str $title The existing title.
 * @return str $title The overridden title.
 */
function bpgsites_override_extension_title( $title ) {

	// Access object.
	global $bp_groupsites;

	// Are we overriding?
	if ( $bp_groupsites->admin->option_get( 'bpgsites_overrides' ) ) {

		// Override with our option.
		$title = $bp_groupsites->admin->option_get( 'bpgsites_overrides_title' );

	}

	// --<
	return $title;

}

// Add filter for the above.
add_filter( 'bpgsites_extension_title', 'bpgsites_override_extension_title', 10, 1 );

/**
 * Override group extension singular name.
 *
 * @since 0.1
 *
 * @param str $name The existing name.
 * @return str $name The overridden name.
 */
function bpgsites_override_extension_name( $name ) {

	// Access object.
	global $bp_groupsites;

	// Are we overriding?
	if ( $bp_groupsites->admin->option_get( 'bpgsites_overrides' ) ) {

		// Override with our option.
		$name = $bp_groupsites->admin->option_get( 'bpgsites_overrides_name' );

	}

	// --<
	return $name;

}

// Add filter for the above.
add_filter( 'bpgsites_extension_name', 'bpgsites_override_extension_name', 10, 1 );

/**
 * Override group extension plural.
 *
 * @since 0.1
 *
 * @param str $plural The existing plural name.
 * @return str $plural The overridden plural name.
 */
function bpgsites_override_extension_plural( $plural ) {

	// Access object.
	global $bp_groupsites;

	// Are we overriding?
	if ( $bp_groupsites->admin->option_get( 'bpgsites_overrides' ) ) {

		// Override with our option.
		$plural = $bp_groupsites->admin->option_get( 'bpgsites_overrides_plural' );

	}

	// --<
	return $plural;

}

// Add filter for the above.
add_filter( 'bpgsites_extension_plural', 'bpgsites_override_extension_plural', 10, 1 );

/**
 * Override group extension slug.
 *
 * @since 0.1
 *
 * @param str $slug The existing slug.
 * @return str $slug The overridden slug.
 */
function bpgsites_override_extension_slug( $slug ) {

	// Access object.
	global $bp_groupsites;

	// Are we overriding?
	if ( $bp_groupsites->admin->option_get( 'bpgsites_overrides' ) ) {

		// Override with our option.
		$slug = $bp_groupsites->admin->option_get( 'bpgsites_overrides_slug' );

	}

	// --<
	return $slug;

}

// Add filter for the above.
add_filter( 'bpgsites_extension_slug', 'bpgsites_override_extension_slug', 10, 1 );

/**
 * Override the name of the button on the BP Group Sites "sites" screen.
 *
 * @since 0.1
 *
 * @param array $button The existing button config array.
 * @return array $button The modified button config array.
 */
function bpgsites_get_visit_site_button( $button ) {

	/*
	[id] => visit_blog
	[component] => blogs
	[must_be_logged_in] =>
	[block_self] =>
	[wrapper_class] => blog-button visit
	[link_href] => http://domain/site-slug/
	[link_class] => blog-button visit
	[link_text] => Visit Site
	[link_title] => Visit Site
	*/

	// Switch by blogtype.
	if ( bpgsites_is_groupsite( bp_get_blog_id() ) ) {

		// Access object.
		global $bp_groupsites;

		// Are we overriding?
		if ( $bp_groupsites->admin->option_get( 'bpgsites_overrides' ) ) {

			// Override with our option.
			$label = $bp_groupsites->admin->option_get( 'bpgsites_overrides_button' );
			$button['link_text'] = apply_filters( 'bpgsites_visit_site_button_text', $label );
			$button['link_title'] = apply_filters( 'bpgsites_visit_site_button_title', $label );

		}

	}

	// --<
	return $button;

}

// Add fliter for the above.
add_filter( 'bp_get_blogs_visit_blog_button', 'bpgsites_get_visit_site_button', 30, 1 );

// =============================================================================
// Globally available utility functions.
// =============================================================================

/**
 * Test existence of a specified site option.
 *
 * @since 0.1
 *
 * @param str $option_name The name of the option.
 * @return bool $exists Whether or not the option exists.
 */
function bpgsites_site_option_exists( $option_name = '' ) {

	// Test for null.
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to bpgsites_option_wpms_exists()', 'bp-group-sites' ) );
	}

	// Test by getting option with unlikely default.
	if ( bpgsites_site_option_get( $option_name, 'fenfgehgefdfdjgrkj' ) == 'fenfgehgefdfdjgrkj' ) {
		return false;
	} else {
		return true;
	}

}

/**
 * Return a value for a specified site option.
 *
 * @since 0.1
 *
 * @param str $option_name The name of the option.
 * @param str $default The default value of the option if it has no value.
 * @return mixed $value The value of the option.
 */
function bpgsites_site_option_get( $option_name = '', $default = false ) {

	// Test for null.
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to bpgsites_site_option_get()', 'bp-group-sites' ) );
	}

	// Get option.
	return get_site_option( $option_name, $default );

}

/**
 * Set a value for a specified site option.
 *
 * @since 0.1
 *
 * @param str $option_name The name of the option.
 * @param mixed $value The value to set the option to.
 * @return bool $success If the value of the option was successfully saved.
 */
function bpgsites_site_option_set( $option_name = '', $value = '' ) {

	// Test for null.
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to bpgsites_site_option_set()', 'bp-group-sites' ) );
	}

	// Set option.
	return update_site_option( $option_name, $value );

}
