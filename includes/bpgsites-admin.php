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
defined( 'ABSPATH' ) || exit;

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
	 * @var array
	 */
	public $bpgsites_options = [];

	/**
	 * Settings Page reference.
	 *
	 * @since 0.3.3
	 * @access public
	 * @var string
	 */
	public $settings_page;

	/**
	 * Settings Page slug.
	 *
	 * @since 0.3.3
	 * @access public
	 * @var string
	 */
	public $settings_page_slug = 'bpgsites_settings';

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

		// Add menu to Network Settings submenu.
		add_action( 'network_admin_menu', [ $this, 'add_admin_menu' ], 30 );

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

		// We'll delete our options in 'uninstall.php'.
		if ( false === BPGSITES_DEBUG ) {
			return;
		}

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

		// Always add the admin page to the Settings menu.
		$this->settings_page = add_submenu_page(
			'settings.php',
			__( 'BP Group Sites', 'bp-group-sites' ),
			__( 'BP Group Sites', 'bp-group-sites' ),
			'manage_options',
			$this->settings_page_slug, // Slug name.
			[ $this, 'network_admin_form' ]
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->settings_page, [ $this, 'form_submitted' ] );

		// Add styles only on our admin page.
		add_action( 'admin_print_styles-' . $this->settings_page, [ $this, 'add_admin_styles' ] );

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
	 * Show our admin page.
	 *
	 * @since 0.1
	 */
	public function network_admin_form() {

		// Only allow network admins through.
		if ( ! is_super_admin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'bp-group-sites' ) );
		}

		// Get Settings Page submit URL.
		$submit_url = $this->network_menu_page_url( $this->settings_page_slug, false );

		// Init public comments checkbox.
		$public = 0;
		if ( 1 === (int) $this->option_get( 'bpgsites_public' ) ) {
			$public = 1;
		}

		// Init name change checkbox.
		$overrides = 0;
		if ( 1 === (int) $this->option_get( 'bpgsites_overrides' ) ) {
			$overrides = 1;
		}

		// Get defaults.
		$defaults = $this->get_defaults();

		// Init plugin title.
		$title = $this->option_get( 'bpgsites_overrides_title' );
		if ( empty( $title ) ) {
			$title = $defaults['title'];
		}

		// Init name.
		$name = $this->option_get( 'bpgsites_overrides_name' );
		if ( empty( $name ) ) {
			$name = $defaults['name'];
		}

		// Init plural.
		$plural = $this->option_get( 'bpgsites_overrides_plural' );
		if ( empty( $plural ) ) {
			$plural = $defaults['plural'];
		}

		// Init button.
		$button = $this->option_get( 'bpgsites_overrides_button' );
		if ( empty( $button ) ) {
			$button = $defaults['button'];
		}

		// Include admin page template.
		include BPGSITES_PATH . 'assets/admin/page-settings.php';

	}

	/**
	 * Get the URL to access a particular menu Page.
	 *
	 * The URL based on the slug it was registered with. If the slug hasn't been
	 * registered properly no url will be returned.
	 *
	 * @since 0.3.3
	 *
	 * @param string $menu_slug The slug name to refer to this menu by (should be unique for this menu).
	 * @param bool   $echo Whether or not to echo the url - default is true.
	 * @return string $url The URL.
	 */
	public function network_menu_page_url( $menu_slug, $echo = true ) {

		global $_parent_pages;

		if ( isset( $_parent_pages[ $menu_slug ] ) ) {
			$parent_slug = $_parent_pages[ $menu_slug ];
			if ( $parent_slug && ! isset( $_parent_pages[ $parent_slug ] ) ) {
				$url = network_admin_url( add_query_arg( 'page', $menu_slug, $parent_slug ) );
			} else {
				$url = network_admin_url( 'admin.php?page=' . $menu_slug );
			}
		} else {
			$url = '';
		}

		$url = esc_url( $url );

		if ( $echo ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $url;
		}

		// --<
		return $url;

	}

	/**
	 * Update options based on content of form.
	 *
	 * @since 0.1
	 * @since 0.3.3 Renamed.
	 */
	public function form_submitted() {

		// Kick out if the form was not submitted.
		if ( ! isset( $_POST['bpgsites_submit'] ) ) {
			return;
		}

		// Check that we trust the source of the data.
		check_admin_referer( 'bpgsites_admin_action', 'bpgsites_nonce' );

		// Get defaults.
		$defaults = $this->get_defaults();

		// Set public comments visibility on/off option.
		$public = isset( $_POST['bpgsites_public'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['bpgsites_public'] ) ) : $defaults['public'];
		$this->option_set( 'bpgsites_public', ( $public ? 1 : 0 ) );

		// Set name change on/off option.
		$overrides = isset( $_POST['bpgsites_overrides'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['bpgsites_overrides'] ) ) : $defaults['overrides'];
		$this->option_set( 'bpgsites_overrides', ( $overrides ? 1 : 0 ) );

		// Set title option.
		$overrides_title = isset( $_POST['bpgsites_overrides_title'] ) ? sanitize_text_field( wp_unslash( $_POST['bpgsites_overrides_title'] ) ) : $defaults['title'];
		$this->option_set( 'bpgsites_overrides_title', $overrides_title );

		// Set name option.
		$overrides_name = isset( $_POST['bpgsites_overrides_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bpgsites_overrides_name'] ) ) : $defaults['name'];
		$this->option_set( 'bpgsites_overrides_name', $overrides_name );

		// Set plural option.
		$overrides_plural = isset( $_POST['bpgsites_overrides_plural'] ) ? sanitize_text_field( wp_unslash( $_POST['bpgsites_overrides_plural'] ) ) : $defaults['plural'];
		$this->option_set( 'bpgsites_overrides_plural', $overrides_plural );

		// Set button option.
		$overrides_button = isset( $_POST['bpgsites_overrides_button'] ) ? sanitize_text_field( wp_unslash( $_POST['bpgsites_overrides_button'] ) ) : $defaults['button'];
		$this->option_set( 'bpgsites_overrides_button', $overrides_button );

		// Set slug option.
		$overrides_slug = sanitize_title( $overrides_plural );
		$this->option_set( 'bpgsites_overrides_slug', $overrides_slug );

		// Save.
		$this->options_save();

		// Now redirect.
		$this->form_redirect();

	}

	/**
	 * Form redirection handler.
	 *
	 * @since 0.3.3
	 */
	public function form_redirect() {

		// Get the Network Settings Page URL.
		$url = $this->network_menu_page_url( $this->settings_page_slug, false );

		// Our array of arguments.
		$args = [ 'updated' => 'true' ];

		// Redirect to our Settings Page.
		wp_safe_redirect( add_query_arg( $args, $url ) );
		exit;

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
	public function option_exists( $option_name ) {

		// Get existence of option in array.
		return array_key_exists( $option_name, $this->bpgsites_options );

	}

	/**
	 * Return a value for a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed  $default The default value if the option does not exist.
	 * @return mixed The option or the default.
	 */
	public function option_get( $option_name, $default = false ) {

		// Get option.
		return ( array_key_exists( $option_name, $this->bpgsites_options ) ) ? $this->bpgsites_options[ $option_name ] : $default;

	}

	/**
	 * Sets a value for a specified option.
	 *
	 * @since 0.1
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed  $value The value of the option.
	 */
	public function option_set( $option_name, $value = '' ) {

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
	public function option_delete( $option_name ) {

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
 * Gets the group extension title.
 *
 * @since 0.3.3
 *
 * @return str $title The group extension title.
 */
function bpgsites_get_extension_title() {

	/**
	 * Filters the group extension plural.
	 *
	 * @since 0.1
	 *
	 * @param string $title The default group extension title.
	 */
	return apply_filters( 'bpgsites_extension_title', __( 'Group Sites', 'bp-group-sites' ) );

}

/**
 * Override group extension title.
 *
 * @since 0.1
 *
 * @param str $title The existing title.
 * @return str $title The overridden title.
 */
function bpgsites_override_extension_title( $title ) {

	// Maybe override with our option.
	if ( bp_groupsites()->admin->option_get( 'bpgsites_overrides' ) ) {
		$title = bp_groupsites()->admin->option_get( 'bpgsites_overrides_title' );
	}

	// --<
	return $title;

}

// Add filter for the above.
add_filter( 'bpgsites_extension_title', 'bpgsites_override_extension_title', 10 );

/**
 * Gets the group extension singular name.
 *
 * @since 0.3.3
 *
 * @return string $name The singular name.
 */
function bpgsites_get_extension_name() {

	/**
	 * Filters the group extension plural.
	 *
	 * @since 0.1
	 *
	 * @param string $name The default group extension singular name.
	 */
	return apply_filters( 'bpgsites_extension_name', __( 'Group Site', 'bp-group-sites' ) );

}

/**
 * Override group extension singular name.
 *
 * @since 0.1
 *
 * @param str $name The existing name.
 * @return str $name The overridden name.
 */
function bpgsites_override_extension_name( $name ) {

	// Maybe override with our option.
	if ( bp_groupsites()->admin->option_get( 'bpgsites_overrides' ) ) {
		$name = bp_groupsites()->admin->option_get( 'bpgsites_overrides_name' );
	}

	// --<
	return $name;

}

// Add filter for the above.
add_filter( 'bpgsites_extension_name', 'bpgsites_override_extension_name', 10 );

/**
 * Gets the group extension plural name.
 *
 * @since 0.3.3
 *
 * @return str $plural The group extension plural name.
 */
function bpgsites_get_extension_plural() {

	/**
	 * Filters the group extension plural.
	 *
	 * @since 0.1
	 *
	 * @param string $name The default group extension plural name.
	 */
	return apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) );

}

/**
 * Override group extension plural.
 *
 * @since 0.1
 *
 * @param str $plural The existing plural name.
 * @return str $plural The overridden plural name.
 */
function bpgsites_override_extension_plural( $plural ) {

	// Maybe override with our option.
	if ( bp_groupsites()->admin->option_get( 'bpgsites_overrides' ) ) {
		$plural = bp_groupsites()->admin->option_get( 'bpgsites_overrides_plural' );
	}

	// --<
	return $plural;

}

// Add filter for the above.
add_filter( 'bpgsites_extension_plural', 'bpgsites_override_extension_plural', 10 );

/**
 * Gets the group extension slug.
 *
 * @since 0.3.3
 *
 * @return str $slug The group extension slug.
 */
function bpgsites_get_extension_slug() {

	/**
	 * Filters the group extension slug.
	 *
	 * @since 0.1
	 *
	 * @param string $slug The default group extension slug.
	 */
	return apply_filters( 'bpgsites_extension_slug', 'group-sites' );

}

/**
 * Override group extension slug.
 *
 * @since 0.1
 *
 * @param str $slug The existing slug.
 * @return str $slug The overridden slug.
 */
function bpgsites_override_extension_slug( $slug ) {

	// Maybe override with our option.
	if ( bp_groupsites()->admin->option_get( 'bpgsites_overrides' ) ) {
		$slug = bp_groupsites()->admin->option_get( 'bpgsites_overrides_slug' );
	}

	// --<
	return $slug;

}

// Add filter for the above.
add_filter( 'bpgsites_extension_slug', 'bpgsites_override_extension_slug', 10 );

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
	 * [id] => visit_blog
	 * [component] => blogs
	 * [must_be_logged_in] =>
	 * [block_self] =>
	 * [wrapper_class] => blog-button visit
	 * [link_href] => https://domain/site-slug/
	 * [link_class] => blog-button visit
	 * [link_text] => Visit Site
	 * [link_title] => Visit Site
	 */

	// Switch by blogtype.
	if ( bpgsites_is_groupsite( bp_get_blog_id() ) ) {

		// Maybe override with our option.
		if ( bp_groupsites()->admin->option_get( 'bpgsites_overrides' ) ) {
			$label                = bp_groupsites()->admin->option_get( 'bpgsites_overrides_button' );
			$button['link_text']  = apply_filters( 'bpgsites_visit_site_button_text', $label );
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
function bpgsites_site_option_exists( $option_name ) {

	// Test by getting option with unlikely default.
	if ( 'fenfgehgefdfdjgrkj' === bpgsites_site_option_get( $option_name, 'fenfgehgefdfdjgrkj' ) ) {
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
function bpgsites_site_option_get( $option_name, $default = false ) {

	// Get option.
	return get_site_option( $option_name, $default );

}

/**
 * Set a value for a specified site option.
 *
 * @since 0.1
 *
 * @param str   $option_name The name of the option.
 * @param mixed $value The value to set the option to.
 * @return bool $success If the value of the option was successfully saved.
 */
function bpgsites_site_option_set( $option_name, $value = '' ) {

	// Set option.
	return update_site_option( $option_name, $value );

}
