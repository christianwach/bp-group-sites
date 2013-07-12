<?php /*
================================================================================
BP Group Sites Activity Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

Throw any functions which deal with BuddyPress activity in here.

--------------------------------------------------------------------------------
*/



/*
================================================================================
Class Name
================================================================================
*/

class BpGroupSites_Activity {

	/*
	============================================================================
	Properties
	============================================================================
	*/

	// groups
	public $groups = array();
	
	
	
	/** 
	 * @description: initialises this object
	 * @return object
	 */
	function __construct() {
	
		// --<
		return $this;

	}
	
	
	
	/**
	 * @description: register hooks for this class
	 * @return nothing
	 */
	public function register_hooks() {
	
		// hooks that always need to be present...
		
		/*
		// add our posts filter
		add_action( 'bp_activity_filter_options', array( $this, 'posts_filter_option' ) );
		add_action( 'bp_group_activity_filter_options', array( $this, 'posts_filter_option' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'posts_filter_option' ) );
		*/
		
		// add our comments filter
		add_action( 'bp_activity_filter_options', array( $this, 'comments_filter_option' ) );
		add_action( 'bp_group_activity_filter_options', array( $this, 'comments_filter_option' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'comments_filter_option' ) );
		
		// if the current blog is a group site...
		if ( bpgsites_is_groupsite( get_current_blog_id() ) ) {
			
			// add custom post activity (disabled until later)
			//add_action( 'bp_activity_before_save', array( $this, 'custom_post_activity' ), 10, 1 );
			
			// add custom comment activity
			add_action( 'bp_activity_before_save', array( $this, 'custom_comment_activity' ), 10, 1 );
			
			// add our dropdown (or hidden input) to comment form
			add_filter( 'comment_id_fields', array( $this, 'get_comment_group_selector' ), 10, 3 );
			
			// hook into comment save process
			add_action( 'comment_post', array( $this, 'save_comment_group_id' ), 10, 2 );
			
			// add action for checking comment moderation
			add_filter( 'pre_comment_approved', array( $this, 'check_comment_approval' ), 100, 2 );
			
			// if on front end...
			if ( !is_admin() ) {
			
				// add navigation items for groups
				add_filter( 'cp_nav_after_network_home_title', array( $this, 'navigation_links' ) );
			
				// override reply to link
				add_filter( 'comment_reply_link', array( $this, 'override_reply_to_link' ), 10, 4 );
			
				// override CommentPress TinyMCE
				add_filter( 'cp_override_tinymce', array( $this, 'disable_tinymce' ), 10, 1 );
				
				// add filter for commenting capability
				add_filter( 'commentpress_allowed_to_comment', array( $this, 'allow_anon_commenting' ), 10, 1 );
				
				// add action to insert comments-by-group filter
				add_action( 'commentpress_before_scrollable_comments', array( $this, 'get_group_comments_filter' ) );
				
				// add group ID as class to comment
				add_filter( 'comment_class', array( $this, 'add_group_to_comment_class' ), 10, 4 );

				// filter comments by group membership
				add_action( 'pre_get_comments', array( $this, 'filter_comments' ), 100, 1 );
				
				// override what is reported by get_comments_number
				add_filter( 'get_comments_number', array( $this, 'get_comments_number' ), 20, 2 );
				
				// override comment form if no group membership
				add_filter( 'commentpress_show_comment_form', array( $this, 'show_comment_form' ), 10, 1 );
				
			}
		
		}
		
	}
	
	
		
	//##########################################################################
	
	
	
