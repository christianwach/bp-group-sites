<?php /*
================================================================================
BP Group Sites Group Extension
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

This class extends BP_Group_Extension to create the screens our plugin requires.
See: http://codex.buddypress.org/developer/plugin-development/group-extension-api/

--------------------------------------------------------------------------------
*/



// prevent problems during upgrade or when Groups are disabled
if ( !class_exists( 'BP_Group_Extension' ) ) { return; }



/*
================================================================================
Class Name
================================================================================
*/

class BPGSites_Group_Extension extends BP_Group_Extension {



	/*
	============================================================================
	Properties
	============================================================================
	*/

	/*
	// 'public' will show our extension to non-group members
	// 'private' means only members of the group can view our extension
	public $visibility = 'public';

	// if our extension does not need a navigation item, set this to false
	public $enable_nav_item = true;

	// if our extension does not need an edit screen, set this to false
	public $enable_edit_item = true;

	// if our extension does not need an admin metabox, set this to false
	public $enable_admin_item = true;

	// the context of our admin metabox. See add_meta_box()
	public $admin_metabox_context = 'core';

	// the priority of our admin metabox. See add_meta_box()
	public $admin_metabox_priority = 'normal';
	*/

	// no need for a creation step
	public $enable_create_step = false;



	/**
	 * Initialises this object
	 *
	 * @return void
	 */
	function __construct() {

		// init vars with filters applied
		$name = apply_filters( 'bpgsites_extension_title', __( 'Group Sites', 'bpgsites' ) );
		$slug = apply_filters( 'bpgsites_extension_slug', 'group-sites' );
		$pos = apply_filters( 'bpgsites_extension_pos', 31 );

		// test for BP 1.8+
		// could also use 'bp_esc_sql_order' (the other core addition)
		if ( function_exists( 'bp_core_get_upload_dir' ) ) {

			// init array
			$args = array(
				'name' => $name,
				'slug' => $slug,
				'nav_item_position' => $pos,
				'enable_create_step' => false,
			);

			// init
			parent::init( $args );

	 	} else {

			// name our tab
			$this->name = $name;
			$this->slug = $slug;

			// set position in navigation
			$this->nav_item_position = $pos;

		}

	}



	/**
	 * The content of the extension tab in the group admin
	 *
	 * @return void
	 */
	function edit_screen() {

		// kick out if not on our edit screen
		if ( !bp_is_group_admin_screen( $this->slug ) ) { return false; }

		// show name
		echo '<h2>'.esc_html( $this->name ).'</h2>';

		// hand off to function
		echo bpgsites_get_extension_edit_screen();

		// add nonce
		wp_nonce_field( 'groups_edit_save_' . $this->slug );

	}



	/**
	 * Runs after the user clicks a submit button on the edit screen
	 *
	 * @return void
	 */
	function edit_screen_save() {

		// parse input name for our values
		$parsed = $this->_parse_input_name();

		// get blog ID
		$blog_id = $parsed['blog_id'];

		// kick out if blog ID is somehow invalid
		if ( !$blog_id ) { return false; }

		// validate form
		check_admin_referer( 'groups_edit_save_' . $this->slug );

		// kick out if group ID is missing
		if ( !isset( $_POST['group-id'] ) ) { return false; }

		// set group ID
		$group_id = (int) $_POST['group-id'];

		//print_r( $parsed ); die();

		// action to perform on the chosen blog
		switch ( $parsed['action'] ) {

			case 'add':

				// link
				bpgsites_link_blog_and_group( $blog_id, $group_id );

				// feedback
				bp_core_add_message( __( 'Site successfully added to Group', 'bpgsites' ) );

				break;

			case 'remove':

				// unlink
				bpgsites_unlink_blog_and_group( $blog_id, $group_id );

				// feedback
				bp_core_add_message( __( 'Site successfully removed from Group', 'bpgsites' ) );

				break;

			case 'update':

				// manage group linkages
				$this->_update_group_linkages( $blog_id, $group_id );

				// feedback
				bp_core_add_message( __( 'Site successfully removed from Group', 'bpgsites' ) );

				break;

		}

		// access BP
		global $bp;

		// return to page
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/' . $this->slug );

	}



