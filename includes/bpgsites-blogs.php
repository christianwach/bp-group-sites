<?php
/**
 * BP Group Sites Blogs functions.
 *
 * Functions that relate to Group Site blogs live here.
 *
 * @package BP_Group_Sites
 * @since 0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query only Group Site blogs.
 *
 * @since 0.1
 *
 * @param array $args Array of arguments with which the query was configured.
 * @return bool $has_blogs Whether or not our modified query has found blogs.
 */
function bpgsites_has_blogs( $args = '' ) {

	// Remove default exclusion filter.
	remove_filter( 'bp_after_has_blogs_parse_args', 'bpgsites_pre_filter_groupsites', 30, 1 );

	// User filtering.
	$user_id = 0;
	if ( bp_displayed_user_id() ) {
		$user_id = bp_displayed_user_id();
	}

	// Do we want all possible group sites?
	if ( isset( $args['possible_sites'] ) && true === $args['possible_sites'] ) {

		// Get all possible group sites.
		$groupsites = bpgsites_get_all_possible_groupsites();

	} else {

		// Get just groupsite IDs.
		$groupsites = bpgsites_get_groupsites();

	}

	// Check for a passed group ID.
	if ( isset( $args['group_id'] ) && ! empty( $args['group_id'] ) ) {

		// Get groupsite IDs for this group.
		$groupsites = bpgsites_get_blogs_by_group_id( $args['group_id'] );

	}

	// If empty, create array guaranteed to produce no result.
	if ( empty( $groupsites ) ) {
		$groupsites = [ PHP_INT_MAX ];
	}

	// Check for and use search terms.
	$search_terms = ! empty( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : false;

	// Declare defaults.
	$defaults = [
		'type'         => 'active',
		'page'         => 1,
		'per_page'     => 20,
		'max'          => false,
		'page_arg'     => 'bpage',
		'user_id'      => $user_id,
		'include_blog_ids'  => $groupsites,
		'search_terms' => $search_terms,
		'update_meta_cache' => true,
	];

	// Parse args.
	$parsed_args = bp_parse_args( $args, $defaults, 'has_blogs' );

	// Set per_page to maximum if max is enforced.
	if ( ! empty( $parsed_args['max'] ) && ( (int) $parsed_args['per_page'] > (int) $parsed_args['max'] ) ) {
		$parsed_args['per_page'] = (int) $parsed_args['max'];
	}

	// Re-query with our params.
	$has_blogs = bp_has_blogs( $parsed_args );

	// Add exclusion filter back as default.
	add_filter( 'bp_after_has_blogs_parse_args', 'bpgsites_pre_filter_groupsites', 30, 1 );

	// Fallback.
	return $has_blogs;

}

/**
 * Intercept blogs query and manage display of blogs.
 *
 * @since 0.1
 *
 * @param array $args The existing arguments used for the query.
 * @return array $args The modified arguments used for the query.
 */
function bpgsites_pre_filter_groupsites( $args ) {

	// Get groupsite IDs.
	$groupsites = bpgsites_get_groupsites();

	// Get all blogs via BP_Blogs_Blog.
	$all = BP_Blogs_Blog::get_all();

	// Init ID array.
	$blog_ids = [];

	if ( is_array( $all['blogs'] ) && count( $all['blogs'] ) > 0 ) {
		foreach ( $all['blogs'] as $blog ) {
			$blog_ids[] = $blog->blog_id;
		}
	}

	// Let's exclude.
	$groupsites_excluded = array_merge( array_diff( $blog_ids, $groupsites ) );

	// Do we have an array of blogs to include?
	if ( isset( $args['include_blog_ids'] ) && ! empty( $args['include_blog_ids'] ) ) {

		// Convert from comma-delimited if needed.
		$include_blog_ids = array_filter( wp_parse_id_list( $args['include_blog_ids'] ) );

		// Exclude groupsites.
		$args['include_blog_ids'] = array_merge( array_diff( $include_blog_ids, $groupsites ) );

		// If we have none left, set as false.
		if ( count( $args['include_blog_ids'] ) === 0 ) {
			$args['include_blog_ids'] = false;
		}

	} else {

		// Exclude groupsites.
		$args['include_blog_ids'] = $groupsites_excluded;

	}

	// --<
	return $args;

}
// Only on front end OR ajax.
if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

	// Use bp_parse_args post-parse filter. Requires BP 2.0.
	add_filter( 'bp_after_has_blogs_parse_args', 'bpgsites_pre_filter_groupsites', 30, 1 );

}

