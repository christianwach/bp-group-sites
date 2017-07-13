<?php /*
================================================================================
BP Group Sites Group Extension
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====


--------------------------------------------------------------------------------
*/



// prevent problems during upgrade or when Groups are disabled
if ( ! class_exists( 'BP_Group_Extension' ) ) { return; }



/**
 * BP Group Sites Group Extension class.
 *
 * This class extends BP_Group_Extension to create the screens our plugin requires.
 * @see http://codex.buddypress.org/developer/plugin-development/group-extension-api/
 *
 * @since 0.1
 */
class BPGSites_Group_Extension extends BP_Group_Extension {



	/**
	 * Properties.
	 *
	 * Many of the following a unused but retained to show possibilities.
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
	 * Constructor.
	 *
	 * @since 0.1
	 */
	function __construct() {

		// init vars with filters applied
		$name = apply_filters( 'bpgsites_extension_title', __( 'Group Sites', 'bp-group-sites' ) );
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
	 * The content of the extension tab in the group admin.
	 *
	 * @since 0.1
	 */
	public function edit_screen( $group_id = null ) {

		// kick out if not on our edit screen
		if ( ! bp_is_group_admin_screen( $this->slug ) ) { return false; }

		// show pending received
		bpgsites_group_linkages_pending_get_markup();

		// hand off to function
		bpgsites_get_extension_edit_screen();

		// add nonce
		wp_nonce_field( 'groups_edit_save_' . $this->slug );

	}



	/**
	 * Runs after the user clicks a submit button on the edit screen.
	 *
	 * @since 0.1
	 */
	public function edit_screen_save( $group_id = null ) {

		// validate form
		check_admin_referer( 'groups_edit_save_' . $this->slug );

		// kick out if current group ID is missing
		if ( ! isset( $_POST['group-id'] ) ) { return false; }

		// get current group ID
		$primary_group_id = (int) $_POST['group-id'];

		// parse input name for our values
		$parsed = $this->_parse_input_name();

		// get blog ID
		$blog_id = $parsed['blog_id'];

		// if blog ID is invalid, it could be multi-value
		if ( ! is_numeric( $blog_id ) ) {

			// first, re-parse
			$parsed = $this->_parse_input_name_multivalue();

			// get blog ID
			$blog_id = $parsed['blog_id'];

			// kick out if blog ID is still invalid
			if ( ! is_numeric( $blog_id ) ) { return false; }

			// get ID of the secondary group
			$secondary_group_id = $parsed['group_id'];

			// kick out if secondary group ID is somehow invalid
			if ( ! is_numeric( $secondary_group_id ) ) { return false; }

		}

		// get name, but allow plugins to override
		$name = apply_filters( 'bpgsites_extension_name', __( 'Group Site', 'bp-group-sites' ) );

		// action to perform on the chosen blog
		switch ( $parsed['action'] ) {

			// top-level "Add" button
			case 'add':

				// link
				bpgsites_link_blog_and_group( $blog_id, $primary_group_id );

				// feedback
				bp_core_add_message( sprintf( __( '%s successfully added to Group', 'bp-group-sites' ), $name ) );

				break;

			// top-level "Remove" button
			case 'remove':

				// unlink
				bpgsites_unlink_blog_and_group( $blog_id, $primary_group_id );

				// feedback
				bp_core_add_message( sprintf( __( '%s successfully removed from Group', 'bp-group-sites' ), $name ) );

				break;

			// read with "Invite" button
			case 'invite':

				// get invited group ID from POST
				$invited_group_id = isset( $_POST['bpgsites_group_linkages_invite_select_' . $blog_id] ) ?
									$_POST['bpgsites_group_linkages_invite_select_' . $blog_id] :
									0;

				// if we get a valid one
				if ( $invited_group_id !== 0 ) {

					// flag groups as linked, but pending
					bpgsites_group_linkages_pending_create( $blog_id, $primary_group_id, $invited_group_id );

					// send private message to group admins
					$this->_send_invitation_message( $blog_id, $primary_group_id, $invited_group_id );

					// feedback
					bp_core_add_message( sprintf( __( 'Group successfully invited', 'bp-group-sites' ), $name ) );

				} else {

					// feedback
					bp_core_add_message( sprintf( __( 'Something went wrong - group invitation not sent.', 'bp-group-sites' ), $name ) );

				}

				break;

			// invitation "Accept" button
			case 'accept':

				// create linkages
				bpgsites_group_linkages_pending_accept( $blog_id, $primary_group_id, $secondary_group_id );

				// feedback
				bp_core_add_message( sprintf( __( 'The invitation has been accepted', 'bp-group-sites' ), $name ) );

				break;

			// invitation "Reject" button
			case 'reject':

				// reject
				bpgsites_group_linkages_pending_delete( $blog_id, $primary_group_id, $secondary_group_id );

				// feedback
				bp_core_add_message( sprintf( __( 'The invitation has been declined', 'bp-group-sites' ), $name ) );

				break;

			// reading with "Stop" button
			case 'unlink':

				// unlink
				bpgsites_group_linkages_delete( $blog_id, $primary_group_id, $secondary_group_id );

				// get blog name
				$blog_name = bp_blogs_get_blogmeta( $blog_id, 'name' );

				// get group object
				$group = groups_get_group( array( 'group_id' => $secondary_group_id ) );

				// feedback
				bp_core_add_message(
					sprintf( __( 'Your group is no longer reading "%1$s" with %2$s', 'bp-group-sites' ), $blog_name, $group->name )
				);

				break;

		}

		// access BP
		global $bp;

		// return to page
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/' . $this->slug );

	}



	/**
	 * Display our content when the nav item is selected.
	 *
	 * @since 0.1
	 */
	public function display( $group_id = null ) {

		// hand off to function
		echo bpgsites_get_extension_display();

	}



	/**
	 * If your group extension requires a meta box in the Dashboard group admin,
	 * use this method to display the content of the metabox.
	 *
	 * As in the case of create_screen() and edit_screen(), it may be helpful
	 * to abstract shared markup into a separate method.
	 *
	 * This is an optional method. If you don't need/want a metabox on the group
	 * admin panel, don't define this method in your class.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id the numeric ID of the group being edited
	 */
	public function admin_screen( $group_id = null ) {

		// hand off to function
		echo bpgsites_get_extension_admin_screen();

	}



	/**
	 * The routine run after the group is saved on the Dashboard group admin screen.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id the numeric ID of the group being edited
	 */
	public function admin_screen_save( $group_id = null ) {

		// Grab your data out of the $_POST global and save as necessary

	}



	/**
	 * Parse the name of an input to extract blog ID and action.
	 *
	 * @since 0.1
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
	 * Parse the name of an input to extract blog ID, group ID and action.
	 *
	 * @since 0.1
	 *
	 * @return array Contains $blog_id and $action
	 */
	protected function _parse_input_name_multivalue() {

		// init return
		$return = array(
			'blog_id' => false,
			'group_id' => false,
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

					// get numeric part
					$numeric = isset( $tmp[1] ) ? $tmp[1] : false;

					// split on the _
					$parts = explode( '_', $numeric );

					// extract blog id
					$return['blog_id'] = ( isset( $parts[0] ) AND is_numeric( $parts[0] ) ) ? (int) $parts[0] : false;

					// extract group id
					$return['group_id'] = ( isset( $parts[1] ) AND is_numeric( $parts[1] ) ) ? (int) $parts[1] : false;

					// extract action
					$return['action'] = isset( $tmp[2] ) ? $tmp[2] : false;

				}

			}

		}

