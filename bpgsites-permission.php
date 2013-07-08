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
 * @description: for a given blog ID, get the array of group IDs
 * @param int $blog_id the numeric ID of the blog
 */
function bpgsites_get_groups_by_blog_id( $blog_id ) {

	// construct option name
	$option_name = BPGSITES_PREFIX . $blog_id;
	
	// return option if it exists
	return get_site_option( $option_name, array() );
	
}



/** 
 * @description: for a given group ID, add a given group ID
 * @param int $group_id the numeric ID of the group
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
 * @description: for a given blog ID, check if it is associated with a given group ID
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 * @return array blogs
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
 * @description: for a given group ID, check if it is associated with a given blog ID
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
 * @return array blogs
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
 * @description: reciprocal addition of IDs
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 */
function bpgsites_link_blog_and_group( $blog_id, $group_id ) {

	// add to blog's option
	bpgsites_add_group_to_blog( $blog_id, $group_id );

	// add to group's option
	bpgsites_add_blog_to_group( $group_id, $blog_id );

}



/**
 * @description: reciprocal deletion of IDs
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 */
function bpgsites_unlink_blog_and_group( $blog_id, $group_id ) {

	// remove from blog's option
	bpgsites_remove_group_from_blog( $blog_id, $group_id );

	// remove from group's option
	bpgsites_remove_blog_from_group( $group_id, $blog_id );

}



/** 
 * @description: for a given blog ID, add a given group ID
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
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
 * @description: for a given group ID, add a given blog ID
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
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
 * @description: for a given blog ID, remove a given group ID
 * @param int $blog_id the numeric ID of the blog
 * @param int $group_id the numeric ID of the group
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
 * @description: for a given group ID, remove a given blog ID
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
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
 * @description sever link when a site gets deleted
 * @param int $blog_id the numeric ID of the blog
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
 * @description sever link before a group gets deleted so we can still access meta
 * @param int $group_id the numeric ID of the group
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



