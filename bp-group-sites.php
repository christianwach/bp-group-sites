<?php
/*
--------------------------------------------------------------------------------
Plugin Name: BP Group Sites
Description: In WordPress Multisite, create a many-to-many replationship between BuddyPress Groups and WordPress Sites. 
Version: 0.1
Author: Christian Wach
Author URI: http://haystack.co.uk
Plugin URI: http://haystack.co.uk
Network: true
--------------------------------------------------------------------------------
*/



// set our version here
define( 'BPGSITES_VERSION', '0.1' );

// store reference to this file
if ( !defined( 'BPGSITES_FILE' ) ) {
	define( 'BPGSITES_FILE', __FILE__ );
}

// store URL to this plugin's directory
if ( !defined( 'BPGSITES_URL' ) ) {
	define( 'BPGSITES_URL', plugin_dir_url( BPGSITES_FILE ) );
}
// store PATH to this plugin's directory
if ( !defined( 'BPGSITES_PATH' ) ) {
	define( 'BPGSITES_PATH', plugin_dir_path( BPGSITES_FILE ) );
}

// set site option prefix
define( 'BPGSITES_PREFIX', 'bpgsites_blog_groups_' );

// set group option name
define( 'BPGSITES_OPTION', 'bpgsites_group_blogs' );

// set comment meta key
define( 'BPGSITES_COMMENT_META_KEY', 'bpgsites_group_id' );




/*
================================================================================
Class Name
================================================================================
*/

class BpGroupSites {

	/*
	============================================================================
	Properties
	============================================================================
	*/

	// plugin options
	public $options = array();
	
	// activity object
	public $activity;
	
	
	
	/** 
	 * @description: initialises this object
	 * @return object
	 */
	function __construct() {
	
		// use translation files
		add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );
		
		// add actions for plugin init on BuddyPress init
		add_action( 'bp_loaded', array( $this, 'initialise' ) );
		add_action( 'bp_loaded', array( $this, 'register_hooks' ) );
		
		// --<
		return $this;

	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * @description: actions to perform on plugin activation
	 * @return nothing
	 */
	public function activate() {
	
		// are we re-activating?
		if ( $this->site_option_get( 'bpgsites_installed', 'false' ) === 'true' ) {
		
			// yes, kick out
			return;
			
		}
		
		// save options array
		$this->site_option_set( 'bpgsites_options', $this->options );
		
		// set installed flag
		$this->site_option_set( 'bpgsites_installed', 'true' );

	}
	
	
	
	/**
	 * @description: actions to perform on plugin deactivation (NOT deletion)
	 * @return nothing
	 */
	public function deactivate() {
		
		// we'll delete our options in 'uninstall.php'
		// but for testing let's delete them here
		delete_site_option( 'bpgsites_options' );
		delete_site_option( 'bpgsites_installed' );

	}
	
	
		
	//##########################################################################
	
	
	
	/** 
	 * @description: load translation files
	 * A good reference on how to implement translation in WordPress:
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
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
	 * @description: do stuff on plugin init
	 * @return nothing
	 */
	public function initialise() {
		
		// get options array, if it exists
		$this->options = $this->site_option_get( 'bpgsites_options', array() );
		
		// load our linkage functions file
		require( BPGSITES_PATH . 'bpgsites-linkage.php' );
	
		// load our display functions file
		require( BPGSITES_PATH . 'bpgsites-display.php' );
	
		// load our activity functions file
		require( BPGSITES_PATH . 'bpgsites-activity.php' );
		
		// init object
		$this->activity = new BpGroupSites_Activity;
	
		// load our blogs extension
		require( BPGSITES_PATH . 'bpgsites-blogs-extension.php' );
	
		// load our group extension
		require( BPGSITES_PATH . 'bpgsites-group-extension.php' );
	
	}
	
	
		
	/**
	 * @description: register hooks on plugin init
	 * @return nothing
	 */
	public function register_hooks() {
	
		// hooks that always need to be present...
		$this->activity->register_hooks();
		
		// add something
		//add_action( 'xxx', 'yyy' );
		
		// if the current blog is a group site...
		if ( bpgsites_is_groupsite( get_current_blog_id() ) ) {
			
			// if on front end...
			if ( !is_admin() ) {
			
				// register any public scripts
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
				
				// register any public styles
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 20 );
			
			}
		
		}
		
	}
	
	
		
	/**
	 * @description: add our javascripts
	 * @return nothing
	 */
	public function enqueue_scripts() {
	
		// CommentPress theme compat
		if ( function_exists( 'commentpress_get_comments_by_para' ) ) {
		
			// enqueue common js
			wp_enqueue_script(
	
				'bpgsites_cp_js', 
				BPGSITES_URL . 'assets/js/bpgsites.js',
				array( 'cp_common_js' ),
				BPGSITES_VERSION
	
			);
	
		}
		
	}
	
	
	
	/**
	 * @description: add our stylesheets
	 * @return nothing
	 */
	public function enqueue_styles() {
	
		// add basic stylesheet
		wp_enqueue_style(
		
			'bpgsites_css', 
			BPGSITES_URL . 'assets/css/bpgsites.css',
			false,
			BPGSITES_VERSION, // version
			'all' // media
			
		);
		
	}
	
	
	
	//##########################################################################
	
	
	
	/** 
	 * @description: test existence of a specified site option
	 */
	function site_option_exists( $option_name = '' ) {
	
		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_wpms_exists()', 'bpgsites' ) );
		}
	
		// get option with unlikely default
		if ( $this->site_option_get( $option_name, 'fenfgehgefdfdjgrkj' ) == 'fenfgehgefdfdjgrkj' ) {
		
			// no
			return false;
		
		} else {
		
			// yes
			return true;
		
		}
		
	}
	
	
	
	/** 
	 * @description: return a value for a specified site option
	 */
	function site_option_get( $option_name = '', $default = false ) {
	
		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to site_option_get()', 'bpgsites' ) );
		}
	
		// get option
		return get_site_option( $option_name, $default );
		
	}
	
	
	
	/** 
	 * @description: set a value for a specified site option
	 */
	function site_option_set( $option_name = '', $value = '' ) {
	
		// test for null
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to site_option_set()', 'bpgsites' ) );
		}
	
		// set option
		return update_site_option( $option_name, $value );
		
	}
	
	
	
} // class ends



// init plugin
global $bp_groupsites;
$bp_groupsites = new BpGroupSites;

// activation
register_activation_hook( __FILE__, array( $bp_groupsites, 'activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $bp_groupsites, 'deactivate' ) );

// will use the 'uninstall.php' method
// see: http://codex.wordpress.org/Function_Reference/register_uninstall_hook