	/**
	 * @description: record the blog activity for the group
	 * @see: bp_groupblog_set_group_to_post_activity()
	 */
	function custom_comment_activity( $activity ) {
	
		//print_r( array( 'a1' => $activity ) ); //die();
	
		// only deal with comments
		if ( ( $activity->type != 'new_blog_comment' ) ) return $activity;
	
		// get group ID from POST
		$group_id = $this->get_group_id_from_comment_form();

		// kick out if not a comment in a group
		if ( false === $group_id ) return $activity;

		// set activity type
		$type = 'new_groupsite_comment';

		// okay, let's get the group object
		$group = groups_get_group( array( 'group_id' => $group_id ) );
		//print_r( $group ); die();

		// see if we already have the modified activity for this blog post
		$id = bp_activity_get_activity_id( array(
	
			'user_id' => $activity->user_id,
			'type' => $type,
			'item_id' => $group_id,
			'secondary_item_id' => $activity->secondary_item_id
		
		) );

		// if we don't find a modified item...
		if ( !$id ) {
	
			// see if we have an unmodified activity item
			$id = bp_activity_get_activity_id( array(
		
				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id
			
			) );
		
		}

		// If we found an activity for this blog comment then overwrite that to avoid having 
		// multiple activities for every blog comment edit
		if ( $id ) $activity->id = $id;

		// get the comment
		$comment = get_comment( $activity->secondary_item_id );
		//print_r( $comment ); //die();
	
		// get the post
		$post = get_post( $comment->comment_post_ID );
		//print_r( $post ); die();
	
		// was it a registered user?
		if ($comment->user_id != '0') {
	
			// get user details
			$user = get_userdata( $comment->user_id );
		
			// construct user link
			$user_link = bp_core_get_userlink( $activity->user_id );
		
		} else {
	
			// show anonymous user
			$user_link = '<span class="anon-commenter">'.__( 'Anonymous', 'bpgsites' ).'</span>';

		}
		
		// allow plugins to override the name of the activity item
		$activity_name = apply_filters(
			'bpgsites_activity_post_name',
			__( 'post', 'bpgsites' ),
			$post
		);
	
		// init target link
		$target_post_link = '<a href="' . get_permalink( $post->ID ) .'">' . 
								esc_html( $post->post_title ) . 
							'</a>';
	
		// Replace the necessary values to display in group activity stream
		$activity->action = sprintf( 
		
			__( '%s left a %s on a %s %s in the group %s:', 'bpgsites' ), 
		
			$user_link, 
			'<a href="' . $activity->primary_link .'">' . __( 'comment', 'bpgsites' ) . '</a>', 
			$activity_name, 
			$target_post_link, 
			'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_html( $group->name ) . '</a>' 
		
		);
	
		// apply group id
		$activity->item_id = (int)$group_id;
	
		// change to groups component
		$activity->component = 'groups';
	
		// having marked all groupblogs as public, we need to hide activity from them if the group is private
		// or hidden, so they don't show up in sitewide activity feeds.
		if ( 'public' != $group->status ) {
			$activity->hide_sitewide = true;
		} else {
			$activity->hide_sitewide = false;
		}
	
		// set unique type
		$activity->type = $type;
	
		// prevent from firing again
		remove_action( 'bp_activity_before_save', array( $this, 'custom_comment_activity' ) );
	
		//print_r( array( 'a2' => $activity ) ); die();
	
		// --<
		return $activity;

	}



	/**
	 * @description: add a filter option to the filter select box on group activity pages
	 */
	function comments_filter_option() { 
		
		// default name, but allow plugins to override
		$comment_name = apply_filters( 
			'bpgsites_comment_name', 
			__( 'Group Site Comments', 'bpgsites' )
		);
		
		// construct option
		$option = '<option value="new_groupsite_comment">'.$comment_name.'</option>'."\n";
		
		// print
		echo $option;
		
		/*
		__( 'Group Site Comments', 'bpgsites' )
		*/
		
	}



	/**
	 * @description: add a filter option to the filter select box on group activity pages
	 * @param int $count the current comment count
	 * @param int $post_id the current post
	 */
	function get_comments_number( $count, $post_id ) {

		// get comments for this post again
		$comments = get_comments( array(
			'post_id' => $post_id
		) );
	
		// did we get any?
		if ( is_array( $comments ) ) {
		
			// return the number
			return count( $comments );
		
		}

		// otherwise, pass through
		return $count;

	}



	/** 
	 * @description: when get_comments is called, show only those from groups to which the user belongs
	 * @param object $comments the current query
	 */
	function filter_comments( $comments ) {

		// get the groups this user can see
		$user_group_ids = $this->get_groups_for_user();
		
		// if none, then what?
		if ( count( $user_group_ids ) === 0 ) {
	
			// set a non-existent group ID
			$user_group_ids = array( 0 );
		
		}
	
		// init meta query
		$meta_query = array(

			// we want comments with any relevant group ID
			//'relation'  => 'OR',
		
			// comments in groups
			 array(
				'key'   => BPGSITES_COMMENT_META_KEY,
				'value' => $user_group_ids,
				'compare' => 'IN'
			),
		
			/*
			// comments not in a group (doesn't show, unfortunately)
			// perhaps we need to save comment_meta with a zero value?
			 array(
				'key'   => BPGSITES_COMMENT_META_KEY,
				'value' => ''
			)
			*/

		);

		// add our meta query
		$comments->query_vars['meta_query'] = $meta_query;
	
		// parse meta query again
		$comments->meta_query->parse_query_vars( $comments->query_vars );

	}



