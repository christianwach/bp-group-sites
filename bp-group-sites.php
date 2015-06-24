<?php
/*
--------------------------------------------------------------------------------
Plugin Name: BP Group Sites
Description: In WordPress Multisite, create a many-to-many replationship between BuddyPress Groups and WordPress Sites.
Version: 0.2
Author: Christian Wach
Author URI: http://haystack.co.uk
Plugin URI: https://github.com/christianwach/bp-group-sites
Network: true
--------------------------------------------------------------------------------
*/



// set our version here
define( 'BPGSITES_VERSION', '0.2' );

// store reference to this file
if ( ! defined( 'BPGSITES_FILE' ) ) {
	define( 'BPGSITES_FILE', __FILE__ );
}

// store URL to this plugin's directory
if ( ! defined( 'BPGSITES_URL' ) ) {
	define( 'BPGSITES_URL', plugin_dir_url( BPGSITES_FILE ) );
}
// store PATH to this plugin's directory
if ( ! defined( 'BPGSITES_PATH' ) ) {
	define( 'BPGSITES_PATH', plugin_dir_path( BPGSITES_FILE ) );
}

// set site option prefix
define( 'BPGSITES_PREFIX', 'bpgsites_blog_groups_' );

// set linked groups option name
define( 'BPGSITES_LINKED', 'bpgsites_linked_groups' );

// set linked groups (pending sent) option name
define( 'BPGSITES_PENDING', 'bpgsites_pending_groups' );

// set group blogs option name
define( 'BPGSITES_OPTION', 'bpgsites_group_blogs' );

// set comment meta key
define( 'BPGSITES_COMMENT_META_KEY', 'bpgsites_group_id' );



/*
================================================================================
Class Name
================================================================================
*/

class BP_Group_Sites {

	/*
	============================================================================
	Properties
	============================================================================
	*/

	// admin object
	public $admin;

	// activity object
	public $activity;



	/**
	 * Initialises this object
	 *
	 * @return object
	 */
	function __construct() {

		// always init admin
		$this->initialise_admin();

		// use translation files
		add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

		// add actions for plugin init on BuddyPress init
		add_action( 'bp_loaded', array( $this, 'initialise' ) );
		add_action( 'bp_loaded', array( $this, 'register_hooks' ) );
		add_action( 'bp_include', array( $this, 'register_theme_hooks' ) );

		// --<
		return $this;

	}



	//##########################################################################



	/**
	 * Actions to perform on plugin activation
	 *
	 * @return void
	 */
	public function activate() {

		// pass through to admin
		$this->admin->activate();

	}



	/**
	 * Actions to perform on plugin deactivation (NOT deletion)
	 *
	 * @return void
	 */
	public function deactivate() {

		// pass through to admin
		$this->admin->deactivate();

	}



	//##########################################################################



	/**
	 * Load translation files
	 * A good reference on how to implement translation in WordPress:
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 *
	 * @return void
	 */
	public function enable_translation() {

		// not used, as there are no translations as yet
		load_plugin_textdomain(

			// unique name
			'bpgsites',

			// deprecated argument
			false,

			// relative path to directory containing translation files
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'

		);

	}



	/**
	 * Initialise the admin object
	 *
	 * @return void
	 */
	public function initialise_admin() {

		// load our admin class file
		require( BPGSITES_PATH . 'includes/bpgsites-admin.php' );

		// init object, sending reference to this class
		$this->admin = new BP_Group_Sites_Admin( $this );

	}



	/**
	 * Do stuff on plugin init
	 *
	 * @return void
	 */
	public function initialise() {

		// load our linkage functions file
		require( BPGSITES_PATH . 'includes/bpgsites-linkage.php' );

		// load our display functions file
		require( BPGSITES_PATH . 'includes/bpgsites-display.php' );

		// load our activity functions file
		require( BPGSITES_PATH . 'includes/bpgsites-activity.php' );

		// init object
		$this->activity = new BpGroupSites_Activity;

		// load our blogs extension
		require( BPGSITES_PATH . 'includes/bpgsites-blogs.php' );

		// load our group extension
		require( BPGSITES_PATH . 'includes/bpgsites-groups.php' );

		// load our component file
		require( BPGSITES_PATH . 'includes/bp-bpgsites-component.php' );

	}



