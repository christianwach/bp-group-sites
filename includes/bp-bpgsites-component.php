<?php

/**
 * BP Group Sites Component
 *
 * The group sites component, for listing group sites.
 */

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;



/**
 * Class definition
 */
class BP_Group_Sites_Component extends BP_Component {



	/**
	 * Start the group_sites component creation process
	 *
	 * @return void
	 */
	function __construct() {

		// get BP reference
		$bp = buddypress();

		// store component ID
		$this->id = 'bpgsites';

		// store component name
		// NOTE: ideally we'll use BP theme compatibility - see bpgsites_load_template_filter() below
		$this->name = apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bpgsites' ) );

		// add this component to active components
		$bp->active_components[$this->id] = '1';

		// init parent
		parent::start(
			$this->id, // unique ID, also used as slug
			$this->name,
			BPGSITES_PATH,
			null // don't need menu item in WP admin bar
		);

		/**
		 * BuddyPress-dependent plugins are loaded too late to depend on BP_Component's
		 * hooks, so we must call the function directly.
		 */
		 $this->includes();

	}



	/**
	 * Include our component's files
	 *
	 * @return void
	 */
	public function includes( $includes = array() ) {

		// include screens file
		include( BPGSITES_PATH . 'includes/bp-bpgsites-screens.php' );

	}



	/**
	 * Set up global settings for the group_sites component.
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 * @return void
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
			// non-multisite installs don't need a top-level BP Group Sites directory, since there's only one site
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
 * Check whether the current page is part of the BP Group Sites component.
 *
 * @return bool True if the current page is part of the BP Group Sites component.
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
 * A custom load template filter for this component
 *
 * @return str $found_template The existing path to the template
 * @return array $templates The array of template paths
 * @return str $found_template The modified path to the template
 */
function bpgsites_load_template_filter( $found_template, $templates ) {

	// check for BP theme compatibility here?

	// only filter the template location when we're on our component's page
	if ( is_multisite() && bp_is_bpgsites_component() && ! bp_current_action() ) {

		// we've got to find the template manually
		foreach ( (array) $templates as $template ) {
			if ( file_exists( get_stylesheet_directory() . '/' . $template ) ) {
				$filtered_templates[] = get_stylesheet_directory() . '/' . $template;
			} elseif ( is_child_theme() && file_exists( get_template_directory() . '/' . $template ) ) {
				$filtered_templates[] = get_template_directory() . '/' . $template;
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
// NOTE: adding this disables BP_Group_Sites_Theme_Compat
add_filter( 'bp_located_template', 'bpgsites_load_template_filter', 10, 2 );



/**
 * Load our loop when requested
 *
 * @return void
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



