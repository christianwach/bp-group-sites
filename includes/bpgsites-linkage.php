<?php /*
================================================================================
BP Group Sites Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

Logic functions which don't need to be in the loop.

--------------------------------------------------------------------------------
*/



/**
 * For a given blog ID, get the array of group IDs
 *
 * @param int $blog_id the numeric ID of the blog
 * @return array $group_ids Array of numeric IDs of groups
 */
function bpgsites_get_groups_by_blog_id( $blog_id ) {

	// construct option name
	$option_name = BPGSITES_PREFIX . $blog_id;

	// return option if it exists
	return get_site_option( $option_name, array() );

}



/**
 * For a given group ID, add a given group ID
 *
 * @param int $group_id the numeric ID of the group
 * @return array $blog_ids Array of numeric IDs of blogs
 */
function bpgsites_get_blogs_by_group_id( $group_id ) {

	// get option if it exists
	$blog_ids = groups_get_groupmeta( $group_id, BPGSITES_OPTION );

	// sanity check
	if ( !is_array( $blog_ids ) ) { $blog_ids = array(); }

	// --<
	return $blog_ids;

}



/**
 * For a given blog ID, check if it is associated with a given group ID
 *
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 * @return bool $return Whether or not the group is associated with a blog
 */
function bpgsites_check_group_by_blog_id( $blog_id, $group_id ) {

	// init return
	$return = false;

	// get array of group IDs
	$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

	// sanity check
	if ( is_array( $group_ids ) AND count( $group_ids ) > 0 ) {

		// is the group ID in the array?
		if ( in_array( $group_id, $group_ids ) ) {
			$return = true;
		}

	}

	// allow for now
	return $return;

}



/**
 * For a given group ID, check if it is associated with a given blog ID
 *
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 * @return bool $return Whether or not the blog is associated with a group
 */
function bpgsites_check_blog_by_group_id( $group_id, $blog_id ) {

	// init return
	$return = false;

	// get array of blog IDs
	$blog_ids = bpgsites_get_blogs_by_group_id( $group_id );

	// is the blog ID present?
	if ( in_array( $blog_id, $blog_ids ) ) {
		$return = true;
	}

	// --<
	return $return;

}



/**
 * Reciprocal addition of IDs
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 * @return void
 */
function bpgsites_link_blog_and_group( $blog_id, $group_id ) {

	// set blog options
	bpgsites_configure_blog_options( $blog_id );

	// add to blog's option
	bpgsites_add_group_to_blog( $blog_id, $group_id );

	// add to group's option
	bpgsites_add_blog_to_group( $group_id, $blog_id );

}



/**
 * Reciprocal deletion of IDs
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 * @return void
 */
function bpgsites_unlink_blog_and_group( $blog_id, $group_id ) {

	// remove from blog's option
	bpgsites_remove_group_from_blog( $blog_id, $group_id );

	// remove from group's option
	bpgsites_remove_blog_from_group( $group_id, $blog_id );

	// unset blog options
	bpgsites_reset_blog_options( $blog_id );

}



/**
 * Set blog options
 *
 * @param int $blog_id the numeric ID of the blog
 * @return void
 */
function bpgsites_configure_blog_options( $blog_id ) {

	// kick out if already a group site
	if ( bpgsites_is_groupsite( $blog_id ) ) return;

	// go there
	switch_to_blog( $blog_id );

	// get existing comment_registration option
	$existing_option = get_option( 'comment_registration', 0 );

	// store it for later
	add_option( 'bpgsites_saved_comment_registration', $existing_option );

	// anonymous commenting - off by default
	$anon_comments = apply_filters(
		'bpgsites_require_comment_registration',
		0 // disallow
	);

	// update option
	update_option( 'comment_registration', $anon_comments );

	// switch back
	restore_current_blog();

	// add blog ID to globally stored option
	bpgsites_register_groupsite( $blog_id );

}



/**
 * Unset blog options
 *
 * @param int $blog_id the numeric ID of the blog
 * @return void
 */
