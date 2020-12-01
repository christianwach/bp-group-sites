<?php

/**
 * BP Group Sites Screens.
 *
 * @since 0.1
 */



// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}



/** Theme Compatability *******************************************************/

/**
 * The main theme compat class for BP Group Sites.
 *
 * This class sets up the necessary theme compatability actions to safely output
 * group template parts to the_title and the_content areas of a theme.
 *
 * @since 0.1
 */
class BP_Group_Sites_Theme_Compat {



	/**
	 * Set up theme compatibility for the BP Group Sites component.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Add theme comaptibility action.
		add_action( 'bp_setup_theme_compat', [ $this, 'is_bpgsites' ] );

	}



	/**
	 * Are we looking at something that needs BP Group Sites theme compatability?
	 *
	 * @since 0.1
	 */
	public function is_bpgsites() {

		// Bail if not looking at a group site component page.
		if ( ! bp_is_bpgsites_component() ) {
			return;
		}

		// BP Group Sites Directory.
		if ( is_multisite() && ! bp_current_action() ) {

			// Set is_directory flag.
			bp_update_is_directory( true, 'bpgsites' );

			// Inform plugins.
			do_action( 'bp_blogs_screen_index' );

			// Add hooks.
			add_filter( 'bp_get_buddypress_template',                [ $this, 'directory_template_hierarchy' ] );
			add_action( 'bp_template_include_reset_dummy_post_data', [ $this, 'directory_dummy_post' ] );
			add_filter( 'bp_replace_the_content',                    [ $this, 'directory_content'    ] );

		}

	}



	/**
	 * Add template hierarchy to theme compat for the BP Group Sites directory page.
	 *
	 * @since 0.1
	 *
	 * @param array $templates The templates from bp_get_theme_compat_templates().
	 * @return array $templates Array of custom templates to look for.
	 */
	public function directory_template_hierarchy( $templates ) {

		// Set up our templates based on priority.
		$new_templates = apply_filters( 'bp_template_hierarchy_bpgsites_directory', [
			'bpgsites/index.php',
		] );

		// Merge new templates with existing stack.
		// @see bp_get_theme_compat_templates()
		$templates = array_merge( (array) $new_templates, $templates );

		// --<
		return $templates;

	}



	/**
	 * Update the global $post with directory data.
	 *
	 * @since 0.1
	 */
	public function directory_dummy_post() {

		// Set title.
		$title = apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) );

		// Create dummy post.
		bp_theme_compat_reset_post( [
			'ID'             => 0,
			'post_title'     => $title,
			'post_author'    => 0,
			'post_date'      => 0,
			'post_content'   => '',
			'post_type'      => 'bp_bpgsites',
			'post_status'    => 'publish',
			'is_page'        => true,
			'comment_status' => 'closed',
		] );

	}



	/**
	 * Filter the_content with the BP Group Sites index template part.
	 *
	 * @since 0.1
	 *
	 * @return str $buffer The buffered template part.
	 */
	public function directory_content() {

		// --<
		return bp_buffer_template_part( 'bpgsites/index', null, false );

	}



} // Class ends



// Init
new BP_Group_Sites_Theme_Compat();



//==============================================================================



/**
 * Load the top-level BP Group Sites directory.
 *
 * @since 0.1
 */
function bpgsites_screen_index() {

	// Is this our component page?
	if ( is_multisite() && bp_is_bpgsites_component() && ! bp_current_action() ) {

		// Make sure BP knows that it's our directory.
		bp_update_is_directory( true, 'bpgsites' );

		// Allow plugins to handle this.
		do_action( 'bpgsites_screen_index' );

		// Load our directory template.
		bp_core_load_template( apply_filters( 'bpgsites_screen_index', 'bpgsites/index' ) );

	}

}

// Add action for the above.
add_action( 'bp_screens', 'bpgsites_screen_index', 20 );