	/**
	 * Display our content when the nav item is selected
	 *
	 * @return void
	 */
	function display() {

		// hand off to function
		echo bpgsites_get_extension_display();

	}



	/**
	 * If your group extension requires a meta box in the Dashboard group admin,
	 * use this method to display the content of the metabox
	 *
	 * As in the case of create_screen() and edit_screen(), it may be helpful
	 * to abstract shared markup into a separate method.
	 *
	 * This is an optional method. If you don't need/want a metabox on the group
	 * admin panel, don't define this method in your class.
	 *
	 * @param int $group_id the numeric ID of the group being edited
	 * @return void
	 */
	function admin_screen( $group_id ) {

		// hand off to function
		echo bpgsites_get_extension_admin_screen();

	}



	/**
	 * The routine run after the group is saved on the Dashboard group admin screen
	 *
	 * @param int $group_id the numeric ID of the group being edited
	 * @return void
	 */
	function admin_screen_save( $group_id ) {

		// Grab your data out of the $_POST global and save as necessary

	}



	/**
	 * Parse the name of the input to extract blog Id and action
	 *
	 * @return array Contains $blog_id and $action
	 */
	protected function _parse_input_name() {

		// init return
		$return = array(
			'blog_id' => false,
			'action' => false
		);

		// get keys of POST array
		$keys = array_keys( $_POST );

		// did we get any?
		if ( is_array( $keys ) AND count( $keys ) > 0 ) {

			// loop
			foreach( $keys AS $key ) {

				// look for our identifier
				if ( strstr( $key, 'bpgsites_manage' ) ) {

					// got it
					$tmp = explode( '-', $key );

					// extract blog id
					$return['blog_id'] = ( isset( $tmp[1] ) AND is_numeric( $tmp[1] ) ) ? (int) $tmp[1] : false;

					// extract action
					$return['action'] = isset( $tmp[2] ) ? $tmp[2] : false;

				}

			}

		}

		// --<
		return $return;

	}



	/**
	 * Manages the linkages between "groups reading together"
	 *
	 * @param int $blog_id the numeric ID of the blog
	 * @param int $group_id the numeric ID of the group
	 * @return void
	 */
	function _update_group_linkages( $blog_id, $group_id ) {

		// bail if the update button has not been pressed
		if ( ! isset( $_POST['bpgsites_manage-'.$blog_id.'-update'] ) ) { return; }
		if ( $_POST['bpgsites_manage-'.$blog_id.'-update'] == '' ) { return; }

		// get existing array
		$linked = bpgsites_get_group_linkages( $group_id );

		// init new groups array
		$group_ids = array();

		// do we have a post array for our checkboxes?
		if ( isset( $_POST['bpgsites_linked_groups_'.$blog_id] ) ) {

			// YES - get values from post array
			$group_ids = $_POST['bpgsites_linked_groups_'.$blog_id];

			// sanitise all the items
			array_walk( $group_ids, create_function( '&$val', '$val = absint( $val );' ) );

		}

		// set reciprocal linkages
		$this->_update_reciprocal_linkages( $blog_id, $group_id, $linked[$blog_id], $group_ids );

		// if we have some group IDs to link
		if ( count( $group_ids ) > 0 ) {

			// overwrite the nested array for this blog ID
			$linked[$blog_id] = $group_ids;

		} else {

			// empty the nested array for this blog ID
			unset( $linked[$blog_id] );

		}

		// save updated option for this group
		groups_update_groupmeta( $group_id, BPGSITES_LINKED, $linked );

	}



