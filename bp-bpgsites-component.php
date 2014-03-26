<?php

/**
 * BP Group Sites Component
 *
 * The group sites component, for listing group sites.
 */

// exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;



/**
 * Class definition
 */
class BP_Group_Sites_Component extends BP_Component {
	
	
	
	/**
	 * Start the group_sites component creation process.
	 */
	function __construct() {
		
		// get BP reference
		$bp = buddypress();

		// store component ID
		$this->id = 'bpgsites';
		//print_r( $this->id ); die();
		
		// store component name
		$this->name = apply_filters( 'bpgsites_extension_title', __( 'Group Sites', 'bpgsites' ) );
		
		// add this component to active components
		$bp->active_components[$this->id] = '1';
		
		// init parent
		parent::start(
			$this->id, // unique ID, also used as slug
			$this->name,
			BPGSITES_PATH,
			null // don't need menu item in WP admin bar
		);
		
	}
	
	
	
	/**
	 * Set up global settings for the group_sites component.
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = array() ) {
	
		// get BP reference
		$bp = buddypress();
		
		// construct search string
		$search_string = sprintf( 
			__( 'Search %s...', 'bpgsites' ),
			apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bpgsites' ) )
		);
		
		// construct args
		$args = array(
			// non-multisite installs don't need a top-level Group Sites directory, since there's only one site
			'root_slug'             => isset( $bp->pages->{$this->id}->slug ) ? $bp->pages->{$this->id}->slug : $this->id,
			'has_directory'         => true, 
			'search_string'         => $search_string,
		);

		// set up the globals
		parent::setup_globals( $args );
		
	}
	
	
	
} // class ends



// set up the bp-group-sites component now, since this file is included on bp_loaded
buddypress()->bpgsites = new BP_Group_Sites_Component();



/**
 * Check whether the current page is part of the Group Sites component.
 *
 * @return bool True if the current page is part of the Group Sites component.
 */
function bp_is_bpgsites_component() {

	// is this our component?
	if ( is_multisite() AND bp_is_current_component( 'bpgsites' ) ) {
	
		// yep
		return true;
		
	}
	
	// --<
	return false;
	
}



/**
 * Load the top-level Group Sites directory.
 */
function bpgsites_screen_index() {
	
	// is this our component page?
	if ( is_multisite() && bp_is_bpgsites_component() && !bp_current_action() ) {
		
		// make sure BP knows that it's our directory
		bp_update_is_directory( true, 'bpgsites' );
		
		// allow plugins to handle this
		do_action( 'bpgsites_screen_index' );
		
		// load our directory template
		bp_core_load_template( apply_filters( 'bpgsites_screen_index', 'bpgsites/index' ) );

	}
	
}

// add action for the above
add_action( 'bp_screens', 'bpgsites_screen_index', 20 );



/**
 * A custom load template filter for this component.
 */
function bpgsites_load_template_filter( $found_template, $templates ) {

	// only filter the template location when we're on our component's page
	if ( is_multisite() && bp_is_bpgsites_component() && !bp_current_action() ) {
	
		// we've got to find the template manually
		foreach ( (array) $templates as $template ) {
			if ( file_exists( STYLESHEETPATH . '/' . $template ) ) {
				$filtered_templates[] = STYLESHEETPATH . '/' . $template;
			} elseif ( is_child_theme() && file_exists( TEMPLATEPATH . '/' . $template ) ) {
				$filtered_templates[] = TEMPLATEPATH . '/' . $template;
			} else {
				$filtered_templates[] = BPGSITES_PATH . 'assets/templates/' . $template;
			}
		}
		
		// should be one by now
		$found_template = $filtered_templates[0];
		
		// --<
		return apply_filters( 'bpgsites_load_template_filter', $found_template );
	
	}
	
	// --<
	return $found_template;

}

// add filter for the above
add_filter( 'bp_located_template', 'bpgsites_load_template_filter', 10, 2 );



/** 
 * Load our loop when requested
 */
function bpgsites_object_template_loader() {
	
	// Bail if not a POST action
	if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	// Bail if no object passed
	if ( empty( $_POST['object'] ) ) {
		return;
	}
	
	// Sanitize the object
	$object = sanitize_title( $_POST['object'] );

	// Bail if object is not an active component to prevent arbitrary file inclusion
	if ( ! bp_is_active( $object ) ) {
		return;
	}

	//trigger_error( print_r( $_POST, true ), E_USER_ERROR ); die();
	
	// enable visit button
	if ( bp_is_active( 'bpgsites' ) ) {
		add_action( 'bp_directory_blogs_actions',  'bp_blogs_visit_blog_button' );
	}
	
 	/**
	 * AJAX requests happen too early to be seen by bp_update_is_directory()
	 * so we do it manually here to ensure templates load with the correct
	 * context. Without this check, templates will load the 'single' version
	 * of themselves rather than the directory version.
	 */
	if ( ! bp_current_action() ) {
		bp_update_is_directory( true, bp_current_component() );
	}
	
	// Locate the object template
	bp_get_template_part( "$object/$object-loop" );
	exit();

}

// add ajax actions for the above
add_action( 'wp_ajax_bpgsites_filter', 'bpgsites_object_template_loader' );
add_action( 'wp_ajax_nopriv_bpgsites_filter', 'bpgsites_object_template_loader' );