/**
 * Override the total number of sites, excluding groupsites.
 *
 * @since 0.1
 *
 * @return int $filtered_count The filtered total number of BuddyPress Groups.
 */
function bpgsites_filter_total_blog_count() {

	// Remove filter to prevent recursion.
	remove_filter( 'bp_get_total_blog_count', 'bpgsites_filter_total_blog_count', 50 );

	// Get actual count.
	$actual_count = bp_blogs_total_blogs();

	// Get groupsites.
	$groupsites = bpgsites_total_blogs();

	// Calculate.
	$filtered_count = $actual_count - $groupsites;

	// Add filter again.
	add_filter( 'bp_get_total_blog_count', 'bpgsites_filter_total_blog_count', 50 );

	// --<
	return $filtered_count;

}

// Only on front end OR ajax.
if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

	// Add filter for the above.
	add_filter( 'bp_get_total_blog_count', 'bpgsites_filter_total_blog_count', 50 );

}

/**
 * Override the total number of sites for a user, excluding groupsites.
 *
 * @since 0.1
 *
 * @param int $count The total number of sites for a user.
 * @return int $filtered_count The filtered total number of blogs for a user.
 */
function bpgsites_filter_total_blog_count_for_user( $count ) {

	// Get user ID if none passed.
	$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

	// Get working groupsites for this user.
	$groupsite_count = bpgsites_get_total_blog_count_for_user( $user_id );

	// Calculate.
	$filtered_count = $count - $groupsite_count;

	// --<
	return $filtered_count;

}

// Only on front end OR ajax.
if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {

	// Add filter for the above, before BP applies its number formatting.
	add_filter( 'bp_get_total_blog_count_for_user', 'bpgsites_filter_total_blog_count_for_user', 8, 1 );

}

// =============================================================================
// Functions which may only be used in the loop.
// =============================================================================

/**
 * Copied from bp_blogs_pagination_count() and amended.
 *
 * @since 0.1
 */
function bpgsites_blogs_pagination_count() {
	global $blogs_template;

	$start_num = intval( ( $blogs_template->pag_page - 1 ) * $blogs_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $blogs_template->pag_num - 1 ) > $blogs_template->total_blog_count ) ? $blogs_template->total_blog_count : $start_num + ( $blogs_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $blogs_template->total_blog_count );

	// Get singular name.
	$singular = strtolower( apply_filters( 'bpgsites_extension_name', __( 'site', 'bp-group-sites' ) ) );

	// Get plural name.
	$plural = strtolower( apply_filters( 'bpgsites_extension_plural', __( 'sites', 'bp-group-sites' ) ) );

	// We need to override the singular name.
	echo sprintf(
		/* translators: 1: The singular name for Group Sites, 2: Starting page number, 3: Ending page number, 4: Total number of pages, 5: The plural name for Group Sites. */
		__( 'Viewing %1$s %2$s to %3$s (of %4$s %5$s)', 'bp-group-sites' ),
		$singular,
		$from_num,
		$to_num,
		$total,
		$plural
	);

}

/**
 * Get the total number of groupsites being tracked.
 * Copied from bp_total_blogs() and amended.
 *
 * @since 0.1
 *
 * @return int $count Total blog count.
 */
function bpgsites_total_blogs() {

	// Get from cache if possible.
	$count = wp_cache_get( 'bpgsites_groupsites', 'bpgsites' );
	if ( ! $count ) {

		// Use function.
		$groupsites = bpgsites_get_groupsites();

		// Get total.
		$count = bp_core_number_format( count( $groupsites ) );

		// Stash it.
		wp_cache_set( 'bpgsites_groupsites', $count, 'bpgsites' );

	}

	// --<
	return $count;

}