		// --<
		return $return;

	}



	/**
	 * Sends a private message to admins of the invited group.
	 *
	 * @since 0.1
	 *
	 * @param int $blog_id The numeric ID of the blog to be "read together"
	 * @param int $inviting_group_id The numeric ID of the inviting group
	 * @param int $invited_group_id The numeric ID of the invited group
	 */
	public function _send_invitation_message( $blog_id, $inviting_group_id, $invited_group_id ) {

		// get sender ID
		$sender_id = bp_loggedin_user_id();

		// get admins of target group
		$group_admins = groups_get_group_admins( $invited_group_id );

		// get group admin IDs
		$group_admin_ids = array();
		if ( ! empty( $group_admins ) ) {
			foreach( $group_admins AS $group_admin ) {
				$group_admin_ids[] = $group_admin->user_id;
			}
		}

		// get inviting group object
		$inviting_group = groups_get_group( array( 'group_id' => $inviting_group_id ) );

		// get invited group object
		$invited_group = groups_get_group( array( 'group_id' => $invited_group_id ) );

		// get blog name
		$blog_name = bp_blogs_get_blogmeta( $blog_id, 'name' );

		// get invited group permalink
		$group_permalink = trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $invited_group->slug );

		// construct links to Group Sites admin page
		$admin_link = trailingslashit( $group_permalink . 'admin/' . $this->slug );

		// construct message body
		$body = __( 'You are receiving this message because you are an administrator of the group "%1$s"', 'bp-group-sites' ) . "\n\n";
		$body .= __( 'Your group has been invited to read the %2$s "%3$s" with the group "%4$s". To accept or decline the invitation, click the link below to visit the %5$s admin page for your group.', 'bp-group-sites' ) . "\n\n";
		$body .= '%6$s' . "\n\n";

		// substitutions
		$content = sprintf(
			$body,
			$invited_group->name,
			apply_filters( 'bpgsites_extension_name', __( 'Group Site', 'bp-group-sites' ) ),
			$blog_name,
			$inviting_group->name,
			apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) ),
			$admin_link
		);

		// construct subject
		$subject =  sprintf(
			__( 'An invitation to read "%1$s" with the group "%2$s"', 'bp-group-sites' ),
			 $blog_name,
			 $inviting_group->name
		);

		// set up message
		$msg_args = array(
			'sender_id'  => $sender_id,
			'thread_id'  => false,
			'recipients' => $group_admin_ids, // can be an array of usernames, user_ids or mixed.
			'subject'    => $subject,
			'content'    => $content,
		);

		// send message
		messages_new_message( $msg_args );

	}



} // class ends



// register our class
bp_register_group_extension( 'BPGSites_Group_Extension' );



/**
 * The content of the public extension page.
 *
 * @since 0.1
 */
