<?php /*
================================================================================
BP Group Sites Blogs Template
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

We extend the BuddyPress BP Blogs template class so that we can filter by group
association, whilst retaining useful stuff like pagination.

--------------------------------------------------------------------------------
*/



/**
 * Query only Group Site blogs
 *
 * @param array $args Array of arguments with which the query was configured
 * @return bool $has_blogs Whether or not our modified query has found blogs
 */
function bpgsites_has_blogs( $args = '' ) {

	// remove default exclusion filter
	remove_filter( 'bp_after_has_blogs_parse_args', 'bpgsites_pre_filter_groupsites', 30, 1 );

	// user filtering
	$user_id = 0;
	if ( bp_displayed_user_id() ) {
		$user_id = bp_displayed_user_id();
	}

	// do we want all possible group sites?
	if ( isset( $args['possible_sites'] ) AND $args['possible_sites'] === true ) {

		// get all possible group sites
		$groupsites = bpgsites_get_all_possible_groupsites();

	} else {

		// get groupsite IDs
		$groupsites = bpgsites_get_groupsites();

	}

	// Check for and use search terms
	$search_terms = ! empty( $_REQUEST['s'] )
		? $_REQUEST['s']
		: false;

	// declare defaults
	$defaults = array(
		'type'         => 'active',
		'page'         => 1,
		'per_page'     => 20,
		'max'          => false,
		'page_arg'     => 'bpage',
		'user_id'      => $user_id,
		'include_blog_ids'  => $groupsites,
		'search_terms' => $search_terms,
		'update_meta_cache' => true,
	);

	// parse args
	$parsed_args = bp_parse_args( $args, $defaults, 'has_blogs' );

	// Set per_page to maximum if max is enforced
	if ( ! empty( $parsed_args['max'] ) && ( (int) $parsed_args['per_page'] > (int) $parsed_args['max'] ) ) {
		$parsed_args['per_page'] = (int) $parsed_args['max'];
	}

	// re-query with our params
	$has_blogs = bp_has_blogs( $parsed_args );

	// add exclusion filter back as default
	add_filter( 'bp_after_has_blogs_parse_args', 'bpgsites_pre_filter_groupsites', 30, 1 );

	// fallback
	return $has_blogs;

}



/**
 * Intercept the bp_has_blogs() query and exclude groupsites
 *
 * @param bool $has_blogs Whether or not this query has found blogs
 * @param object $blogs_template BuddyPress blogs template object
 * @param array $params Array of arguments with which the query was configured
 * @return bool $has_blogs Whether or not this query has found blogs
 */
function bpgsites_filter_groupsites( $has_blogs, $blogs_template, $params ) {

	// get groupsite IDs
	$groupsites = bpgsites_get_groupsites();

	// get all blogs via BP_Blogs_Blog
	$all = BP_Blogs_Blog::get_all();

	// init ID array
	$blog_ids = array();

	if ( is_array( $all['blogs'] ) AND count( $all['blogs'] ) > 0 ) {
		foreach ( $all['blogs'] AS $blog ) {
			$blog_ids[] = $blog->blog_id;
		}
	}

	// let's exclude
	$groupsites_excluded = array_merge( array_diff( $blog_ids, $groupsites ) );

	// do we have an array of blogs to include?
	if ( isset( $params['include_blog_ids'] ) AND ! empty( $params['include_blog_ids'] ) ) {

		// convert from comma-delimited if needed
		$include_blog_ids = array_filter( wp_parse_id_list( $params['include_blog_ids'] ) );

		// exclude groupsites
		$params['include_blog_ids'] = array_merge( array_diff( $include_blog_ids, $groupsites ) );

		// if we have none left, return false
		if ( count( $params['include_blog_ids'] ) === 0 ) return false;

	} else {

		// exclude groupsites
		$params['include_blog_ids'] = $groupsites_excluded;

	}

	// remove this filter to avoid recursion
	remove_filter( 'bp_has_blogs', 'bpgsites_filter_groupsites', 20 );

	// re-query with our params
	$has_blogs = bp_has_blogs( $params );

	// re-add filter
	add_filter( 'bp_has_blogs', 'bpgsites_filter_groupsites', 20, 3 );

	// fallback
	return $has_blogs;

}



/**
 * Intercept blogs query and manage display of blogs
 *
 * @param array $args The existing arguments used for the query
 * @return array $args The modified arguments used for the query
 */
