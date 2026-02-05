<?php
/**
 * BP Group Sites Component.
 *
 * The group sites component, for listing group sites.
 *
 * @package BP_Group_Sites
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class definition.
 *
 * @since 0.1
 */
class BP_Group_Sites_Component extends BP_Component {

	/**
	 * Start the group_sites component creation process.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Get BP reference.
		$bp = buddypress();

		// Store component ID.
		$this->id = 'bpgsites';

		/*
		 * Store component name.
		 *
		 * NOTE: Ideally we'll use BP theme compatibility - see bpgsites_load_template_filter() below.
		 *
		 * Since 14.3.0 BuddyPress says: Do not use translatable strings here as this part is set
		 * before WP's `init` hook.
		 */
		$this->name = 'Group Sites';

		// Add this component to active components.
		$bp->active_components[ $this->id ] = '1';

		// Init parent.
		parent::start(
			$this->id, // Unique ID, also used as slug.
			$this->name,
			BPGSITES_PATH,
			null // Don't need menu item in WP admin bar.
		);

		/*
		 * BuddyPress-dependent plugins are loaded too late to depend on BP_Component's
		 * hooks, so we must call the function directly.
		 */
		$this->includes();

	}

	/**
	 * Include our component's files.
	 *
	 * @since 0.1
	 *
	 * @param array $includes An array of file names, or file name chunks, to be parsed and then included.
	 */
	public function includes( $includes = [] ) {

		// Include screens file.
		include BPGSITES_PATH . 'includes/bp-bpgsites-screens.php';

	}

	/**
	 * Set up global settings for the group_sites component.
	 *
	 * @since 0.1
	 *
	 * @see BP_Component::setup_globals() for description of parameters.
	 *
	 * @param array $args See {@link BP_Component::setup_globals()}.
	 */
	public function setup_globals( $args = [] ) {

		// Get BP reference.
		$bp = buddypress();

		// Construct search string.
		$search_string = sprintf(
			/* translators: %s: The plural name for Group Sites. */
			__( 'Search %s...', 'bp-group-sites' ),
			bpgsites_get_extension_plural()
		);

		// Construct args.
		$args = [
			// Non-multisite installs don't need a top-level BP Group Sites directory, since there's only one site.
			'root_slug'     => isset( $bp->pages->{$this->id}->slug ) ? $bp->pages->{$this->id}->slug : $this->id,
			'has_directory' => true,
			'search_string' => $search_string,
		];

		// Set up the globals.
		parent::setup_globals( $args );

	}

}

// Set up the bp-group-sites component now, since this file is included on bp_loaded.
buddypress()->bpgsites = new BP_Group_Sites_Component();

/**
 * Check whether the current page is part of the BP Group Sites component.
 *
 * @since 0.1
 *
 * @return bool True if the current page is part of the BP Group Sites component.
 */
function bp_is_bpgsites_component() {

	// Is this our component?
	if ( is_multisite() && bp_is_current_component( 'bpgsites' ) ) {
		return true;
	}

	// --<
	return false;

}

/**
 * A custom load template filter for this component.
 *
 * @since 0.1
 *
 * @param str   $found_template The existing path to the template.
 * @param array $templates The array of template paths.
 * @return str $found_template The modified path to the template.
 */
function bpgsites_load_template_filter( $found_template, $templates ) {

	// Check for BP theme compatibility here?

	// Only filter the template location when we're on our component's page.
	if ( is_multisite() && bp_is_bpgsites_component() && ! bp_current_action() ) {

		// We've got to find the template manually.
		foreach ( (array) $templates as $template ) {
			if ( file_exists( get_stylesheet_directory() . '/' . $template ) ) {
				$filtered_templates[] = get_stylesheet_directory() . '/' . $template;
			} elseif ( is_child_theme() && file_exists( get_template_directory() . '/' . $template ) ) {
				$filtered_templates[] = get_template_directory() . '/' . $template;
			} else {
				$filtered_templates[] = BPGSITES_PATH . 'assets/templates/' . $template;
			}
		}

		// Should be one by now.
		$found_template = $filtered_templates[0];

		// --<
		return apply_filters( 'bpgsites_load_template_filter', $found_template );

	}

	// --<
	return $found_template;

}

// Add filter for the above.
// NOTE: adding this disables BP_Group_Sites_Theme_Compat.
add_filter( 'bp_located_template', 'bpgsites_load_template_filter', 10, 2 );

/**
 * Load our loop when requested.
 *
 * @since 0.1
 */
function bpgsites_object_template_loader() {

	// Bail if not a POST action.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
	if ( 'POST' !== strtoupper( $method ) ) {
		return;
	}

	// Bail if no object passed.
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Missing
	if ( empty( $_POST['object'] ) ) {
		return;
	}

	// Sanitize the object.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$object = sanitize_title( wp_unslash( $_POST['object'] ) );

	// Bail if object is not an active component to prevent arbitrary file inclusion.
	if ( ! bp_is_active( $object ) ) {
		return;
	}

	// Enable visit button.
	if ( bp_is_active( 'bpgsites' ) ) {
		add_action( 'bp_directory_blogs_actions', 'bp_blogs_visit_blog_button' );
	}

	/*
	 * AJAX requests happen too early to be seen by bp_update_is_directory()
	 * so we do it manually here to ensure templates load with the correct
	 * context. Without this check, templates will load the 'single' version
	 * of themselves rather than the directory version.
	 */
	if ( ! bp_current_action() ) {
		bp_update_is_directory( true, bp_current_component() );
	}

	// Locate the object template.
	bp_get_template_part( "$object/$object-loop" );
	exit();

}

// Add ajax actions for the above.
add_action( 'wp_ajax_bpgsites_filter', 'bpgsites_object_template_loader' );
add_action( 'wp_ajax_nopriv_bpgsites_filter', 'bpgsites_object_template_loader' );