	/**
	 * Manages the linkages between "groups reading together"
	 *
	 * @param int $blog_id the numeric ID of the blog
	 * @param int $group_id the numeric ID of the group
	 * @param array $existing_group_ids the numeric IDs of the groups that are already linked
	 * @param array $new_group_ids the numeric IDs of the groups to link
	 * @return void
	 */
	function _update_reciprocal_linkages( $blog_id, $group_id, $existing_group_ids, $new_group_ids ) {

		// bail if we didn't get any
		//if ( count( $new_group_ids ) === 0 ) { return; }

		// parse incoming arrays
		$to_keep = array_intersect( $existing_group_ids, $new_group_ids );
		$to_add = array_diff( $new_group_ids, $existing_group_ids );
		$to_delete = array_diff( $existing_group_ids, $new_group_ids );

		// first add/keep
		$keep_and_add = array_merge( $to_keep, $to_add );

		// sanity check
		if ( count( $keep_and_add ) > 0 ) {

			// loop through them
			foreach( $keep_and_add AS $linked_group_id ) {

				// get their linkages
				$linked = bpgsites_get_group_linkages( $linked_group_id );

				// get the array for this blog ID
				$remote_group_ids = isset( $linked[$blog_id] ) ? $linked[$blog_id] : array();

				// is this one in the remote list?
				if ( !in_array( $group_id, $remote_group_ids ) ) {

					// no, add it
					$remote_group_ids[] = $group_id;

					// overwrite in parent array
					$linked[$blog_id] = $remote_group_ids;

					// save updated option
					groups_update_groupmeta( $linked_group_id, BPGSITES_LINKED, $linked );

				}

			}

		}

		// sanity check
		if ( count( $to_delete ) > 0 ) {

			// loop through them
			foreach( $to_delete AS $linked_group_id ) {

				// get their linkages
				$linked = bpgsites_get_group_linkages( $linked_group_id );

				// get the array for this blog ID
				$remote_group_ids = isset( $linked[$blog_id] ) ? $linked[$blog_id] : array();

				// is this one in the remote list?
				if ( in_array( $group_id, $remote_group_ids ) ) {

					// yes - remove group and re-index
					$updated = array_merge( array_diff( $remote_group_ids, array( $group_id ) ) );

					// overwrite in parent array
					$linked[$blog_id] = $updated;

					// save updated option
					groups_update_groupmeta( $linked_group_id, BPGSITES_LINKED, $linked );

				}

			}

		}

	}



} // class ends



// register our class
bp_register_group_extension( 'BPGSites_Group_Extension' );



/**
 * The content of the public extension page
 *
 * @return void
 */
function bpgsites_get_extension_display() {

	// show something
	echo '<h3>'.apply_filters( 'bpgsites_extension_title', __( 'Group Sites', 'bpgsites' ) ).'</h3>';

	do_action( 'bp_before_blogs_loop' );

	// use current group
	$defaults = array(
		'group_id' => bp_get_current_group_id()
	);

	// search for them - TODO: add AJAX query string compatibility
	if ( bpgsites_has_blogs( $defaults ) ) {

		?>

		<div id="pag-top" class="pagination">

			<div class="pag-count" id="blog-dir-count-top">
				<?php bpgsites_blogs_pagination_count(); ?>
			</div>

			<div class="pagination-links" id="blog-dir-pag-top">
				<?php bp_blogs_pagination_links(); ?>
			</div>

		</div>

		<?php do_action( 'bp_before_directory_blogs_list' ); ?>

		<ul id="blogs-list" class="item-list" role="main">

		<?php while ( bp_blogs() ) : bp_the_blog(); ?>

			<li>
				<div class="item-avatar">
					<a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_avatar( 'type=thumb' ); ?></a>
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_name(); ?></a></div>
					<div class="item-meta"><span class="activity"><?php bp_blog_last_active(); ?></span></div>

					<?php do_action( 'bp_directory_blogs_item' ); ?>
				</div>

				<div class="action">

					<?php do_action( 'bp_directory_blogs_actions' ); ?>

					<div class="meta">

						<?php bp_blog_latest_post(); ?>

					</div>

				</div>

				<div class="clear"></div>
			</li>

		<?php endwhile; ?>

		</ul>

		<?php do_action( 'bp_after_directory_blogs_list' ); ?>

		<?php bp_blog_hidden_fields(); ?>

		<div id="pag-bottom" class="pagination">

			<div class="pag-count" id="blog-dir-count-bottom">

				<?php bpgsites_blogs_pagination_count(); ?>

			</div>

			<div class="pagination-links" id="blog-dir-pag-bottom">

				<?php bp_blogs_pagination_links(); ?>

			</div>

		</div>

		<?php

	} else {

		?>

		<div id="message" class="info">
			<p><?php _e( 'Sorry, there were no sites found.', 'bpgsites' ); ?></p>
		</div>

		<?php

	}

	do_action( 'bp_after_blogs_loop' );

}



