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



/**
 * BP Group Sites Activity class.
 *
 * A class that encapsulates activity functionality.
 *
 * @since 0.1
 */
class BP_Group_Sites_Activity {

	/**
	 * Groups array.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $groups The groups array.
	 */
	public $groups = array();



	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	function __construct() {

	}



	/**
	 * Register hooks for this class.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Hooks that always need to be present.

		/*
		// Add our posts filter.
		add_action( 'bp_activity_filter_options', array( $this, 'posts_filter_option' ) );
		add_action( 'bp_group_activity_filter_options', array( $this, 'posts_filter_option' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'posts_filter_option' ) );
		*/

		// Add our comments filter.
		add_action( 'bp_activity_filter_options', array( $this, 'comments_filter_option' ) );
		add_action( 'bp_group_activity_filter_options', array( $this, 'comments_filter_option' ) );
		add_action( 'bp_member_activity_filter_options', array( $this, 'comments_filter_option' ) );

		// Filter the AJAX query string to add the "action" variable.
		add_filter( 'bp_ajax_querystring', array( $this, 'comments_ajax_querystring' ), 20, 2 );

		// Filter the comment link so replies are done in CommentPress.
		add_filter( 'bp_get_activity_comment_link', array( $this, 'filter_comment_link' ) );

		// Filter the activity item permalink to point to the comment.
		add_filter( 'bp_activity_get_permalink', array( $this, 'filter_comment_permalink' ), 20, 2 );

		// If the current blog is a group site.
		if ( bpgsites_is_groupsite( get_current_blog_id() ) ) {

			// Add custom post activity. (Disabled until later)
			//add_action( 'bp_activity_before_save', array( $this, 'custom_post_activity' ), 10, 1 );

			// Make sure "Allow activity stream commenting on blog and forum posts" is disabled for group sites.
			add_action( 'bp_disable_blogforum_comments', array( $this, 'disable_blogforum_comments' ), 100, 1 );

			// Add custom comment activity.
			add_action( 'bp_activity_before_save', array( $this, 'custom_comment_activity' ), 10, 1 );

			// Add our dropdown (or hidden input) to comment form.
			add_filter( 'comment_id_fields', array( $this, 'get_comment_group_selector' ), 10, 3 );

			// Hook into comment save process.
			add_action( 'comment_post', array( $this, 'save_comment_group_id' ), 10, 2 );

			// Add action for checking comment moderation.
			add_filter( 'pre_comment_approved', array( $this, 'check_comment_approval' ), 100, 2 );

			// Allow comment authors to edit their own comments.
			add_filter( 'map_meta_cap', array( $this, 'enable_comment_editing' ), 10, 4 );

			// Add navigation items for groups.
			add_filter( 'cp_nav_after_network_home_title', array( $this, 'get_group_navigation_links' ) );

			// Override reply to link.
			add_filter( 'comment_reply_link', array( $this, 'override_reply_to_link' ), 10, 4 );

			// Override CommentPress TinyMCE.
			add_filter( 'cp_override_tinymce', array( $this, 'disable_tinymce' ), 10, 1 );

			// Add action to insert comments-by-group filter.
			add_action( 'commentpress_before_scrollable_comments', array( $this, 'get_group_comments_filter' ) );

			// Add group ID as class to comment.
			add_filter( 'comment_class', array( $this, 'add_group_to_comment_class' ), 10, 4 );

			// Filter comments by group membership.
			add_action( 'parse_comment_query', array( $this, 'filter_comments' ), 100, 1 );

			// Override what is reported by get_comments_number.
			add_filter( 'get_comments_number', array( $this, 'get_comments_number' ), 20, 2 );

			// Override comment form if no group membership.
			add_filter( 'commentpress_show_comment_form', array( $this, 'show_comment_form' ), 10, 1 );

			// Add section to activity sidebar in CommentPress.
			add_filter( 'commentpress_bp_activity_sidebar_before_members', array( $this, 'get_activity_sidebar_section' ) );

			// Override cp_activity_tab_recent_title_blog.
			add_filter( 'cp_activity_tab_recent_title_blog', array( $this, 'get_activity_sidebar_recent_title' ) );

			// Register a meta box.
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );

			// Intercept comment edit process.
			add_action( 'edit_comment', array( $this, 'save_comment_metadata' ) );