function bpgsites_get_extension_display() {

	do_action( 'bp_before_blogs_loop' );

	// use current group
	$defaults = array(
		'group_id' => bp_get_current_group_id()
	);

	// search for them
	// TODO: add AJAX query string compatibility
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

		<ul id="blogs-list" class="item-list group-groupsites-list" role="main">

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
			<p><?php _e( 'Sorry, there were no sites found.', 'bp-group-sites' ); ?></p>
		</div>

		<?php

	}

	do_action( 'bp_after_blogs_loop' );

}



/**
 * The content of the extension group admin page.
 *
 * @since 0.1
 */
function bpgsites_get_extension_edit_screen() {

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

		<ul id="blogs-list" class="item-list group-manage-groupsites-list" role="main">

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

					// if blog already in group
					if ( $in_group ) {

						// show linkage management tools
						bpgsites_group_linkages_get_markup();

					}

					?>
				</div>

				<div class="action">
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
			<p><?php _e( 'Sorry, there were no sites found.', 'bp-group-sites' ); ?></p>
		</div>

		<?php

	}

	do_action( 'bp_after_blogs_loop' );

}



/**
 * The content of the extension admin screen.
 *
 * @since 0.1
 */
function bpgsites_get_extension_admin_screen() {

	echo '<p>' . __( 'BP Group Sites Admin Screen', 'bp-group-sites' ) . '</p>';

}



/**
 * Get group ID on admin and creation screens.
 *
 * @since 0.1
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
 * Adds "accept" and "reject" invitations for "reading with" other groups.
 *
 * @since 0.1
 *
 * @param bool $echo Whether to echo or not
 */
function bpgsites_group_linkages_pending_get_markup( $echo = true ) {

	// get current group ID
	$current_group_id = bp_get_current_group_id();

	// bail if we have no pending invitations
	if ( ! bpgsites_group_linkages_pending_received_exists( $current_group_id ) ) return;

	// init HTML output
	$html = '';

	// open container div
	$html .= '<div class="bpgsites_group_linkages_pending">' . "\n";

	// construct heading
	$html .= '<h5 class="bpgsites_group_linkages_pending_heading">' . __( 'Invitations to read with other groups', 'bp-group-sites' ) . '</h5>' . "\n";

	// open reveal div
	$html .= '<div class="bpgsites_group_linkages_pending_reveal">' . "\n";

	// get pending invites
	$pending = bpgsites_group_linkages_pending_received_get( $current_group_id );

	// open list
	$html .= '<ol class="bpgsites_group_linkages_pending_list">' . "\n";

	// loop through blog IDs
	foreach( $pending AS $blog_id => $inviting_group_ids ) {

		// show invitations from each group
		foreach( $inviting_group_ids AS $inviting_group_id ) {

			// get blog name
			$blog_name = bp_blogs_get_blogmeta( $blog_id, 'name' );

			// get inviting group object
			$inviting_group = groups_get_group( array( 'group_id' => $inviting_group_id ) );

			// construct text
			$text = sprintf(
				__( 'Read "%1$s" with "%2$s"' ),
				$blog_name,
				esc_html( $inviting_group->name )
			);

			// add label
			$html .= '<li>' .
					 '<span class="bpgsites_invite_received">' . $text . '</span> ' .
					 '<input type="submit" class="bpgsites_invite_received_button" name="bpgsites_manage-' . $blog_id . '_' . $inviting_group_id . '-accept" value="' . __( 'Accept', 'bp-group-sites' ) . '" /> ' .
					 '<input type="submit" class="bpgsites_invite_received_button" name="bpgsites_manage-' . $blog_id . '_' . $inviting_group_id . '-reject" value="' . __( 'Decline', 'bp-group-sites' ) . '" /></li>' . "\n";

		}

	}

	// close list
	$html .= '</ol>' . "\n\n";

	// close reveal div
	$html .= '</div>' . "\n";

	// close container div
	$html .= '</div>' . "\n\n\n\n";

	// output unless overridden
	if ( $echo ) echo $html;

}


/**
 * For a given group ID, get all invitation data.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group
 * @return array $pending_groups Array containing "sent" and "received" arrays of numeric IDs of pending groups
 */
function bpgsites_group_linkages_pending_get( $group_id ) {

	// get option if it exists
	$pending_groups = groups_get_groupmeta( $group_id, BPGSITES_PENDING );

	// sanity check master array
	if ( ! is_array( $pending_groups ) OR empty( $pending_groups ) ) {
		$pending_groups = array( 'sent' => array(), 'received' => array() );
	}

	// sanity check sub-arrays
	if ( ! isset( $pending_groups['sent'] ) ) $pending_groups['sent'] = array();
	if ( ! isset( $pending_groups['received'] ) ) $pending_groups['received'] = array();

	// --<
	return $pending_groups;

}



/**
 * For a given group ID and blog ID, get the group IDs that have been invited.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group
 * @param int $blog_id The numeric ID of the blog (optional)
 * @return array $linked_groups Array of numeric IDs of linked groups
 */
function bpgsites_group_linkages_pending_sent_get( $group_id, $blog_id = 0 ) {

	// get option if it exists
	$pending_groups = bpgsites_group_linkages_pending_get( $group_id );

	// did we request a particular blog?
	if ( $blog_id !== 0 ) {

		 // overwrite with just the nested array for that blog
		 $pending_groups['sent'] = isset( $pending_groups['sent'][$blog_id] ) ? $pending_groups['sent'][$blog_id] : array();

	}

	// --<
	return $pending_groups['sent'];

}