function bpgsites_pre_filter_groupsites( $args ) {

	/*
	print_r( array(
		'args' => $args,
	) ); die();
	*/

	// get groupsite IDs
	$groupsites = bpgsites_get_groupsites();

	// get all blogs via BP_Blogs_Blog
	$all = BP_Blogs_Blog::get_all();

	// init ID array
	$blog_ids = array();

	if ( is_array( $all['blogs'] ) AND count( $all['blogs'] ) > 0 ) {
		foreach ( $all['blogs'] AS $blog ) {
			$blog_ids[] = $blog->blog_id;
		}
	}

	// let's exclude
	$groupsites_excluded = array_merge( array_diff( $blog_ids, $groupsites ) );

	// do we have an array of blogs to include?
	if ( isset( $args['include_blog_ids'] ) AND ! empty( $args['include_blog_ids'] ) ) {

		// convert from comma-delimited if needed
		$include_blog_ids = array_filter( wp_parse_id_list( $args['include_blog_ids'] ) );

		// exclude groupsites
		$args['include_blog_ids'] = array_merge( array_diff( $include_blog_ids, $groupsites ) );

		// if we have none left, set as false
		if ( count( $args['include_blog_ids'] ) === 0 ) $args['include_blog_ids'] = false;

	} else {

		// exclude groupsites
		$args['include_blog_ids'] = $groupsites_excluded;

	}

	// --<
	return $args;

}


// only on front end OR ajax
if ( ! is_admin() OR ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) ) {

	// use bp_parse_args post-parse filter (requires BP 2.0)
	add_filter( 'bp_after_has_blogs_parse_args', 'bpgsites_pre_filter_groupsites', 30, 1 );

}



/**
 * Override the total number of sites, excluding groupsites
 *
 * @return int $filtered_count The filtered total number of BuddyPress Groups
 */
function bpgsites_filter_total_blog_count() {

	// remove filter to prevent recursion
	remove_filter( 'bp_get_total_blog_count', 'bpgsites_filter_total_blog_count', 50 );

	// get actual count
	$actual_count = bp_blogs_total_blogs();

	// get groupsites
	$groupsites = bpgsites_total_blogs();

	// calculate
	$filtered_count = $actual_count - $groupsites;

	// add filter again
	add_filter( 'bp_get_total_blog_count', 'bpgsites_filter_total_blog_count', 50 );

	/*
	print_r( array(
		'actual_count' => $actual_count,
		'groupsites' => $groupsites,
		'filtered_count' => $filtered_count,
	) ); die();
	*/

	// --<
	return $filtered_count;

}

// only on front end OR ajax
if ( ! is_admin() OR ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) ) {

	// add filter for the above
	add_filter( 'bp_get_total_blog_count', 'bpgsites_filter_total_blog_count', 50 );

}



/**
 * Override the total number of sites for a user, excluding groupsites
 *
 * @param int $count The total number of sites for a user
 * @return int $filtered_count The filtered total number of blogs for a user
 */
function bpgsites_filter_total_blog_count_for_user( $count ) {

	// get user ID if none passed
	$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();

	// get working groupsites for this user
	$groupsite_count = bpgsites_get_total_blog_count_for_user( $user_id );

	// calculate
	$filtered_count = $count - $groupsite_count;

	// --<
	return $filtered_count;

}

// only on front end OR ajax
if ( ! is_admin() OR ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) ) {

	// add filter for the above, before BP applies its number formatting
	add_filter( 'bp_get_total_blog_count_for_user', 'bpgsites_filter_total_blog_count_for_user', 8, 1 );

}



/*
================================================================================
Functions which may only be used in the loop
================================================================================
*/



/**
 * Copied from bp_blogs_pagination_count() and amended
 *
 * @return void
 */
function bpgsites_blogs_pagination_count() {
	global $blogs_template;

	$start_num = intval( ( $blogs_template->pag_page - 1 ) * $blogs_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $blogs_template->pag_num - 1 ) > $blogs_template->total_blog_count ) ? $blogs_template->total_blog_count : $start_num + ( $blogs_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $blogs_template->total_blog_count );

	// get singular name
	$singular = strtolower( apply_filters( 'bpgsites_extension_name', __( 'site', 'bpgsites' ) ) );

	// get plural name
	$plural = strtolower( apply_filters( 'bpgsites_extension_plural', __( 'sites', 'bpgsites' ) ) );

	// we need to override the singular name
	echo sprintf(
		__( 'Viewing %1$s %2$s to %3$s (of %4$s %5$s)', 'bpgsites' ),
		$singular,
		$from_num,
		$to_num,
		$total,
		$plural
	);

}



/**
 * Get the total number of groupsites being tracked.
 * Copied from bp_total_blogs() and amended
 *
 * @return int $count Total blog count.
 */
function bpgsites_total_blogs() {

	// get from cache if possible
	if ( ! $count = wp_cache_get( 'bpgsites_groupsites', 'bpgsites' ) ) {

		// use function
		$groupsites = bpgsites_get_groupsites();

		// get total
		$count = bp_core_number_format( count( $groupsites ) );

		// stash it
		wp_cache_set( 'bpgsites_groupsites', $count, 'bpgsites' );

	}

	// --<
	return $count;

}



