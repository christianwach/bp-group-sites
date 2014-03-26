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

// set linked groups option name
define( 'BPGSITES_LINKED', 'bpgsites_linked_groups' );

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
	 * @description: initialises this object
	 * @return object
	 */
	function __construct() {
	
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
	 * @description: actions to perform on plugin activation
	 * @return nothing
	 */
	public function activate() {
	
		// pass through to admin
		$this->admin->activate();

	}
	
	
	
	/**
	 * @description: actions to perform on plugin deactivation (NOT deletion)
	 * @return nothing
	 */
	public function deactivate() {
		
		// pass through to admin
		$this->admin->deactivate();

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
		
		// load our linkage functions file
		require( BPGSITES_PATH . 'includes/bpgsites-linkage.php' );
	
		// load our display functions file
		require( BPGSITES_PATH . 'includes/bpgsites-display.php' );
	
		// load our admin class file
		require( BPGSITES_PATH . 'includes/bpgsites-admin.php' );
		
		// init object, sending reference to this class
		$this->admin = new BP_Group_Sites_Admin( $this );
	
		// load our activity functions file
		require( BPGSITES_PATH . 'includes/bpgsites-activity.php' );
		
		// init object
		$this->activity = new BpGroupSites_Activity;
	
		// load our blogs extension
		require( BPGSITES_PATH . 'includes/bpgsites-blogs-extension.php' );
	
		// load our group extension
		require( BPGSITES_PATH . 'includes/bpgsites-group-extension.php' );
	
		// load our component file
		require( BPGSITES_PATH . 'includes/bp-bpgsites-component.php' );
	
	}
	
	
		
	/**
	 * @description: register hooks on plugin init
	 * @return nothing
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
			if ( !is_admin() ) {
			
				// register our CommentPress scripts
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_commentpress_scripts' ), 20 );
				
			}
		
		}
		
	}
	
	
		
	/**
	 * @description: register theme hooks on bp include
	 * @return nothing
	 */
	public function register_theme_hooks() {
	
		// add our templates to the theme compatibility layer
		add_action( 'bp_register_theme_packages', array( $this, 'theme_compat' ) );
		//add_filter( 'pre_option__bp_theme_package_id', array( $this, 'package_id' ) );
		//add_filter( 'bp_get_template_part', array( $this, 'template_part' ), 10, 3 );
		//add_filter( 'bp_get_template_stack', array( $this, 'template_stack' ), 10, 1 );
		
	}
	
	
	
	/**
	 * @description: add our templates to the theme stack
	 * @return nothing
	 */
	public function theme_compat() {
	
		//print_r( 'theme_compat' ); die();
		
		/*
		bp_register_theme_package( array(
			'id'      => 'bpgsites',
			'name'    => __( 'BuddyPress Default', 'buddypress' ),
			'version' => bp_get_version(),
			'dir'     => trailingslashit( $this->themes_dir . '/bp-legacy' ),
			'url'     => trailingslashit( $this->themes_url . '/bp-legacy' )
		) );
		*/
		
		// add templates dir to BuddyPress
		bp_register_template_stack( 'bpgsites_templates_dir',  16 );
		
	}
	
	
	
	/**
	 * @description: returns the unique package ID for our plugin's templates
	 * @return str $package_id unique package ID
	 */
	public function package_id( $package_id ) {
		
		// return unique package ID
		return 'bpgsites';
		
	}
	
	
	
	/**
	 * @description: returns our template part
	 * @return array $template path to required template
	 */
	public function template_part( $templates, $slug, $name ) {
		
		print_r( 'template_part' ); die();
		
		// kick out if not our slug
		if ( 'texts' != $slug ) { return $templates; }
		
		// --<
		return array( 'bpgsites/index.php' );
		
	}
	
	
	
	/**
	 * @description: returns our template stack
	 * @return array $template path to required template
	 */
	public function template_stack( $template_stack ) {
		
		print_r( array( 'template_stack' => $template_stack ) ); die();
		
		// kick out if not our slug
		if ( 'texts' != $slug ) { return $templates; }
		
		// --<
		return array( 'bpgsites/index.php' );
		
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
	
	
	
	/**
	 * @description: add our global scripts
	 * @return nothing
	 */
	public function enqueue_scripts() {
	
		// only on root blog
		if ( is_multisite() AND bp_is_root_blog() ) {
		
			// enqueue common js
			wp_enqueue_script(

				'bpgsites_js', 
				BPGSITES_URL . 'assets/js/bpgsites.js',
				array( 'jquery' ),
				BPGSITES_VERSION

			);
		
		}
	
	}
	
	
	
	/**
	 * @description: add our CommentPress-specific scripts
	 * @return nothing
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
 * @description: returns the path to our templates directory
 * @return str $path path to this plugin's templates directory
 */
function bpgsites_templates_dir() {
	
	// return filterable path to templates
	$path = apply_filters(
		'bpgsites_templates_dir', // hook
		BPGSITES_PATH . 'assets/templates' // path
	);
	
	/*
	print_r( array( 
		'method' => 'bpgsites_theme_dir',
		'path' => $path,
	) ); die();
	*/
	
	// --<
	return $path;
	
}