/**
 * Create a sent invitation.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together"
 * @param int $inviting_group_id The numeric ID of the inviting group
 * @param int $invited_group_id The numeric ID of the invited group
 */
function bpgsites_group_linkages_pending_sent_create( $blog_id, $inviting_group_id, $invited_group_id ) {

	// get all data for the inviting group
	$pending_for_inviting_group = bpgsites_group_linkages_pending_get( $inviting_group_id );

	// make sure we have the blog's array
	if ( ! isset( $pending_for_inviting_group['sent'][$blog_id] ) ) {
		$pending_for_inviting_group['sent'][$blog_id] = array();
	}

	// if the invited group isn't present...
	if ( ! in_array( $invited_group_id, $pending_for_inviting_group['sent'][$blog_id] ) ) {

		// add it
		$pending_for_inviting_group['sent'][$blog_id][] = $invited_group_id;

		// resave
		groups_update_groupmeta( $inviting_group_id, BPGSITES_PENDING, $pending_for_inviting_group );

	}

}



/**
 * Delete a sent invitation.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together"
 * @param int $inviting_group_id The numeric ID of the inviting group
 * @param int $invited_group_id The numeric ID of the invited group
 */
function bpgsites_group_linkages_pending_sent_delete( $blog_id, $inviting_group_id, $invited_group_id ) {

	// get all data for the inviting group
	$pending_for_inviting_group = bpgsites_group_linkages_pending_get( $inviting_group_id );

	// make sure we have the blog's array
	if ( ! isset( $pending_for_inviting_group['sent'][$blog_id] ) ) {
		$pending_for_inviting_group['sent'][$blog_id] = array();
	}

	// if the invited group is present...
	if ( in_array( $invited_group_id, $pending_for_inviting_group['sent'][$blog_id] ) ) {

		// remove group ID and re-index
		$updated = array_merge( array_diff( $pending_for_inviting_group['sent'][$blog_id], array( $invited_group_id ) ) );

		// resave
		groups_update_groupmeta( $inviting_group_id, BPGSITES_PENDING, $updated );

	}

}



/**
 * Check if there are outstanding sent invitations for "reading with" other groups.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group
 * @return bool $has_pending Whether or not there are pending linkages
 */
function bpgsites_group_linkages_pending_sent_exists( $group_id ) {

	// get all sent data
	$pending_sent = bpgsites_group_linkages_pending_sent_get( $group_id );

	// if we have any...
	if ( count( $pending_sent ) > 0 ) return true;

	// fallback
	return false;

}



/**
 * For a given group ID and blog ID, get the group IDs that have submitted invitations.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group
 * @param int $blog_id The numeric ID of the blog (optional)
 * @return array $linked_groups Array of numeric IDs of linked groups
 */
function bpgsites_group_linkages_pending_received_get( $group_id, $blog_id = 0 ) {

	// get option if it exists
	$pending_groups = bpgsites_group_linkages_pending_get( $group_id );

	// did we request a particular blog?
	if ( $blog_id !== 0 ) {

		 // overwrite with just the nested array for that blog
		 $pending_groups['received'] = isset( $pending_groups['received'][$blog_id] ) ? $pending_groups['received'][$blog_id] : array();

	}

	// --<
	return $pending_groups['received'];

}



/**
 * Create a received invitation.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together"
 * @param int $inviting_group_id The numeric ID of the inviting group
 * @param int $invited_group_id The numeric ID of the invited group
 */
function bpgsites_group_linkages_pending_received_create( $blog_id, $inviting_group_id, $invited_group_id ) {

	// get all data for the invited group
	$pending_for_invited_group = bpgsites_group_linkages_pending_get( $invited_group_id );

	// make sure we have the blog's array
	if ( ! isset( $pending_for_invited_group['received'][$blog_id] ) ) {
		$pending_for_invited_group['received'][$blog_id] = array();
	}

	// if the inviting group isn't present...
	if ( ! in_array( $inviting_group_id, $pending_for_invited_group['received'][$blog_id] ) ) {

		// add it
		$pending_for_invited_group['received'][$blog_id][] = $inviting_group_id;

		// resave
		groups_update_groupmeta( $invited_group_id, BPGSITES_PENDING, $pending_for_invited_group );

	}

}



/**
 * Delete a received invitation.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together"
 * @param int $invited_group_id The numeric ID of the invited group
 * @param int $inviting_group_id The numeric ID of the inviting group
 */
function bpgsites_group_linkages_pending_received_delete( $blog_id, $invited_group_id, $inviting_group_id ) {

	// get all data for the invited group
	$pending_for_invited_group = bpgsites_group_linkages_pending_get( $invited_group_id );

	// make sure we have the blog's array
	if ( ! isset( $pending_for_invited_group['received'][$blog_id] ) ) {
		$pending_for_invited_group['received'][$blog_id] = array();
	}

	// if the inviting group is present...
	if ( in_array( $inviting_group_id, $pending_for_invited_group['received'][$blog_id] ) ) {

		// remove group ID and re-index
		$updated = array_merge( array_diff( $pending_for_invited_group['received'][$blog_id], array( $inviting_group_id ) ) );

		// resave
		groups_update_groupmeta( $invited_group_id, BPGSITES_PENDING, $updated );

	}

}