/**
 * The content of the extension group admin page
 *
 * @return void
 */
function bpgsites_get_extension_edit_screen() {

	?><p><?php _e( 'In order to "Read With" other groups, <em>all admins of this group</em> should be members of those groups.', 'bpgsites' ); ?></p>

	<?php

	do_action( 'bp_before_blogs_loop' );

	// configure to get all possible group sites
	$args = array( 'possible_sites' => true );

	// get all group sites - TODO: add AJAX query string compatibility?
	if ( bpgsites_has_blogs( $args ) ) {

		?>

		<div id="pag-top" class="pagination">

			<div class="pag-count" id="blog-dir-count-top">
				<?php bpgsites_blogs_pagination_count(); ?>
			</div>

			<div class="pagination-links" id="blog-dir-pag-top">
				<?php bp_blogs_pagination_links(); ?>
			</div>

		</div>

		<?php do_action( 'bp_before_directory_blogs_list' ); ?>

		<ul id="blogs-list" class="item-list" role="main">

		<?php while ( bp_blogs() ) : bp_the_blog();

			// is this blog in the group?
			$in_group = bpgsites_is_blog_in_group();

			?>

			<li>
				<div class="item-avatar">
					<a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_avatar( 'type=thumb' ); ?></a>
				</div>

				<div class="item">
					<div class="item-title"><a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_name(); ?></a></div>
					<div class="item-meta"><span class="activity"><?php bp_blog_last_active(); ?></span></div>
					<?php do_action( 'bp_directory_blogs_item' ); ?>
					<?php

					// init linkage
					$has_linkage = false;

					// if blog already in group
					if ( $in_group ) {

						// see if we have other groups (and echo while we're at it)
						$has_linkage = bpgsites_get_group_linkage();

					}

					?>
				</div>

				<div class="action">
					<?php if ( $in_group AND $has_linkage ) { ?>
						<input type="submit" class="bpgsites_manage_button" name="bpgsites_manage-<?php bp_blog_id() ?>-update" value="<?php _e( 'Update', 'bpgsites' ); ?>" />
					<? } ?>
					<input type="submit" class="bpgsites_manage_button" name="bpgsites_manage-<?php bp_blog_id() ?>-<?php bpgsites_admin_button_action() ?>" value="<?php bpgsites_admin_button_value(); ?>" />
				</div>

				<div class="clear"></div>
			</li>

		<?php endwhile; ?>

		</ul>

		<?php do_action( 'bp_after_directory_blogs_list' ); ?>

		<?php bp_blog_hidden_fields(); ?>

		<div id="pag-bottom" class="pagination">

			<div class="pag-count" id="blog-dir-count-bottom">

				<?php bpgsites_blogs_pagination_count(); ?>

			</div>

			<div class="pagination-links" id="blog-dir-pag-bottom">

				<?php bp_blogs_pagination_links(); ?>

			</div>

		</div>

		<?php

	} else {

		?>

		<div id="message" class="info">
			<p><?php _e( 'Sorry, there were no sites found.', 'bpgsites' ); ?></p>
		</div>

		<?php

	}

	do_action( 'bp_after_blogs_loop' );

}



/**
 * The content of the extension admin screen
 *
 * @return void
 */
function bpgsites_get_extension_admin_screen() {

	echo '<p>BP Group Sites Admin Screen</p>';

}