/**
 * Output the total number of groupsites on the site.
 *
 * @return void
 */
function bpgsites_total_blog_count() {
	echo bpgsites_get_total_blog_count();
}

	/**
	 * Return the total number of groupsites on the site
	 *
	 * @return int Total number of groupsites.
	 */
	function bpgsites_get_total_blog_count() {
		return apply_filters( 'bpgsites_get_total_blog_count', bpgsites_total_blogs() );
	}

	// format number that gets returned
	add_filter( 'bpgsites_get_total_blog_count', 'bp_core_number_format' );



/**
 * Get the total number of groupsites for a user
 * Copied from bp_blogs_total_blogs_for_user() and amended
 *
 * @return int $count Total blog count for a user
 */
function bpgsites_total_blogs_for_user( $user_id = 0 ) {

	// get user ID if none passed
	if ( empty( $user_id ) ) {
		$user_id = ( bp_displayed_user_id() ) ? bp_displayed_user_id() : bp_loggedin_user_id();
	}

	if ( ! $count = wp_cache_get( 'bpgsites_total_blogs_for_user_' . $user_id, 'bpgsites' ) ) {

		// get groupsites for this user (kind meaningless, so empty)
		$blogs = array();

		// get count
		$count = bp_core_number_format( count( $blogs ) );

		// stash it
		wp_cache_set( 'bpgsites_total_blogs_for_user_' . $user_id, $count, 'bpgsites' );

	}

	// --<
	return $count;

}



/**
 * Output the total number of working blogs for a user
 *
 * @return void
 */
function bpgsites_total_blog_count_for_user( $user_id = 0 ) {
	echo bpgsites_get_total_blog_count_for_user( $user_id );
}

	/**
	 * Return the total number of working blogs for this user
	 *
	 * @return int Total number of working blogs for this user
	 */
	function bpgsites_get_total_blog_count_for_user( $user_id = 0 ) {
		return apply_filters( 'bpgsites_get_total_blog_count_for_user', bpgsites_total_blogs_for_user( $user_id ) );
	}

	// format number that gets returned
	add_filter( 'bpgsites_get_total_blog_count_for_user', 'bp_core_number_format' );



/**
 * For a blog in the loop, check if it is associated with the current group
 *
 * @return bool Whether or not the blog is in the group
 */
function bpgsites_is_blog_in_group() {

	// get groups for this blog
	$groups = bpgsites_get_groups_by_blog_id( bp_get_blog_id() );

	// init return
	$return = false;

	// sanity check
	if (

		is_array( $groups ) AND
		count( $groups ) > 0

	) {

		// is the current group in the array?
		if ( in_array( bp_get_current_group_id(), $groups ) ) {
			$return = true;
		}

	}

	// --<
	return apply_filters( 'bpgsites_is_blog_in_group', $return );

}



/**
 * Get the text value of a submit button
 *
 * @return void
 */
function bpgsites_admin_button_value() {

	// is this blog already associated?
	if ( bpgsites_is_blog_in_group() ) {
		echo __( 'Remove', 'bpgsites' );
	} else {
		echo __( 'Add', 'bpgsites' );
	}

}



/**
 * Get the action of a submit button
 *
 * @return void
 */
function bpgsites_admin_button_action() {

	// is this blog already associated?
	if ( bpgsites_is_blog_in_group() ) {
		echo 'remove';
	} else {
		echo 'add';
	}

}



/**
 * Output the group sites component root slug.
 *
 * @uses bpgsites_get_root_slug()
 * @return void
 */
function bpgsites_root_slug() {
	echo bpgsites_get_root_slug();
}

	/**
	 * Return the group sites component root slug
	 *
	 * @return string The 'blogs' root slug.
	 */
	function bpgsites_get_root_slug() {
		return apply_filters( 'bpgsites_get_root_slug', buddypress()->bpgsites->root_slug );
	}



/**
 * Capture "Site Image" uploads and store
 *
 * @param mixed|array $old_value The previous value
 * @param mixed|array $new_value The new value
 * @return void
 */
function bpgsites_commentpress_site_image( $old_value, $new_value ) {

	// do we have a site image?
	if ( isset( $new_value['cp_site_image'] ) AND ! empty( $new_value['cp_site_image'] ) ) {

		// we should get the attachment ID
		$attachment_full = wp_get_attachment_image( $new_value['cp_site_image'], 'full' );

		print_r( $attachment_full ); die();

	}

}
//add_action( 'update_option_commentpress_theme_settings', 'bpgsites_commentpress_site_image', 10, 2 );