/**
 * Check if there are outstanding received invitations for "reading with" other groups.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group
 * @return bool $has_pending Whether or not there are pending linkages
 */
function bpgsites_group_linkages_pending_received_exists( $group_id ) {

	// get all received data
	$pending_received = bpgsites_group_linkages_pending_received_get( $group_id );

	// if we have any...
	if ( count( $pending_received ) > 0 ) return true;

	// fallback
	return false;

}



/**
 * Creates a pending linkage to another group which - when accepted - means
 * that the two groups are considered to be "reading together".
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together"
 * @param int $inviting_group_id The numeric ID of the inviting group
 * @param int $invited_group_id The numeric ID of the invited group
 */
function bpgsites_group_linkages_pending_create( $blog_id, $inviting_group_id, $invited_group_id ) {

	// create "sent" invitation
	bpgsites_group_linkages_pending_sent_create( $blog_id, $inviting_group_id, $invited_group_id );

	// create "received" invitation
	bpgsites_group_linkages_pending_received_create( $blog_id, $inviting_group_id, $invited_group_id );

}



/**
 * Converts a pending linkage to another group so that the two groups are now
 * considered to be "reading together".
 *
 * First, remove "sent" and "received" items from the inviter and invited data
 * arrays, then create the actual linkage data.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together"
 * @param int $invited_group_id The numeric ID of the invited group
 * @param int $inviting_group_id The numeric ID of the inviting group
 */
function bpgsites_group_linkages_pending_accept( $blog_id, $invited_group_id, $inviting_group_id ) {

	// delete invitations
	bpgsites_group_linkages_pending_delete( $blog_id, $invited_group_id, $inviting_group_id );

	// create new inter-group linkage
	bpgsites_group_linkages_create( $blog_id, $inviting_group_id, $invited_group_id );

	// link blog with accepting group
	bpgsites_link_blog_and_group( $blog_id, $invited_group_id );

}



/**
 * Delete "sent" and "received" items from the inviter and invited data arrays.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together"
 * @param int $invited_group_id The numeric ID of the invited group
 * @param int $inviting_group_id The numeric ID of the inviting group
 */
function bpgsites_group_linkages_pending_delete( $blog_id, $invited_group_id, $inviting_group_id ) {

	// delete "sent" invitation
	bpgsites_group_linkages_pending_sent_delete( $blog_id, $inviting_group_id, $invited_group_id );

	// delete "received" invitation
	bpgsites_group_linkages_pending_received_delete( $blog_id, $invited_group_id, $inviting_group_id );

}



// -----------------------------------------------------------------------------



/**
 * Adds UI to groups loop for "reading with" other groups.
 *
 * @since 0.1
 *
 * @param bool $echo Whether to echo or not
 * @return bool $has_linkage Whether there is a linkage or not
 */
function bpgsites_group_linkages_get_markup( $echo = true ) {

	// init return
	$has_linkage = false;

	// init HTML output
	$html = '';

	// open container div
	$html .= '<div class="bpgsites_group_linkage">' . "\n";

	// construct heading
	$html .= '<h5 class="bpgsites_group_linkage_heading">' . __( 'Read with other groups', 'bp-group-sites' ) . '</h5>' . "\n";

	// open reveal div
	$html .= '<div class="bpgsites_group_linkage_reveal">' . "\n";

	// get current blog ID
	$blog_id = bp_get_blog_id();

	// get this blog's group IDs
	$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

	// get current group ID
	$current_group_id = bp_get_current_group_id();

	// get linkages for this blog
	$linked_groups = bpgsites_group_linkages_get( $current_group_id, $blog_id );

	// if empty, set to impossible value
	if ( count( $linked_groups ) == 0 ) { $linked_groups = array( PHP_INT_MAX ); };

	// define config array
	$config_array = array(
		//'user_id' => $user_id,
		'type' => 'alphabetical',
		'max' => 1000,
		'per_page' => 1000,
		'populate_extras' => 0,
		'include' => $linked_groups,
		'page_arg' => 'bpgsites'
	);

	// new groups query
	$groups_query = new BP_Groups_Template( $config_array );

	// get linked groups
	if ( $groups_query->has_groups() ) {

		// set flag
		$has_linkage = true;

		// open existing linkages div
		$html .= '<div class="bpgsites_group_linkages">' . "\n";

		// construct heading
		$html .= '<h6 class="bpgsites_group_linkages_heading">' . __( 'Reading with', 'bp-group-sites' ) . '</h6>' . "\n";

		// open list
		$html .= '<ol class="bpgsites_group_linkages_list">' . "\n";

		// do the loop
		while ( $groups_query->groups() ) { $groups_query->the_group();

			// get group ID
			$group_id = $groups_query->group->id;

			// add label
			$html .= '<li><span class="bpgsites_group_unlink">' . $groups_query->group->name . '</span> <input type="submit" class="bpgsites_unlink_button" name="bpgsites_manage-' . $blog_id . '_' . $group_id . '-unlink" value="' . __( 'Stop', 'bp-group-sites' ) . '" /></li>' . "\n";

		}

		// close list
		$html .= '</ol>' . "\n\n";

		// close existing linkages div
		$html .= '</div>' . "\n";

	}


	// open invite div
	$html .= '<div id="bpgsites_group_linkages_invite-' . $blog_id . '" class="bpgsites_group_linkages_invite">' . "\n";

	// construct heading
	$html .= '<h6 class="bpgsites_group_linkage_heading">' . __( 'Invite to read', 'bp-group-sites' ) . '</h6>' . "\n";

	// add select2
	$html .= '<p><select class="bpgsites_group_linkages_invite_select" name="bpgsites_group_linkages_invite_select_' . $blog_id . '" style="width: 70%"><option value="0">' . __( 'Find a group to invite', 'bp-group-sites' ) . '</option></select></p>' . "\n";

	// add "Send invitation" button
	$html .= '<p class="bpgsites_invite_actions"><input type="submit" class="bpgsites_invite_button" name="bpgsites_manage-' . $blog_id . '-invite" value="' . __( 'Send invitation', 'bp-group-sites' ) . '" /></p>' . "\n";

	// close invite div
	$html .= '</div>' . "\n";

	// close reveal div
	$html .= '</div>' . "\n";

	// close container div
	$html .= '</div>' . "\n\n\n\n";

	// clear it
	unset( $groups_query );

	// output unless overridden
	if ( $echo ) echo $html;

	// --<
	return $has_linkage;

}