function bpgsites_reset_blog_options( $blog_id ) {

	// kick out if still a group site
	if ( bpgsites_is_groupsite( $blog_id ) ) return;

	// go there
	switch_to_blog( $blog_id );

	// get saved comment_registration option
	$previous_option = get_option( 'bpgsites_saved_comment_registration', 0 );

	// remove our saved one
	delete_option( 'bpgsites_saved_comment_registration' );

	// update option
	update_option( 'comment_registration', $previous_option );

	// switch back
	restore_current_blog();

	// remove blog ID from globally stored option
	bpgsites_deregister_groupsite( $blog_id );

}



/**
 * For a given blog ID, add a given group ID
 *
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 * @return void
 */
function bpgsites_add_group_to_blog( $blog_id, $group_id ) {

	// get array of group IDs
	$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

	// add group ID
	$group_ids[] = $group_id;

	// save updated option
	update_site_option( BPGSITES_PREFIX . $blog_id, $group_ids );

}



/**
 * For a given group ID, add a given blog ID
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 * @return void
 */
function bpgsites_add_blog_to_group( $group_id, $blog_id ) {

	// get array of blog IDs
	$blog_ids = bpgsites_get_blogs_by_group_id( $group_id );

	// is the blog ID present?
	if ( !in_array( $blog_id, $blog_ids ) ) {

		// no, add blog ID
		$blog_ids[] = $blog_id;

		// save updated option
		groups_update_groupmeta( $group_id, BPGSITES_OPTION, $blog_ids );

	}

}



/**
 * For a given blog ID, remove a given group ID
 *
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 * @return void
 */
function bpgsites_remove_group_from_blog( $blog_id, $group_id ) {

	// get array of group IDs
	$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

	// is the group ID present?
	if ( in_array( $group_id, $group_ids ) ) {

		// remove group ID and re-index
		$updated = array_merge( array_diff( $group_ids, array( $group_id ) ) );

		// save updated option
		update_site_option( BPGSITES_PREFIX . $blog_id, $updated );

	}

}



/**
 * For a given group ID, remove a given blog ID
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 * @return void
 */
function bpgsites_remove_blog_from_group( $group_id, $blog_id ) {

	// get array of blog IDs
	$blog_ids = bpgsites_get_blogs_by_group_id( $group_id );

	// is the blog ID present?
	if ( in_array( $blog_id, $blog_ids ) ) {

		// yes, remove blog ID and re-index
		$updated = array_merge( array_diff( $blog_ids, array( $blog_id ) ) );

		// save updated option
		groups_update_groupmeta( $group_id, BPGSITES_OPTION, $updated );

	}

}



/**
 * Sever link when a site gets deleted
 *
 * @param int $blog_id the numeric ID of the blog
 * @return void
 */
function bpgsites_remove_blog_from_groups( $blog_id, $drop = false ) {

	// get array of group IDs
	$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

	// sanity check
	if ( is_array( $group_ids ) AND count( $group_ids ) > 0 ) {

		// loop through them
		foreach( $group_ids AS $group_id ) {

			// unlink
			bpgsites_remove_blog_from_group( $group_id, $blog_id );

		}

	}

	// delete the site option
	delete_site_option( BPGSITES_PREFIX . $blog_id );

}

// sever links when site deleted
add_action( 'delete_blog', 'bpgsites_remove_blog_from_groups', 10, 1 );



/**
 * Sever link before a group gets deleted so we can still access meta
 *
 * @param int $group_id the numeric ID of the group
 * @return void
 */
function bpgsites_remove_group_from_blogs( $group_id ) {

	// get array of blog IDs
	$blog_ids = bpgsites_get_blogs_by_group_id( $group_id );

	// sanity check
	if ( count( $blog_ids ) > 0 ) {

		// loop through them
		foreach( $blog_ids AS $blog_id ) {

			// unlink
			bpgsites_remove_group_from_blog( $blog_id, $group_id );

		}

	}

	// our option will be deleted by groups_delete_group()

}

// sever links just before group is deleted, while meta still exists
add_action( 'groups_before_delete_group', 'bpgsites_remove_group_from_blogs', 10, 1 );



/**
 * Check if blog is a groupblog
 *
 * @param int $blog_id the numeric ID of the blog
 * @return bool $return Whether the blog is a groupblog or not
 */