	/** 
	 * @description: when a comment is saved, this also saves the ID of the group it was submitted to
	 * @param integer $comment_id the ID of the comment
	 * @param integer $comment_status the approval status of the comment
	 */
	function save_comment_group_id( $comment_id, $comment_status ) {

		// we don't need to look at approval status
		$group_id = $this->get_group_id_from_comment_form();

		// is this a comment in a group?
		if ( false !== $group_id ) {
	
			// if the custom field already has a value...
			if ( get_comment_meta( $comment_id, BPGSITES_COMMENT_META_KEY, true ) !== '' ) {
		
				// update the data
				update_comment_meta( $comment_id, BPGSITES_COMMENT_META_KEY, $group_id );
			
			} else {
		
				// add the data
				add_comment_meta( $comment_id, BPGSITES_COMMENT_META_KEY, $group_id, true );
			
			}

		}
	
	}



	/** 
	 * @description: override CommentPress Reply To link
	 * @param string $link the existing link
	 * @param array $args the setup array
	 * @param object $comment the comment
	 * @param object $post the post
	 */
	function override_reply_to_link( $link, $args, $comment, $post ) {

		// pass through if not logged in
		if ( ! is_user_logged_in() ) return $link;
	
		// get current blog ID
		$blog_id = get_current_blog_id();
	
		// pass through if not group site
		if ( ! bpgsites_is_groupsite( $blog_id ) ) return $link;
	
		// get comment group
		$group_id = $this->get_comment_group_id( $comment->comment_ID );
	
		// is this user is a member?
		if ( groups_is_user_member( bp_loggedin_user_id(), $group_id ) ) return $link;
	
		// get the group
		$group = groups_get_group( array(
			'group_id'   => $group_id
		) );
	
		// construct link
		$link = '<a rel="nofollow" href="'.bp_get_group_permalink( $group ).'">'.__( 'Join group to reply', 'bpgsites' ).'</a>';
	
		// --<
		return $link;

	}



	/** 
	 * @description: override CommentPress TinyMCE setting
	 * @param bool $tinymce whether TinyMCE is enabled or not
	 * @return bool $tinymce whether TinyMCE is enabled or not
	 */
	function disable_tinymce( $tinymce ) {

		// get current blog ID
		$blog_id = get_current_blog_id();
	
		// pass through if not group site
		if ( ! bpgsites_is_groupsite( $blog_id ) ) return $tinymce;
		
		// is the current member in a relevant group?
		if ( ! $this->is_user_in_group_reading_this_site() ) {
		
			// add filters on reply to link
			add_filter( 'commentpress_reply_to_para_link_text', array( $this, 'override_reply_to_text' ), 10, 2 );
			add_filter( 'commentpress_reply_to_para_link_href', array( $this, 'override_reply_to_href' ), 10, 1 );
			add_filter( 'commentpress_reply_to_para_link_onclick', array( $this, 'override_reply_to_onclick' ), 10, 2 );
		
			// disable
			return 0;
			
		}
		
		// use TinyMCE if logged in
		if ( is_user_logged_in() ) return $tinymce;
	
		// don't use TinyMCE
		return 0;

	}



	/** 
	 * @description: decides whether or not to show comment form
	 * @param bool $show whether or not to show comment form
	 * @return bool $show Show the comment form
	 */
	function show_comment_form( $show ) {
	
		// get current blog ID
		$blog_id = get_current_blog_id();
	
		// pass through if not group site
		if ( ! bpgsites_is_groupsite( $blog_id ) ) return $show;
		
		// is the current member in a relevant group?
		if ( $this->is_user_in_group_reading_this_site() ) return $show;
		
		// --<
		return false;
	
	}