/**
 * For a given group ID, get linked group IDs.
 *
 * By default, this function will return a master array of linkages for all
 * blogs with nested arrays of group IDs keyed by blog ID. Passing a blog ID as
 * a parameter, however, will return the specific array for that blog.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group
 * @param int $blog_id The numeric ID of the blog (optional)
 * @return array $linked_groups Array of numeric IDs of linked groups
 */
function bpgsites_group_linkages_get( $group_id, $blog_id = 0 ) {

	// get option if it exists
	$linked_groups = groups_get_groupmeta( $group_id, BPGSITES_LINKED );

	// sanity check
	if ( ! is_array( $linked_groups ) ) { $linked_groups = array(); }

	// did we request a particular blog?
	if ( $blog_id !== 0 ) {

		 // overwrite with just the nested array for that blog
		 $linked_groups = isset( $linked_groups[$blog_id] ) ? $linked_groups[$blog_id] : array();

	}

	// --<
	return $linked_groups;

}



/**
 * Create linkages between two groups "reading together".
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together"
 * @param int $inviting_group_id The numeric ID of the inviting group
 * @param int $invited_group_id The numeric ID of the invited group
 */
function bpgsites_group_linkages_create( $blog_id, $inviting_group_id, $invited_group_id ) {

	// create sender linkages
	bpgsites_group_linkage_create( $blog_id, $inviting_group_id, $invited_group_id );

	// create recipient linkages
	bpgsites_group_linkage_create( $blog_id, $invited_group_id, $inviting_group_id );

}



/**
 * Delete linkages between two groups "reading together".
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog being "read together"
 * @param int $primary_group_id The numeric ID of the primary group
 * @param int $secondary_group_id The numeric ID of the secondary group
 */
function bpgsites_group_linkages_delete( $blog_id, $primary_group_id, $secondary_group_id ) {

	// delete primary group linkages
	bpgsites_group_linkage_delete( $blog_id, $primary_group_id, $secondary_group_id );

	// delete secondary group linkages
	bpgsites_group_linkage_delete( $blog_id, $secondary_group_id, $primary_group_id );

}



/**
 * For a given group ID, get linked group IDs for a specific blog.
 *
 * @since 0.1
 *
 * @param int $group_id the numeric ID of the group
 * @param int $blog_id the numeric ID of the blog
 * @return array $linked_groups Array of numeric IDs of linked groups
 */
function bpgsites_group_linkages_get_groups_by_blog_id( $group_id, $blog_id ) {

	// get linked groups
	$linked = bpgsites_group_linkages_get( $group_id );

	// get those for this blog
	$linked_groups = isset( $linked[$blog_id] ) ? $linked[$blog_id] : array();

	// --<
	return $linked_groups;

}



/**
 * For a given blog ID, check if two group IDs are linked.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog being "read together"
 * @param int $primary_group_id The numeric ID of the primary group
 * @param int $secondary_group_id The numeric ID of the secondary group
 * @return bool Whether or not the groups are linked
 */
function bpgsites_group_linkages_link_exists( $blog_id, $primary_group_id, $secondary_group_id ) {

	// get the existing linkages for the primary group
	$linked = bpgsites_group_linkages_get( $primary_group_id );

	// get the array for this blog ID
	$linked_group_ids = isset( $linked[$blog_id] ) ? $linked[$blog_id] : array();

	// if the secondary group is in the list, there must be a linkage
	if ( in_array( $secondary_group_id, $linked_group_ids ) ) return true;

	// fallback
	return false;

}



/**
 * AJAX handler for group linkage autocomplete using the Select2 JS library.
 *
 * @since 0.1
 */