			// Show group at top of comment content.
			add_filter( 'get_comment_text', array( $this, 'show_comment_group' ), 20, 3 );

		}

	}



	//##########################################################################



	/**
	 * Show the group into which a comment has been posted.
	 *
	 * @since 0.1
	 *
	 * @param str $comment_content The content of the comment.
	 * @param object $comment The comment object.
	 * @param array $args The arguments.
	 * @return str $comment_content The content of the comment.
	 */
	public function show_comment_group( $comment_content, $comment, $args ) {

		// Init prefix.
		$prefix = '';

		// Get group ID.
		$group_id = $this->get_comment_group_id( $comment->comment_ID );

		// Sanity check.
		if ( is_numeric( $group_id ) AND $group_id > 0 ) {

			// Get the group.
			$group = groups_get_group( array(
				'group_id'   => $group_id
			) );

			// Get group name.
			$name = bp_get_group_name( $group );

			// Wrap name in anchor.
			$link = '<a href="' . bp_get_group_permalink( $group ) . '">' . $name . '</a>';

			// Construct prefix.
			$prefix = apply_filters(
				'bpgsites_comment_prefix',
				sprintf( __( 'Posted in: %s', 'bp-group-sites' ), $link ),
				$name,
				$comment,
				$group_id
			);

		}

		// Prepend to comment content.
		$comment_content = '<div class="bpgsites_comment_posted_in"><p>' . $prefix . "</p></div>\n\n" . $comment_content;

		// --<
		return $comment_content;

	}



	/**
	 * Register a meta box for the comment edit screen.
	 *
	 * @since 0.1
	 */
	public function add_meta_box() {

		// Add meta box.
		add_meta_box(
			'bpgsites_comment_options_meta_box',
			__( 'BuddyPress Comment Group', 'bp-group-sites' ),
			array( $this, 'comment_meta_box' ),
			'comment',
			'normal'
		);

	}



	/**
	 * Add a meta box to the comment edit screen.
	 *
	 * @since 0.1
	 */
	public function comment_meta_box() {

		// Access comment.
		global $comment;

		// Comment ID.
		$comment_id = $comment->comment_ID;

		// Get reply-to ID, if present.
		$reply_to_id = is_numeric( $comment->comment_parent ) ? absint( $comment->comment_parent ) : 0;

		// If this is a reply.
		if ( $reply_to_id !== 0 ) {

			// The group that comment replies have must be the same as its parent.

			// Get group ID.
			$group_id = $this->get_comment_group_id( $comment_id );

			// Sanity check.
			if ( is_numeric( $group_id ) AND $group_id > 0 ) {

				// Show message.
				echo '<p>' . __( 'This comment is a reply. It appears in the same group as the comment it is in reply to. If there is a deeper thread of replies, then the original comment determines the group in which it appears.', 'bp-group-sites' ) . '</p>';

				// Get group name.
				$name = bp_get_group_name( groups_get_group( array( 'group_id' => $group_id ) ) );

				// Construct message.
				$message = sprintf(
					__( 'This comment appears in the group %1$s.', 'bp-group-sites' ),
					$name
				);

				echo '<p>' . $message . '</p>';

			}

		} else {

			// Top level comments can be re-assigned.

			// Use nonce for verification.
			wp_nonce_field( 'bpgsites_comments_metabox', 'bpgsites_comments_nonce' );

			// Open para.
			echo '<p>';

			// Get select dropdown.
			echo $this->get_comment_group_select(
				'', // No existing content.
				$comment_id,
				$reply_to_id,
				true // Trigger edit mode.
			);

			// Close para.
			echo '</p>';

		}

	}



	/**
	 * Save data returned by our comment meta box.
	 *
	 * @since 0.1
	 *
	 * @param int $comment_id The ID of the comment being saved.
	 */
	public function save_comment_metadata( $comment_id ) {

		// If there's no nonce then there's no comment meta data.
		if ( ! isset( $_POST['bpgsites_comments_nonce'] ) ) { return; }

		// Authenticate submission.
		if ( ! wp_verify_nonce( $_POST['bpgsites_comments_nonce'], 'bpgsites_comments_metabox' ) ) { return; }

		// Check capabilities.
		if ( ! current_user_can( 'edit_comment', $comment_id ) ) {

			// Cheating!
			wp_die( __( 'You are not allowed to edit comments on this post.', 'bp-group-sites' ) );

		}

		// Save data, ignoring comment status param.
		$this->save_comment_group_id( $comment_id, null );

	}



	/**
	 * Disable comment sync because parent activity items may not be in the same
	 * group as the comment. Content may also predate the site becoming a group
	 * site, muddling the process of locating the parent item further.
	 *
	 * CommentPress also disables this because its comments should be read in
	 * context rather than appearing as if globally attached to the post or page.
	 *
	 * @since 0.1
	 *
	 * @param bool $is_disabled The BP setting that determines blogforum sync.
	 * @return bool $is_disabled The modified value that determines blogforum sync.
	 */
	public function disable_blogforum_comments( $is_disabled ) {

		// Get current blog ID.
		$blog_id = get_current_blog_id();

		// If it's is a groupsite, disable.
		if ( bpgsites_is_groupsite( $blog_id ) ) return 1;

		// Pass through.
		return $is_disabled;

	}



	/**
	 * Record the blog activity for the group.
	 *
	 * @since 0.1
	 *
	 * @see: bp_groupblog_set_group_to_post_activity()
	 *
	 * @param object $activity The BP activity object.
	 * @return object $activity The modified BP activity object.
	 */
	public function custom_comment_activity( $activity ) {

		// Only deal with comments.
		if ( ( $activity->type != 'new_blog_comment' ) ) return $activity;

		// Get group ID from POST.
		$group_id = $this->get_group_id_from_comment_form();

		// Kick out if not a comment in a group.
		if ( false === $group_id ) return $activity;

		// Set activity type.
		$type = 'new_groupsite_comment';

		// Okay, let's get the group object.
		$group = groups_get_group( array( 'group_id' => $group_id ) );

		// See if we already have the modified activity for this blog post.
		$id = bp_activity_get_activity_id( array(
			'user_id' => $activity->user_id,
			'type' => $type,
			'item_id' => $group_id,
			'secondary_item_id' => $activity->secondary_item_id
		) );

		// If we don't find a modified item.
		if ( ! $id ) {

			// See if we have an unmodified activity item.
			$id = bp_activity_get_activity_id( array(
				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id
			) );

		}

		// If we found an activity for this blog comment then overwrite that to avoid having
		// Multiple activities for every blog comment edit.
		if ( $id ) $activity->id = $id;

		// Get the comment.
		$comment = get_comment( $activity->secondary_item_id );

		// Get the post.
		$post = get_post( $comment->comment_post_ID );

		// Was it a registered user?
		if ($comment->user_id != '0') {

			// Get user details.
			$user = get_userdata( $comment->user_id );

			// Construct user link.
			$user_link = bp_core_get_userlink( $activity->user_id );

		} else {

			// Show anonymous user.
			$user_link = '<span class="anon-commenter">' . __( 'Anonymous', 'bp-group-sites' ) . '</span>';

		}

		// Allow plugins to override the name of the activity item.
		$activity_name = apply_filters(
			'bpgsites_activity_post_name',
			__( 'post', 'bp-group-sites' ),
			$post
		);

		// Init target link.
		$target_post_link = '<a href="' . get_permalink( $post->ID ) . '">' .
								esc_html( $post->post_title ) .
							'</a>';

		// Replace the necessary values to display in group activity stream.
		$activity->action = sprintf(
			__( '%1$s left a %2$s on a %3$s %4$s in the group %5$s:', 'bp-group-sites' ),
			$user_link,
			'<a href="' . $activity->primary_link . '">' . __( 'comment', 'bp-group-sites' ) . '</a>',
			$activity_name,
			$target_post_link,
			'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_html( $group->name ) . '</a>'
		);

		// Apply group id.
		$activity->item_id = (int)$group_id;

		// Change to groups component.
		$activity->component = 'groups';

		// Having marked all groupblogs as public, we need to hide activity from them if the group is private
		// Or hidden, so they don't show up in sitewide activity feeds.
		if ( 'public' != $group->status ) {
			$activity->hide_sitewide = true;
		} else {
			$activity->hide_sitewide = false;
		}

		// Set unique type.
		$activity->type = $type;

		// Prevent from firing again.
		remove_action( 'bp_activity_before_save', array( $this, 'custom_comment_activity' ) );

		// --<
		return $activity;

	}



	/**
	 * Filter the activity comment permalink on activity items to point to the
	 * original comment in context.
	 *
	 * @since 0.1
	 *
	 * @param array $args Existing activity permalink data.
	 * @param object $activity The activity item.
	 * @return array $args The overridden activity permalink data.
	 */
	public function filter_comment_permalink( $args, $activity ) {

		// Our custom activity types.
		$types = array( 'new_groupsite_comment' );

		// Not one of ours?
		if ( ! in_array( $activity->type, $types ) ) return $args;

		// --<
		return bp_get_activity_feed_item_link();

	}



	/**
	 * Filter the comment reply link on activity items. This is called during the
	 * loop, so we can assume that the activity item API will work.
	 *
	 * @since 0.1
	 *
	 * @return string $link The overridden comment reply link.
	 */
	public function filter_comment_link( $link ) {

		// Get type of activity.
		$type = bp_get_activity_action_name();

		// Our custom activity types.
		$types = array( 'new_groupsite_comment' );

		// Not one of ours?
		if ( ! in_array( $type, $types ) ) return $link;

		/*
		if ( $type == 'new_groupsite_comment' ) {
			$link_text = __( 'Reply', 'bpwpapers' );
		}

		// Construct new link to actual comment.
		$link = '<a href="' . bp_get_activity_feed_item_link() . '" class="button acomment-reply bp-primary-action">' .
					$link_text .
				'</a>';
		*/

		// --<
		return bp_get_activity_feed_item_link();

	}



	/**
	 * Add a filter option to the filter select box on group activity pages.
	 *
	 * @since 0.1
	 */
	public function comments_filter_option() {

		// Default name, but allow plugins to override.
		$comment_name = apply_filters(
			'bpgsites_comment_name',
			sprintf(
				__( 'Comments in %s', 'bp-group-sites' ),
				apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) )
			)
		);

		// Construct option.
		$option = '<option value="new_groupsite_comment">' . $comment_name . '</option>' . "\n";

		// Print.
		echo $option;

		/*
		__( 'Group Site Comments', 'bp-group-sites' )
		*/

	}



	/**
	 * Modify the AJAX query string.
	 *
	 * @since 0.2.5
	 *
	 * @param string $qs The query string for the BP loop.
	 * @param string $object The current object for the query string.
	 * @return string Modified query string.
	 */
	public function comments_ajax_querystring( $qs, $object ) {

		// Bail if not an activity object.
		if ( $object != 'activity' ) return $qs;

		// Parse query string into an array.
		$r = wp_parse_args( $qs );

		// Bail if no type is set.
		if ( empty( $r['type'] ) ) return $qs;

		// Bail if not a type that we're looking for.
		if ( 'new_groupsite_comment' !== $r['type'] ) return $qs;

		// Add the 'new_groupsite_comment' type if it doesn't exist.
		if ( ! isset( $r['action'] ) OR false === strpos( $r['action'], 'new_groupsite_comment' ) ) {
			// 'action' filters activity items by the 'type' column.
			$r['action'] = 'new_groupsite_comment';
		}

		// 'type' isn't used anywhere internally.
		unset( $r['type'] );

		// Return a querystring.
		return build_query( $r );

	}



	/**
	 * Add a filter option to the filter select box on group activity pages.
	 *
	 * @since 0.1
	 *
	 * @param int $count The current comment count.
	 * @param int $post_id The current post.
	 */
	public function get_comments_number( $count, $post_id ) {

		// Get comments for this post again.
		$comments = get_comments( array(
			'post_id' => $post_id
		) );

		// Did we get any?
		if ( is_array( $comments ) ) {

			// Return the number.
			return count( $comments );

		}

		// Otherwise, pass through.
		return $count;

	}



	/**
	 * When get_comments is called, show only those from groups to which the user belongs.
	 *
	 * @since 0.1
	 *
	 * @param object $comments The current query.
	 */
	public function filter_comments( $comments ) {

		// Only on front-end.
		if ( is_admin() ) return $comments;

		// Init array.
		$groups = array();

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// If we get some.
		if (
			count( $user_group_ids['my_groups'] ) > 0 OR
			count( $user_group_ids['linked_groups'] ) > 0 OR
			count( $user_group_ids['public_groups'] ) > 0 OR
			count( $user_group_ids['auth_groups'] ) > 0
		) {

			// Merge the arrays.
			$groups = array_unique( array_merge(
				$user_group_ids['my_groups'],
				$user_group_ids['linked_groups'],
				$user_group_ids['public_groups'],
				$user_group_ids['auth_groups']
			) );

		}

		// If none.
		if (
			count( $user_group_ids['my_groups'] ) === 0 AND
			count( $user_group_ids['linked_groups'] ) === 0 AND
			count( $user_group_ids['public_groups'] ) === 0 AND
			count( $user_group_ids['auth_groups'] ) === 0
		) {

			// Set a non-existent group ID.
			$groups = array( 0 );

		}

		/*
		 * At this point, we need both a meta query that queries for the group ID
		 * in the comment meta as well as a meta query that queries for the absence
		 * of a meta value so that any legacy comments that were not attached to
		 * a group show up.
		 */

		// Construct our meta query addition.
		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'   => BPGSITES_COMMENT_META_KEY,
				'value' => $groups,
				'compare' => 'IN'
			),
			array(
				'key'   => BPGSITES_COMMENT_META_KEY,
				'value' => '',
				'compare' => 'NOT EXISTS',
			),
		);

		// Make sure meta query is an array.
		if ( ! is_array( $comments->query_vars['meta_query'] ) ) {
			$comments->query_vars['meta_query'] = array();
		}

		// Add our meta query.
		$comments->query_vars['meta_query'][] = $meta_query;

		// We need an AND relation too.
		$comments->query_vars['meta_query']['relation'] = 'AND';

		// Parse meta query again.
		$comments->meta_query = new WP_Meta_Query();
		$comments->meta_query->parse_query_vars( $comments->query_vars );

	}



	/**
	 * When a comment is saved, this also saves the ID of the group it was submitted to.
	 *
	 * @since 0.1
	 *
	 * @param integer $comment_id The ID of the comment.
	 * @param integer $comment_status The approval status of the comment.
	 */
	public function save_comment_group_id( $comment_id, $comment_status ) {

		// We don't need to look at approval status.
		$group_id = $this->get_group_id_from_comment_form();

		// Is this a comment in a group?
		if ( false !== $group_id ) {

			// If the custom field already has a value.
			if ( get_comment_meta( $comment_id, BPGSITES_COMMENT_META_KEY, true ) !== '' ) {

				// Update the data.
				update_comment_meta( $comment_id, BPGSITES_COMMENT_META_KEY, $group_id );

			} else {

				// Add the data.
				add_comment_meta( $comment_id, BPGSITES_COMMENT_META_KEY, $group_id, true );

			}

		}

	}



	/**
	 * Override CommentPress "Reply To" link.
	 *
	 * @since 0.1
	 *
	 * @param string $link The existing link.
	 * @param array $args The setup array.
	 * @param object $comment The comment.
	 * @param object $post The post.
	 * @return string $link The modified link.
	 */
	public function override_reply_to_link( $link, $args, $comment, $post ) {

		// If not logged in.
		if ( ! is_user_logged_in() ) {

			// Is registration allowed?
			if ( bp_get_signup_allowed() ) {
				$link_text = __( 'Create an account to reply', 'bp-group-sites' );
				$href = bp_get_signup_page();
			} else {
				$link_text = __( 'Login to reply', 'bp-group-sites' );
				$href = wp_login_url();
			}

			// Construct link.
			$link = '<a rel="nofollow" href="' . $href . '">' . $link_text . '</a>';

			// --<
			return $link;

		}

		// Get current blog ID.
		$blog_id = get_current_blog_id();

		// Pass through if not group site.
		if ( ! bpgsites_is_groupsite( $blog_id ) ) return $link;

		// Get comment group.
		$group_id = $this->get_comment_group_id( $comment->comment_ID );

		// Sanity check.
		if ( ! is_numeric( $group_id ) OR $group_id == 0 ) {

			// Comments can pre-exist that are not group-linked.
			return $link;

		}

		// Get user ID.
		$user_id = bp_loggedin_user_id();

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// Is this group one of these?
		if (
			in_array( $group_id, $user_group_ids['my_groups'] ) OR
			in_array( $group_id, $user_group_ids['linked_groups'] )
		) {
			// --<
			return $link;
		}


		// Get the group.
		$group = groups_get_group( array(
			'group_id'   => $group_id
		) );

		// Get showcase groups.
		$showcase_groups = bpgsites_showcase_groups_get();

		// Is it a showcase group?
		if ( in_array( $group_id, $showcase_groups ) ) {

			// Clear link.
			$link = '';

		} else {

			// Construct link.
			$link = '<a rel="nofollow" href="' . bp_get_group_permalink( $group ) . '">' . __( 'Join group to reply', 'bp-group-sites' ) . '</a>';

		}

		// --<
		return $link;

	}



	/**
	 * Override CommentPress TinyMCE setting.
	 *
	 * @since 0.1
	 *
	 * @param bool $tinymce Whether TinyMCE is enabled or not.
	 * @return bool $tinymce Modified value for whether TinyMCE is enabled or not.
	 */
	public function disable_tinymce( $tinymce ) {

		// Get current blog ID.
		$blog_id = get_current_blog_id();

		// Pass through if not group site.
		if ( ! bpgsites_is_groupsite( $blog_id ) ) return $tinymce;

		// Is the current member in a relevant group?
		if ( ! $this->is_user_in_group_reading_this_site() ) {

			// Add filters on reply to link.
			add_filter( 'commentpress_reply_to_para_link_text', array( $this, 'override_reply_to_text' ), 10, 2 );
			add_filter( 'commentpress_reply_to_para_link_href', array( $this, 'override_reply_to_href' ), 10, 2 );
			add_filter( 'commentpress_reply_to_para_link_onclick', array( $this, 'override_reply_to_onclick' ), 10, 1 );

			// Disable.
			return 0;

		}

		// Use TinyMCE if logged in.
		if ( is_user_logged_in() ) return $tinymce;

		// Don't use TinyMCE.
		return 0;

	}



	/**
	 * Decides whether or not to show comment form.
	 *
	 * @since 0.1
	 *
	 * @param bool $show Whether or not to show comment form.
	 * @return bool $show Show the comment form.
	 */
	public function show_comment_form( $show ) {

		// Get current blog ID.
		$blog_id = get_current_blog_id();

		// Pass through if not group site.
		if ( ! bpgsites_is_groupsite( $blog_id ) ) return $show;

		// Pass through if the current member is in a relevant group.
		if ( $this->is_user_in_group_reading_this_site() ) return $show;

		// Filter the comment form message.
		add_filter( 'commentpress_comment_form_hidden', array( $this, 'override_comment_form_hidden' ), 10, 1 );

		// --<
		return false;

	}



	/**
	 * Show a message if we are hiding the comment form.
	 *
	 * @since 0.1
	 *
	 * @param str $hidden The message shown when the comment form is hidden.
	 * @return str $link The overridden message shown when the comment form is hidden.
	 */
	public function override_comment_form_hidden( $hidden_text ) {

		// If not logged in.
		if ( ! is_user_logged_in() ) {

			// Is registration allowed?
			if ( bp_get_signup_allowed() ) {
				$text = __( 'Create an account to leave a comment', 'bp-group-sites' );
				$href = bp_get_signup_page();
			} else {
				$text = __( 'Login to leave a comment', 'bp-group-sites' );
				$href = wp_login_url();
			}

			// Construct link.
			$link = apply_filters(
				'bpgsites_override_comment_form_hidden_denied',
				sprintf( __( '<a href="%1$s">%2$s</a>', 'commentpress-core' ), $href, $text )
			);

			// Show link.
			return $link;

		}

		// Send to groups directory.
		$text = __( 'Join a group to leave a comment', 'bp-group-sites' );
		$href = bp_get_groups_directory_permalink();

		// Construct link.
		$link = apply_filters(
			'bpgsites_override_comment_form_hidden',
			sprintf( __( '<a href="%1$s">%2$s</a>', 'commentpress-core' ), $href, $text )
		);

		// Show link.
		return $link;

	}



	/**
	 * Override content of the reply to link.
	 *
	 * @since 0.1
	 *
	 * @param string $link_text The full text of the reply to link.
	 * @param string $paragraph_text Paragraph text.
	 * @return string $link_text Updated content of the reply to link.
	 */
	public function override_reply_to_text( $link_text, $paragraph_text ) {

		// If not logged in.
		if ( ! is_user_logged_in() ) {

			// Is registration allowed?
			if ( bp_get_signup_allowed() ) {
				$link_text = __( 'Create an account to leave a comment', 'bp-group-sites' );
			} else {
				$link_text = __( 'Login to leave a comment', 'bp-group-sites' );
			}

			// Show helpful message.
			return apply_filters( 'bpgsites_override_reply_to_text_denied', $link_text, $paragraph_text );

		}

		// Construct link content.
		$link_text = sprintf(
			__( 'Join a group to leave a comment on %s', 'bp-group-sites' ),
			$paragraph_text
		);

		// --<
		return apply_filters( 'bpgsites_override_reply_to_text', $link_text, $paragraph_text );

	}



	/**
	 * Override content of the reply to link target.
	 *
	 * @since 0.1
	 *
	 * @param string $href The existing target URL.
	 * @param string $text_sig The text signature of the paragraph.
	 * @return string $href Overridden target URL.
	 */
	public function override_reply_to_href( $href, $text_sig ) {

		// If not logged in.
		if ( ! is_user_logged_in() ) {

			// Is registration allowed?
			if ( bp_get_signup_allowed() ) {
				$href = bp_get_signup_page();
			} else {
				$href = wp_login_url();
			}

			// --<
			return apply_filters( 'bpgsites_override_reply_to_href_denied', $href );

		}

		// Send to groups directory.
		$href = bp_get_groups_directory_permalink();

		// --<
		return apply_filters( 'bpgsites_override_reply_to_href', $href, $text_sig );

	}



	/**
	 * Override content of the reply to link.
	 *
	 * @since 0.1
	 *
	 * @param string $onclick The reply-to onclick attribute.
	 * @return string $onclick The modified reply-to onclick attribute.
	 */
	public function override_reply_to_onclick( $onclick ) {

		// --<
		return '';

	}



	/**
	 * For group sites, if the user is a member of the group, allow unmoderated comments.
	 *
	 * @since 0.1
	 *
	 * @param int $approved The comment status.
	 * @param array $commentdata The comment data.
	 * @return int $approved The modified comment status.
	 */
	public function check_comment_approval( $approved, $commentdata ) {

		// Get current blog ID.
		$blog_id = get_current_blog_id();

		// Pass through if not group site.
		if ( ! bpgsites_is_groupsite( $blog_id ) ) { return $approved; }

		// Get the user ID of the comment author.
		$user_id = absint( $commentdata['user_ID'] );

		// Get group that comment was posted into - comment meta is not saved yet.
		$group_id = $this->get_group_id_from_comment_form();

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// Did we get one?
		if ( $group_id != '' AND is_numeric( $group_id ) AND $group_id > 0 ) {

			// Is this user a member?
			if ( in_array( $group_id, $user_group_ids['my_groups'] ) ) {

				// Allow un-moderated commenting.
				return 1;

			}

			// If commenting into a linked group.
			if ( in_array( $group_id, $user_group_ids['linked_groups'] ) ) {

				// TODO: if linked group, hold and send BP notification?

				// For now, allow un-moderated commenting.
				return 1;

			}

		}

		// Pass through
		return $approved;

	}



	/**
	 * For group sites, add capability to edit own comments.
	 *
	 * @param array $caps The existing capabilities array for the WordPress user.
	 * @param str $cap The capability in question.
	 * @param int $user_id The numerical ID of the WordPress user.
	 * @param array $args The additional arguments.
	 * @return array $caps The modified capabilities array for the WordPress user.
	 */
	public function enable_comment_editing( $caps, $cap, $user_id, $args ) {

		// Only apply this to queries for edit_comment cap.
		if ( 'edit_comment' == $cap ) {

			// Get comment.
			$comment = get_comment( $args[0] );

			// Is the user the same as the comment author?
			if ( $comment->user_id == $user_id ) {

				// Allow.
				$caps = array( 'exist' );

			}

		}

		// --<
		return $caps;

	}



	/**
	 * For a given comment ID, get the ID of the group it is posted in.
	 *
	 * @since 0.1
	 *
	 * @param int $comment_id The comment ID.
	 * @return int $group_id The group ID or empty string if none found.
	 */
	public function get_comment_group_id( $comment_id ) {

		// Get group ID from comment meta.
		$group_id = get_comment_meta(
			$comment_id,
			BPGSITES_COMMENT_META_KEY,
			true // Only return a single value.
		);

		// --<
		return $group_id;

	}



	/**
	 * Adds links to the Special Pages menu in CommentPress themes.
	 *
	 * @since 0.1
	 */
	public function get_group_navigation_links() {

		// Is a CommentPress theme active?
		if ( function_exists( 'commentpress_setup' ) ) {

			// Init HTML output.
			$html = '';

			// Get the groups this user can see.
			$user_group_ids = $this->get_groups_for_user();

			// Kick out if all are empty.
			if (
				count( $user_group_ids['my_groups'] ) == 0 AND
				count( $user_group_ids['linked_groups'] ) == 0 AND
				count( $user_group_ids['public_groups'] ) == 0
			) {
				// --<
				return;
			}

			// Init array.
			$groups = array();

			// If any has entries.
			if (
				count( $user_group_ids['my_groups'] ) > 0 OR
				count( $user_group_ids['public_groups'] ) > 0
			) {

				// Merge the arrays.
				$groups = array_unique( array_merge(
					$user_group_ids['my_groups'],
					$user_group_ids['linked_groups'],
					$user_group_ids['public_groups']
				) );

			}

			// Define config array.
			$config_array = array(
				//'user_id' => $user_id,
				'type' => 'alphabetical',
				'populate_extras' => 0,
				'include' => $groups
			);

			// Get groups.
			if ( bp_has_groups( $config_array ) ) {

				// Access object.
				global $groups_template, $post;

				// Only show if user has more than one.
				if ( $groups_template->group_count > 1 ) {

					// Set title, but allow plugins to override.
					$title = apply_filters(
						'bpgsites_groupsites_menu_item_title',
						sprintf(
							__( 'Groups reading this %s', 'bp-group-sites' ),
							apply_filters( 'bpgsites_extension_name', __( 'site', 'bp-group-sites' ) )
						)
					);

					// Construct item.
					$html .= '<li><a href="#groupsites-list" id="btn_groupsites" class="css_btn" title="' . $title . '">' . $title . '</a>';

					// Open sublist.
					$html .= '<ul class="children" id="groupsites-list">' . "\n";

					// Init lists.
					$mine = array();
					$linked = array();
					$public = array();

					// Do the loop.
					while ( bp_groups() ) {  bp_the_group();

						// Construct item.
						$item = '<li>' .
									'<a href="' . bp_get_group_permalink() . '" class="css_btn btn_groupsites" title="' . bp_get_group_name() . '">' .
										bp_get_group_name() .
									'</a>' .
								'</li>';

						// Get group ID.
						$group_id = bp_get_group_id();

						// Mine?
						if ( in_array( $group_id, $user_group_ids['my_groups'] ) ) {
							$mine[] = $item;
							continue;
						}

						// Linked?
						if ( in_array( $group_id, $user_group_ids['linked_groups'] ) ) {
							$linked[] = $item;
							continue;
						}

						// Public?
						if ( in_array( $group_id, $user_group_ids['public_groups'] ) ) {
							$public[] = $item;
						}

					} // End while.

					// Did we get any that are mine?
					if ( count( $mine ) > 0 ) {

						// Join items.
						$items = implode( "\n", $mine );

						// Only show if we one of the other lists is populated.
						if ( count( $linked ) > 0 OR count( $public ) > 0 ) {

							// Construct title.
							$title = __( 'My Groups', 'bp-group-sites' );

							// Construct item.
							$sublist = '<li><a href="#groupsites-list-mine" id="btn_groupsites_mine" class="css_btn" title="' . $title . '">' . $title . '</a>';

							// Open sublist.
							$sublist .= '<ul class="children" id="groupsites-list-mine">' . "\n";

							// Insert items.
							$sublist .= $items;

							// Close sublist.
							$sublist .= '</ul>' . "\n";
							$sublist .= '</li>' . "\n";

							// Replace items.
							$items = $sublist;

						}

						// Add to html.
						$html .= $items;

					}

					// Did we get any that are linked?
					if ( count( $linked ) > 0 ) {

						// Join items.
						$items = implode( "\n", $linked );

						// Only show if we one of the other lists is populated.
						if ( count( $mine ) > 0 OR count( $public ) > 0 ) {

							// Construct title.
							$title = __( 'Linked Groups', 'bp-group-sites' );

							// Construct item.
							$sublist = '<li><a href="#groupsites-list-linked" id="btn_groupsites_linked" class="css_btn" title="' . $title . '">' . $title . '</a>';

							// Open sublist.
							$sublist .= '<ul class="children" id="groupsites-list-linked">' . "\n";

							// Insert items.
							$sublist .= $items;

							// Close sublist.
							$sublist .= '</ul>' . "\n";
							$sublist .= '</li>' . "\n";

							// Replace items.
							$items = $sublist;

						}

						// Add to html.
						$html .= $items;

					}

					// Did we get any that are public?
					if ( count( $public ) > 0 ) {

						// Join items.
						$items = implode( "\n", $public );

						// Only show if we one of the other lists is populated.
						if ( count( $mine ) > 0 OR count( $linked ) > 0 ) {

							// Construct title.
							$title = __( 'Public Groups', 'bp-group-sites' );

							// Construct item.
							$sublist = '<li><a href="#groupsites-list-public" id="btn_groupsites_public" class="css_btn" title="' . $title . '">' . $title . '</a>';

							// Open sublist.
							$sublist .= '<ul class="children" id="groupsites-list-public">' . "\n";

							// Insert items.
							$sublist .= $items;

							// Close sublist.
							$sublist .= '</ul>' . "\n";
							$sublist .= '</li>' . "\n";

							// Replace items.
							$items = $sublist;

						}

						// Add to html.
						$html .= $items;

					}

					// Close tags.
					$html .= '</ul>' . "\n";
					$html .= '</li>' . "\n";

				} else {

					// Set title.
					$title = __( 'Group Home Page', 'bp-group-sites' );

					// Do we want to use bp_get_group_name()

					// Do the loop (though there will only be one item.
					while ( bp_groups() ) {  bp_the_group();

						// Construct item.
						$html .= '<li>' .
									'<a href="' . bp_get_group_permalink() . '" id="btn_groupsites" class="css_btn" title="' . $title . '">' .
										$title .
									'</a>' .
								 '</li>';

					}

				}

			}

			// Output.
			echo $html;

		}

	}



	/**
	 * Adds filtering above scrollable comments in CommentPress Responsive.
	 *
	 * @since 0.1
	 */
	public function get_group_comments_filter() {

		// Init HTML output.
		$html = '';

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// Kick out if all are empty.
		if (
			count( $user_group_ids['auth_groups'] ) == 0 AND
			count( $user_group_ids['my_groups'] ) == 0 AND
			count( $user_group_ids['linked_groups'] ) == 0 AND
			count( $user_group_ids['public_groups'] ) == 0
		) {
			// --<
			return;
		}

		// Init array.
		$groups = array();

		// If any has entries.
		if (
			count( $user_group_ids['auth_groups'] ) > 0 OR
			count( $user_group_ids['my_groups'] ) > 0 OR
			count( $user_group_ids['linked_groups'] ) > 0 OR
			count( $user_group_ids['public_groups'] ) > 0
		) {

			// Merge the arrays.
			$groups = array_unique( array_merge(
				$user_group_ids['auth_groups'],
				$user_group_ids['my_groups'],
				$user_group_ids['linked_groups'],
				$user_group_ids['public_groups']
			) );

		}

		// Define config array.
		$config_array = array(
			//'user_id' => $user_id,
			'type' => 'alphabetical',
			'max' => 100,
			'per_page' => 100,
			'populate_extras' => 0,
			'include' => $groups
		);

		// Get groups.
		if ( bp_has_groups( $config_array ) ) {

			// Access object.
			global $groups_template, $post;

			// Only show if user has more than one.
			if ( $groups_template->group_count > 1 ) {

				// Construct heading (the no_comments class prevents this from printing)
				$html .= '<h3 class="bpgsites_group_filter_heading no_comments">' .
							'<a href="#bpgsites_group_filter">' . __( 'Filter comments by group', 'bp-group-sites' ) . '</a>' .
						 '</h3>' . "\n";

				// Open div.
				$html .= '<div id="bpgsites_group_filter" class="bpgsites_group_filter no_comments">' . "\n";

				// Open form.
				$html .= '<form id="bpgsites_comment_group_filter" name="bpgsites_comment_group_filter" action="' . get_permalink( $post->ID ) . '" method="post">' . "\n";

				// Init lists.
				$auth = array();
				$mine = array();
				$linked = array();
				$public = array();

				// Init checked for public groups.
				$checked = '';

				// Get option.
				global $bp_groupsites;
				$public_shown = $bp_groupsites->admin->option_get( 'bpgsites_public' );

				// Are they to be shown?
				if ( $public_shown == '1' ) {
					$checked = ' checked="checked"';
				}

				// Do the loop.
				while ( bp_groups() ) {  bp_the_group();

					// Add arbitrary divider.
					$item = '<span class="bpgsites_comment_group">' . "\n";

					// Get group ID.
					$group_id = bp_get_group_id();

					// Showcase?
					if ( in_array( $group_id, $user_group_ids['auth_groups'] ) ) {

						// Add checkbox.
						$item .= '<input type="checkbox" class="bpgsites_group_checkbox bpgsites_group_checkbox_auth" name="bpgsites_comment_groups[]" id="bpgsites_comment_group_' . $group_id . '" value="' . $group_id . '" checked="checked" />' . "\n";

						// Add label.
						$item .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_' . $group_id . '">' .
									bp_get_group_name() .
								 '</label>' . "\n";

						// Close arbitrary divider.
						$item .= '</span>' . "\n";

						// Public.
						$auth[] = $item;

						// Next.
						continue;

					}

					// Mine?
					if ( in_array( $group_id, $user_group_ids['my_groups'] ) ) {

						// Add checkbox.
						$item .= '<input type="checkbox" class="bpgsites_group_checkbox bpgsites_group_checkbox_mine" name="bpgsites_comment_groups[]" id="bpgsites_comment_group_' . $group_id . '" value="' . $group_id . '" checked="checked" />' . "\n";

						// Add label.
						$item .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_' . $group_id . '">' .
									bp_get_group_name() .
								 '</label>' . "\n";

						// Close arbitrary divider.
						$item .= '</span>' . "\n";

						// Public.
						$mine[] = $item;

						// Next.
						continue;

					}

					// Linked?
					if ( in_array( $group_id, $user_group_ids['linked_groups'] ) ) {

						// Add checkbox.
						$item .= '<input type="checkbox" class="bpgsites_group_checkbox bpgsites_group_checkbox_linked" name="bpgsites_comment_groups[]" id="bpgsites_comment_group_' . $group_id . '" value="' . $group_id . '" checked="checked" />' . "\n";

						// Add label.
						$item .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_' . $group_id . '">' .
									bp_get_group_name() .
								 '</label>' . "\n";

						// Close arbitrary divider.
						$item .= '</span>' . "\n";

						// Public.
						$linked[] = $item;

						// Next.
						continue;

					}

					// Public?
					if ( in_array( $group_id, $user_group_ids['public_groups'] ) ) {

						// Add checkbox.
						$item .= '<input type="checkbox" class="bpgsites_group_checkbox bpgsites_group_checkbox_public" name="bpgsites_comment_groups[]" id="bpgsites_comment_group_' . $group_id . '" value="' . $group_id . '"' . $checked . ' />' . "\n";

						// Add label.
						$item .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_' . $group_id . '">' .
									bp_get_group_name() .
								 '</label>' . "\n";

						// Close arbitrary divider.
						$item .= '</span>' . "\n";

						// Public.
						$public[] = $item;

					}

				} // End while.

				// Add arbitrary divider for toggle.
				$html .= '<span class="bpgsites_comment_group">' . "\n";

				// Add checkbox, toggled on.
				$html .= '<input type="checkbox" name="bpgsites_comment_group_toggle" id="bpgsites_comment_group_toggle" class="bpgsites_group_checkbox_toggle" value="" checked="checked" />' . "\n";

				// Add label.
				$html .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_toggle">' . __( 'Show all groups', 'bp-group-sites' ) .
						 '</label>' . "\n";

				// Close arbitrary divider.
				$html .= '</span>' . "\n";

				// Did we get any showcase groups?
				if ( count( $auth ) > 0 ) {

					// Only show if we one of the other lists is populated.
					if ( count( $mine ) > 0 OR  count( $public ) > 0 OR count( $linked ) > 0 ) {

						// Add heading.
						$html .= '<span class="bpgsites_comment_group bpgsites_comment_group_header bpgsites_comment_group_auth">' . __( 'Showcase Groups', 'bp-group-sites' ) . '</span>' . "\n";

					}

					// Add items.
					$html .= implode( "\n", $auth );

				}

				// Did we get any that are mine?
				if ( count( $mine ) > 0 ) {

					// Only show if we one of the other lists is populated.
					if ( count( $auth ) > 0 OR count( $public ) > 0 OR count( $linked ) > 0 ) {

						// Add heading.
						$html .= '<span class="bpgsites_comment_group bpgsites_comment_group_header bpgsites_comment_group_mine">' . __( 'My Groups', 'bp-group-sites' ) . '</span>' . "\n";

					}

					// Add items.
					$html .= implode( "\n", $mine );

				}

				// Did we get any that are linked?
				if ( count( $linked ) > 0 ) {

					// Only show if we one of the other lists is populated.
					if ( count( $auth ) > 0 OR count( $mine ) > 0 OR count( $linked ) > 0 ) {

						// Add heading.
						$html .= '<span class="bpgsites_comment_group bpgsites_comment_group_header bpgsites_comment_group_linked">' . __( 'Linked Groups', 'bp-group-sites' ) . '</span>' . "\n";

					}

					// Add items.
					$html .= implode( "\n", $linked );

				}

				// Did we get any that are public?
				if ( count( $public ) > 0 ) {

					// Only show if we one of the other lists is populated.
					if (  count( $auth ) > 0 OR count( $mine ) > 0 OR count( $linked ) > 0 ) {

						// Add heading.
						$html .= '<span class="bpgsites_comment_group bpgsites_comment_group_header bpgsites_comment_group_public">' . __( 'Public Groups', 'bp-group-sites' ) . '</span>' . "\n";

					}

					// Add items.
					$html .= implode( "\n", $public );

				}

				// Add submit button.
				$html .= '<input type="submit" id="bpgsites_comment_group_submit" value="' . __( 'Filter', 'bp-group-sites' ) . '" />' . "\n";

				// Close tags.
				$html .= '</form>' . "\n";
				$html .= '</div>' . "\n";

			}

		}

		// Output.
		echo $html;

	}



	/**
	 * Inserts a dropdown (or hidden input) into the comment form.
	 *
	 * @since 0.1
	 *
	 * @param string $result Existing markup to be sent to browser.
	 * @param int $comment_id The comment ID.
	 * @param int $reply_to_id The comment ID to which this comment is a reply.
	 * @return string $result The modified markup sent to the browser.
	 */
	public function get_comment_group_selector( $result, $comment_id, $reply_to_id ) {

		// Pass to general method without 4th param.
		return $this->get_comment_group_select( $result, $comment_id, $reply_to_id );

	}



	/**
	 * Gets a dropdown (or hidden input) for a comment.
	 *
	 * @since 0.1
	 *
	 * @param string $result Existing markup to be sent to browser.
	 * @param int $comment_id The comment ID.
	 * @param int $reply_to_id The comment ID to which this comment is a reply.
	 * @param bool $edit Triggers edit mode to return an option selected.
	 * @return string $result The modified markup sent to the browser.
	 */
	public function get_comment_group_select( $result, $comment_id, $reply_to_id, $edit = false ) {

		// If the comment is a reply to another.
		if ( $reply_to_id !== 0 ) {

			/*
			 * This will only kick in if Javascript is off or the moveForm script is
			 * Not used in the theme. Our plugin Javascript handles this when the
			 * Form is moved around the DOM.
			 */

			// Get the group of the reply_to_id.
			$group_id = $this->get_comment_group_id( $reply_to_id );

			// Did we get one?
			if ( $group_id != '' AND is_numeric( $group_id ) AND $group_id > 0 ) {

				// Show a hidden input so that this comment is also posted in that group.
				$result .= '<input type="hidden" id="bpgsites-post-in" name="bpgsites-post-in" value="' . $group_id . '" />' . "\n";

			}

			// --<
			return $result;

		}

		// Get current blog ID.
		$blog_id = get_current_blog_id();

		// Kick out if not group site.
		if ( ! bpgsites_is_groupsite( $blog_id ) ) { return $result; }

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// Kick out if the ones the user can post into are empty.
		if (
			count( $user_group_ids['my_groups'] ) == 0 AND
			count( $user_group_ids['linked_groups'] ) == 0
		) {
			// --<
			return $result;
		}

		// Init array.
		$groups = array();

		// If any has entries.
		if (
			count( $user_group_ids['my_groups'] ) > 0 OR
			count( $user_group_ids['linked_groups'] ) > 0
		) {

			// Merge the arrays.
			$groups = array_unique( array_merge(
				$user_group_ids['my_groups'],
				$user_group_ids['linked_groups']
			) );

		}

		// Define config array.
		$config_array = array(
			//'user_id' => bp_loggedin_user_id(),
			'type' => 'alphabetical',
			'max' => 100,
			'per_page' => 100,
			'populate_extras' => 0,
			'include' => $groups
		);

		// Get groups.
		if ( bp_has_groups( $config_array ) ) {

			global $groups_template;

			// If more than one.
			if ( $groups_template->group_count > 1 ) {

				// Is this edit?
				if ( $edit ) {

					// Get the group of the comment ID.
					$comment_group_id = $this->get_comment_group_id( $comment_id );

				}

				// Init lists.
				$mine = array();
				$linked = array();

				// Do the loop.
				while ( bp_groups() ) {  bp_the_group();

					// Get group ID.
					$group_id = bp_get_group_id();

					// Init selected.
					$selected = '';

					// Is this edit?
					if ( $edit ) {

						// Is this the relevant group?
						if ( $comment_group_id == $group_id ) {

							// Yes, insert selected.
							$selected = ' selected="selected"';

						}

					}

					// Mine?
					if ( in_array( $group_id, $user_group_ids['my_groups'] ) ) {

						// Add option.
						$mine[] = '<option value="' . $group_id . '"' . $selected . '>' . bp_get_group_name() . '</option>';

					}

					// Linked?
					if ( in_array( $group_id, $user_group_ids['linked_groups'] ) ) {

						// Add option.
						$linked[] = '<option value="' . $group_id . '"' . $selected . '>' . bp_get_group_name() . '</option>' . "\n";

					}

				} // End while.

				// Construct dropdown.
				$result .= '<span id="bpgsites-post-in-box">' . "\n";
				$result .= '<span>' . __( 'Post in', 'bp-group-sites' ) . ':</span>' . "\n";
				$result .= '<select id="bpgsites-post-in" name="bpgsites-post-in">' . "\n";

				// Did we get any that are mine?
				if ( count( $mine ) > 0 ) {

					// Join items.
					$items = implode( "\n", $mine );

					// Only show optgroup if the other list is populated.
					if ( count( $linked ) > 0 ) {

						// Construct title.
						$title = __( 'My Groups', 'bp-group-sites' );

						// Construct item.
						$sublist = '<optgroup label="' . $title . '">' . "\n";

						// Insert items.
						$sublist .= $items;

						// Close sublist.
						$sublist .= '</optgroup>' . "\n";

						// Replace items.
						$items = $sublist;

					}

					// Add to html.
					$result .= $items;

				}

				// Did we get any that are linked?
				if ( count( $linked ) > 0 ) {

					// Join items.
					$items = implode( "\n", $linked );

					// Only show optgroup if the other list is populated.
					if ( count( $mine ) > 0 ) {

						// Construct title.
						$title = __( 'Linked Groups', 'bp-group-sites' );

						// Construct item.
						$sublist = '<optgroup label="' . $title . '">' . "\n";

						// Insert items.
						$sublist .= $items;

						// Close sublist.
						$sublist .= '</optgroup>' . "\n";

						// Replace items.
						$items = $sublist;

					}

					// Add to html.
					$result .= $items;

				}

				// Close tags.
				$result .= '</select>' . "\n";
				$result .= '</span>' . "\n";

			} else {

				// Do the loop, but only has one item.
				while ( bp_groups() ) { bp_the_group();

					// Show a hidden input.
					$result .= '<input type="hidden" id="bpgsites-post-in" name="bpgsites-post-in" value="' . bp_get_group_id() . '" />' . "\n";

				} // End while.

			}

		}

		// --<
		return $result;

	}



	/**
	 * When a comment is saved, get the ID of the group it was submitted to.
	 *
	 * @since 0.1
	 *
	 * @return int $group_id The group ID of the input in the comment form.
	 */
	public function get_group_id_from_comment_form() {

		// Init as false.
		$group_id = false;

		// Is this a comment in a group?
		if ( isset( $_POST['bpgsites-post-in'] ) AND is_numeric( $_POST['bpgsites-post-in'] ) ) {

			// Get group ID.
			$group_id = absint( $_POST['bpgsites-post-in'] );

		}

		// --<
		return $group_id;

	}



	/**
	 * When our filtering form is is submitted, parse groups by selection.
	 *
	 * @since 0.1
	 *
	 * @param array $group_ids The group IDs.
	 * @return array $group_ids The filtered group IDs.
	 */
	public function filter_groups_by_checkboxes( $group_ids ) {

		// Is this a comment in a group?
		if ( isset( $_POST['bpgsites_comment_groups'] ) AND is_array( $_POST['bpgsites_comment_groups'] ) ) {

			// Overwrite with post array.
			$group_ids = $_POST['bpgsites_comment_groups'];

			// Sanitise all the items.
			$group_ids = array_map( 'intval', $group_ids );

			// Set cookie with delmited array.
			//setcookie( 'bpgsites_comment_groups', implode( '-', $group_ids ) );

		} else {

			/*
			// Do we have the cookie?
			if ( isset( $_COOKIE['bpgsites_comment_groups'] ) ) {

				// Get its contents.
				$group_ids = explode( '-', $_COOKIE['bpgsites_comment_groups'] );

			}

			// Set empty cookie.
			//setcookie( 'bpgsites_comment_groups', '' );
			*/

		}

		// --<
		return $group_ids;

	}



	/**
	 * When our filtering form is is submitted, parse groups by selection.
	 *
	 * @since 0.1
	 *
	 * @param array $classes The classes to be appied to the comment.
	 * @param array $class The comment class.
	 * @param array $comment_id The numerical ID of the comment.
	 * @param array $post_id The numerical ID of the post.
	 * @return array $filtered the filtered group IDs.
	 */
	public function add_group_to_comment_class( $classes, $class, $comment_id, $post_id ) {

		// Add utility class to all comments.
		$classes[] = 'bpgsites-shown';

		// Get group ID for this comment.
		$group_id = $this->get_comment_group_id( $comment_id );

		// Did we get one?
		if ( $group_id != '' AND is_numeric( $group_id ) AND $group_id > 0 ) {

			// Add group identifier.
			$classes[] = 'bpgsites-group-' . $group_id;

		}

		// Is the group a showcase group?
		if ( bpgsites_is_showcase_group( $group_id ) ) {

			// Add class so showcase groups can be styled.
			$classes[] = 'bpgsites-auth-group';

		}

		// --<
		return $classes;

	}



	/**
	 * Parse groups by user membership.
	 *
	 * @since 0.1
	 *
	 * @return array $user_group_ids Associative array of group IDs to which the user has access.
	 */
	public function get_groups_for_user() {

		// Have we already calculated this?
		if ( isset( $this->user_group_ids ) ) return $this->user_group_ids;

		// Init return.
		$this->user_group_ids = array(
			'my_groups' => array(),
			'linked_groups' => array(),
			'public_groups' => array(),
			'auth_groups' => bpgsites_showcase_groups_get(),
		);

		// Get current blog.
		$current_blog_id = get_current_blog_id();

		// Get this blog's group IDs.
		$group_ids = bpgsites_get_groups_by_blog_id( $current_blog_id );

		// Get user ID.
		$user_id = bp_loggedin_user_id();

		// Loop through the groups.
		foreach( $group_ids AS $group_id ) {

			// If this user is a member, add it.
			if ( groups_is_user_member( $user_id, $group_id ) ) {

				// If it's not already there.
				if ( ! in_array( $group_id, $this->user_group_ids['my_groups'] ) ) {

					// Add to our array.
					$this->user_group_ids['my_groups'][] = $group_id;

				}

			} else {

				// Get the group.
				$group = groups_get_group( array(
					'group_id' => $group_id
				) );

				// Get status of group.
				$status = bp_get_group_status( $group );

				// If public.
				if ( $status == 'public' ) {

					// Access object.
					//global $bp_groupsites;

					// Do we allow public comments?
					//if ( $bp_groupsites->admin->option_get( 'bpgsites_public' ) ) {

						// Add to our array.
						$this->user_group_ids['public_groups'][] = $group_id;

					//}

				} else {

					// If the user is not a member, is it one of the groups that is
					// Reading the site with this group?

					// Get linked groups.
					$linked_groups = bpgsites_group_linkages_get_groups_by_blog_id( $group_id, $current_blog_id );

					// Loop through them.
					foreach( $linked_groups AS $linked_group_id ) {

						// If the user is a member.
						if ( groups_is_user_member( $user_id, $linked_group_id ) ) {

							// Add the current one if it's not already there.
							if ( ! in_array( $group_id, $this->user_group_ids['my_groups'] ) ) {

								// Add to our array.
								$this->user_group_ids['linked_groups'][] = $group_id;

								// Don't need to check any further.
								break;

							}

						}

					}

				} // End public check.

			}

		}

		// --<
		return $this->user_group_ids;

	}



	/**
	 * Check if the user is a member of a group reading this site.
	 *
	 * @since 0.1
	 *
	 * @return boolean $this->user_in_group Whether the user is a member or not.
	 */
	public function is_user_in_group_reading_this_site() {

		// Have we already calculated this?
		if ( isset( $this->user_in_group ) ) return $this->user_in_group;

		// Init return.
		$this->user_in_group = false;

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// Does the user have any groups reading this site?
		$groups = groups_get_user_groups( bp_loggedin_user_id() );

		// Loop through them.
		foreach( $groups['groups'] AS $group ) {

			// If the user is a member.
			if (
				in_array( $group, $user_group_ids['my_groups'] ) OR
				in_array( $group, $user_group_ids['linked_groups'] )
			) {

				// Yes, kick out.
				$this->user_in_group = true;

				// --<
				return $this->user_in_group;

			}

		}

		// --<
		return $this->user_in_group;

	}



	/**
	 * Show group sites activity in sidebar.
	 *
	 * @since 0.1
	 */
	public function get_activity_sidebar_section() {

		// All Activity.

		// Get activities.
		if ( bp_has_activities( array(

			'scope' => 'groups',
			'action' => 'new_groupsite_comment,new_groupsite_post',

		) ) ) {

			// Change header depending on logged in status.
			if ( is_user_logged_in() ) {

				// Set default.
				$section_header_text = apply_filters(
					'bpgsites_activity_tab_recent_title_all_yours',
					sprintf(
						__( 'All Recent Activity in your %s', 'bp-group-sites' ),
						apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) )
					)
				);

			} else {

				// Set default.
				$section_header_text = apply_filters(
					'bpgsites_activity_tab_recent_title_all_public',
					sprintf(
						__( 'Recent Activity in Public %s', 'bp-group-sites' ),
						apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) )
					)
				);

			}

			// Open section.
			echo '<h3 class="activity_heading">' . $section_header_text . '</h3>

			<div class="paragraph_wrapper groupsites_comments_output">

			<ol class="comment_activity">';

			// Do the loop.
			while ( bp_activities() ) { bp_the_activity();
				echo $this->get_activity_item();
			}

			// Close section.
			echo '</ol>

			</div>';

		}



		// Friends Activity.

		// For logged in users only.
		if ( is_user_logged_in() ) {

			// Get activities.
			if ( bp_has_activities( array(

				'scope' => 'friends',
				'action' => 'new_groupsite_comment,new_groupsite_post',

			) ) ) {

				// Set default.
				$section_header_text = apply_filters(
					'bpgsites_activity_tab_recent_title_all_yours',
					sprintf(
						__( 'Friends Activity in your %s', 'bp-group-sites' ),
						apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) )
					)
				);

				// Open section.
				echo '<h3 class="activity_heading">' . $section_header_text . '</h3>

				<div class="paragraph_wrapper groupsites_comments_output">

				<ol class="comment_activity">';

				// Do the loop.
				while ( bp_activities() ) { bp_the_activity();
					echo $this->get_activity_item();
				}

				// Close section.
				echo '</ol>

				</div>';

			}

		}

	}



	/**
	 * Show group sites activity in sidebar.
	 *
	 * @since 0.1
	 */
	public function get_activity_item() {

		$same_post = '';

		?>

		<?php do_action( 'bp_before_activity_entry' ); ?>

		<li class="<?php bp_activity_css_class(); echo $same_post; ?>" id="activity-<?php bp_activity_id(); ?>">

			<div class="comment-wrapper">

				<div class="comment-identifier">

					<a href="<?php bp_activity_user_link(); ?>"><?php bp_activity_avatar( 'width=32&height=32' ); ?></a>
					<?php bp_activity_action(); ?>

				</div>

				<div class="comment-content">

					<?php if ( bp_activity_has_content() ) : ?>

						<?php bp_activity_content_body(); ?>

					<?php endif; ?>

					<?php do_action( 'bp_activity_entry_content' ); ?>

				</div>

			</div>

		</li>

		<?php do_action( 'bp_after_activity_entry' ); ?>

		<?php

	}



	/**
	 * Override the title of the Recent Posts section in the activity sidebar.
	 *
	 * @since 0.1
	 *
	 * @return str $title The overridden value of the Recent Posts section.
	 */
	public function get_activity_sidebar_recent_title() {

		// Set title, but allow plugins to override.
		$title = sprintf(
			__( 'Recent Comments in this %s', 'bp-group-sites' ),
			apply_filters( 'bpgsites_extension_name', __( 'Group Site', 'bp-group-sites' ) )
		);

		// --<
		return $title;

	}



	// =============================================================================
	// We may or may not use what follows...
	// =============================================================================



	/**
	 * Record the blog post activity for the group.
	 *
	 * @see: bp_groupblog_set_group_to_post_activity ( $activity )
	 *
	 * @since 0.1
	 *
	 * @param object $activity The existing activity.
	 * @return object $activity The modified activity.
	 */
	public function custom_post_activity( $activity ) {

		// Kick out until we figure out how to do this with multiple groups.
		return $activity;



		// -------------------------------------------------------------------------



		// Only on new blog posts.
		if ( ( $activity->type != 'new_blog_post' ) ) return $activity;

		// Clarify data.
		$blog_id = $activity->item_id;
		$post_id = $activity->secondary_item_id;
		$post = get_post( $post_id );



		// Get the group IDs for this blog.
		$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

		// Sanity check.
		if ( ! is_array( $group_ids ) OR count( $group_ids ) == 0 ) return $activity;



		// -------------------------------------------------------------------------
		// WHAT NOW???
		// -------------------------------------------------------------------------



		// Get group.
		$group = groups_get_group( array( 'group_id' => $group_id ) );

		// Set activity type.
		$type = 'new_groupsite_post';

		// See if we already have the modified activity for this blog post.
		$id = bp_activity_get_activity_id( array(
			'user_id' => $activity->user_id,
			'type' => $type,
			'item_id' => $group_id,
			'secondary_item_id' => $activity->secondary_item_id
		) );

		// If we don't find a modified item.
		if ( ! $id ) {

			// See if we have an unmodified activity item.
			$id = bp_activity_get_activity_id( array(
				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id
			) );

		}

		// If we found an activity for this blog post then overwrite that to avoid
		// Having multiple activities for every blog post edit.
		if ( $id ) {
			$activity->id = $id;
		}

		// Allow plugins to override the name of the activity item.
		$activity_name = apply_filters(
			'bpgsites_activity_post_name',
			__( 'post', 'bp-group-sites' )
		);

		// Default to standard BP author.
		$activity_author = bp_core_get_userlink( $post->post_author );

		// Compat with Co-Authors Plus.
		if ( function_exists( 'get_coauthors' ) ) {

			// Get multiple authors.
			$authors = get_coauthors();

			// If we get some.
			if ( ! empty( $authors ) ) {

				// We only want to override if we have more than one.
				if ( count( $authors ) > 1 ) {

					// Use the Co-Authors format of "name, name, name and name".
					$activity_author = '';

					// Init counter.
					$n = 1;

					// Find out how many author we have.
					$author_count = count( $authors );

					// Loop.
					foreach( $authors AS $author ) {

						// Default to comma.
						$sep = ', ';

						// If we're on the penultimate.
						if ( $n == ($author_count - 1) ) {

							// Use ampersand.
							$sep = __( ' &amp; ', 'bp-group-sites' );

						}

						// If we're on the last, don't add.
						if ( $n == $author_count ) { $sep = ''; }

						// Add name.
						$activity_author .= bp_core_get_userlink( $author->ID );

						// And separator.
						$activity_author .= $sep;

						// Increment.
						$n++;

					}

				}

			}

		}

		// If we're replacing an item, show different message.
		if ( $id ) {

			// Replace the necessary values to display in group activity stream.
			$activity->action = sprintf(
				__( '%1$s updated a %2$s %3$s in the group %4$s:', 'bp-group-sites' ),
				$activity_author,
				$activity_name,
				'<a href="' . get_permalink( $post->ID ) . '">' . esc_attr( $post->post_title ) . '</a>',
				'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>'
			);

		} else {

			// Replace the necessary values to display in group activity stream.
			$activity->action = sprintf(
				__( '%1$s wrote a new %2$s %3$s in the group %4$s:', 'bp-group-sites' ),
				$activity_author,
				$activity_name,
				'<a href="' . get_permalink( $post->ID ) . '">' . esc_attr( $post->post_title ) . '</a>',
				'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>'
			);

		}

		$activity->item_id = (int)$group_id;
		$activity->component = 'groups';

		// Having marked all groupblogs as public, we need to hide activity from them if the group is private
		// Or hidden, so they don't show up in sitewide activity feeds.
		if ( 'public' != $group->status ) {
			$activity->hide_sitewide = true;
		} else {
			$activity->hide_sitewide = false;
		}

		// Set to relevant custom type.
		$activity->type = $type;

		// Prevent from firing again.
		remove_action( 'bp_activity_before_save', array( $this, 'custom_post_activity' ) );

		// --<
		return $activity;

	}



	/**
	 * Add a filter option to the filter select box on group activity pages.
	 *
	 * @since 0.1
	 */
	public function posts_filter_option() {

		// Default name.
		$post_name = __( 'Group Site Posts', 'bp-group-sites' );

		// Allow plugins to override the name of the option.
		$post_name = apply_filters( 'bpgsites_post_name', $post_name );

		// Construct option.
		$option = '<option value="new_groupsite_post">' . $post_name . '</option>' . "\n";

		// Print.
		echo $option;

	}



} // End class BP_Group_Sites_Activity.



