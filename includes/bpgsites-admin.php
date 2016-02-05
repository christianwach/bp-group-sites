<?php /*
================================================================================
BP Group Sites Admin Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

The plugin's admin screen logic

--------------------------------------------------------------------------------
*/



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
	 * @var object $bpgsites_options The plugin options array
	 */
	public $bpgsites_options = array();



	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	function __construct() {

		// get options array, if it exists
		$this->bpgsites_options = bpgsites_site_option_get( 'bpgsites_options', array() );

	}



	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function register_hooks() {

		// if on back end...
		if ( is_admin() ) {

			// add menu to Network Settings submenu
			add_action( 'network_admin_menu', array( $this, 'add_admin_menu' ), 30 );

		}

	}



	/**
	 * Actions to perform on plugin activation.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function activate() {

		// kick out if we are re-activating
		if ( bpgsites_site_option_exists( 'bpgsites_installed', 'false' ) === 'true' ) return;

		// get defaults
		$defaults = $this->_get_defaults();

		// default public comment visibility to "off"
		$this->option_set( 'bpgsites_public', $defaults['public'] );

		// default name changes to "off"
		$this->option_set( 'bpgsites_overrides', $defaults['overrides'] );

		// default plugin name to "Group Sites"
		$this->option_set( 'bpgsites_overrides_title', $defaults['title'] );

		// default singular to "Group Site"
		$this->option_set( 'bpgsites_overrides_name', $defaults['name'] );

		// default plural to "Group Sites"
		$this->option_set( 'bpgsites_overrides_plural', $defaults['plural'] );

		// default button to "Visit Group Site"
		$this->option_set( 'bpgsites_overrides_button', $defaults['button'] );

		// default slug to "group-sites"
		$this->option_set( 'bpgsites_overrides_slug', $defaults['slug'] );

		// default list of group sites to an empty array
		$this->option_set( 'bpgsites_groupsites', $defaults['groupsites'] );

		// save options array
		$this->options_save();

		// set installed flag
		bpgsites_site_option_set( 'bpgsites_installed', 'true' );

	}



	/**
	 * Actions to perform on plugin deactivation (NOT deletion).
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function deactivate() {

		// we'll delete our options in 'uninstall.php'
		// but for testing let's delete them here
		delete_site_option( 'bpgsites_options' );
		delete_site_option( 'bpgsites_installed' );
		delete_site_option( 'bpgsites_auth_groups' );

	}



	/**
	 * Add an admin page for this plugin.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function add_admin_menu() {

		// we must be network admin
		if ( ! is_super_admin() ) { return false; }

		// try and update options
		$saved = $this->options_update();

		// always add the admin page to the Settings menu
		$page = add_submenu_page(
			'settings.php',
			__( 'BP Group Sites', 'bp-group-sites' ),
			__( 'BP Group Sites', 'bp-group-sites' ),
			'manage_options',
			'bpgsites_admin_page',
			array( $this, '_network_admin_form' )
		);

		// add styles only on our admin page, see:
		// http://codex.wordpress.org/Function_Reference/wp_enqueue_script#Load_scripts_only_on_plugin_pages
		add_action( 'admin_print_styles-' . $page, array( $this, 'add_admin_styles' ) );

	}



	/**
	 * Enqueue any styles and scripts needed by our admin page.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function add_admin_styles() {

		// add admin css
		wp_enqueue_style(
			'bpgsites-admin-style',
			BPGSITES_URL . 'assets/css/bpgsites-admin.css',
			null,
			BPGSITES_VERSION,
			'all' // media
		);

	}



	/**
	 * Update options based on content of form.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function options_update() {

	 	// kick out if the form was not submitted
		if( ! isset( $_POST['bpgsites_submit'] ) ) return;

		// check that we trust the source of the data
		check_admin_referer( 'bpgsites_admin_action', 'bpgsites_nonce' );

		// debugging switch for admins and network admins - if set, triggers do_debug() below
		if ( is_super_admin() AND isset( $_POST['bpgsites_debug'] ) ) {
			$settings_debug = absint( $_POST['bpgsites_debug'] );
			$debug = $settings_debug ? 1 : 0;
			if ( $debug ) { $this->do_debug(); }
			return;
		}

		// init vars
		$bpgsites_public = 0;
		$bpgsites_overrides = 0;
		$bpgsites_overrides_title = '';
		$bpgsites_overrides_name = '';
		$bpgsites_overrides_plural = '';
		$bpgsites_overrides_button = '';

		// okay, we're through - get variables
		extract( $_POST );

		// get defaults
		$defaults = $this->_get_defaults();

		// set public comments visibility on/off option
		$bpgsites_public = absint( $bpgsites_public );
		$this->option_set( 'bpgsites_public', ( $bpgsites_public ? 1 : 0 ) );

		// set name change on/off option
		$bpgsites_overrides = absint( $bpgsites_overrides );
		$this->option_set( 'bpgsites_overrides', ( $bpgsites_overrides ? 1 : 0 ) );

		// get plugin title option
		$bpgsites_overrides_title = esc_sql( $bpgsites_overrides_title );

		// revert to default if we didn't get one...
		if ( $bpgsites_overrides_title == '' ) {
			$bpgsites_overrides_title = $defaults['title'];
		}

		// set title option
		$this->option_set( 'bpgsites_overrides_title', $bpgsites_overrides_title );

		// get name option
		$bpgsites_overrides_name = esc_sql( $bpgsites_overrides_name );

		// revert to default if we didn't get one...
		if ( $bpgsites_overrides_name == '' ) {
			$bpgsites_overrides_name = $defaults['name'];
		}

		// set name option
		$this->option_set( 'bpgsites_overrides_name', $bpgsites_overrides_name );

		// get plural option
		$bpgsites_overrides_plural = esc_sql( $bpgsites_overrides_plural );

		// revert to default if we didn't get one...
		if ( $bpgsites_overrides_plural == '' ) {
			$bpgsites_overrides_plural = $defaults['plural'];
		}

		// set plural option
		$this->option_set( 'bpgsites_overrides_plural', $bpgsites_overrides_plural );

		// get button option
		$bpgsites_overrides_button = esc_sql( $bpgsites_overrides_button );

		// revert to default if we didn't get one...
		if ( $bpgsites_overrides_button == '' ) {
			$bpgsites_overrides_button = $defaults['button'];
		}

		// set button option
		$this->option_set( 'bpgsites_overrides_button', $bpgsites_overrides_button );

		// set slug option
		$bpgsites_overrides_slug = sanitize_title( $bpgsites_overrides_plural );
		$this->option_set( 'bpgsites_overrides_slug', $bpgsites_overrides_slug );

		// save
		$this->options_save();

	}



	/**
	 * Save array as site option.
	 *
	 * @since 0.1
	 *
	 * @return bool Success or failure
	 */
	public function options_save() {

		// save array as site option
		return bpgsites_site_option_set( 'bpgsites_options', $this->bpgsites_options );

	}



	/**
	 * Return a value for a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option
	 * @return bool Whether or not the option exists
	 */
	public function option_exists( $option_name = '' ) {

		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_exists()', 'bp-group-sites' ) );
		}

		// get existence of option in array
		return array_key_exists( $option_name, $this->bpgsites_options );

	}



	/**
	 * Return a value for a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option
	 * @param mixed $default The default value if the option does not exist
	 * @return mixed The option or the default
	 */
	public function option_get( $option_name = '', $default = false ) {

		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_get()', 'bp-group-sites' ) );
		}

		// get option
		return ( array_key_exists( $option_name, $this->bpgsites_options ) ) ? $this->bpgsites_options[ $option_name ] : $default;

	}



	/**
	 * Sets a value for a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option
	 * @param mixed $value The value of the option
	 * @return void
	 */
	public function option_set( $option_name = '', $value = '' ) {

		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_set()', 'bp-group-sites' ) );
		}

		// test for other than string
		if ( ! is_string( $option_name ) ) {
			die( __( 'You must supply the option as a string to option_set()', 'bp-group-sites' ) );
		}

		// set option
		$this->bpgsites_options[ $option_name ] = $value;

	}



	/**
	 * Deletes a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option
	 * @return void
	 */
	public function option_delete( $option_name = '' ) {

		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_delete()', 'bp-group-sites' ) );
		}

		// unset option
		unset( $this->bpgsites_options[ $option_name ] );

	}



	/**
	 * General debugging utility.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function do_debug() {

	}



	/**
	 * Show our admin page.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function _network_admin_form() {

		// only allow network admins through
		if( is_super_admin() == false ) {
			wp_die( __( 'You do not have permission to access this page.', 'bp-group-sites' ) );
		}

		// show message
		if ( isset( $_GET['updated'] ) ) {
			echo '<div id="message" class="updated"><p>' . __( 'Options saved.', 'bp-group-sites' ) . '</p></div>';
		}

		// sanitise admin page url
		$url = $_SERVER['REQUEST_URI'];
		$url_array = explode( '&', $url );
		if ( is_array( $url_array ) ) { $url = $url_array[0]; }

		// get defaults
		$defaults = $this->_get_defaults();

		// init public comments checkbox
		$bpgsites_public = '';
		if ( $this->option_get( 'bpgsites_public' ) == '1' ) $bpgsites_public = ' checked="checked"';

		// init name change checkbox
		$bpgsites_overrides = '';
		if ( $this->option_get( 'bpgsites_overrides' ) == '1' ) $bpgsites_overrides = ' checked="checked"';

		// init plugin title
		$bpgsites_overrides_title = $this->option_get( 'bpgsites_overrides_title' );
		if ( $bpgsites_overrides_title == '' ) $bpgsites_overrides_title = esc_attr( $defaults['title'] );

		// init name
		$bpgsites_overrides_name = $this->option_get( 'bpgsites_overrides_name' );
		if ( $bpgsites_overrides_name == '' ) $bpgsites_overrides_name = esc_attr( $defaults['name'] );

		// init plural
		$bpgsites_overrides_plural = $this->option_get( 'bpgsites_overrides_plural' );
		if ( $bpgsites_overrides_plural == '' ) $bpgsites_overrides_plural = esc_attr( $defaults['plural'] );

		// init button
		$bpgsites_overrides_button = $this->option_get( 'bpgsites_overrides_button' );
		if ( $bpgsites_overrides_button == '' ) $bpgsites_overrides_button = esc_attr( $defaults['button'] );



		// open admin page
		echo '
		<div class="wrap" id="bpgsites_admin_wrapper">

		<div class="icon32" id="icon-options-general"><br/></div>

		<h2>' . __( 'BP Group Sites Settings', 'bp-group-sites' ) . '</h2>

		<form method="post" action="' . htmlentities( $url . '&updated=true' ) . '">

		' . wp_nonce_field( 'bpgsites_admin_action', 'bpgsites_nonce', true, false ) . '
		' . wp_referer_field( false ) . "\n\n";



		// show multisite options
		echo '
		<div id="bpgsites_admin_options">

		<h3>' . __( 'BP Group Sites Settings', 'bp-group-sites' ) . '</h3>

		<p>' . __( 'Configure how BP Group Sites behaves.', 'bp-group-sites' ) . '</p>' . "\n\n";



		// add global options
		echo '
		<h4>' . __( 'Global Options', 'bp-group-sites' ) . '</h4>

		<table class="form-table">

			<tr valign="top">
				<th scope="row"><label for="bpgsites_public">' . __( 'Should comments in public groups be visible to readers who are not members of those groups?', 'bp-group-sites' ) . '</label></th>
				<td><input id="bpgsites_public" name="bpgsites_public" value="1" type="checkbox"' . $bpgsites_public . ' /></td>
			</tr>

		</table>' . "\n\n";



		// add global options
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

			// show debugger
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



		// close form
		echo '</div>' . "\n\n";



		// close admin form
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
	 * @return array The default values for this plugin
	 */
	public function _get_defaults() {

		// init return
		$defaults = array();

		// default visibility of public group comments to off
		$defaults['public'] = 0;

		// default to off
		$defaults['overrides'] = 0;

		// default plugin title to "Group Sites"
		$defaults['title'] = __( 'Group Sites', 'bp-group-sites' );

		// default singular to "Group Site"
		$defaults['name'] = __( 'Group Site', 'bp-group-sites' );

		// default plural to "Group Sites"
		$defaults['plural'] = __( 'Group Sites', 'bp-group-sites' );

		// default button to "Visit Group Site"
		$defaults['button'] = __( 'Visit Group Site', 'bp-group-sites' );

		// default slug to "group-sites"
		$defaults['slug'] = 'group-sites';

		// default list of group sites to empty
		$defaults['groupsites'] = array();

		// --<
		return $defaults;

	}



} // end class BP_Group_Sites_Admin