/**
 * Output the total number of groupsites on the site.
 *
 * @since 0.1
 */
function bpgsites_total_blog_count() {
	echo bpgsites_get_total_blog_count();
}

/**
 * Return the total number of groupsites on the site.
 *
 * @since 0.1
 *
 * @return int Total number of groupsites.
 */
function bpgsites_get_total_blog_count() {
	return apply_filters( 'bpgsites_get_total_blog_count', bpgsites_total_blogs() );
}

// Format number that gets returned.
add_filter( 'bpgsites_get_total_blog_count', 'bp_core_number_format' );

/**
 * Get the total number of groupsites for a user
 * Copied from bp_blogs_total_blogs_for_user() and amended.
 *
 * @since 0.1
 *
 * @param int $user_id The numeric ID of a user.
 * @return int $count Total blog count for a user.
 */
function bpgsites_total_blogs_for_user( $user_id = 0 ) {

	// Get user ID if none passed.
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	$count = wp_cache_get( 'bpgsites_total_blogs_for_user_' . $user_id, 'bpgsites' );
	if ( ! $count ) {

		// Get groupsites for this user - kind of meaningless, so empty.
		$blogs = [];

		// Get count.
		$count = bp_core_number_format( count( $blogs ) );

		// Stash it.
		wp_cache_set( 'bpgsites_total_blogs_for_user_' . $user_id, $count, 'bpgsites' );

	}

	// --<
	return $count;

}

/**
 * Output the total number of working blogs for a user.
 *
 * @since 0.1
 *
 * @param int $user_id The numeric ID of a user.
 */
function bpgsites_total_blog_count_for_user( $user_id = 0 ) {
	echo bpgsites_get_total_blog_count_for_user( $user_id );
}

/**
 * Return the total number of working blogs for this user.
 *
 * @since 0.1
 *
 * @param int $user_id The numeric ID of a user.
 * @return int Total number of working blogs for this user.
 */
function bpgsites_get_total_blog_count_for_user( $user_id = 0 ) {
	return apply_filters( 'bpgsites_get_total_blog_count_for_user', bpgsites_total_blogs_for_user( $user_id ) );
}

// Format number that gets returned.
add_filter( 'bpgsites_get_total_blog_count_for_user', 'bp_core_number_format' );

/**
 * For a blog in the loop, check if it is associated with the current group.
 *
 * @since 0.1
 *
 * @return bool Whether or not the blog is in the group.
 */
function bpgsites_is_blog_in_group() {

	// Get groups for this blog.
	$groups = bpgsites_get_groups_by_blog_id( bp_get_blog_id() );

	// Init return.
	$return = false;

	// Sanity check.
	if ( is_array( $groups ) && count( $groups ) > 0 ) {

		// Is the current group in the array?
		if ( in_array( bp_get_current_group_id(), $groups ) ) {
			$return = true;
		}

	}

	// --<
	return apply_filters( 'bpgsites_is_blog_in_group', $return );

}

/**
 * Get the text value of a submit button.
 *
 * @since 0.1
 */
function bpgsites_admin_button_value() {

	// Is this blog already associated?
	if ( bpgsites_is_blog_in_group() ) {
		echo __( 'Remove', 'bp-group-sites' );
	} else {
		echo __( 'Add', 'bp-group-sites' );
	}

}

/**
 * Get the action of a submit button.
 *
 * @since 0.1
 */
function bpgsites_admin_button_action() {

	// Is this blog already associated?
	if ( bpgsites_is_blog_in_group() ) {
		echo 'remove';
	} else {
		echo 'add';
	}

}

/**
 * Output the group sites component root slug.
 *
 * @since 0.1
 *
 * @uses bpgsites_get_root_slug()
 */
function bpgsites_root_slug() {
	echo bpgsites_get_root_slug();
}

/**
 * Return the group sites component root slug.
 *
 * @since 0.1
 *
 * @return string The 'blogs' root slug.
 */