/**
 * Adds checkboxes to groups loop for "reading with" other groups
 *
 * @param bool $echo Whether to echo or not
 * @return bool $has_linkage Whether there is a linkage or not
 */
function bpgsites_get_group_linkage( $echo = true ) {

	// init return
	$has_linkage = false;

	// init HTML output
	$html = '';

	// init user groups array
	$user_group_ids = array();

	// get current blog ID
	$blog_id = bp_get_blog_id();

	// get this blog's group IDs
	$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

	// get user ID
	$user_id = bp_loggedin_user_id();

	// get current group ID
	$current_group_id = bp_get_current_group_id();

	// loop through the groups
	foreach( $group_ids AS $group_id ) {

		// get the group
		$group = groups_get_group( array(
			'group_id'   => $group_id
		) );

		// either this admin user is a member or it's public
		if (
			groups_is_user_member( $user_id, $group_id ) OR
			'public' == bp_get_group_status( $group )
		) {

			// exclude the current group
			if ( $group_id != $current_group_id ) {

				// add to our array
				$user_group_ids[] = $group_id;

			}

		}

	}

	// kick out if empty
	if ( count( $user_group_ids ) == 0 ) return $has_linkage;

	// define config array
	$config_array = array(
		//'user_id' => $user_id,
		'type' => 'alphabetical',
		'max' => 100,
		'per_page' => 100,
		'populate_extras' => 0,
		'include' => $user_group_ids,
		'page_arg' => 'bpgsites'
	);

	// new groups query
	$groups_query = new BP_Groups_Template( $config_array );

	// get groups
	if ( $groups_query->has_groups() ) {

		// set flag
		$has_linkage = true;

		// get linkages
		$linkages = bpgsites_get_group_linkages( $current_group_id );

		// get those for this blog
		$linked_groups = isset( $linkages[$blog_id] ) ? $linkages[$blog_id] : array();

		// only show if user has more than one...
		//if ( $groups_query->group_count > 1 ) {

			// open div
			$html .= '<div class="bpgsites_group_linkage">'."\n";

			// construct heading
			$html .= '<h5 class="bpgsites_group_linkage_heading">'.__( 'Read this with:', 'bpgsites' ).'</h5>'."\n";

			// open div
			$html .= '<div class="bpgsites_group_linkages">'."\n";

			// do the loop
			while ( $groups_query->groups() ) { $groups_query->the_group();

				// get group ID
				$group_id = $groups_query->group->id;

				// assume not linked
				$checked = '';

				// is this one in the array?
				if ( in_array( $group_id, $linked_groups ) ) {

					// check the box
					$checked = ' checked="checked"';

				}

				// add arbitrary divider
				$html .= '<span class="bpgsites_linked_group">'."\n";

				// add checkbox
				$html .= '<input type="checkbox" class="bpgsites_group_checkbox" name="bpgsites_linked_groups_'.$blog_id.'[]" id="bpgsites_linked_group_'.$blog_id.'_'.$group_id.'" value="'.$group_id.'" '.$checked.'/>'."\n";

				// add label
				$html .= '<label class="bpgsites_linked_group_label" for="bpgsites_linked_group_'.$blog_id.'_'.$group_id.'">'.$groups_query->group->name.'</label>'."\n";

				// close arbitrary divider
				$html .= '</span>'."\n";

			} // end while

			// close tags
			$html .= '</div>'."\n";
			$html .= '</div>'."\n";

		//}

	}

	// clear it
	unset( $groups_query );

	// output
	echo $html;

	// --<
	return $has_linkage;

}



/**
 * For a given group ID, get linked group IDs for all blogs
 *
 * @param int $group_id the numeric ID of the group
 * @return array $linked_groups Array of numeric IDs of linked groups
 */
function bpgsites_get_group_linkages( $group_id ) {

	// get option if it exists
	$linked_groups = groups_get_groupmeta( $group_id, BPGSITES_LINKED );

	// sanity check
	if ( !is_array( $linked_groups ) ) { $linked_groups = array(); }

	// --<
	return $linked_groups;

}