function bpgsites_group_linkages_get_ajax() {

	global $groups_template;

	// get current group (or set impossible value if not present)
	$current_group_id = isset( $_POST['group_id'] ) ? $_POST['group_id'] : PHP_INT_MAX;

	// get current blog
	$current_blog_id = isset( $_POST['blog_id'] ) ? $_POST['blog_id'] : PHP_INT_MAX;

	// get already-linked groups for this blog
	$linked = bpgsites_group_linkages_get( $current_group_id, $current_blog_id );

	// construct exclude
	$exclude = array_unique( array_merge( $linked, array( $current_group_id ) ) );

	// get groups this user can see for this search
	$groups = groups_get_groups( array(
		'user_id' => is_super_admin() ? 0 : bp_loggedin_user_id(),
		'search_terms' => $_POST['s'],
		'show_hidden' => true,
		'populate_extras' => false,
		'exclude' => $exclude,
	) );

	// init return
	$json = array();

	// fake a group template
	$groups_template = new stdClass;
	$groups_template->group = new stdClass;

	// loop through our groups
	foreach( $groups['groups'] AS $group ) {

		// apply group object to template so API functions
		$groups_template->group = $group;

		// gert description
		$description = bp_create_excerpt(

			// content
			strip_tags( stripslashes( $group->description ) ),

			// max length
			70,

			// options
			array(
				'ending' => '&hellip;',
				'filter_shortcodes' => false,
			)

		);

		// add item to output array
		$json[] = array(
			'id'          => $group->id,
			'name'        => stripslashes( $group->name ),
			'type'        => bp_get_group_type(),
			'description' => $description,
			'avatar' => bp_get_group_avatar_mini(),
			'total_member_count' => $group->total_member_count,
			'private' => $group->status !== 'public'
		);

	}

	// send data
	echo json_encode( $json );
	exit();

}

// ajax handler for group linkage autocomplete
add_action( 'wp_ajax_bpgsites_get_groups', 'bpgsites_group_linkages_get_ajax' );



/**
 * Create a linkage from a primary group to a secondary group.
 *
 * This must be called for both groups in order to establish a two-way linkage.
 *
 * @see bpgsites_group_linkages_create()
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together"
 * @param int $primary_group_id The numeric ID of the primary group
 * @param int $secondary_group_id The numeric ID of the secondary group
 */
function bpgsites_group_linkage_create( $blog_id, $primary_group_id, $secondary_group_id ) {

	// get the existing linkages for the inviting group
	$linked = bpgsites_group_linkages_get( $primary_group_id );

	// get the array for this blog ID
	$linked_group_ids = isset( $linked[$blog_id] ) ? $linked[$blog_id] : array();

	// is the invited group in the list?
	if ( ! in_array( $secondary_group_id, $linked_group_ids ) ) {

		// no, add it
		$linked_group_ids[] = $secondary_group_id;

		// overwrite in parent array
		$linked[$blog_id] = $linked_group_ids;

		// save updated option
		groups_update_groupmeta( $primary_group_id, BPGSITES_LINKED, $linked );

	}

}



/**
 * Delete the linkage from a primary group to a secondary group.
 *
 * This must be called for both groups in order to destroy a two-way linkage.
 *
 * @see bpgsites_group_linkages_delete()
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog being "read together"
 * @param int $primary_group_id The numeric ID of the primary group
 * @param int $secondary_group_id The numeric ID of the secondary group
 */
function bpgsites_group_linkage_delete( $blog_id, $primary_group_id, $secondary_group_id ) {

	// get their linkages
	$linked = bpgsites_group_linkages_get( $primary_group_id );

	// get the array for this blog ID
	$linked_group_ids = isset( $linked[$blog_id] ) ? $linked[$blog_id] : array();

	// is this one in the list?
	if ( in_array( $secondary_group_id, $linked_group_ids ) ) {

		// yes - remove group and re-index
		$updated = array_merge( array_diff( $linked_group_ids, array( $secondary_group_id ) ) );

		// overwrite in parent array
		$linked[$blog_id] = $updated;

		// save updated option
		groups_update_groupmeta( $primary_group_id, BPGSITES_LINKED, $linked );

	}

}



// -----------------------------------------------------------------------------



/**
 * Show option to make a group a showcase group.
 *
 * @since 0.1
 */
function bpgsites_showcase_group_settings_form() {

	// get name
	$name = apply_filters( 'bpgsites_extension_title', __( 'Group Sites', 'bp-group-sites' ) );

	// init checked
	$checked = '';

	// get existing option
	$showcase_groups = bpgsites_showcase_groups_get();

	// get current group ID
	$group_id = bpgsites_get_current_group_id();

	// sanity check list and group ID
	if ( count( $showcase_groups ) > 0 AND ! is_null( $group_id ) ) {

		// is this group's ID in the list
		if ( in_array( $group_id, $showcase_groups ) ) {

			// override checked
			$checked = ' checked="checked"';

		}

	}

	?>
	<h4><?php echo esc_html( $name ); ?></h4>

	<p><?php _e( 'To make this group a showcase group, make sure that it is set to "Private" above, then check the box below. The effect will be that the comments left by members of this group will always appear to readers. Only other members of this group will be able to reply to those comments.', 'bp-group-sites' ); ?></p>

	<div class="checkbox">
		<label><input type="checkbox" id="bpgsites-showcase-group" name="bpgsites-showcase-group" value="1"<?php echo $checked ?> /> <?php _e( 'Make this group a showcase group', 'bp-group-sites' ) ?></label>
	</div>

	<hr />

	<?php

}