/*
================================================================================
Primary filters for overrides
================================================================================
*/



/**
 * Override group extension title.
 *
 * @since 0.1
 *
 * @param str $title The existing title
 * @return str $title The overridden title
 */
function bpgsites_override_extension_title( $title ) {

	// access object
	global $bp_groupsites;

	// are we overriding?
	if ( $bp_groupsites->admin->option_get( 'bpgsites_overrides' ) ) {

		// override with our option
		$title = $bp_groupsites->admin->option_get( 'bpgsites_overrides_title' );

	}

	// --<
	return $title;

}

// add filter for the above
add_filter( 'bpgsites_extension_title', 'bpgsites_override_extension_title', 10, 1 );



/**
 * Override group extension singular name.
 *
 * @since 0.1
 *
 * @param str $name The existing name
 * @return str $name The overridden name
 */
function bpgsites_override_extension_name( $name ) {

	// access object
	global $bp_groupsites;

	// are we overriding?
	if ( $bp_groupsites->admin->option_get( 'bpgsites_overrides' ) ) {

		// override with our option
		$name = $bp_groupsites->admin->option_get( 'bpgsites_overrides_name' );

	}

	// --<
	return $name;

}

// add filter for the above
add_filter( 'bpgsites_extension_name', 'bpgsites_override_extension_name', 10, 1 );