/**
 * For a given group ID, get linked group IDs for a specific blog
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 * @return array $linked_groups Array of numeric IDs of linked groups
 */
function bpgsites_get_linked_groups_by_blog_id( $group_id, $blog_id ) {

	// get linked groups
	$linked = bpgsites_get_group_linkages( $group_id );

	// get those for this blog
	$linked_groups = isset( $linked[$blog_id] ) ? $linked[$blog_id] : array();

	// --<
	return $linked_groups;

}



/**
 * For a given group ID, get linked group IDs for a given blog ID
 *
 * TODO: This function is not yet used - or finished.
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 * @return bool $return Whether or not the group is linked
 */
function bpgsites_is_linked_group( $group_id, $blog_id ) {

	// init return
	$return = false;

	// get linked groups
	$linked = bpgsites_get_group_linkages( $group_id );

	// get those for this blog
	$linked_groups = isset( $linked[$blog_id] ) ? $linked[$blog_id] : array();

	///*
	print_r( array(
		'linked' => $linked,
		'group_id' => $group_id,
		'blog_id' => $blog_id,
		'linked_groups' => $linked_groups
	) ); //die();
	//*/

	// did we get any?
	if ( count( $linked_groups ) > 0 ) {

		//

	}

	// if this one is in the array, it's linked
	if ( in_array( $group_id, $linked_groups ) ) { $return = true; }

	// --<
	return $return;

}



/**
 * Show option to make a group an authoritative group
 *
 * @return void
 */
function bpgsites_authoritative_group_settings_form() {

	// get name
	$name = apply_filters( 'bpgsites_extension_title', __( 'Group Sites', 'bpgsites' ) );

	// init checked
	$checked = '';

	// get existing option
	$auth_groups = bpgsites_authoritative_groups_get();

	// get current group ID
	$group_id = bpgsites_get_current_group_id();

	// sanity check list and group ID
	if ( count( $auth_groups ) > 0 AND !is_null( $group_id ) ) {

		// is this group's ID in the list
		if ( in_array( $group_id, $auth_groups ) ) {

			// override checked
			$checked = ' checked="checked"';

		}

	}

	?>
	<h4><?php echo $name; ?></h4>

	<p><?php _e( 'To make this group an authoritative group, make sure that it is set to "Private" above, then check the box below. The effect will be that the comments left by members of this group will always appear to readers. Only other members of this group will be able to reply to those comments.', 'bpgsites' ); ?></p>

	<div class="checkbox">
		<label><input type="checkbox" id="bpgsites-authoritative-group" name="bpgsites-authoritative-group" value="1"<?php echo $checked ?> /> <?php _e( 'Make this group an authoritative group', 'bpgsites' ) ?></label>
	</div>

	<hr />

	<?php

}

// add actions for the above
add_action ( 'bp_after_group_settings_admin' ,'bpgsites_authoritative_group_settings_form' );
add_action ( 'bp_after_group_settings_creation_step' ,'bpgsites_authoritative_group_settings_form' );




/**
 * Get group ID on admin and creation screens
 *
 * @return int $group_id the current group ID
 */
function bpgsites_get_current_group_id() {

	// access BP global
	global $bp;

	// init return
	$group_id = null;

	// test for new group ID
	if ( isset( $bp->groups->new_group_id ) ) {
		$group_id = $bp->groups->new_group_id;

	// test for current group ID
	} elseif ( isset( $bp->groups->current_group->id ) ) {
		$group_id = $bp->groups->current_group->id;
	}

	// --<
	return $group_id;

}



/**
 * Intercept group settings save process
 *
 * @param object $group the group object
 * @return void
 */
