<?php
/**
 * BP Group Sites Activity class.
 *
 * Handles BuddyPress Activity functionality.
 *
 * @package BP_Group_Sites
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
	 * @var array
	 */
	public $groups = [];

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

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
		add_action( 'bp_activity_filter_options', [ $this, 'posts_filter_option' ] );
		add_action( 'bp_group_activity_filter_options', [ $this, 'posts_filter_option' ] );
		add_action( 'bp_member_activity_filter_options', [ $this, 'posts_filter_option' ] );
		*/

		// Add our comments filter.
		add_action( 'bp_activity_filter_options', [ $this, 'comments_filter_option' ] );
		add_action( 'bp_group_activity_filter_options', [ $this, 'comments_filter_option' ] );
		add_action( 'bp_member_activity_filter_options', [ $this, 'comments_filter_option' ] );

		// Filter the AJAX query string to add the "action" variable.
		add_filter( 'bp_ajax_querystring', [ $this, 'comments_ajax_querystring' ], 20, 2 );

		// Filter the comment link so replies are done in CommentPress.
		add_filter( 'bp_get_activity_comment_link', [ $this, 'filter_comment_link' ] );

		// Filter the activity item permalink to point to the comment.
		add_filter( 'bp_activity_get_permalink', [ $this, 'filter_comment_permalink' ], 20, 2 );

		// If the current blog is a group site.
		if ( bpgsites_is_groupsite( get_current_blog_id() ) ) {

			/*
			// Add custom post activity. Disabled until later.
			add_action( 'bp_activity_before_save', [ $this, 'custom_post_activity' ], 10 );
			*/

			// Make sure "Allow activity stream commenting on blog and forum posts" is disabled for group sites.
			add_action( 'bp_disable_blogforum_comments', [ $this, 'disable_blogforum_comments' ], 100, 1 );

			// Add custom comment activity.
			add_action( 'bp_activity_before_save', [ $this, 'custom_comment_activity' ], 10 );

			// Add our dropdown (or hidden input) to comment form.
			add_filter( 'comment_id_fields', [ $this, 'get_comment_group_selector' ], 10, 3 );

			// Hook into comment save process.
			add_action( 'comment_post', [ $this, 'save_comment_group_id' ], 10, 2 );

			// Add action for checking comment moderation.
			add_filter( 'pre_comment_approved', [ $this, 'check_comment_approval' ], 100, 2 );

			// Allow comment authors to edit their own comments.
			add_filter( 'map_meta_cap', [ $this, 'enable_comment_editing' ], 10, 4 );

			// Add navigation items for groups.
			add_filter( 'cp_nav_after_network_home_title', [ $this, 'get_group_navigation_links' ] );

			// Override reply to link.
			add_filter( 'comment_reply_link', [ $this, 'override_reply_to_link' ], 10, 4 );

			// Override CommentPress TinyMCE.
			add_filter( 'cp_override_tinymce', [ $this, 'disable_tinymce' ], 10 );

			// Add action to insert comments-by-group filter.
			add_action( 'commentpress_before_scrollable_comments', [ $this, 'get_group_comments_filter' ] );

			// Add group ID as class to comment.
			add_filter( 'comment_class', [ $this, 'add_group_to_comment_class' ], 10, 4 );

			// Filter comments by group membership.
			add_action( 'parse_comment_query', [ $this, 'filter_comments' ], 100, 1 );

			// Override what is reported by get_comments_number.
			add_filter( 'get_comments_number', [ $this, 'get_comments_number' ], 20, 2 );

			// Override comment form if no group membership.
			add_filter( 'commentpress_show_comment_form', [ $this, 'show_comment_form' ], 10 );

			// Add section to activity sidebar in CommentPress.
			add_filter( 'commentpress_bp_activity_sidebar_before_members', [ $this, 'get_activity_sidebar_section' ] );

			// Override cp_activity_tab_recent_title_blog.
			add_filter( 'cp_activity_tab_recent_title_blog', [ $this, 'get_activity_sidebar_recent_title' ] );

			// Register a meta box.
			add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );

			// Intercept comment edit process in WordPress admin.
			add_action( 'edit_comment', [ $this, 'save_comment_metabox_metadata' ] );

			// Intercept comment edit process in CommentPress front-end.
			add_action( 'edit_comment', [ $this, 'save_comment_edit_metadata' ] );

			// Show group at top of comment content.
			add_filter( 'get_comment_text', [ $this, 'show_comment_group' ], 20, 3 );

			// Add group ID to AJAX edit comment data.
			add_filter( 'commentpress_ajax_get_comment', [ $this, 'filter_ajax_get_comment' ], 10 );

			// Add group data to AJAX edited comment data.
			add_filter( 'commentpress_ajax_edited_comment', [ $this, 'filter_ajax_edited_comment' ], 10 );

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Show the group into which a comment has been posted.
	 *
	 * @since 0.1
	 *
	 * @param str    $comment_content The content of the comment.
	 * @param object $comment The comment object.
	 * @param array  $args The arguments.
	 * @return str $comment_content The content of the comment.
	 */
	public function show_comment_group( $comment_content, $comment, $args ) {

		// Init prefix.
		$prefix = '';

		// Get group ID.
		$group_id = $this->get_comment_group_id( $comment->comment_ID );

		// Sanity check.
		if ( is_numeric( $group_id ) && $group_id > 0 ) {

			// Get the group.
			$group = groups_get_group( [ 'group_id' => $group_id ] );

			// Get group name.
			$name = bp_get_group_name( $group );

			// Wrap name in anchor.
			$link = '<a href="' . bp_get_group_permalink( $group ) . '">' . $name . '</a>';

			/**
			 * Construct prefix and allow filtering.
			 *
			 * @since 0.1
			 *
			 * @param str The inner markup.
			 * @param str $name The group name.
			 * @param object $comment The comment data object.
			 * @param int $group_id The group ID.
			 * @return str The modified inner markup.
			 */
			$prefix = apply_filters(
				'bpgsites_comment_prefix',
				/* translators: %s The link to the Group. */
				sprintf( __( 'Posted in: %s', 'bp-group-sites' ), $link ),
				$name,
				$comment,
				$group_id
			);

			// Wrap prefix in para tag.
			$prefix = '<p>' . $prefix . '</p>';

		}

		// Prepend to comment content.
		$comment_content = '<div class="bpgsites_comment_posted_in">' . $prefix . "</div>\n\n" . $comment_content;

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
			[ $this, 'comment_meta_box' ],
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
		if ( 0 !== $reply_to_id ) {

			// The group that comment replies have must be the same as its parent.

			// Get group ID.
			$group_id = $this->get_comment_group_id( $comment_id );

			// Sanity check.
			if ( is_numeric( $group_id ) && $group_id > 0 ) {

				// Show message.
				echo '<p>' . esc_html__( 'This comment is a reply. It appears in the same group as the comment it is in reply to. If there is a deeper thread of replies, then the original comment determines the group in which it appears.', 'bp-group-sites' ) . '</p>';

				// Get group name.
				$name = bp_get_group_name( groups_get_group( [ 'group_id' => $group_id ] ) );

				// Construct message.
				$message = sprintf(
					/* translators: %s The name of the Group. */
					esc_html__( 'This comment appears in the group %s.', 'bp-group-sites' ),
					$name
				);

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<p>' . $message . '</p>';

			}

		} else {

			// Top level comments can be re-assigned.

			// Use nonce for verification.
			wp_nonce_field( 'bpgsites_comments_metabox', 'bpgsites_comments_nonce' );

			// Open para.
			echo '<p>';

			// Get select dropdown.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_comment_group_select(
				'', // No existing content.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$comment_id,
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$reply_to_id,
				true // Trigger edit mode.
			);

			// Close para.
			echo '</p>';

		}

	}

	/**
	 * Save data returned by our comment meta box in WordPress admin.
	 *
	 * @since 0.1
	 *
	 * @param int $comment_id The ID of the comment being saved.
	 */
	public function save_comment_metabox_metadata( $comment_id ) {

		// If there's no nonce then there's no comment meta data.
		if ( ! isset( $_POST['bpgsites_comments_nonce'] ) ) {
			return;
		}

		// Authenticate submission.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! wp_verify_nonce( wp_unslash( $_POST['bpgsites_comments_nonce'] ), 'bpgsites_comments_metabox' ) ) {
			return;
		}

		// Check capabilities.
		if ( ! current_user_can( 'edit_comment', $comment_id ) ) {

			// Cheating!
			wp_die( esc_html__( 'You are not allowed to edit comments on this post.', 'bp-group-sites' ) );

		}

		// Save data, ignoring comment status param.
		$this->save_comment_group_id( $comment_id, null );

	}

	/**
	 * Save data returned by editing a comment in CommentPress front-end.
	 *
	 * @since 0.2.8
	 *
	 * @param int $comment_id The ID of the comment being saved.
	 */
	public function save_comment_edit_metadata( $comment_id ) {

		// If there's no nonce then there's no comment meta data.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['cpajax_comment_nonce'] ) ) {
			return;
		}

		// Bail if there's no POST data for us.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['bpgsites-post-in'] ) ) {
			return;
		}

		// Check capabilities.
		if ( ! current_user_can( 'edit_comment', $comment_id ) ) {

			// Cheating!
			wp_die( esc_html__( 'You are not allowed to edit comments on this post.', 'bp-group-sites' ) );

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
		if ( bpgsites_is_groupsite( $blog_id ) ) {
			return 1;
		}

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
		if ( 'new_blog_comment' !== $activity->type ) {
			return $activity;
		}

		// Get group ID from POST.
		$group_id = $this->get_group_id_from_comment_form();

		// Kick out if not a comment in a group.
		if ( false === $group_id ) {
			return $activity;
		}

		// Set activity type.
		$type = 'new_groupsite_comment';

		// Okay, let's get the group object.
		$group = groups_get_group( [ 'group_id' => $group_id ] );

		// See if we already have the modified activity for this blog post.
		$args = [
			'user_id'           => $activity->user_id,
			'type'              => $type,
			'item_id'           => $group_id,
			'secondary_item_id' => $activity->secondary_item_id,
		];

		$id = bp_activity_get_activity_id( $args );

		// If we don't find a modified item.
		if ( ! $id ) {

			// See if we have an unmodified activity item.
			$args = [
				'user_id'           => $activity->user_id,
				'type'              => $activity->type,
				'item_id'           => $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id,
			];

			$id = bp_activity_get_activity_id( $args );

		}

		// If we found an activity for this blog comment then overwrite that to avoid having
		// Multiple activities for every blog comment edit.
		if ( $id ) {
			$activity->id = $id;
		}

		// Get the comment.
		$comment = get_comment( $activity->secondary_item_id );

		// Get the post.
		$post = get_post( $comment->comment_post_ID );

		// Was it a registered user?
		if ( 0 !== (int) $comment->user_id ) {

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
			/* translators: 1: The User link, 2: The link to the comment, 3: The activity name, 4: The link to the post, 5: The link to the group. */
			__( '%1$s left a %2$s on a %3$s %4$s in the group %5$s:', 'bp-group-sites' ),
			$user_link,
			'<a href="' . $activity->primary_link . '">' . __( 'comment', 'bp-group-sites' ) . '</a>',
			$activity_name,
			$target_post_link,
			'<a href="' . bp_get_group_permalink( $group ) . '">' . esc_html( $group->name ) . '</a>'
		);

		// Apply group id.
		$activity->item_id = (int) $group_id;

		// Change to groups component.
		$activity->component = 'groups';

		// Having marked all groupblogs as public, we need to hide activity from them if the group is private
		// Or hidden, so they don't show up in sitewide activity feeds.
		if ( 'public' !== $group->status ) {
			$activity->hide_sitewide = true;
		} else {
			$activity->hide_sitewide = false;
		}

		// Set unique type.
		$activity->type = $type;

		// Prevent from firing again.
		remove_action( 'bp_activity_before_save', [ $this, 'custom_comment_activity' ] );

		// --<
		return $activity;

	}

	/**
	 * Filter the activity comment permalink on activity items to point to the
	 * original comment in context.
	 *
	 * @since 0.1
	 *
	 * @param array  $args Existing activity permalink data.
	 * @param object $activity The activity item.
	 * @return array $args The overridden activity permalink data.
	 */
	public function filter_comment_permalink( $args, $activity ) {

		// Our custom activity types.
		$types = [ 'new_groupsite_comment' ];

		// Not one of ours?
		if ( ! in_array( $activity->type, $types, true ) ) {
			return $args;
		}

		// --<
		return bp_get_activity_feed_item_link();

	}

	/**
	 * Filter the comment reply link on activity items. This is called during the
	 * loop, so we can assume that the activity item API will work.
	 *
	 * @since 0.1
	 *
	 * @param string $link The existing comment reply link.
	 * @return string $link The overridden comment reply link.
	 */
	public function filter_comment_link( $link ) {

		// Get type of activity.
		$type = bp_get_activity_action_name();

		// Our custom activity types.
		$types = [ 'new_groupsite_comment' ];

		// Not one of ours?
		if ( ! in_array( $type, $types, true ) ) {
			return $link;
		}

		/*
		// Check for our custom activity type.
		if ( $type == 'new_groupsite_comment' ) {
			$link_text = __( 'Reply', 'bp-group-sites' );
		}

		// Construct new link to actual comment.
		$link = '<a href="' . bp_get_activity_feed_item_link() . '" class="button acomment-reply bp-primary-action">' . $link_text . '</a>';
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
				/* translators: %s: The plural name for Group Sites. */
				__( 'Comments in %s', 'bp-group-sites' ),
				bpgsites_get_extension_plural()
			)
		);

		// Construct option.
		$option = '<option value="new_groupsite_comment">' . esc_html( $comment_name ) . '</option>' . "\n";

		// Print.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $option;

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
		if ( 'activity' !== $object ) {
			return $qs;
		}

		// Parse query string into an array.
		$r = wp_parse_args( $qs );

		// Bail if no type is set.
		if ( empty( $r['type'] ) ) {
			return $qs;
		}

		// Bail if not a type that we're looking for.
		if ( 'new_groupsite_comment' !== $r['type'] ) {
			return $qs;
		}

		// Add the 'new_groupsite_comment' type if it doesn't exist.
		if ( ! isset( $r['action'] ) || false === strpos( $r['action'], 'new_groupsite_comment' ) ) {
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
		$comments = get_comments( [ 'post_id' => $post_id ] );

		// Return the number if we get some.
		if ( is_array( $comments ) ) {
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
		if ( is_admin() ) {
			return $comments;
		}

		// Init array.
		$groups = [];

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// If we get some.
		if (
			count( $user_group_ids['my_groups'] ) > 0 ||
			count( $user_group_ids['linked_groups'] ) > 0 ||
			count( $user_group_ids['public_groups'] ) > 0 ||
			count( $user_group_ids['auth_groups'] ) > 0
		) {

			// Merge the arrays.
			$groups = array_unique(
				array_merge(
					$user_group_ids['my_groups'],
					$user_group_ids['linked_groups'],
					$user_group_ids['public_groups'],
					$user_group_ids['auth_groups']
				)
			);

		}

		// If none.
		if (
			count( $user_group_ids['my_groups'] ) === 0 &&
			count( $user_group_ids['linked_groups'] ) === 0 &&
			count( $user_group_ids['public_groups'] ) === 0 &&
			count( $user_group_ids['auth_groups'] ) === 0
		) {

			// Set a non-existent group ID.
			$groups = [ 0 ];

		}

		/*
		 * At this point, we need both a meta query that queries for the group ID
		 * in the comment meta as well as a meta query that queries for the absence
		 * of a meta value so that any legacy comments that were not attached to
		 * a group show up.
		 */

		// Construct our meta query addition.
		$meta_query = [
			'relation' => 'OR',
			[
				'key'     => BPGSITES_COMMENT_META_KEY,
				'value'   => $groups,
				'compare' => 'IN',
			],
			[
				'key'     => BPGSITES_COMMENT_META_KEY,
				'value'   => '',
				'compare' => 'NOT EXISTS',
			],
		];

		// Make sure meta query is an array.
		if ( ! is_array( $comments->query_vars['meta_query'] ) ) {
			// phpcs:ignore: WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$comments->query_vars['meta_query'] = [];
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
	 * @param array  $args The setup array.
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
				$href      = bp_get_signup_page();
			} else {
				$link_text = __( 'Login to reply', 'bp-group-sites' );
				$href      = wp_login_url();
			}

			// Construct link.
			$link = '<a rel="nofollow" href="' . $href . '">' . $link_text . '</a>';

			// --<
			return $link;

		}

		// Get current blog ID.
		$blog_id = get_current_blog_id();

		// Pass through if not group site.
		if ( ! bpgsites_is_groupsite( $blog_id ) ) {
			return $link;
		}

		// Get comment group.
		$group_id = $this->get_comment_group_id( $comment->comment_ID );

		// Comments can pre-exist that are not group-linked.
		if ( ! is_numeric( $group_id ) || 0 === (int) $group_id ) {
			return $link;
		}

		// Get user ID.
		$user_id = bp_loggedin_user_id();

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// Is this group one of these?
		if (
			in_array( (int) $group_id, $user_group_ids['my_groups'], true ) ||
			in_array( (int) $group_id, $user_group_ids['linked_groups'], true )
		) {
			return $link;
		}

		// Get the group.
		$args = [
			'group_id' => $group_id,
		];

		$group = groups_get_group( $args );

		// Get showcase groups.
		$showcase_groups = bpgsites_showcase_groups_get();

		// Is it a showcase group?
		if ( in_array( (int) $group_id, $showcase_groups, true ) ) {

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
		if ( ! bpgsites_is_groupsite( $blog_id ) ) {
			return $tinymce;
		}

		// Is the current member in a relevant group?
		if ( ! $this->is_user_in_group_reading_this_site() ) {

			// Add filters on reply to link.
			add_filter( 'commentpress_reply_to_para_link_text', [ $this, 'override_reply_to_text' ], 10, 2 );
			add_filter( 'commentpress_reply_to_para_link_href', [ $this, 'override_reply_to_href' ], 10, 2 );
			add_filter( 'commentpress_reply_to_para_link_onclick', [ $this, 'override_reply_to_onclick' ], 10 );

			// Disable.
			return 0;

		}

		// Use TinyMCE if logged in.
		if ( is_user_logged_in() ) {
			return $tinymce;
		}

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
		if ( ! bpgsites_is_groupsite( $blog_id ) ) {
			return $show;
		}

		// Pass through if the current member is in a relevant group.
		if ( $this->is_user_in_group_reading_this_site() ) {
			return $show;
		}

		// Filter the comment form message.
		add_filter( 'commentpress_comment_form_hidden', [ $this, 'override_comment_form_hidden' ], 10 );

		// --<
		return false;

	}

	/**
	 * Show a message if we are hiding the comment form.
	 *
	 * @since 0.1
	 *
	 * @param str $hidden_text The message shown when the comment form is hidden.
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
				/* translators: 1: The URL, 2: The anchor text. */
				sprintf( __( '<a href="%1$s">%2$s</a>', 'bp-group-sites' ), $href, $text )
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
			/* translators: 1: The URL, 2: The anchor text. */
			sprintf( __( '<a href="%1$s">%2$s</a>', 'bp-group-sites' ), $href, $text )
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
			/* translators: %s: The paragraph text. */
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
	 * @param int   $approved The comment status.
	 * @param array $commentdata The comment data.
	 * @return int $approved The modified comment status.
	 */
	public function check_comment_approval( $approved, $commentdata ) {

		// Get current blog ID.
		$blog_id = get_current_blog_id();

		// Pass through if not group site.
		if ( ! bpgsites_is_groupsite( $blog_id ) ) {
			return $approved;
		}

		// Get the user ID of the comment author.
		$user_id = absint( $commentdata['user_ID'] );

		// Get group that comment was posted into - comment meta is not saved yet.
		$group_id = $this->get_group_id_from_comment_form();

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// Did we get one?
		if ( ! empty( $group_id ) && is_numeric( $group_id ) && 0 < $group_id ) {

			// Is this user a member?
			if ( in_array( (int) $group_id, $user_group_ids['my_groups'], true ) ) {

				// Allow un-moderated commenting.
				return 1;

			}

			// If commenting into a linked group.
			if ( in_array( (int) $group_id, $user_group_ids['linked_groups'], true ) ) {

				// TODO: if linked group, hold and send BP notification?

				// For now, allow un-moderated commenting.
				return 1;

			}

		}

		// Pass through.
		return $approved;

	}

	/**
	 * For group sites, add capability to edit own comments.
	 *
	 * @param array $caps The existing capabilities array for the WordPress user.
	 * @param str   $cap The capability in question.
	 * @param int   $user_id The numerical ID of the WordPress user.
	 * @param array $args The additional arguments.
	 * @return array $caps The modified capabilities array for the WordPress user.
	 */
	public function enable_comment_editing( $caps, $cap, $user_id, $args ) {

		// Only apply this to queries for edit_comment cap.
		if ( 'edit_comment' === $cap ) {

			// Get comment.
			$comment = get_comment( $args[0] );

			// Is the user the same as the comment author?
			if ( (int) $comment->user_id === (int) $user_id ) {

				// Allow.
				$caps = [ 'exist' ];

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
				count( $user_group_ids['my_groups'] ) === 0 &&
				count( $user_group_ids['linked_groups'] ) === 0 &&
				count( $user_group_ids['public_groups'] ) === 0
			) {
				// --<
				return;
			}

			// Init array.
			$groups = [];

			// If any has entries.
			if (
				count( $user_group_ids['my_groups'] ) > 0 ||
				count( $user_group_ids['public_groups'] ) > 0
			) {

				// Merge the arrays.
				$groups = array_unique(
					array_merge(
						$user_group_ids['my_groups'],
						$user_group_ids['linked_groups'],
						$user_group_ids['public_groups']
					)
				);

			}

			// Define config array.
			$config_array = [
				// 'user_id'         => $user_id,
				'type'            => 'alphabetical',
				'populate_extras' => 0,
				'include'         => $groups,
			];

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
							/* translators: %s: The singular name of a Group Site. */
							__( 'Groups reading this %s', 'bp-group-sites' ),
							bpgsites_get_extension_name()
						)
					);

					// Construct item.
					$html .= '<li><a href="#groupsites-list" id="btn_groupsites" class="css_btn" title="' . esc_attr( $title ) . '">' . esc_html( $title ) . '</a>';

					// Open sublist.
					$html .= '<ul class="children" id="groupsites-list">' . "\n";

					// Init lists.
					$mine   = [];
					$linked = [];
					$public = [];

					// Do the loop.
					while ( bp_groups() ) {
						bp_the_group();

						// Construct item.
						$item = '<li>' .
									'<a href="' . esc_url( bp_get_group_permalink() ) . '" class="css_btn btn_groupsites" title="' . esc_attr( bp_get_group_name() ) . '">' .
										esc_html( bp_get_group_name() ) .
									'</a>' .
								'</li>';

						// Get group ID.
						$group_id = bp_get_group_id();

						// Mine?
						if ( in_array( (int) $group_id, $user_group_ids['my_groups'], true ) ) {
							$mine[] = $item;
							continue;
						}

						// Linked?
						if ( in_array( (int) $group_id, $user_group_ids['linked_groups'], true ) ) {
							$linked[] = $item;
							continue;
						}

						// Public?
						if ( in_array( (int) $group_id, $user_group_ids['public_groups'], true ) ) {
							$public[] = $item;
						}

					} // End while.

					// Did we get any that are mine?
					if ( count( $mine ) > 0 ) {

						// Join items.
						$items = implode( "\n", $mine );

						// Only show if we one of the other lists is populated.
						if ( count( $linked ) > 0 || count( $public ) > 0 ) {

							// Construct title.
							$title = __( 'My Groups', 'bp-group-sites' );

							// Construct item.
							$sublist = '<li><a href="#groupsites-list-mine" id="btn_groupsites_mine" class="css_btn" title="' . esc_attr( $title ) . '">' . esc_html( $title ) . '</a>';

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
						if ( count( $mine ) > 0 || count( $public ) > 0 ) {

							// Construct title.
							$title = __( 'Linked Groups', 'bp-group-sites' );

							// Construct item.
							$sublist = '<li><a href="#groupsites-list-linked" id="btn_groupsites_linked" class="css_btn" title="' . esc_attr( $title ) . '">' . esc_html( $title ) . '</a>';

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
						if ( count( $mine ) > 0 || count( $linked ) > 0 ) {

							// Construct title.
							$title = __( 'Public Groups', 'bp-group-sites' );

							// Construct item.
							$sublist = '<li><a href="#groupsites-list-public" id="btn_groupsites_public" class="css_btn" title="' . esc_attr( $title ) . '">' . esc_html( $title ) . '</a>';

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

					// Do we want to use bp_get_group_name()?

					// Do the loop (though there will only be one item.
					while ( bp_groups() ) {
						bp_the_group();

						// Construct item.
						$html .= '<li>' .
							'<a href="' . esc_url( bp_get_group_permalink() ) . '" id="btn_groupsites" class="css_btn" title="' . esc_attr( $title ) . '">' .
								esc_html( $title ) .
							'</a>' .
						'</li>';

					}

				}

			}

			// Output.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
			count( $user_group_ids['auth_groups'] ) === 0 &&
			count( $user_group_ids['my_groups'] ) === 0 &&
			count( $user_group_ids['linked_groups'] ) === 0 &&
			count( $user_group_ids['public_groups'] ) === 0
		) {
			// --<
			return;
		}

		// Init array.
		$groups = [];

		// If any has entries.
		if (
			count( $user_group_ids['auth_groups'] ) > 0 ||
			count( $user_group_ids['my_groups'] ) > 0 ||
			count( $user_group_ids['linked_groups'] ) > 0 ||
			count( $user_group_ids['public_groups'] ) > 0
		) {

			// Merge the arrays.
			$groups = array_unique(
				array_merge(
					$user_group_ids['auth_groups'],
					$user_group_ids['my_groups'],
					$user_group_ids['linked_groups'],
					$user_group_ids['public_groups']
				)
			);

		}

		// Define config array.
		$config_array = [
			// 'user_id'         => $user_id,
			'type'            => 'alphabetical',
			'max'             => 100,
			'per_page'        => 100,
			'populate_extras' => 0,
			'include'         => $groups,
		];

		// Get groups.
		if ( bp_has_groups( $config_array ) ) {

			// Access object.
			global $groups_template, $post;

			// Only show if user has more than one.
			if ( $groups_template->group_count > 1 ) {

				// Construct heading - the no_comments class prevents this from printing.
				$html .= '<h3 class="bpgsites_group_filter_heading no_comments">' .
					'<a href="#bpgsites_group_filter">' . esc_html__( 'Filter comments by group', 'bp-group-sites' ) . '</a>' .
				'</h3>' . "\n";

				// Open div.
				$html .= '<div id="bpgsites_group_filter" class="bpgsites_group_filter no_comments">' . "\n";

				// Open form.
				$html .= '<form id="bpgsites_comment_group_filter" name="bpgsites_comment_group_filter" action="' . esc_url( get_permalink( $post->ID ) ) . '" method="post">' . "\n";

				// Init lists.
				$auth   = [];
				$mine   = [];
				$linked = [];
				$public = [];

				// Init checked for public groups.
				$checked = '';

				// Get option.
				$public_shown = bp_groupsites()->admin->option_get( 'bpgsites_public' );

				// Are they to be shown?
				if ( 1 === (int) $public_shown ) {
					$checked = ' checked="checked"';
				}

				// Do the loop.
				while ( bp_groups() ) {
					bp_the_group();

					// Add arbitrary divider.
					$item = '<span class="bpgsites_comment_group">' . "\n";

					// Get group ID.
					$group_id = bp_get_group_id();

					// Showcase?
					if ( in_array( (int) $group_id, $user_group_ids['auth_groups'], true ) ) {

						// Add checkbox.
						$item .= '<input type="checkbox" class="bpgsites_group_checkbox bpgsites_group_checkbox_auth" name="bpgsites_comment_groups[]" id="bpgsites_comment_group_' . esc_attr( $group_id ) . '" value="' . esc_attr( $group_id ) . '" checked="checked" />' . "\n";

						// Add label.
						$item .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_' . esc_attr( $group_id ) . '">' .
							esc_html( bp_get_group_name() ) .
						'</label>' . "\n";

						// Close arbitrary divider.
						$item .= '</span>' . "\n";

						// Public.
						$auth[] = $item;

						// Next.
						continue;

					}

					// Mine?
					if ( in_array( (int) $group_id, $user_group_ids['my_groups'], true ) ) {

						// Add checkbox.
						$item .= '<input type="checkbox" class="bpgsites_group_checkbox bpgsites_group_checkbox_mine" name="bpgsites_comment_groups[]" id="bpgsites_comment_group_' . esc_attr( $group_id ) . '" value="' . esc_attr( $group_id ) . '" checked="checked" />' . "\n";

						// Add label.
						$item .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_' . esc_attr( $group_id ) . '">' .
							esc_html( bp_get_group_name() ) .
						'</label>' . "\n";

						// Close arbitrary divider.
						$item .= '</span>' . "\n";

						// Public.
						$mine[] = $item;

						// Next.
						continue;

					}

					// Linked?
					if ( in_array( (int) $group_id, $user_group_ids['linked_groups'], true ) ) {

						// Add checkbox.
						$item .= '<input type="checkbox" class="bpgsites_group_checkbox bpgsites_group_checkbox_linked" name="bpgsites_comment_groups[]" id="bpgsites_comment_group_' . esc_attr( $group_id ) . '" value="' . esc_attr( $group_id ) . '" checked="checked" />' . "\n";

						// Add label.
						$item .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_' . esc_attr( $group_id ) . '">' .
							esc_html( bp_get_group_name() ) .
						'</label>' . "\n";

						// Close arbitrary divider.
						$item .= '</span>' . "\n";

						// Public.
						$linked[] = $item;

						// Next.
						continue;

					}

					// Public?
					if ( in_array( (int) $group_id, $user_group_ids['public_groups'], true ) ) {

						// Add checkbox.
						$item .= '<input type="checkbox" class="bpgsites_group_checkbox bpgsites_group_checkbox_public" name="bpgsites_comment_groups[]" id="bpgsites_comment_group_' . esc_attr( $group_id ) . '" value="' . esc_attr( $group_id ) . '"' . $checked . ' />' . "\n";

						// Add label.
						$item .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_' . esc_attr( $group_id ) . '">' .
							esc_html( bp_get_group_name() ) .
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
				$html .= '<label class="bpgsites_comment_group_label" for="bpgsites_comment_group_toggle">' . esc_html__( 'Show all groups', 'bp-group-sites' ) . '</label>' . "\n";

				// Close arbitrary divider.
				$html .= '</span>' . "\n";

				// Did we get any showcase groups?
				if ( count( $auth ) > 0 ) {

					// Add heading if we one of the other lists is populated.
					if ( count( $mine ) > 0 || count( $public ) > 0 || count( $linked ) > 0 ) {
						$html .= '<span class="bpgsites_comment_group bpgsites_comment_group_header bpgsites_comment_group_auth">' . esc_html__( 'Showcase Groups', 'bp-group-sites' ) . '</span>' . "\n";
					}

					// Add items.
					$html .= implode( "\n", $auth );

				}

				// Did we get any that are mine?
				if ( count( $mine ) > 0 ) {

					// Add heading if we one of the other lists is populated.
					if ( count( $auth ) > 0 || count( $public ) > 0 || count( $linked ) > 0 ) {
						$html .= '<span class="bpgsites_comment_group bpgsites_comment_group_header bpgsites_comment_group_mine">' . esc_html__( 'My Groups', 'bp-group-sites' ) . '</span>' . "\n";
					}

					// Add items.
					$html .= implode( "\n", $mine );

				}

				// Did we get any that are linked?
				if ( count( $linked ) > 0 ) {

					// Add heading if we one of the other lists is populated.
					if ( count( $auth ) > 0 || count( $mine ) > 0 || count( $linked ) > 0 ) {
						$html .= '<span class="bpgsites_comment_group bpgsites_comment_group_header bpgsites_comment_group_linked">' . esc_html__( 'Linked Groups', 'bp-group-sites' ) . '</span>' . "\n";
					}

					// Add items.
					$html .= implode( "\n", $linked );

				}

				// Did we get any that are public?
				if ( count( $public ) > 0 ) {

					// Add heading if we one of the other lists is populated.
					if ( count( $auth ) > 0 || count( $mine ) > 0 || count( $linked ) > 0 ) {
						$html .= '<span class="bpgsites_comment_group bpgsites_comment_group_header bpgsites_comment_group_public">' . esc_html__( 'Public Groups', 'bp-group-sites' ) . '</span>' . "\n";
					}

					// Add items.
					$html .= implode( "\n", $public );

				}

				// Add submit button.
				$html .= '<input type="submit" id="bpgsites_comment_group_submit" value="' . esc_html__( 'Filter', 'bp-group-sites' ) . '" />' . "\n";

				// Close tags.
				$html .= '</form>' . "\n";
				$html .= '</div>' . "\n";

			}

		}

		// Output escaped markup.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;

	}

	/**
	 * Inserts a dropdown (or hidden input) into the comment form.
	 *
	 * @since 0.1
	 *
	 * @param string $result Existing markup to be sent to browser.
	 * @param int    $comment_id The comment ID.
	 * @param int    $reply_to_id The comment ID to which this comment is a reply.
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
	 * @param int    $comment_id The comment ID.
	 * @param int    $reply_to_id The comment ID to which this comment is a reply.
	 * @param bool   $edit Triggers edit mode to return an option selected.
	 * @return string $result The modified markup sent to the browser.
	 */
	public function get_comment_group_select( $result, $comment_id, $reply_to_id, $edit = false ) {

		// If the comment is a reply to another.
		if ( 0 !== $reply_to_id ) {

			/*
			 * This will only kick in if Javascript is off or the moveForm script is
			 * Not used in the theme. Our plugin Javascript handles this when the
			 * Form is moved around the DOM.
			 */

			// Get the group of the reply_to_id.
			$group_id = $this->get_comment_group_id( $reply_to_id );

			// Did we get one?
			if ( ! empty( $group_id ) && is_numeric( $group_id ) && 0 < $group_id ) {

				// Show a hidden input so that this comment is also posted in that group.
				$result .= '<input type="hidden" id="bpgsites-post-in" name="bpgsites-post-in" value="' . $group_id . '" />' . "\n";

			}

			// --<
			return $result;

		}

		// Get current blog ID.
		$blog_id = get_current_blog_id();

		// Kick out if not group site.
		if ( ! bpgsites_is_groupsite( $blog_id ) ) {
			return $result;
		}

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// Super admins can see all groups.
		if ( is_super_admin() ) {
			$user_group_ids['my_groups']     = bpgsites_get_groups_by_blog_id( $blog_id );
			$user_group_ids['linked_groups'] = [];
		}

		// Kick out if the ones the user can post into are empty.
		if (
			count( $user_group_ids['my_groups'] ) === 0 &&
			count( $user_group_ids['linked_groups'] ) === 0
		) {
			// --<
			return $result;
		}

		// Init array.
		$groups = [];

		// If any has entries.
		if (
			count( $user_group_ids['my_groups'] ) > 0 ||
			count( $user_group_ids['linked_groups'] ) > 0
		) {

			// Merge the arrays.
			$groups = array_unique(
				array_merge(
					$user_group_ids['my_groups'],
					$user_group_ids['linked_groups']
				)
			);

		}

		// Define config array.
		$config_array = [
			// 'user_id'         => bp_loggedin_user_id(),
			'type'            => 'alphabetical',
			'max'             => 100,
			'per_page'        => 100,
			'populate_extras' => 0,
			'include'         => $groups,
		];

		// Get groups.
		if ( bp_has_groups( $config_array ) ) {

			global $groups_template;

			// If more than one.
			if ( $groups_template->group_count > 1 ) {

				// Get the group of the comment ID in edit mode.
				if ( $edit ) {
					$comment_group_id = $this->get_comment_group_id( $comment_id );
				}

				// Init lists.
				$mine   = [];
				$linked = [];

				// Do the loop.
				while ( bp_groups() ) {
					bp_the_group();

					// Get group ID.
					$group_id = bp_get_group_id();

					// Init selected.
					$selected = '';

					// Is this edit?
					if ( $edit ) {

						// Insert selected if this is the relevant group.
						if ( (int) $comment_group_id === (int) $group_id ) {
							$selected = ' selected="selected"';
						}

					}

					// Add option if one of my groups.
					if ( in_array( (int) $group_id, $user_group_ids['my_groups'], true ) ) {
						$mine[] = '<option value="' . $group_id . '"' . $selected . '>' . bp_get_group_name() . '</option>';
					}

					// Add option if one of my linked groups.
					if ( in_array( (int) $group_id, $user_group_ids['linked_groups'], true ) ) {
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
				while ( bp_groups() ) {
					bp_the_group();

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

		// Try and get the Group ID.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$group_id = isset( $_POST['bpgsites-post-in'] ) ? sanitize_text_field( wp_unslash( $_POST['bpgsites-post-in'] ) ) : false;

		// Cast Group ID as integer.
		if ( is_numeric( $group_id ) ) {
			$group_id = (int) $group_id;
		} else {
			$group_id = false;
		}

		// --<
		return $group_id;

	}

	/**
	 * Filter the comment data returned via AJAX when editing a comment.
	 *
	 * @since 0.2.8
	 *
	 * @param array $data The existing array of comment data.
	 * @return array $data The modified array of comment data.
	 */
	public function filter_ajax_get_comment( $data ) {

		// Sanity check.
		if ( ! isset( $data['id'] ) ) {
			return $data;
		}

		// Get the group ID of the comment.
		$group_id = $this->get_comment_group_id( $data['id'] );

		// Add to array.
		$data['bpgsites_group_id'] = $group_id;

		// --<
		return $data;

	}

	/**
	 * Filter the comment data returned via AJAX when a comment has been edited.
	 *
	 * @since 0.2.8
	 *
	 * @param array $data The existing array of comment data.
	 * @return array $data The modified array of comment data.
	 */
	public function filter_ajax_edited_comment( $data ) {

		// Sanity check.
		if ( ! isset( $data['id'] ) ) {
			return $data;
		}

		// Add tag data.
		$data = $this->filter_ajax_get_comment( $data );

		// Get comment.
		$comment = get_comment( $data['id'] );

		// Get markup.
		$markup = $this->show_comment_group( '', $comment, [] );

		// Add markup to array.
		$data['bpgsites_group_markup'] = $markup;

		// --<
		return $data;

	}

	/**
	 * When our filtering form is is submitted, parse groups by selection.
	 *
	 * Unused method.
	 *
	 * @since 0.1
	 *
	 * @param array $group_ids The group IDs.
	 * @return array $group_ids The filtered group IDs.
	 */
	private function filter_groups_by_checkboxes( $group_ids ) {

		// phpcs:disable

		// Is this a comment in a group?
		if ( isset( $_POST['bpgsites_comment_groups'] ) && is_array( $_POST['bpgsites_comment_groups'] ) ) {

			// Overwrite with post array.
			$group_ids = $_POST['bpgsites_comment_groups'];

			// Sanitise all the items.
			$group_ids = array_map( 'intval', $group_ids );

			// Set cookie with delimited array.
			// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
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

		// phpcs:enable

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
		if ( ! empty( $group_id ) && is_numeric( $group_id ) && 0 < $group_id ) {

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
		if ( isset( $this->user_group_ids ) ) {
			return $this->user_group_ids;
		}

		// Init return.
		$this->user_group_ids = [
			'my_groups'     => [],
			'linked_groups' => [],
			'public_groups' => [],
			'auth_groups'   => bpgsites_showcase_groups_get(),
		];

		// Get current blog.
		$current_blog_id = get_current_blog_id();

		// Get this blog's group IDs.
		$group_ids = bpgsites_get_groups_by_blog_id( $current_blog_id );

		// Get user ID.
		$user_id = bp_loggedin_user_id();

		// Loop through the groups.
		foreach ( $group_ids as $group_id ) {

			// If this user is a member, add it.
			if ( groups_is_user_member( $user_id, $group_id ) ) {

				// Add to our array if it's not already there.
				if ( ! in_array( (int) $group_id, $this->user_group_ids['my_groups'], true ) ) {
					$this->user_group_ids['my_groups'][] = (int) $group_id;
				}

			} else {

				// Get the group.
				$group = groups_get_group( [ 'group_id' => $group_id ] );

				// Get status of group.
				$status = bp_get_group_status( $group );

				// If public.
				if ( 'public' === $status ) {

					/*
					// Add to our array only if we allow public comments.
					if ( bp_groupsites()->admin->option_get( 'bpgsites_public' ) ) {
						$this->user_group_ids['public_groups'][] = $group_id;
					}
					*/

					// Add to our array.
					$this->user_group_ids['public_groups'][] = (int) $group_id;

				} else {

					/*
					 * If the user is not a member, is it one of the groups that is
					 * Reading the site with this group?
					 */

					// Get linked groups.
					$linked_groups = bpgsites_group_linkages_get_groups_by_blog_id( $group_id, $current_blog_id );

					// Loop through them.
					foreach ( $linked_groups as $linked_group_id ) {

						// If the user is a member.
						if ( groups_is_user_member( $user_id, $linked_group_id ) ) {

							// Add the current one if it's not already there.
							if ( ! in_array( (int) $group_id, $this->user_group_ids['my_groups'], true ) ) {

								// Add to our array.
								$this->user_group_ids['linked_groups'][] = (int) $group_id;

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
		if ( isset( $this->user_in_group ) ) {
			return $this->user_in_group;
		}

		// Init return.
		$this->user_in_group = false;

		// Get the groups this user can see.
		$user_group_ids = $this->get_groups_for_user();

		// Does the user have any groups reading this site?
		$groups = groups_get_user_groups( bp_loggedin_user_id() );

		// Loop through them.
		foreach ( $groups['groups'] as $group ) {

			// If the user is a member.
			if (
				in_array( (int) $group, $user_group_ids['my_groups'], true ) ||
				in_array( (int) $group, $user_group_ids['linked_groups'], true )
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
		$args = [
			'scope'  => 'groups',
			'action' => 'new_groupsite_comment,new_groupsite_post',
		];

		// Get Activities.
		if ( bp_has_activities( $args ) ) {

			// Change header depending on logged in status.
			if ( is_user_logged_in() ) {

				// Set default.
				$section_header_text = sprintf(
					/* translators: %s: The plural name of the User's Group Sites. */
					__( 'All Recent Activity in your %s', 'bp-group-sites' ),
					bpgsites_get_extension_plural()
				);

				/**
				 * Filters the "All Recent Activity" section header text.
				 *
				 * @since 0.1
				 *
				 * @param string $section_header_text The default "All Recent Activity" section header text.
				 */
				$section_header_text = apply_filters( 'bpgsites_activity_tab_recent_title_all_yours', $section_header_text );

			} else {

				// Set default.
				$section_header_text = sprintf(
					/* translators: %s: The plural name of the public Group Sites. */
					__( 'Recent Activity in Public %s', 'bp-group-sites' ),
					bpgsites_get_extension_plural()
				);

				/**
				 * Filters the "Recent Activity" section header text.
				 *
				 * @since 0.1
				 *
				 * @param string $section_header_text The default "Recent Activity" section header text.
				 */
				$section_header_text = apply_filters( 'bpgsites_activity_tab_recent_title_all_public', $section_header_text );

			}

			// Open section.
			echo '<h3 class="activity_heading">' . esc_html( $section_header_text ) . '</h3>

			<div class="paragraph_wrapper groupsites_comments_output">

			<ol class="comment_activity">';

			// Do the loop.
			while ( bp_activities() ) {
				bp_the_activity();
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
			$args = [
				'scope'  => 'friends',
				'action' => 'new_groupsite_comment,new_groupsite_post',
			];

			if ( bp_has_activities( $args ) ) {

				// Set default.
				$section_header_text = sprintf(
					/* translators: %s: The plural name of Group Sites. */
					__( 'Friends Activity in your %s', 'bp-group-sites' ),
					bpgsites_get_extension_plural()
				);

				/**
				 * Filters the "Friends Activity" section header text.
				 *
				 * @since 0.1
				 *
				 * @param string $section_header_text The default "Friends Activity" section header text.
				 */
				$section_header_text = apply_filters( 'bpgsites_activity_tab_recent_title_all_yours', $section_header_text );

				// Open section.
				echo '<h3 class="activity_heading">' . esc_html( $section_header_text ) . '</h3>

				<div class="paragraph_wrapper groupsites_comments_output">

				<ol class="comment_activity">';

				// Do the loop.
				while ( bp_activities() ) {
					bp_the_activity();
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

		?>

		<?php do_action( 'bp_before_activity_entry' ); ?>

		<li class="<?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>">

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
			/* translators: %s: The singular name of a Group Site. */
			__( 'Recent Comments in this %s', 'bp-group-sites' ),
			bpgsites_get_extension_name()
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

		/*
		// -------------------------------------------------------------------------

		// Only on new blog posts.
		if ( ( $activity->type != 'new_blog_post' ) ) {
			return $activity;
		}

		// Clarify data.
		$blog_id = $activity->item_id;
		$post_id = $activity->secondary_item_id;
		$post = get_post( $post_id );

		// Get the group IDs for this blog.
		$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

		// Sanity check.
		if ( ! is_array( $group_ids ) || count( $group_ids ) == 0 ) {
			return $activity;
		}

		// -------------------------------------------------------------------------
		// WHAT NOW???
		// -------------------------------------------------------------------------

		// Get group.
		$group = groups_get_group( [ 'group_id' => $group_id ] );

		// Set activity type.
		$type = 'new_groupsite_post';

		// See if we already have the modified activity for this blog post.
		$id = bp_activity_get_activity_id( [
			'user_id' => $activity->user_id,
			'type' => $type,
			'item_id' => $group_id,
			'secondary_item_id' => $activity->secondary_item_id,
		] );

		// If we don't find a modified item.
		if ( ! $id ) {

			// See if we have an unmodified activity item.
			$id = bp_activity_get_activity_id( [
				'user_id' => $activity->user_id,
				'type' => $activity->type,
				'item_id' => $activity->item_id,
				'secondary_item_id' => $activity->secondary_item_id,
			] );

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
					foreach ( $authors as $author ) {

						// Default to comma.
						$sep = ', ';

						// If we're on the penultimate.
						if ( $n == ( $author_count - 1 ) ) {

							// Use ampersand.
							$sep = __( ' &amp; ', 'bp-group-sites' );

						}

						// If we're on the last, don't add.
						if ( $n == $author_count ) {
							$sep = '';
						}

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

		$activity->item_id = (int) $group_id;
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
		remove_action( 'bp_activity_before_save', [ $this, 'custom_post_activity' ] );

		// --<
		return $activity;
		*/

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
		$option = '<option value="new_groupsite_post">' . esc_html( $post_name ) . '</option>' . "\n";

		// Output escaped markup.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $option;

	}

}