/**
 * Override group extension plural.
 *
 * @since 0.1
 *
 * @param str $plural The existing plural name
 * @return str $plural The overridden plural name
 */
function bpgsites_override_extension_plural( $plural ) {

	// access object
	global $bp_groupsites;

	// are we overriding?
	if ( $bp_groupsites->admin->option_get( 'bpgsites_overrides' ) ) {

		// override with our option
		$plural = $bp_groupsites->admin->option_get( 'bpgsites_overrides_plural' );

	}

	// --<
	return $plural;

}

// add filter for the above
add_filter( 'bpgsites_extension_plural', 'bpgsites_override_extension_plural', 10, 1 );



/**
 * Override group extension slug.
 *
 * @since 0.1
 *
 * @param str $slug The existing slug
 * @return str $slug The overridden slug
 */
function bpgsites_override_extension_slug( $slug ) {

	// access object
	global $bp_groupsites;

	// are we overriding?
	if ( $bp_groupsites->admin->option_get( 'bpgsites_overrides' ) ) {

		// override with our option
		$slug = $bp_groupsites->admin->option_get( 'bpgsites_overrides_slug' );

	}

	// --<
	return $slug;

}

// add filter for the above
add_filter( 'bpgsites_extension_slug', 'bpgsites_override_extension_slug', 10, 1 );