function bpgsites_is_groupblog( $blog_id ) {

	// init return
	$return = false;

	// do we have groupblogs enabled?
	if ( function_exists( 'get_groupblog_group_id' ) ) {

		// yes, get group id
		$group_id = get_groupblog_group_id( $blog_id );

		// is this blog a groupblog?
		if ( is_numeric( $group_id ) ) { $return = true; }

	}

	// --<
	return $return;

}



/**
 * Check if blog is a groupsite
 *
 * @param int $blog_id the numeric ID of the blog
 * @return bool $return Whether the blog is a groupsite
 */
function bpgsites_is_groupsite( $blog_id ) {

	// init return
	$return = false;

	// get groups this site belongs to
	$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

	// if we have any group IDs, then it is
	if ( count( $group_ids ) > 0 ) {
		$return = true;
	}

	// --<
	return $return;

}



/**
 * Get array of all groupsite blog IDs
 *
 * @return array $blog_ids Array of numeric IDs of the group site blogs
 */
function bpgsites_get_groupsites() {

	// access object
	global $bp_groupsites;

	// create option if it doesn't exist
	if ( ! $bp_groupsites->admin->option_exists( 'bpgsites_groupsites' ) ) {
		$bp_groupsites->admin->option_set( 'bpgsites_groupsites', array() );
		$bp_groupsites->admin->options_save();
	}

	// get existing option
	$existing = $bp_groupsites->admin->option_get( 'bpgsites_groupsites' );

	// --<
	return $existing;

}



/**
 * Get all blogs that are (or can be) group sites
 *
 * At present, this means excluding the root blog and group blogs, but additional
 * blogs (such as "working papers") can be excluded using the provided filter
 *
 * @return array $filtered_blogs An array of all possible group sites
 */
function bpgsites_get_all_possible_groupsites() {

	// get all blogs via BP_Blogs_Blog
	$all = BP_Blogs_Blog::get_all();

	// init return
	$filtered_blogs = array();

	// get array of blog IDs
	if ( is_array( $all['blogs'] ) AND count( $all['blogs'] ) > 0 ) {
		foreach ( $all['blogs'] AS $blog ) {

			// is it the BP root blog?
			if ( $blog->blog_id == bp_get_root_blog_id() ) continue;

			// is it a groupblog?
			if ( bpgsites_is_groupblog( $blog->blog_id ) ) continue;

			// okay, none of those - add it
			$filtered_blogs[] = $blog->blog_id;

		}
	}

	// allow other plugins to exclude further blogs
	return apply_filters( 'bpgsites_get_all_possible_groupsites', $filtered_blogs );

}



/**
 * Store blog ID in plugin data
 *
 * @param int $blog_id the numeric ID of the blog
 * @return void
 */
function bpgsites_register_groupsite( $blog_id ) {

	// access object
	global $bp_groupsites;

	// create option if it doesn't exist
	if ( ! $bp_groupsites->admin->option_exists( 'bpgsites_groupsites' ) ) {
		$bp_groupsites->admin->option_set( 'bpgsites_groupsites', array() );
	}

	// get existing option
	$existing = $bp_groupsites->admin->option_get( 'bpgsites_groupsites' );

	// bail if the blog already present
	if ( in_array( $blog_id, $existing ) ) return;

	// add to the array (key is the same for easier removal)
	$existing[$blog_id] = $blog_id;

	// overwrite
	$bp_groupsites->admin->option_set( 'bpgsites_groupsites', $existing );

	// save
	$bp_groupsites->admin->options_save();

}



/**
 * Clear blog ID from plugin data
 *
 * @param int $blog_id the numeric ID of the blog
 * @return void
 */
function bpgsites_deregister_groupsite( $blog_id ) {

	// get existing option
	$existing = $bp_groupsites->admin->option_get( 'bpgsites_groupsites' );

	// sanity check
	if ( $existing === false ) return;

	// bail if the blog is not present
	if ( ! in_array( $blog_id, $existing ) ) return;

	// add to the array (key is the same as the value)
	unset( $existing[$blog_id] );

	// overwrite
	$bp_groupsites->admin->option_set( 'bpgsites_groupsites', $existing );

	// save
	$bp_groupsites->admin->options_save();

}