function bpgsites_authoritative_group_save( $group ) {

	/*
	If the checkbox IS NOT checked, remove from option if it is there
	If the checkbox IS checked, add it to the option if not already there
	*/

	// get existing option
	$auth_groups = bpgsites_authoritative_groups_get();

	// if not checked
	if ( !isset( $_POST['bpgsites-authoritative-group'] ) ) {

		// sanity check list
		if ( count( $auth_groups ) > 0 ) {

			// is this group's ID in the list?
			if ( in_array( $group->id, $auth_groups ) ) {

				// yes, remove group ID and re-index
				$updated = array_merge( array_diff( $auth_groups, array( $group->id ) ) );

				// save option
				bpgsites_site_option_set( 'bpgsites_auth_groups', $updated );

			}

		}

	} else {

		// kick out if value is not 1
		if ( absint( $_POST['bpgsites-authoritative-group'] ) !== 1 ) { return; }

		// is this group's ID missing from the list?
		if ( !in_array( $group->id, $auth_groups ) ) {

			// add it
			$auth_groups[] = $group->id;

			// save option
			bpgsites_site_option_set( 'bpgsites_auth_groups', $auth_groups );

		}

	}

}

// add action for the above
add_action( 'groups_group_after_save', 'bpgsites_authoritative_group_save' );



/**
 * Get all authoritative groups
 *
 * @return array $auth_groups the authoritative group IDs
 */
function bpgsites_authoritative_groups_get() {

	// get existing option
	$auth_groups = bpgsites_site_option_get( 'bpgsites_auth_groups', array() );

	// --<
	return $auth_groups;

}



/**
 * Get all authoritative groups
 *
 * @param int $group_id the group ID
 * @return bool $is_auth_group the group is or is not authoritative
 */
function bpgsites_is_authoritative_group( $group_id ) {

	// get existing option
	$auth_groups = bpgsites_authoritative_groups_get();

	// sanity check list
	if ( count( $auth_groups ) > 0 ) {

		// is this group's ID in the list?
		if ( in_array( $group_id, $auth_groups ) ) {

			// --<
			return true;

		}

	}

	// --<
	return false;

}



/**
 * Check if user is a member of an authoritative group for this blog
 *
 * @return bool $passed user is a member of an authoritative group for this blog
 */
function bpgsites_is_authoritative_group_member() {

	// false by default
	$passed = false;

	// get existing option
	$auth_groups = bpgsites_authoritative_groups_get();

	// sanity check list
	if ( count( $auth_groups ) > 0 ) {

		// get current blog
		$current_blog_id = get_current_blog_id();

		// get user ID
		$user_id = bp_loggedin_user_id();

		// loop
		foreach( $auth_groups AS $group_id ) {

			// is this user a member?
			if ( groups_is_user_member( $user_id, $group_id ) ) {

				// if this auth group is an auth group for this blog
				if ( bpgsites_check_group_by_blog_id( $current_blog_id, $group_id ) ) {

					// no need to delve further
					return true;

				}

			}

		}

	}

	// --<
	return $passed;

}



/**
 * Filter media buttons by authoritative groups context
 *
 * @param bool $enabled if media buttons are enabled
 * @return bool $enabled if media buttons are enabled
 */
function bpgsites_authoritative_group_media_buttons( $allowed ) {

	// disallow by default
	$allowed = false;

	// is this user a member of an auth group on this blog?
	if ( bpgsites_is_authoritative_group_member() ) {

		// allow
		return true;

	}

	// --<
	return $allowed;

}

// add filter for the above
add_filter( 'commentpress_rte_media_buttons', 'bpgsites_authoritative_group_media_buttons', 10, 1 );



/**
 * Filter quicktags by authoritative groups context
 *
 * @param array $quicktags the quicktags
 * @return array/bool $quicktags false if quicktags are disabled, array of buttons otherwise
 */
function bpgsites_authoritative_group_quicktags( $quicktags ) {

	// disallow quicktags by default
	$quicktags = false;

	// is this user a member of an auth group on this blog?
	if ( bpgsites_is_authoritative_group_member() ) {

		// allow quicktags
		$quicktags = array(
			'buttons' => 'strong,em,ul,ol,li,link,close'
		);

		// --<
		return $quicktags;

	}

	// --<
	return $quicktags;

}

// add filter for the above
add_filter( 'commentpress_rte_quicktags', 'bpgsites_authoritative_group_quicktags', 10, 1 );