/**
 * Override the name of the button on the BP Group Sites "sites" screen.
 *
 * @since 0.1
 *
 * @param array $button The existing button config array
 * @return array $button The modified button config array
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

	// switch by blogtype
	if ( bpgsites_is_groupsite( bp_get_blog_id() ) ) {

		// access object
		global $bp_groupsites;

		// are we overriding?
		if ( $bp_groupsites->admin->option_get( 'bpgsites_overrides' ) ) {

			// override with our option
			$label = $bp_groupsites->admin->option_get( 'bpgsites_overrides_button' );
			$button['link_text'] = apply_filters( 'bpgsites_visit_site_button_text', $label );
			$button['link_title'] = apply_filters( 'bpgsites_visit_site_button_title', $label );

		}

	}

	// --<
	return $button;

}

// add fliter for the above
add_filter( 'bp_get_blogs_visit_blog_button', 'bpgsites_get_visit_site_button', 30, 1 );



/*
================================================================================
Globally available utility functions
================================================================================
*/



/**
 * Test existence of a specified site option.
 *
 * @since 0.1
 *
 * @param str $option_name The name of the option
 * @return bool $exists Whether or not the option exists
 */
function bpgsites_site_option_exists( $option_name = '' ) {

	// test for null
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to bpgsites_option_wpms_exists()', 'bp-group-sites' ) );
	}

	// test by getting option with unlikely default
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
 * @param str $option_name The name of the option
 * @param str $default The default value of the option if it has no value
 * @return mixed $value the value of the option
 */
function bpgsites_site_option_get( $option_name = '', $default = false ) {

	// test for null
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to bpgsites_site_option_get()', 'bp-group-sites' ) );
	}

	// get option
	return get_site_option( $option_name, $default );

}



/**
 * Set a value for a specified site option.
 *
 * @since 0.1
 *
 * @param str $option_name The name of the option
 * @param mixed $value The value to set the option to
 * @return bool $success If the value of the option was successfully saved
 */
function bpgsites_site_option_set( $option_name = '', $value = '' ) {

	// test for null
	if ( $option_name == '' ) {
		die( __( 'You must supply an option to bpgsites_site_option_set()', 'bp-group-sites' ) );
	}

	// set option
	return update_site_option( $option_name, $value );

}