	/**
	 * Register hooks on plugin init
	 *
	 * @return void
	 */
	public function register_hooks() {

		// hooks that always need to be present...
		$this->admin->register_hooks();
		$this->activity->register_hooks();

		// register any public styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 20 );

		// register any public scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );

		// if the current blog is a group site...
		if ( bpgsites_is_groupsite( get_current_blog_id() ) ) {

			// if on front end...
			if ( ! is_admin() ) {

				// register our CommentPress scripts
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_commentpress_scripts' ), 20 );

			}

		}

	}



	/**
	 * Register theme hooks on bp include
	 *
	 * @return void
	 */
	public function register_theme_hooks() {

		// add our templates to the theme compatibility layer
		add_action( 'bp_register_theme_packages', array( $this, 'theme_compat' ) );

	}



	/**
	 * Add our templates to the theme stack
	 *
	 * @return void
	 */
	public function theme_compat() {

		// add templates dir to BuddyPress
		bp_register_template_stack( 'bpgsites_templates_dir',  16 );

	}



	/**
	 * Add our front-end stylesheets
	 *
	 * @return void
	 */
	public function enqueue_styles() {

		// if on group admin screen
		if ( bp_is_group_admin_screen( apply_filters( 'bpgsites_extension_slug', 'group-sites' ) ) ) {

			// register Select2 styles
			wp_register_style(
				'bpgsites_select2_css',
				set_url_scheme( 'http://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css' )
			);

			// enqueue styles
			wp_enqueue_style( 'bpgsites_select2_css' );

		}

		// add basic stylesheet
		wp_enqueue_style(
			'bpgsites_css',
			BPGSITES_URL . 'assets/css/bpgsites.css',
			false,
			BPGSITES_VERSION, // version
			'all' // media
		);

	}



	/**
	 * Add our front-end Javascripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {

		// only on root blog
		if ( is_multisite() AND bp_is_root_blog() ) {

			// enqueue activity stream javascript
			wp_enqueue_script(
				'bpgsites_activity_js',
				BPGSITES_URL . 'assets/js/bpgsites-activity.js',
				array( 'jquery' ),
				BPGSITES_VERSION
			);

			// if on group admin screen
			if ( bp_is_group_admin_screen( apply_filters( 'bpgsites_extension_slug', 'group-sites' ) ) ) {

				// register Select2
				wp_register_script(
					'bpgsites_select2_js',
					set_url_scheme( 'http://cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js' ),
					array( 'jquery' )
				);

				// enqueue script
				wp_enqueue_script( 'bpgsites_select2_js' );

				// enqueue group admin js
				wp_enqueue_script(
					'bpgsites_select2_custom_js',
					BPGSITES_URL . 'assets/js/bpgsites-group-admin.js',
					array( 'bpgsites_select2_js' ),
					BPGSITES_VERSION
				);

				// localisation array
				$vars = array(
					'localisation' => array(),
					'data' => array(
						'group_id' => bp_get_current_group_id(),
					),
				);

				// localise with wp function
				wp_localize_script(
					'bpgsites_select2_custom_js',
					'BuddypressGroupSitesSettings',
					$vars
				);

			}

		}

	}



	/**
	 * Add our CommentPress-specific scripts
	 *
	 * @return void
	 */
	public function enqueue_commentpress_scripts() {

		// CommentPress theme compat
		if ( function_exists( 'commentpress_get_comments_by_para' ) ) {

			// enqueue common js
			wp_enqueue_script(
				'bpgsites_cp_js',
				BPGSITES_URL . 'assets/js/bpgsites-commentpress.js',
				array( 'cp_common_js' ),
				BPGSITES_VERSION
			);

			// get vars
			$vars = array(
				'show_public' => $this->admin->option_get( 'bpgsites_public' )
			);

			// localise with wp function
			wp_localize_script( 'bpgsites_cp_js', 'BpgsitesSettings', $vars );

		}

	}



	//##########################################################################



} // class ends



// init plugin
global $bp_groupsites;
$bp_groupsites = new BP_Group_Sites;

// activation
register_activation_hook( __FILE__, array( $bp_groupsites, 'activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $bp_groupsites, 'deactivate' ) );

// will use the 'uninstall.php' method
// see: http://codex.wordpress.org/Function_Reference/register_uninstall_hook



/**
 * Returns the path to our templates directory
 *
 * @return str $path path to this plugin's templates directory
 */
function bpgsites_templates_dir() {

	// return filterable path to templates
	$path = apply_filters(
		'bpgsites_templates_dir', // hook
		BPGSITES_PATH . 'assets/templates' // path
	);

	// --<
	return $path;

}