	/** 
	 * @description: override content of the reply to link
	 * @param string $link_text the full text of the reply to link
	 * @param string $paragraph_text paragraph text
	 * @return string $link_text updated content of the reply to link
	 */
	function override_reply_to_text( $link_text, $paragraph_text ) {
	
		// construct link content
		$link_text = sprintf(
			__( 'Join a group to leave a comment on %s', 'bpgsites' ),
			$paragraph_text
		);
		
		// --<
		return $link_text;
	
	}



	/** 
	 * @description: override content of the reply to link target
	 * @param string $href existing target URL
	 * @return string $href permalink of the groups directory
	 */
	function override_reply_to_href( $href ) {
	
		// --<
		return bp_get_groups_directory_permalink();
	
	}



	/** 
	 * @description: override content of the reply to link
	 * @return string $onclick the reply to onclick attribute
	 */
	function override_reply_to_onclick( $onclick ) {
	
		// --<
		return '';
	
	}



	/** 
	 * @description: check if anonymous commenting is allowed
	 * @param bool $allowed whether commenting is is allowed or not
	 * @return bool $allowed whether commenting is is allowed or not
	 */
	function allow_anon_commenting( $allowed ) {

		// get current blog ID
		$blog_id = get_current_blog_id();
	
		// pass through if not group site
		if ( ! bpgsites_is_groupsite( $blog_id ) ) { return $allowed; }
	
		// not allowed
		return false;

	}



	/** 
	 * @description for group sites, if the user is a member of the group, allow unmoderated comments
	 * @param int $approved the comment status
	 * @param array $commentdata the comment data
	 */
	function check_comment_approval( $approved, $commentdata ) {
	
		// get current blog ID
		$blog_id = get_current_blog_id();
	
		// pass through if not group site
		if ( ! bpgsites_is_groupsite( $blog_id ) ) { return $approved; }
	
		// get the user ID of the comment author
		$user_id = absint( $commentdata['user_ID'] );
	
		// get group that comment was posted into (comment meta is not saved yet)
		$group_id = $this->get_group_id_from_comment_form();
	
		// did we get one?
		if ( $group_id != '' AND is_numeric( $group_id ) ) {
		
			// is this user a member?
			if ( groups_is_user_member( $user_id, $group_id ) ) {
	
				// allow un-moderated commenting
				return 1;
	
			}
	
		}
	
		// pass through
		return $approved;
	
	}



	/** 
	 * @description: for a given comment ID, get the ID of the group it is posted in
	 * @param int $comment_id the comment ID
	 * @param int $group_id the group ID (empty string if none found)
	 */
	function get_comment_group_id( $comment_id ) {
	
		// get group ID from comment meta
		$group_id = get_comment_meta( 
			$comment_id, 
			BPGSITES_COMMENT_META_KEY, 
			true // only return a single value
		);
	
		// --<
		return $group_id;

	}



	/** 
	 * @description: inserts a dropdown (or hidden input) into the comment form
	 * @param string $result existing markup to be sent to browser
	 * @param int $comment_id the comment ID
	 * @param int $reply_to_id the comment ID to which this comment is a reply
	 * @return string $result the markup sent to the browser
	 */
	function get_comment_group_selector( $result, $comment_id, $reply_to_id ) {
	
		// if the comment is a reply to another...
		if ( $reply_to_id !== 0 ) {
	
			// this will only kick in if Javascript is off or the moveForm script is
			// not used in the theme. Our plugin Javascript handles this when the
			// form is moved around the DOM.
		
			// get the group of the reply_to_id
			$group_id = $this->get_comment_group_id( $reply_to_id );
		
			// did we get one?
			if ( $group_id != '' AND is_numeric( $group_id ) ) {
		
				// show a hidden input so that this comment is also posted in that group
				$result .= '<input type="hidden" id="bpgsites-post-in" name="bpgsites-post-in" value="'.$group_id.'" />'."\n";
		
			}
		
			// --<
			return $result;
		
		}
	
		// get current blog ID
		$blog_id = get_current_blog_id();
	
		// kick out if not group site
		if ( ! bpgsites_is_groupsite( $blog_id ) ) { return $result; }
	
		// get this blog's group IDs
		$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );
	
		// define config array
		$config_array = array(
			'user_id' => bp_loggedin_user_id(),
			'type' => 'alphabetical',
			'max' => 100,
			'per_page' => 100,
			'populate_extras' => 0,
			'include' => $group_ids
		);
	