// add actions for the above
add_action ( 'bp_after_group_settings_admin' ,'bpgsites_showcase_group_settings_form' );
add_action ( 'bp_after_group_settings_creation_step' ,'bpgsites_showcase_group_settings_form' );




/**
 * Intercept group settings save process.
 *
 * @since 0.1
 *
 * @param object $group the group object
 */
function bpgsites_showcase_group_save( $group ) {

	/*
	If the checkbox IS NOT checked, remove from option if it is there
	If the checkbox IS checked, add it to the option if not already there
	*/

	// get existing option
	$showcase_groups = bpgsites_showcase_groups_get();

	// if not checked
	if ( ! isset( $_POST['bpgsites-showcase-group'] ) ) {

		// sanity check list
		if ( count( $showcase_groups ) > 0 ) {

			// is this group's ID in the list?
			if ( in_array( $group->id, $showcase_groups ) ) {

				// yes, remove group ID and re-index
				$updated = array_merge( array_diff( $showcase_groups, array( $group->id ) ) );

				// save option
				bpgsites_site_option_set( 'bpgsites_auth_groups', $updated );

			}

		}

	} else {

		// kick out if value is not 1
		if ( absint( $_POST['bpgsites-showcase-group'] ) !== 1 ) { return; }

		// is this group's ID missing from the list?
		if ( ! in_array( $group->id, $showcase_groups ) ) {

			// add it
			$showcase_groups[] = $group->id;

			// save option
			bpgsites_site_option_set( 'bpgsites_auth_groups', $showcase_groups );

		}

	}

}

// add action for the above
add_action( 'groups_group_after_save', 'bpgsites_showcase_group_save' );



/**
 * Get all showcase groups.
 *
 * @since 0.1
 *
 * @return array $showcase_groups the showcase group IDs
 */
function bpgsites_showcase_groups_get() {

	// get existing option
	$showcase_groups = bpgsites_site_option_get( 'bpgsites_auth_groups', array() );

	// --<
	return $showcase_groups;

}



/**
 * Test if a group is a showcase group.
 *
 * @since 0.1
 *
 * @param int $group_id the group ID
 * @return bool $is_showcase_group the group is or is not a showcase group
 */
function bpgsites_is_showcase_group( $group_id ) {

	// get existing option
	$showcase_groups = bpgsites_showcase_groups_get();

	// sanity check list
	if ( count( $showcase_groups ) > 0 ) {

		// is this group's ID in the list?
		if ( in_array( $group_id, $showcase_groups ) ) {

			// --<
			return true;

		}

	}

	// --<
	return false;

}



/**
 * Check if user is a member of a showcase group for this blog.
 *
 * @since 0.1
 *
 * @return bool $passed user is a member of a showcase group for this blog
 */
function bpgsites_is_showcase_group_member() {

	// super admins can post anywhere, so allow
	if ( is_super_admin() ) return true;

	// false by default
	$passed = false;

	// get existing option
	$showcase_groups = bpgsites_showcase_groups_get();

	// sanity check list
	if ( count( $showcase_groups ) > 0 ) {

		// get current blog
		$current_blog_id = get_current_blog_id();

		// get user ID
		$user_id = bp_loggedin_user_id();

		// loop
		foreach( $showcase_groups AS $group_id ) {

			// is this user a member?
			if ( groups_is_user_member( $user_id, $group_id ) ) {

				// if this showcase group is a showcase group for this blog
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
 * Filter media buttons by showcase groups context.
 *
 * @since 0.1
 *
 * @param bool $enabled if media buttons are enabled
 * @return bool $enabled if media buttons are enabled
 */
function bpgsites_showcase_group_media_buttons( $allowed ) {

	// get current blog
	$current_blog_id = get_current_blog_id();

	// if this isn't a group site, pass through
	if ( ! bpgsites_is_groupsite( $current_blog_id ) ) return $allowed;

	// disallow by default
	$allowed = false;

	// is this user a member of a showcase group on this blog?
	if ( bpgsites_is_showcase_group_member() ) {

		// allow
		return true;

	}

	// --<
	return $allowed;

}

// add filter for the above
add_filter( 'commentpress_rte_media_buttons', 'bpgsites_showcase_group_media_buttons', 10, 1 );



/**
 * Filter quicktags by showcase groups context.
 *
 * @since 0.1
 *
 * @param array $quicktags the quicktags
 * @return array/bool $quicktags false if quicktags are disabled, array of buttons otherwise
 */
function bpgsites_showcase_group_quicktags( $quicktags ) {

	// get current blog
	$current_blog_id = get_current_blog_id();

	// if this isn't a group site, pass through
	if ( ! bpgsites_is_groupsite( $current_blog_id ) ) return $quicktags;

	// disallow quicktags by default
	$quicktags = false;

	// is this user a member of a showcase group on this blog?
	if ( bpgsites_is_showcase_group_member() ) {

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
add_filter( 'commentpress_rte_quicktags', 'bpgsites_showcase_group_quicktags', 10, 1 );