function bpgsites_get_root_slug() {
	return apply_filters( 'bpgsites_get_root_slug', buddypress()->bpgsites->root_slug );
}

// =============================================================================
// Functions which enable loop compatibility with CommentPress "Site Image".
// =============================================================================

/**
 * Capture "Site Image" uploads and store.
 *
 * @since 0.1
 *
 * @param mixed|array $old_value The previous value.
 * @param mixed|array $new_value The new value.
 */
function bpgsites_commentpress_site_image( $old_value, $new_value ) {

	// Get current blog ID.
	$blog_id = get_current_blog_id();

	// Is this a group site?
	if ( bpgsites_is_groupsite( $blog_id ) ) {

		// Access object.
		global $bp_groupsites;

		// Create option if it doesn't exist.
		if ( ! $bp_groupsites->admin->option_exists( 'bpgsites_bloginfo' ) ) {
			$bp_groupsites->admin->option_set( 'bpgsites_bloginfo', [] );
			$bp_groupsites->admin->options_save();
		}

		// Do we have a site image?
		if ( ! empty( $new_value['cp_site_image'] ) ) {

			// We should get the attachment ID.
			$attachment_id = $new_value['cp_site_image'];

			// Get the attachment data.
			$attachment_thumb = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
			$attachment_medium = wp_get_attachment_image_src( $attachment_id, 'medium' );
			$attachment_large = wp_get_attachment_image_src( $attachment_id, 'large' );
			$attachment_full = wp_get_attachment_image_src( $attachment_id, 'full' );

			// Get existing option.
			$existing = $bp_groupsites->admin->option_get( 'bpgsites_bloginfo' );

			// Overwrite - or create if it doesn't already exist.
			$existing[ $blog_id ] = [
				'blog_id' => $blog_id,
				'attachment_id' => $attachment_id,
				'thumb' => $attachment_thumb,
				'medium' => $attachment_medium,
				'large' => $attachment_large,
				'full' => $attachment_full,
			];

		} else {

			// Get existing option.
			$existing = $bp_groupsites->admin->option_get( 'bpgsites_bloginfo' );

			// Remove entry.
			unset( $existing[ $blog_id ] );

		}

		// Overwrite.
		$bp_groupsites->admin->option_set( 'bpgsites_bloginfo', $existing );

		// Save.
		$bp_groupsites->admin->options_save();

	}

}

// Add action for the above.
add_action( 'update_option_commentpress_theme_settings', 'bpgsites_commentpress_site_image', 10, 2 );

/**
 * Replace groupsite avatar with "Site Image".
 *
 * @since 0.1
 *
 * @param string $avatar  Formatted HTML <img> element, or raw avatar
 *                        URL based on $html arg.
 * @param int    $blog_id ID of the blog whose avatar is being displayed.
 * @param array  $r       Array of arguments used when fetching avatar.
 */
function bpgsites_commentpress_site_image_avatar( $avatar, $blog_id, $r ) {

	// Access object.
	global $bp_groupsites;

	// Get existing option.
	$existing = $bp_groupsites->admin->option_get( 'bpgsites_bloginfo' );

	// Do we have an entry?
	if ( is_array( $existing ) && array_key_exists( $blog_id, $existing ) ) {

		// Get type to use.
		$type = apply_filters( 'bpgsites_bloginfo_avatar_type', 'thumb' );

		// Sanity check.
		if ( isset( $existing[ $blog_id ][ $type ] ) ) {

			// Get image by type.
			$image = $existing[ $blog_id ][ $type ];

			// Get blog name.
			$blog_name = bp_blogs_get_blogmeta( $blog_id, 'name' );

			// Override.
			$avatar = '<img src="' . $image[0] . '" class="avatar avatar-' . $image[1] . ' groupsite-avatar photo" width="' . $image[1] . '" height="' . $image[2] . '" alt="' . esc_attr( $blog_name ) . '" title="' . esc_attr( $blog_name ) . '" />';

		}

	}

	// --<
	return $avatar;

}

// Add action for the above.
add_filter( 'bp_get_blog_avatar', 'bpgsites_commentpress_site_image_avatar', 100, 3 );