		// get groups
		if ( bp_has_groups( $config_array ) ) {
		
			global $groups_template;
		
			// if more than one...
			if ( $groups_template->group_count > 1 ) {
			
				// construct dropdown
				$result .= '<span id="bpgsites-post-in-box">'."\n";
				$result .= '<span>'.__( 'Post in', 'bpgsites' ).':</span>'."\n";
				$result .= '<select id="bpgsites-post-in" name="bpgsites-post-in">'."\n";
			
				// do the loop
				while ( bp_groups() ) {  bp_the_group();
			
					// add option
					$result .= '<option value="'.bp_get_group_id().'">'.bp_get_group_name().'</option>'."\n";
				
				} // end while
			
				// close tags
				$result .= '</select>'."\n";
				$result .= '</span>'."\n";
			
			} else {
			
				// do the loop, but only has one item
				while ( bp_groups() ) { bp_the_group(); 
			
					// show a hidden input
					$result .= '<input type="hidden" id="bpgsites-post-in" name="bpgsites-post-in" value="'.bp_get_group_id().'" />'."\n";
			
				} // end while
			
			}
	
		}
	
		// --<
		return $result;
			
	}



	/** 
	 * @description: adds filtering above scrollable comments in CommentPress Responsive
	 */
	function get_group_comments_filter() {

		// init HTML output
		$html = '';
		
		// get the groups this user can see
		$user_group_ids = $this->get_groups_for_user();
	
		// kick out if empty
		if ( count( $user_group_ids ) === 0 ) return;
	
		// define config array
		$config_array = array(
			//'user_id' => $user_id,
			'type' => 'alphabetical',
			'max' => 100,
			'per_page' => 100,
			'populate_extras' => 0,
			'include' => $user_group_ids
		);
	
		// get groups
		if ( bp_has_groups( $config_array ) ) {
		
			// access object
			global $groups_template, $post;
		
			// only show if user has more than one...
			if ( $groups_template->group_count > 1 ) {
			
				// construct heading (the no_comments class prevents this from printing)
				$html .= '<h3 class="bpgsites_group_filter_heading no_comments">'.
							'<a href="#bpgsites_group_filter">'.__( 'Filter comments by group', 'bpgsites' ).'</a>'.
						 '</h3>'."\n";
			
				// open div
				$html .= '<div id="bpgsites_group_filter" class="bpgsites_group_filter">'."\n";
			
				// open form
				$html .= '<form id="bpgsites_comment_group_filter" name="bpgsites_comment_group_filter" action="'.get_permalink( $post->ID ).'" method="post">'."\n";
			
				// do the loop
				while ( bp_groups() ) {  bp_the_group();
			
					// add arbitrary divider
					$html .= '<span class="bpgsites_comment_group">'."\n";
			
					// add checkbox
					$html .= '<input type="checkbox" class="bpgsites_group_checkbox" name="bpgsites_comment_groups[]" id="bpgsites_comment_group_'.bp_get_group_id().'" value="'.bp_get_group_id().'" checked="checked" />'."\n";
				
					// add label
					$html .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_'.bp_get_group_id().'">'.bp_get_group_name().'</label>'."\n";
				
					// close arbitrary divider
					$html .= '</span>'."\n";
			
				} // end while
			
				// add submit button
				$html .= '<input type="submit" id="bpgsites_comment_group_submit" value="'.__( 'Filter', 'bpgsites' ).'" />'."\n";
			
				// close tags
				$html .= '</form>'."\n";
				$html .= '</div>'."\n";
			
			}
	
		}
	
		// output
		echo $html;
	
	}



	/** 
	 * @description: when a comment is saved, get the ID of the group it was submitted to
	 * @return integer $group_id the group ID of the input in the comment form
	 */
	function get_group_id_from_comment_form() {

		// init as false
		$group_id = false;

		// is this a comment in a group?
		if ( isset( $_POST['bpgsites-post-in'] ) AND is_numeric( $_POST['bpgsites-post-in'] ) ) {
	
			// get group ID
			$group_id = absint( $_POST['bpgsites-post-in'] );
		
		}
	
		// --<
		return $group_id;
	
	}



	/** 
	 * @description: when our filtering form is is submitted, parse groups by selection
	 * @return array $filtered the filtered group IDs
	 */
	function filter_groups_by_checkboxes( $group_ids ) {

		// is this a comment in a group?
		if ( isset( $_POST['bpgsites_comment_groups'] ) AND is_array( $_POST['bpgsites_comment_groups'] ) ) {
	
			// overwrite with post array
			$group_ids = $_POST['bpgsites_comment_groups'];
		
			// sanitise all the items
			array_walk( $group_ids, create_function( '&$val', '$val = absint( $val );' ) );
	
			// set cookie with delmited array
			//setcookie( 'bpgsites_comment_groups', implode( '-', $group_ids ) );
		
		} else {
		
			/*
			// do we have the cookie?
			if ( isset( $_COOKIE['bpgsites_comment_groups'] ) ) {
		
				// get its contents
				$group_ids = explode( '-', $_COOKIE['bpgsites_comment_groups'] );
			
			}
	
			// set empty cookie
			//setcookie( 'bpgsites_comment_groups', '' );
			*/
		
		}
	
		// --<
		return $group_ids;
	
	}



	/** 
	 * @description: when our filtering form is is submitted, parse groups by selection
	 * @return array $filtered the filtered group IDs
	 */
	function add_group_to_comment_class( $classes, $class, $comment_id, $post_id ) {
	
		// add utility class to all comments
		$classes[] = 'bpgsites-shown';

		// get group ID for this comment
		$group_id = $this->get_comment_group_id( $comment_id );
	
		// did we get one?
		if ( $group_id != '' AND is_numeric( $group_id ) ) {
		
			$classes[] = 'bpgsites-group-'.$group_id;
	
		}

		//print_r( array( $classes, $class, $comment_id, $post_id, $group_id ) ); die();

		// --<
		return $classes;
	
	}



	/** 
	 * @description: adds links to the Special Pages menu in CommentPress themes
	 */
	function navigation_links() {
	
		// is a CommentPress theme active?
		if ( function_exists( 'commentpress_setup' ) ) {

			// init HTML output
			$html = '';
	
			// get the groups this user can see
			$user_group_ids = $this->get_groups_for_user();
	
			// kick out if empty
			if ( count( $user_group_ids ) == 0 ) return;
	
			// define config array
			$config_array = array(
				//'user_id' => $user_id,
				'type' => 'alphabetical',
				'populate_extras' => 0,
				'include' => $user_group_ids
			);
	
			// get groups
			if ( bp_has_groups( $config_array ) ) {
		
				// access object
				global $groups_template, $post;
		
				// only show if user has more than one...
				if ( $groups_template->group_count > 1 ) {
				
					// set title, but allow plugins to override
					$title = apply_filters( 
						'bpgsites_groupsites_menu_item_title', 
						sprintf(
							__( 'Groups reading this %s', 'bpgsites' ),
							apply_filters( 'bpgsites_extension_name', __( 'site', 'bpgsites' ) )
						)
					);
					
					// construct item
					$html .= '<li><a href="#groupsites-list" id="btn_groupsites" class="css_btn" title="'.$title.'">'.$title.'</a>';
				
					// open sublist
					$html .= '<ul class="children" id="groupsites-list">'."\n";

					// do the loop
					while ( bp_groups() ) {  bp_the_group();
			
						// construct item
						$html .= '<li>'.
									'<a href="'.bp_get_group_permalink().'" class="css_btn btn_groupsites" title="'.bp_get_group_name().'">'.
										bp_get_group_name().
									'</a>'.
								 '</li>';
				
					} // end while
			
					// close tags
					$html .= '</ul>'."\n";
					$html .= '</li>'."\n";
			
				} else {
			
					// set title
					$title = __( 'Group Home Page', 'bpgsites' );
				
					// do want to use bp_get_group_name()
			
					// do the loop (though there will only be one item
					while ( bp_groups() ) {  bp_the_group();
			
						// construct item
						$html .= '<li><a href="'.bp_get_group_permalink().'" id="btn_groupsites" class="css_btn" title="'.$title.'">'.$title.'</a></li>';
				
					}
			
				}
	
			}
	
			// output
			echo $html;
	
		}

	}
	
	
	
	/** 
	 * @description: parse groups by user membership
	 * @return array $user_group_ids the group IDs to which the user has access
	 */
	function get_groups_for_user() {
	
		// have we already calculated this?
		if ( isset( $this->user_group_ids ) ) return $this->user_group_ids;
	
		// init return
		$this->user_group_ids = array();
		
		// get current blog
		$current_blog_id = get_current_blog_id();
		
		// get this blog's group IDs
		$group_ids = bpgsites_get_groups_by_blog_id( $current_blog_id );
		//print '<pre>';
		//print_r( $group_ids ); //die();

		// get user ID
		$user_id = bp_loggedin_user_id();

		// loop through the groups
		foreach( $group_ids AS $group_id ) {

			//print_r( array ('testing group_id' => $group_id ) ); //die();

			// get the group
			$group = groups_get_group( array(
				'group_id'   => $group_id
			) );
			//print_r( $group ); //die();
			
			// get status of group
			$status = bp_get_group_status( $group );
			
			// if public...
			if ( $status == 'public' ) {
				
				// access object
				global $bp_groupsites;
				
				// assume not allowed
				$allowed = false;
				
				// do we allow public comments?
				if ( $bp_groupsites->admin->option_get( 'public' ) ) {
				
					// override
					$allowed = true;
				
				}
				
				// override for now
				$allowed = true;
			
			}
	
			// if it's allowed or this user is a member, add it
			if ( $allowed OR groups_is_user_member( $user_id, $group_id ) ) {
				
				// if it's not already there...
				if ( !in_array( $group_id, $this->user_group_ids ) ) {
					
					// add to our array
					$this->user_group_ids[] = $group_id;
				
				}
	
			} else {
			
				// if the user is not a member, is it one of the groups that is
				// reading the site with this group?
				
				// get linked groups
				$linked_groups = bpgsites_get_linked_groups_by_blog_id( $group_id, $current_blog_id );
				//print_r( array ('linked_groups' => $linked_groups ) ); //die();
				
				// loop through them
				foreach( $linked_groups AS $linked_group_id ) {
					
					// if the user is a member...
					if ( groups_is_user_member( $user_id, $linked_group_id ) ) {
					
						// add the current one if it's not already there...
						if ( !in_array( $group_id, $this->user_group_ids ) ) {
					
							// add to our array
							$this->user_group_ids[] = $group_id;
							//print_r( array ('adding group_id' => $group_id ) ); //die();
							
							// don't need to check any further
							break;
				
						}
	
					}

				}
				
			}
		
		}

		//die();
		//print_r( $this->user_group_ids ); die();

		// --<
		return $this->user_group_ids;

	}
	
	
	
	/** 
	 * @description: when a comment is saved, get the ID of the group it was submitted to
	 * @return integer $group_id the group ID of the input in the comment form
	 */
	function is_user_in_group_reading_this_site() {

		// have we already calculated this?
		if ( isset( $this->user_in_group ) ) return $this->user_in_group;
	
		// init return
		$this->user_in_group = false;
		
		// get the groups this user can see
		$user_group_ids = $this->get_groups_for_user();
	
		// does the user have any groups reading this site?
		$groups = groups_get_user_groups( bp_loggedin_user_id() );
		
		/*
		print_r( array( 
			$user_group_ids,
			$groups['groups'] 
		) ); die();
		*/
		
		// loop through them
		foreach( $groups['groups'] AS $group ) {
		
			// if the user is a member...
			if ( in_array( $group, $user_group_ids ) ) {
			
				// yes, kick out
				$this->user_in_group = true;
				
				// --<
				return $this->user_in_group;
			
			}

		}
		
		// --<
		return $this->user_in_group;

	}



	// =============================================================================
	// We may or may not use what follows...
	// =============================================================================
	
	
	
	/**
	 * @description: record the blog post activity for the group
	 * @see: bp_groupblog_set_group_to_post_activity ( $activity )
	 */
	function custom_post_activity( $activity ) {

		//print_r( array( 'activity BEFORE' => $activity ) ); //die();
	
		// kick out until we figure out how to do this with multiple groups...
		return $activity;
	
	
	
		// -------------------------------------------------------------------------
	
	
	
		// only on new blog posts
		if ( ( $activity->type != 'new_blog_post' ) ) return $activity;
	
		// clarify data
		$blog_id = $activity->item_id;
		$post_id = $activity->secondary_item_id;
		$post = get_post( $post_id );
	
		
		
		// get the group IDs for this blog
		$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

		// sanity check
		if ( !is_array( $group_ids ) OR count( $group_ids ) == 0 ) return $activity;
	
	
	
		// -------------------------------------------------------------------------
		// WHAT NOW???
		// -------------------------------------------------------------------------
	
	
	
		// get group
		$group = groups_get_group( array( 'group_id' => $group_id ) );
	
		// set activity type
		$type = 'new_groupsite_post';
	
		// see if we already have the modified activity for this blog post
		$id = bp_activity_get_activity_id( array(
	
			'user_id' => $activity->user_id,
			'type' => $type,
			'item_id' => $group_id,
			'secondary_item_id' => $activity->secondary_item_id
		
		) );

		// if we don't find a modified item...
		if ( !$id ) {
	
			// see if we have an unmodified activity item
			$id = bp_activity_get_activity_id( array(
		
				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id
			
			) );
		
		}

		// If we found an activity for this blog post then overwrite that to avoid 
		// having multiple activities for every blog post edit
		if ( $id ) {
			$activity->id = $id;
		}
	
		// allow plugins to override the name of the activity item
		$activity_name = apply_filters(
			'bpgsites_activity_post_name',
			__( 'post', 'bpgsites' )
		);
	
		// default to standard BP author
		$activity_author = bp_core_get_userlink( $post->post_author );

		// compat with Co-Authors Plus
		if ( function_exists( 'get_coauthors' ) ) {
	
			// get multiple authors
			$authors = get_coauthors();
			//print_r( $authors ); die();
	
			// if we get some
			if ( !empty( $authors ) ) {
		
				// we only want to override if we have more than one...
				if ( count( $authors ) > 1 ) {
				
					// use the Co-Authors format of "name, name, name and name"
					$activity_author = '';
				
					// init counter
					$n = 1;
				
					// find out how many author we have
					$author_count = count( $authors );
			
					// loop
					foreach( $authors AS $author ) {
					
						// default to comma
						$sep = ', ';
					
						// if we're on the penultimate
						if ( $n == ($author_count - 1) ) {
					
							// use ampersand
							$sep = __( ' &amp; ', 'bpgsites' );
						
						}
					
						// if we're on the last, don't add
						if ( $n == $author_count ) { $sep = ''; }
					
						// add name
						$activity_author .= bp_core_get_userlink( $author->ID );
					
						// and separator
						$activity_author .= $sep;
					
						// increment
						$n++;
					
					}
				
				}
			
			}
		
		}
	
		// if we're replacing an item, show different message...
		if ( $id ) {
		
			// replace the necessary values to display in group activity stream
			$activity->action = sprintf( 
		
				__( '%s updated a %s %s in the group %s:', 'bpgsites' ),
			
				$activity_author, 
				$activity_name, 
				'<a href="' . get_permalink( $post->ID ) .'">' . esc_attr( $post->post_title ) . '</a>', 
				'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' 
			
			);
		
		} else {
	
			// replace the necessary values to display in group activity stream
			$activity->action = sprintf( 
		
				__( '%s wrote a new %s %s in the group %s:', 'bpgsites' ),
			
				$activity_author, 
				$activity_name, 
				'<a href="' . get_permalink( $post->ID ) .'">' . esc_attr( $post->post_title ) . '</a>', 
				'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' 
			
			);
		
		}
	
		$activity->item_id = (int)$group_id;
		$activity->component = 'groups';

		// having marked all groupblogs as public, we need to hide activity from them if the group is private
		// or hidden, so they don't show up in sitewide activity feeds.
		if ( 'public' != $group->status ) {
			$activity->hide_sitewide = true;
		} else {
			$activity->hide_sitewide = false;
		}
	
		// set to relevant custom type
		$activity->type = $type;
	
		//print_r( array( 'activity AFTER' => $activity ) ); die();
	
		// prevent from firing again
		remove_action( 'bp_activity_before_save', 'bpgsites_group_custom_post_activity' );
	
		// --<
		return $activity;

	}
	
	
	
	/** 
	 * @description: add a filter option to the filter select box on group activity pages
	 */
	function posts_filter_option( $slug ) {

		// default name
		$post_name = __( 'Group Site Posts', 'bpgsites' );

		// allow plugins to override the name of the option
		$post_name = apply_filters( 'bpgsites_post_name', $post_name );
	
		// construct option
		$option = '<option value="new_groupsite_post">'.$post_name.'</option>'."\n";
	
		// print
		echo $option;

	}
	
	
	
} // end class BpGroupSites_Activity



