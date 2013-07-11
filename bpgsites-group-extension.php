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
	 * @description: initialises this object
	 * @return nothing
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
	 * @description: the content of the extension tab in the group admin
	 * @return nothing
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
	 * @description: runs after the user clicks a submit button on the edit screen
	 * @return nothing
	 */
	function edit_screen_save() {
		
		//print_r( $_POST ); die();
		
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
	 * @description display our content when the nav item is selected
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
	 */
	function admin_screen( $group_id ) {
		
		// hand off to function
		echo bpgsites_get_extension_admin_screen();

	}
	
	
	
	/**
	 * The routine run after the group is saved on the Dashboard group admin screen
	 *
	 * @param int $group_id the numeric ID of the group being edited
	 */
	function admin_screen_save( $group_id ) {
	
		// Grab your data out of the $_POST global and save as necessary
		
	}
	
	
	
	/**
	 * @return array contains $blog_id and $action
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
	 * @description manages the linkages between "groups reading together"
	 * @param int $group_id the numeric ID of the group
	 * @param int $blog_id the numeric ID of the blog
	 */
	function _update_group_linkages( $blog_id, $group_id ) {
	
		// is this a comment in a group?
		if ( isset( $_POST['bpgsites_linked_groups_'.$blog_id] ) AND is_array( $_POST['bpgsites_linked_groups_'.$blog_id] ) ) {
	
			// get values from post array
			$group_ids = $_POST['bpgsites_linked_groups_'.$blog_id];
		
			// sanitise all the items
			array_walk( $group_ids, create_function( '&$val', '$val = absint( $val );' ) );
			
			//print_r( $group_ids ); die();
			
		}
	
	}
	
	
	
} // class ends



// register our class
bp_register_group_extension( 'BPGSites_Group_Extension' );



/** 
 * @description: the content of the public extension page
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
 * @description: the content of the extension group admin page
 */
function bpgsites_get_extension_edit_screen() {

	do_action( 'bp_before_blogs_loop' );
	
	// get all blogs - TODO: add AJAX query string compatibility?
	if ( bpgsites_has_blogs() ) {
	
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
			$in_group = bpgsites_is_blog_in_group()
			
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
 * @description: the content of the extension admin screen
 */
function bpgsites_get_extension_admin_screen() {

	echo '<p>BP Group Sites Admin Screen</p>';

}



/** 
 * @description: adds checkboxes to groups loop for "reading with" other groups
 */
function bpgsites_get_group_linkage() {

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
	//print_r( $group_ids ); die();

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
		//print_r( $group ); //die();

		// either this user is a member or it's public
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

	//print_r( $user_group_ids ); die();
	
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
		
				// add arbitrary divider
				$html .= '<span class="bpgsites_linked_group">'."\n";
		
				// add checkbox
				$html .= '<input type="checkbox" class="bpgsites_group_checkbox" name="bpgsites_linked_groups_'.$blog_id.'[]" id="bpgsites_linked_group_'.$blog_id.'_'.$group_id.'" value="'.$group_id.'" />'."\n";
			
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



