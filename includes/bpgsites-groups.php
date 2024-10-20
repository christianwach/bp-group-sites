<?php
/**
 * BP Group Sites Group Extension.
 *
 * Handles the screens our plugin requires.
 *
 * @package BP_Group_Sites
 * @since 0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prevent problems during upgrade or when Groups are disabled.
if ( ! class_exists( 'BP_Group_Extension' ) ) {
	return;
}

/**
 * BP Group Sites Group Extension class.
 *
 * This class extends BP_Group_Extension to create the screens our plugin requires.
 *
 * @see https://codex.buddypress.org/developer/group-extension-api/
 *
 * @since 0.1
 */
class BPGSites_Group_Extension extends BP_Group_Extension {

	/**
	 * Disable the creation step.
	 *
	 * @since 0.1
	 * @access public
	 * @var bool
	 */
	public $enable_create_step = false;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Init vars with filters applied.
		$name = apply_filters( 'bpgsites_extension_title', __( 'Group Sites', 'bp-group-sites' ) );
		$slug = apply_filters( 'bpgsites_extension_slug', 'group-sites' );
		$pos  = apply_filters( 'bpgsites_extension_pos', 31 );

		/*
		 * Test for BP 1.8+.
		 * Could also use 'bp_esc_sql_order', the other core addition.
		 */
		if ( function_exists( 'bp_core_get_upload_dir' ) ) {

			// Init array.
			$args = [
				'name'               => $name,
				'slug'               => $slug,
				'nav_item_position'  => $pos,
				'enable_create_step' => false,
			];

			// Init.
			parent::init( $args );

		} else {

			// Name our tab.
			$this->name = $name;
			$this->slug = $slug;

			// Set position in navigation.
			$this->nav_item_position = $pos;

		}

	}

	/**
	 * The content of the extension tab in the group admin.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id The ID of the Group.
	 */
	public function edit_screen( $group_id = null ) {

		// Kick out if not on our edit screen.
		if ( ! bp_is_group_admin_screen( $this->slug ) ) {
			return false;
		}

		// Show pending received.
		bpgsites_group_linkages_pending_get_markup();

		// Hand off to function.
		bpgsites_get_extension_edit_screen();

		// Add nonce.
		wp_nonce_field( 'groups_edit_save_' . $this->slug );

	}

	/**
	 * Runs after the user clicks a submit button on the edit screen.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id The ID of the Group.
	 */
	public function edit_screen_save( $group_id = null ) {

		// Validate form.
		check_admin_referer( 'groups_edit_save_' . $this->slug );

		// Kick out if current group ID is missing.
		if ( ! isset( $_POST['group-id'] ) ) {
			return false;
		}

		// Get current group ID.
		$primary_group_id = (int) sanitize_text_field( wp_unslash( $_POST['group-id'] ) );

		// Parse input name for our values.
		$parsed = $this->parse_input_name();

		// Get blog ID.
		$blog_id = $parsed['blog_id'];

		// If blog ID is invalid, it could be multi-value.
		if ( ! is_numeric( $blog_id ) ) {

			// First, re-parse.
			$parsed = $this->parse_input_name_multivalue();

			// Get blog ID.
			$blog_id = $parsed['blog_id'];

			// Kick out if blog ID is still invalid.
			if ( ! is_numeric( $blog_id ) ) {
				return false;
			}

			// Get ID of the secondary group.
			$secondary_group_id = $parsed['group_id'];

			// Kick out if secondary group ID is somehow invalid.
			if ( ! is_numeric( $secondary_group_id ) ) {
				return false;
			}

		}

		// Get name, but allow plugins to override.
		$name = apply_filters( 'bpgsites_extension_name', __( 'Group Site', 'bp-group-sites' ) );

		// Action to perform on the chosen blog.
		switch ( $parsed['action'] ) {

			// Top-level "Add" button.
			case 'add':

				// Link.
				bpgsites_link_blog_and_group( $blog_id, $primary_group_id );

				// Feedback.
				bp_core_add_message(
					sprintf(
						/* translators: %s: The singular name for Group Sites. */
						__( '%s successfully added to Group', 'bp-group-sites' ),
						$name
					)
				);

				break;

			// Top-level "Remove" button.
			case 'remove':

				// Unlink.
				bpgsites_unlink_blog_and_group( $blog_id, $primary_group_id );

				// Feedback.
				bp_core_add_message(
					sprintf(
						/* translators: %s: The singular name for Group Sites. */
						__( '%s successfully removed from Group', 'bp-group-sites' ),
						$name
					)
				);

				break;

			// Read with "Invite" button.
			case 'invite':

				// Get invited group ID from POST.
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$invited_group_id = isset( $_POST[ 'bpgsites_group_linkages_invite_select_' . $blog_id ] ) ? (int) wp_unslash( $_POST[ 'bpgsites_group_linkages_invite_select_' . $blog_id ] ) : 0;

				// If we get a valid one.
				if ( 0 !== $invited_group_id ) {

					// Flag groups as linked, but pending.
					bpgsites_group_linkages_pending_create( $blog_id, $primary_group_id, $invited_group_id );

					// Send private message to group admins.
					$this->send_invitation_message( $blog_id, $primary_group_id, $invited_group_id );

					// Feedback.
					bp_core_add_message( sprintf( __( 'Group successfully invited', 'bp-group-sites' ), $name ) );

				} else {

					// Feedback.
					bp_core_add_message( sprintf( __( 'Something went wrong - group invitation not sent.', 'bp-group-sites' ), $name ) );

				}

				break;

			// Invitation "Accept" button.
			case 'accept':

				// Create linkages.
				bpgsites_group_linkages_pending_accept( $blog_id, $primary_group_id, $secondary_group_id );

				// Feedback.
				bp_core_add_message( sprintf( __( 'The invitation has been accepted', 'bp-group-sites' ), $name ) );

				break;

			// Invitation "Reject" button.
			case 'reject':

				// Reject.
				bpgsites_group_linkages_pending_delete( $blog_id, $primary_group_id, $secondary_group_id );

				// Feedback.
				bp_core_add_message( sprintf( __( 'The invitation has been declined', 'bp-group-sites' ), $name ) );

				break;

			// Reading with "Stop" button.
			case 'unlink':

				// Unlink.
				bpgsites_group_linkages_delete( $blog_id, $primary_group_id, $secondary_group_id );

				// Get blog name.
				$blog_name = bp_blogs_get_blogmeta( $blog_id, 'name' );

				// Get group object.
				$group = groups_get_group( [ 'group_id' => $secondary_group_id ] );

				// Feedback.
				bp_core_add_message(
					sprintf(
						/* translators: 1: The name of the site, 2: The name of the Group. */
						__( 'Your group is no longer reading "%1$s" with %2$s', 'bp-group-sites' ),
						$blog_name,
						$group->name
					)
				);

				break;

		}

		// Access BP.
		global $bp;

		// Return to page.
		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . 'admin/' . $this->slug );

	}

	/**
	 * Display our content when the nav item is selected.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id The ID of the Group.
	 */
	public function display( $group_id = null ) {

		// Hand off to function.
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
	 * @param int $group_id The numeric ID of the group being edited.
	 */
	public function admin_screen( $group_id = null ) {

		// Hand off to function.
		echo bpgsites_get_extension_admin_screen();

	}

	/**
	 * The routine run after the group is saved on the Dashboard group admin screen.
	 *
	 * @since 0.1
	 *
	 * @param int $group_id The numeric ID of the group being edited.
	 */
	public function admin_screen_save( $group_id = null ) {
		// Grab your data out of the $_POST global and save as necessary.
	}

	/**
	 * Parse the name of an input to extract blog ID and action.
	 *
	 * @since 0.1
	 *
	 * @return array Contains $blog_id and $action.
	 */
	protected function parse_input_name() {

		// Init return.
		$return = [
			'blog_id' => false,
			'action'  => false,
		];

		// Get keys of POST array.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$keys = array_keys( $_POST );

		// Did we get any?
		if ( is_array( $keys ) && count( $keys ) > 0 ) {

			// Loop.
			foreach ( $keys as $key ) {

				// Look for our identifier.
				if ( strstr( $key, 'bpgsites_manage' ) ) {

					// Got it.
					$tmp = explode( '-', $key );

					// Extract blog id.
					$return['blog_id'] = ( isset( $tmp[1] ) && is_numeric( $tmp[1] ) ) ? (int) $tmp[1] : false;

					// Extract action.
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
	 * @return array Contains $blog_id and $action.
	 */
	protected function parse_input_name_multivalue() {

		// Init return.
		$return = [
			'blog_id'  => false,
			'group_id' => false,
			'action'   => false,
		];

		// Get keys of POST array.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$keys = array_keys( $_POST );

		// Did we get any?
		if ( is_array( $keys ) && count( $keys ) > 0 ) {

			// Loop.
			foreach ( $keys as $key ) {

				// Look for our identifier.
				if ( strstr( $key, 'bpgsites_manage' ) ) {

					// Got it.
					$tmp = explode( '-', $key );

					// Get numeric part.
					$numeric = isset( $tmp[1] ) ? $tmp[1] : false;

					// Split on the _.
					$parts = explode( '_', $numeric );

					// Extract blog id.
					$return['blog_id'] = ( isset( $parts[0] ) && is_numeric( $parts[0] ) ) ? (int) $parts[0] : false;

					// Extract group id.
					$return['group_id'] = ( isset( $parts[1] ) && is_numeric( $parts[1] ) ) ? (int) $parts[1] : false;

					// Extract action.
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
	 * @param int $blog_id The numeric ID of the blog to be "read together".
	 * @param int $inviting_group_id The numeric ID of the inviting group.
	 * @param int $invited_group_id The numeric ID of the invited group.
	 */
	public function send_invitation_message( $blog_id, $inviting_group_id, $invited_group_id ) {

		// Get sender ID.
		$sender_id = bp_loggedin_user_id();

		// Get admins of target group.
		$group_admins = groups_get_group_admins( $invited_group_id );

		// Get group admin IDs.
		$group_admin_ids = [];
		if ( ! empty( $group_admins ) ) {
			foreach ( $group_admins as $group_admin ) {
				$group_admin_ids[] = $group_admin->user_id;
			}
		}

		// Get inviting group object.
		$inviting_group = groups_get_group( [ 'group_id' => $inviting_group_id ] );

		// Get invited group object.
		$invited_group = groups_get_group( [ 'group_id' => $invited_group_id ] );

		// Get blog name.
		$blog_name = bp_blogs_get_blogmeta( $blog_id, 'name' );

		// Get invited group permalink.
		$group_permalink = trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $invited_group->slug );

		// Construct links to Group Sites admin page.
		$admin_link = trailingslashit( $group_permalink . 'admin/' . $this->slug );

		// Construct message body.
		/* translators: 1: The name of the invited group. */
		$body = __( 'You are receiving this message because you are an administrator of the group "%1$s"', 'bp-group-sites' ) . "\n\n";
		/* translators: 2: The singular name for Group Sites, 3: The name of the site, 4: The name of the inviting group, 5: The plural name for Group Sites. */
		$body .= __( 'Your group has been invited to read the %2$s "%3$s" with the group "%4$s". To accept or decline the invitation, click the link below to visit the %5$s admin page for your group.', 'bp-group-sites' ) . "\n\n";
		/* translators: 6: The link to the group admin page. */
		$body .= '%6$s' . "\n\n";

		// Substitutions.
		$content = sprintf(
			$body,
			$invited_group->name,
			apply_filters( 'bpgsites_extension_name', __( 'Group Site', 'bp-group-sites' ) ),
			$blog_name,
			$inviting_group->name,
			apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) ),
			$admin_link
		);

		// Construct subject.
		$subject = sprintf(
			/* translators: 1: The blog name, 2: The name of the inviting group. */
			__( 'An invitation to read "%1$s" with the group "%2$s"', 'bp-group-sites' ),
			$blog_name,
			$inviting_group->name
		);

		// Set up message.
		$msg_args = [
			'sender_id'  => $sender_id,
			'thread_id'  => false,
			'recipients' => $group_admin_ids, // Can be an array of usernames, user_ids or mixed.
			'subject'    => $subject,
			'content'    => $content,
		];

		// Send message.
		messages_new_message( $msg_args );

	}

}

// Register our class.
bp_register_group_extension( 'BPGSites_Group_Extension' );

/**
 * The content of the public extension page.
 *
 * @since 0.1
 */
function bpgsites_get_extension_display() {

	do_action( 'bp_before_blogs_loop' );

	// Use current group.
	$defaults = [ 'group_id' => bp_get_current_group_id() ];

	// Search for them.
	// TODO: add AJAX query string compatibility.
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

		<?php while ( bp_blogs() ) : ?>
			<?php bp_the_blog(); ?>

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
			<p><?php esc_html_e( 'Sorry, there were no sites found.', 'bp-group-sites' ); ?></p>
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

	// Configure to get all possible group sites.
	$args = [ 'possible_sites' => true ];

	// Get all group sites - TODO: add AJAX query string compatibility?
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

		<?php

		while ( bp_blogs() ) :
			bp_the_blog();

			// Is this blog in the group?
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

					// If blog already in group.
					if ( $in_group ) {

						// Show linkage management tools.
						bpgsites_group_linkages_get_markup();

					}

					?>
				</div>

				<div class="action">
					<input type="submit" class="bpgsites_manage_button" name="bpgsites_manage-<?php bp_blog_id(); ?>-<?php bpgsites_admin_button_action(); ?>" value="<?php bpgsites_admin_button_value(); ?>" />
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
			<p><?php esc_html_e( 'Sorry, there were no sites found.', 'bp-group-sites' ); ?></p>
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
 * @return int $group_id The current group ID.
 */
function bpgsites_get_current_group_id() {

	// Access BP global.
	global $bp;

	// Init return.
	$group_id = null;

	if ( isset( $bp->groups->new_group_id ) ) {
		// Use new group ID.
		$group_id = $bp->groups->new_group_id;
	} elseif ( isset( $bp->groups->current_group->id ) ) {
		// Use current group ID.
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
 * @param bool $echo Whether to echo or not.
 */
function bpgsites_group_linkages_pending_get_markup( $echo = true ) {

	// Get current group ID.
	$current_group_id = bp_get_current_group_id();

	// Bail if we have no pending invitations.
	if ( ! bpgsites_group_linkages_pending_received_exists( $current_group_id ) ) {
		return;
	}

	// Init HTML output.
	$html = '';

	// Open container div.
	$html .= '<div class="bpgsites_group_linkages_pending">' . "\n";

	// Construct heading.
	$html .= '<h5 class="bpgsites_group_linkages_pending_heading">' . __( 'Invitations to read with other groups', 'bp-group-sites' ) . '</h5>' . "\n";

	// Open reveal div.
	$html .= '<div class="bpgsites_group_linkages_pending_reveal">' . "\n";

	// Get pending invites.
	$pending = bpgsites_group_linkages_pending_received_get( $current_group_id );

	// Open list.
	$html .= '<ol class="bpgsites_group_linkages_pending_list">' . "\n";

	// Loop through blog IDs.
	foreach ( $pending as $blog_id => $inviting_group_ids ) {

		// Show invitations from each group.
		foreach ( $inviting_group_ids as $inviting_group_id ) {

			// Get blog name.
			$blog_name = bp_blogs_get_blogmeta( $blog_id, 'name' );

			// Get inviting group object.
			$inviting_group = groups_get_group( [ 'group_id' => $inviting_group_id ] );

			// Construct text.
			$text = sprintf(
				/* translators: 1: The name of the blog, 2: The name of the inviting group. */
				__( 'Read "%1$s" with "%2$s"', 'bp-group-sites' ),
				$blog_name,
				esc_html( $inviting_group->name )
			);

			// Add label.
			$html .= '<li><span class="bpgsites_invite_received">' . $text . '</span> ' .
				'<input type="submit" class="bpgsites_invite_received_button" name="bpgsites_manage-' . $blog_id . '_' . $inviting_group_id . '-accept" value="' . __( 'Accept', 'bp-group-sites' ) . '" /> ' .
				'<input type="submit" class="bpgsites_invite_received_button" name="bpgsites_manage-' . $blog_id . '_' . $inviting_group_id . '-reject" value="' . __( 'Decline', 'bp-group-sites' ) . '" /></li>' . "\n";

		}

	}

	// Close list.
	$html .= '</ol>' . "\n\n";

	// Close reveal div.
	$html .= '</div>' . "\n";

	// Close container div.
	$html .= '</div>' . "\n\n\n\n";

	// Output unless overridden.
	if ( $echo ) {
		echo $html;
	}

}

/**
 * For a given group ID, get all invitation data.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group.
 * @return array $pending_groups Array containing "sent" and "received" arrays of numeric IDs of pending groups.
 */
function bpgsites_group_linkages_pending_get( $group_id ) {

	// Get option if it exists.
	$pending_groups = groups_get_groupmeta( $group_id, BPGSITES_PENDING );

	// Sanity check master array.
	if ( ! is_array( $pending_groups ) || empty( $pending_groups ) ) {
		$pending_groups = [
			'sent'     => [],
			'received' => [],
		];
	}

	// Sanity check sub-arrays.
	if ( ! isset( $pending_groups['sent'] ) ) {
		$pending_groups['sent'] = [];
	}
	if ( ! isset( $pending_groups['received'] ) ) {
		$pending_groups['received'] = [];
	}

	// --<
	return $pending_groups;

}

/**
 * For a given group ID and blog ID, get the group IDs that have been invited.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group.
 * @param int $blog_id The numeric ID of the blog. Optional.
 * @return array $linked_groups Array of numeric IDs of linked groups.
 */
function bpgsites_group_linkages_pending_sent_get( $group_id, $blog_id = 0 ) {

	// Get option if it exists.
	$pending_groups = bpgsites_group_linkages_pending_get( $group_id );

	// Did we request a particular blog?
	if ( 0 !== $blog_id ) {

		// Overwrite with just the nested array for that blog.
		$pending_groups['sent'] = isset( $pending_groups['sent'][ $blog_id ] ) ? $pending_groups['sent'][ $blog_id ] : [];

	}

	// --<
	return $pending_groups['sent'];

}

/**
 * Create a sent invitation.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together".
 * @param int $inviting_group_id The numeric ID of the inviting group.
 * @param int $invited_group_id The numeric ID of the invited group.
 */
function bpgsites_group_linkages_pending_sent_create( $blog_id, $inviting_group_id, $invited_group_id ) {

	// Get all data for the inviting group.
	$pending_for_inviting_group = bpgsites_group_linkages_pending_get( $inviting_group_id );

	// Make sure we have the blog's array.
	if ( ! isset( $pending_for_inviting_group['sent'][ $blog_id ] ) ) {
		$pending_for_inviting_group['sent'][ $blog_id ] = [];
	}

	// If the invited group isn't present.
	if ( ! in_array( $invited_group_id, $pending_for_inviting_group['sent'][ $blog_id ] ) ) {

		// Add it.
		$pending_for_inviting_group['sent'][ $blog_id ][] = $invited_group_id;

		// Resave.
		groups_update_groupmeta( $inviting_group_id, BPGSITES_PENDING, $pending_for_inviting_group );

	}

}

/**
 * Delete a sent invitation.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together".
 * @param int $inviting_group_id The numeric ID of the inviting group.
 * @param int $invited_group_id The numeric ID of the invited group.
 */
function bpgsites_group_linkages_pending_sent_delete( $blog_id, $inviting_group_id, $invited_group_id ) {

	// Get all data for the inviting group.
	$pending_for_inviting_group = bpgsites_group_linkages_pending_get( $inviting_group_id );

	// Make sure we have the blog's array.
	if ( ! isset( $pending_for_inviting_group['sent'][ $blog_id ] ) ) {
		$pending_for_inviting_group['sent'][ $blog_id ] = [];
	}

	// If the invited group is present.
	if ( in_array( $invited_group_id, $pending_for_inviting_group['sent'][ $blog_id ] ) ) {

		// Remove group ID and re-index.
		$updated = array_merge( array_diff( $pending_for_inviting_group['sent'][ $blog_id ], [ $invited_group_id ] ) );

		// Resave.
		groups_update_groupmeta( $inviting_group_id, BPGSITES_PENDING, $updated );

	}

}

/**
 * Check if there are outstanding sent invitations for "reading with" other groups.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group.
 * @return bool $has_pending Whether or not there are pending linkages.
 */
function bpgsites_group_linkages_pending_sent_exists( $group_id ) {

	// Get all sent data.
	$pending_sent = bpgsites_group_linkages_pending_sent_get( $group_id );

	// If we have any.
	if ( count( $pending_sent ) > 0 ) {
		return true;
	}

	// Fallback.
	return false;

}

/**
 * For a given group ID and blog ID, get the group IDs that have submitted invitations.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group.
 * @param int $blog_id The numeric ID of the blog. Optional.
 * @return array $linked_groups Array of numeric IDs of linked groups.
 */
function bpgsites_group_linkages_pending_received_get( $group_id, $blog_id = 0 ) {

	// Get option if it exists.
	$pending_groups = bpgsites_group_linkages_pending_get( $group_id );

	// Did we request a particular blog?
	if ( 0 !== $blog_id ) {

		// Overwrite with just the nested array for that blog.
		$pending_groups['received'] = isset( $pending_groups['received'][ $blog_id ] ) ? $pending_groups['received'][ $blog_id ] : [];

	}

	// --<
	return $pending_groups['received'];

}

/**
 * Create a received invitation.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together".
 * @param int $inviting_group_id The numeric ID of the inviting group.
 * @param int $invited_group_id The numeric ID of the invited group.
 */
function bpgsites_group_linkages_pending_received_create( $blog_id, $inviting_group_id, $invited_group_id ) {

	// Get all data for the invited group.
	$pending_for_invited_group = bpgsites_group_linkages_pending_get( $invited_group_id );

	// Make sure we have the blog's array.
	if ( ! isset( $pending_for_invited_group['received'][ $blog_id ] ) ) {
		$pending_for_invited_group['received'][ $blog_id ] = [];
	}

	// If the inviting group isn't present.
	if ( ! in_array( $inviting_group_id, $pending_for_invited_group['received'][ $blog_id ] ) ) {

		// Add it.
		$pending_for_invited_group['received'][ $blog_id ][] = $inviting_group_id;

		// Resave.
		groups_update_groupmeta( $invited_group_id, BPGSITES_PENDING, $pending_for_invited_group );

	}

}

/**
 * Delete a received invitation.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together".
 * @param int $invited_group_id The numeric ID of the invited group.
 * @param int $inviting_group_id The numeric ID of the inviting group.
 */
function bpgsites_group_linkages_pending_received_delete( $blog_id, $invited_group_id, $inviting_group_id ) {

	// Get all data for the invited group.
	$pending_for_invited_group = bpgsites_group_linkages_pending_get( $invited_group_id );

	// Make sure we have the blog's array.
	if ( ! isset( $pending_for_invited_group['received'][ $blog_id ] ) ) {
		$pending_for_invited_group['received'][ $blog_id ] = [];
	}

	// If the inviting group is present.
	if ( in_array( $inviting_group_id, $pending_for_invited_group['received'][ $blog_id ] ) ) {

		// Remove group ID and re-index.
		$updated = array_merge( array_diff( $pending_for_invited_group['received'][ $blog_id ], [ $inviting_group_id ] ) );

		// Resave.
		groups_update_groupmeta( $invited_group_id, BPGSITES_PENDING, $updated );

	}

}

/**
 * Check if there are outstanding received invitations for "reading with" other groups.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group.
 * @return bool $has_pending Whether or not there are pending linkages.
 */
function bpgsites_group_linkages_pending_received_exists( $group_id ) {

	// Get all received data.
	$pending_received = bpgsites_group_linkages_pending_received_get( $group_id );

	// If we have any.
	if ( count( $pending_received ) > 0 ) {
		return true;
	}

	// Fallback.
	return false;

}

/**
 * Creates a pending linkage to another group which - when accepted - means
 * that the two groups are considered to be "reading together".
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together".
 * @param int $inviting_group_id The numeric ID of the inviting group.
 * @param int $invited_group_id The numeric ID of the invited group.
 */
function bpgsites_group_linkages_pending_create( $blog_id, $inviting_group_id, $invited_group_id ) {

	// Create "sent" invitation.
	bpgsites_group_linkages_pending_sent_create( $blog_id, $inviting_group_id, $invited_group_id );

	// Create "received" invitation.
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
 * @param int $blog_id The numeric ID of the blog to be "read together".
 * @param int $invited_group_id The numeric ID of the invited group.
 * @param int $inviting_group_id The numeric ID of the inviting group.
 */
function bpgsites_group_linkages_pending_accept( $blog_id, $invited_group_id, $inviting_group_id ) {

	// Delete invitations.
	bpgsites_group_linkages_pending_delete( $blog_id, $invited_group_id, $inviting_group_id );

	// Create new inter-group linkage.
	bpgsites_group_linkages_create( $blog_id, $inviting_group_id, $invited_group_id );

	// Link blog with accepting group.
	bpgsites_link_blog_and_group( $blog_id, $invited_group_id );

}

/**
 * Delete "sent" and "received" items from the inviter and invited data arrays.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together".
 * @param int $invited_group_id The numeric ID of the invited group.
 * @param int $inviting_group_id The numeric ID of the inviting group.
 */
function bpgsites_group_linkages_pending_delete( $blog_id, $invited_group_id, $inviting_group_id ) {

	// Delete "sent" invitation.
	bpgsites_group_linkages_pending_sent_delete( $blog_id, $inviting_group_id, $invited_group_id );

	// Delete "received" invitation.
	bpgsites_group_linkages_pending_received_delete( $blog_id, $invited_group_id, $inviting_group_id );

}

// -----------------------------------------------------------------------------

/**
 * Adds UI to groups loop for "reading with" other groups.
 *
 * @since 0.1
 *
 * @param bool $echo Whether to echo or not.
 * @return bool $has_linkage Whether there is a linkage or not.
 */
function bpgsites_group_linkages_get_markup( $echo = true ) {

	// Init return.
	$has_linkage = false;

	// Init HTML output.
	$html = '';

	// Open container div.
	$html .= '<div class="bpgsites_group_linkage">' . "\n";

	// Construct heading.
	$html .= '<h5 class="bpgsites_group_linkage_heading">' . __( 'Read with other groups', 'bp-group-sites' ) . '</h5>' . "\n";

	// Open reveal div.
	$html .= '<div class="bpgsites_group_linkage_reveal">' . "\n";

	// Get current blog ID.
	$blog_id = bp_get_blog_id();

	// Get this blog's group IDs.
	$group_ids = bpgsites_get_groups_by_blog_id( $blog_id );

	// Get current group ID.
	$current_group_id = bp_get_current_group_id();

	// Get linkages for this blog.
	$linked_groups = bpgsites_group_linkages_get( $current_group_id, $blog_id );

	// If empty, set to impossible value.
	if ( count( $linked_groups ) === 0 ) {
		$linked_groups = [ PHP_INT_MAX ];
	}

	// Define config array.
	$config_array = [
		//'user_id'         => $user_id,
		'type'            => 'alphabetical',
		'max'             => 1000,
		'per_page'        => 1000,
		'populate_extras' => 0,
		'include'         => $linked_groups,
		'page_arg'        => 'bpgsites',
	];

	// New groups query.
	$groups_query = new BP_Groups_Template( $config_array );

	// Get linked groups.
	if ( $groups_query->has_groups() ) {

		// Set flag.
		$has_linkage = true;

		// Open existing linkages div.
		$html .= '<div class="bpgsites_group_linkages">' . "\n";

		// Construct heading.
		$html .= '<h6 class="bpgsites_group_linkages_heading">' . __( 'Reading with', 'bp-group-sites' ) . '</h6>' . "\n";

		// Open list.
		$html .= '<ol class="bpgsites_group_linkages_list">' . "\n";

		// Do the loop.
		while ( $groups_query->groups() ) {
			$groups_query->the_group();

			// Get group ID.
			$group_id = $groups_query->group->id;

			// Add label.
			$html .= '<li><span class="bpgsites_group_unlink">' . $groups_query->group->name . '</span> <input type="submit" class="bpgsites_unlink_button" name="bpgsites_manage-' . $blog_id . '_' . $group_id . '-unlink" value="' . __( 'Stop', 'bp-group-sites' ) . '" /></li>' . "\n";

		}

		// Close list.
		$html .= '</ol>' . "\n\n";

		// Close existing linkages div.
		$html .= '</div>' . "\n";

	}

	// Open invite div.
	$html .= '<div id="bpgsites_group_linkages_invite-' . $blog_id . '" class="bpgsites_group_linkages_invite">' . "\n";

	// Construct heading.
	$html .= '<h6 class="bpgsites_group_linkage_heading">' . __( 'Invite to read', 'bp-group-sites' ) . '</h6>' . "\n";

	// Add select2.
	$html .= '<p><select class="bpgsites_group_linkages_invite_select" name="bpgsites_group_linkages_invite_select_' . $blog_id . '" style="width: 70%"><option value="0">' . __( 'Find a group to invite', 'bp-group-sites' ) . '</option></select></p>' . "\n";

	// Add "Send invitation" button.
	$html .= '<p class="bpgsites_invite_actions"><input type="submit" class="bpgsites_invite_button" name="bpgsites_manage-' . $blog_id . '-invite" value="' . __( 'Send invitation', 'bp-group-sites' ) . '" /></p>' . "\n";

	// Close invite div.
	$html .= '</div>' . "\n";

	// Close reveal div.
	$html .= '</div>' . "\n";

	// Close container div.
	$html .= '</div>' . "\n\n\n\n";

	// Clear it.
	unset( $groups_query );

	// Output unless overridden.
	if ( $echo ) {
		echo $html;
	}

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
 * @param int $group_id The numeric ID of the group.
 * @param int $blog_id The numeric ID of the blog. Optional.
 * @return array $linked_groups Array of numeric IDs of linked groups.
 */
function bpgsites_group_linkages_get( $group_id, $blog_id = 0 ) {

	// Get option if it exists.
	$linked_groups = groups_get_groupmeta( $group_id, BPGSITES_LINKED );

	// Sanity check.
	if ( ! is_array( $linked_groups ) ) {
		$linked_groups = [];
	}

	// Did we request a particular blog?
	if ( 0 !== $blog_id ) {

		// Overwrite with just the nested array for that blog.
		$linked_groups = isset( $linked_groups[ $blog_id ] ) ? $linked_groups[ $blog_id ] : [];

	}

	// --<
	return $linked_groups;

}

/**
 * Create linkages between two groups "reading together".
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog to be "read together".
 * @param int $inviting_group_id The numeric ID of the inviting group.
 * @param int $invited_group_id The numeric ID of the invited group.
 */
function bpgsites_group_linkages_create( $blog_id, $inviting_group_id, $invited_group_id ) {

	// Create sender linkages.
	bpgsites_group_linkage_create( $blog_id, $inviting_group_id, $invited_group_id );

	// Create recipient linkages.
	bpgsites_group_linkage_create( $blog_id, $invited_group_id, $inviting_group_id );

}

/**
 * Delete linkages between two groups "reading together".
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog being "read together".
 * @param int $primary_group_id The numeric ID of the primary group.
 * @param int $secondary_group_id The numeric ID of the secondary group.
 */
function bpgsites_group_linkages_delete( $blog_id, $primary_group_id, $secondary_group_id ) {

	// Delete primary group linkages.
	bpgsites_group_linkage_delete( $blog_id, $primary_group_id, $secondary_group_id );

	// Delete secondary group linkages.
	bpgsites_group_linkage_delete( $blog_id, $secondary_group_id, $primary_group_id );

}

/**
 * For a given group ID, get linked group IDs for a specific blog.
 *
 * @since 0.1
 *
 * @param int $group_id The numeric ID of the group.
 * @param int $blog_id The numeric ID of the blog.
 * @return array $linked_groups Array of numeric IDs of linked groups.
 */
function bpgsites_group_linkages_get_groups_by_blog_id( $group_id, $blog_id ) {

	// Get linked groups.
	$linked = bpgsites_group_linkages_get( $group_id );

	// Get those for this blog.
	$linked_groups = isset( $linked[ $blog_id ] ) ? $linked[ $blog_id ] : [];

	// --<
	return $linked_groups;

}

/**
 * For a given blog ID, check if two group IDs are linked.
 *
 * @since 0.1
 *
 * @param int $blog_id The numeric ID of the blog being "read together".
 * @param int $primary_group_id The numeric ID of the primary group.
 * @param int $secondary_group_id The numeric ID of the secondary group.
 * @return bool Whether or not the groups are linked.
 */
function bpgsites_group_linkages_link_exists( $blog_id, $primary_group_id, $secondary_group_id ) {

	// Get the existing linkages for the primary group.
	$linked = bpgsites_group_linkages_get( $primary_group_id );

	// Get the array for this blog ID.
	$linked_group_ids = isset( $linked[ $blog_id ] ) ? $linked[ $blog_id ] : [];

	// If the secondary group is in the list, there must be a linkage.
	if ( in_array( $secondary_group_id, $linked_group_ids ) ) {
		return true;
	}

	// Fallback.
	return false;

}

/**
 * AJAX handler for group linkage autocomplete using the Select2 JS library.
 *
 * @since 0.1
 */
function bpgsites_group_linkages_get_ajax() {

	global $groups_template;

	// Get current group - or set impossible value if not present.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$current_group_id = isset( $_POST['group_id'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['group_id'] ) ) : PHP_INT_MAX;

	// Get current blog.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$current_blog_id = isset( $_POST['blog_id'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['blog_id'] ) ) : PHP_INT_MAX;

	// Get already-linked groups for this blog.
	$linked = bpgsites_group_linkages_get( $current_group_id, $current_blog_id );

	// Construct exclude.
	$exclude = array_unique( array_merge( $linked, [ $current_group_id ] ) );

	// Get groups this user can see for this search.
	$groups = groups_get_groups( [
		'user_id'         => is_super_admin() ? 0 : bp_loggedin_user_id(),
		'search_terms'    => isset( $_POST['s'] ) ? sanitize_text_field( wp_unslash( $_POST['s'] ) ) : '',
		'show_hidden'     => true,
		'populate_extras' => false,
		'exclude'         => $exclude,
	] );

	// Init return.
	$json = [];

	// Fake a group template.
	$groups_template        = new stdClass();
	$groups_template->group = new stdClass();

	// Loop through our groups.
	foreach ( $groups['groups'] as $group ) {

		// Apply group object to template so API functions.
		$groups_template->group = $group;

		// Get description.
		$description = bp_create_excerpt(
			wp_strip_all_tags( stripslashes( $group->description ) ), // Content.
			70, // Max length.
			[
				'ending'            => '&hellip;',
				'filter_shortcodes' => false,
			]
		);

		// Add item to output array.
		$json[] = [
			'id'                 => $group->id,
			'name'               => stripslashes( $group->name ),
			'type'               => bp_get_group_type(),
			'description'        => $description,
			'avatar'             => bp_get_group_avatar_mini(),
			'total_member_count' => $group->total_member_count,
			'private'            => ( 'public' !== $group->status ),
		];

	}

	// Send data.
	echo wp_json_encode( $json );
	exit();

}

// Ajax handler for group linkage autocomplete.
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
 * @param int $blog_id The numeric ID of the blog to be "read together".
 * @param int $primary_group_id The numeric ID of the primary group.
 * @param int $secondary_group_id The numeric ID of the secondary group.
 */
function bpgsites_group_linkage_create( $blog_id, $primary_group_id, $secondary_group_id ) {

	// Get the existing linkages for the inviting group.
	$linked = bpgsites_group_linkages_get( $primary_group_id );

	// Get the array for this blog ID.
	$linked_group_ids = isset( $linked[ $blog_id ] ) ? $linked[ $blog_id ] : [];

	// Is the invited group in the list?
	if ( ! in_array( $secondary_group_id, $linked_group_ids ) ) {

		// No, add it.
		$linked_group_ids[] = $secondary_group_id;

		// Overwrite in parent array.
		$linked[ $blog_id ] = $linked_group_ids;

		// Save updated option.
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
 * @param int $blog_id The numeric ID of the blog being "read together".
 * @param int $primary_group_id The numeric ID of the primary group.
 * @param int $secondary_group_id The numeric ID of the secondary group.
 */
function bpgsites_group_linkage_delete( $blog_id, $primary_group_id, $secondary_group_id ) {

	// Get their linkages.
	$linked = bpgsites_group_linkages_get( $primary_group_id );

	// Get the array for this blog ID.
	$linked_group_ids = isset( $linked[ $blog_id ] ) ? $linked[ $blog_id ] : [];

	// Is this one in the list?
	if ( in_array( $secondary_group_id, $linked_group_ids ) ) {

		// Yes - remove group and re-index.
		$updated = array_merge( array_diff( $linked_group_ids, [ $secondary_group_id ] ) );

		// Overwrite in parent array.
		$linked[ $blog_id ] = $updated;

		// Save updated option.
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

	// Get name.
	$name = apply_filters( 'bpgsites_extension_title', __( 'Group Sites', 'bp-group-sites' ) );

	// Init checked.
	$checked = '';

	// Get existing option.
	$showcase_groups = bpgsites_showcase_groups_get();

	// Get current group ID.
	$group_id = bpgsites_get_current_group_id();

	// Sanity check list and group ID.
	if ( count( $showcase_groups ) > 0 && ! is_null( $group_id ) ) {

		// Override checked if this group's ID is in the list.
		if ( in_array( $group_id, $showcase_groups ) ) {
			$checked = ' checked="checked"';
		}

	}

	?>
	<h4><?php echo esc_html( $name ); ?></h4>

	<p><?php esc_html_e( 'To make this group a showcase group, make sure that it is set to "Private" above, then check the box below. The effect will be that the comments left by members of this group will always appear to readers. Only other members of this group will be able to reply to those comments.', 'bp-group-sites' ); ?></p>

	<div class="checkbox">
		<label><input type="checkbox" id="bpgsites-showcase-group" name="bpgsites-showcase-group" value="1"<?php echo $checked; ?> /> <?php esc_html_e( 'Make this group a showcase group', 'bp-group-sites' ); ?></label>
	</div>

	<hr />

	<?php

}

// Add actions for the above.
add_action( 'bp_after_group_settings_admin', 'bpgsites_showcase_group_settings_form' );
add_action( 'bp_after_group_settings_creation_step', 'bpgsites_showcase_group_settings_form' );

/**
 * Intercept group settings save process.
 *
 * @since 0.1
 *
 * @param object $group The group object.
 */
function bpgsites_showcase_group_save( $group ) {

	/*
	 * If the checkbox IS NOT checked, remove from option if it is there.
	 * If the checkbox IS checked, add it to the option if not already there.
	 */

	// Get existing option.
	$showcase_groups = bpgsites_showcase_groups_get();

	// If not checked.
	if ( ! isset( $_POST['bpgsites-showcase-group'] ) ) {

		// Sanity check list.
		if ( count( $showcase_groups ) > 0 ) {

			// Is this group's ID in the list?
			if ( in_array( $group->id, $showcase_groups ) ) {

				// Yes, remove group ID and re-index.
				$updated = array_merge( array_diff( $showcase_groups, [ $group->id ] ) );

				// Save option.
				bpgsites_site_option_set( 'bpgsites_auth_groups', $updated );

			}

		}

	} else {

		// Kick out if value is not 1.
		if ( absint( $_POST['bpgsites-showcase-group'] ) !== 1 ) {
			return;
		}

		// Is this group's ID missing from the list?
		if ( ! in_array( $group->id, $showcase_groups ) ) {

			// Add it.
			$showcase_groups[] = $group->id;

			// Save option.
			bpgsites_site_option_set( 'bpgsites_auth_groups', $showcase_groups );

		}

	}

}

// Add action for the above.
add_action( 'groups_group_after_save', 'bpgsites_showcase_group_save' );

/**
 * Get all showcase groups.
 *
 * @since 0.1
 *
 * @return array $showcase_groups The showcase group IDs.
 */
function bpgsites_showcase_groups_get() {

	// Get existing option.
	$showcase_groups = bpgsites_site_option_get( 'bpgsites_auth_groups', [] );

	// --<
	return $showcase_groups;

}

/**
 * Test if a group is a showcase group.
 *
 * @since 0.1
 *
 * @param int $group_id The group ID.
 * @return bool $is_showcase_group The group is or is not a showcase group.
 */
function bpgsites_is_showcase_group( $group_id ) {

	// Get existing option.
	$showcase_groups = bpgsites_showcase_groups_get();

	// Sanity check list.
	if ( count( $showcase_groups ) > 0 ) {

		// Is this group's ID in the list?
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
 * @return bool $passed User is a member of a showcase group for this blog.
 */
function bpgsites_is_showcase_group_member() {

	// Super admins can post anywhere, so allow.
	if ( is_super_admin() ) {
		return true;
	}

	// False by default.
	$passed = false;

	// Get existing option.
	$showcase_groups = bpgsites_showcase_groups_get();

	// Sanity check list.
	if ( count( $showcase_groups ) > 0 ) {

		// Get current blog.
		$current_blog_id = get_current_blog_id();

		// Get user ID.
		$user_id = bp_loggedin_user_id();

		// Loop.
		foreach ( $showcase_groups as $group_id ) {

			// Is this user a member?
			if ( groups_is_user_member( $user_id, $group_id ) ) {

				// If this showcase group is a showcase group for this blog.
				if ( bpgsites_check_group_by_blog_id( $current_blog_id, $group_id ) ) {

					// No need to delve further.
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
 * @param bool $allowed If media buttons are enabled.
 * @return bool $allowed If media buttons are enabled.
 */
function bpgsites_showcase_group_media_buttons( $allowed ) {

	// Get current blog.
	$current_blog_id = get_current_blog_id();

	// If this isn't a group site, pass through.
	if ( ! bpgsites_is_groupsite( $current_blog_id ) ) {
		return $allowed;
	}

	// Disallow by default.
	$allowed = false;

	// Is this user a member of a showcase group on this blog?
	if ( bpgsites_is_showcase_group_member() ) {

		// Allow.
		return true;

	}

	// --<
	return $allowed;

}

// Add filter for the above.
add_filter( 'commentpress_rte_media_buttons', 'bpgsites_showcase_group_media_buttons', 10, 1 );

/**
 * Filter quicktags by showcase groups context.
 *
 * @since 0.1
 *
 * @param array $quicktags The quicktags.
 * @return array/bool $quicktags False if quicktags are disabled, array of buttons otherwise.
 */
function bpgsites_showcase_group_quicktags( $quicktags ) {

	// Get current blog.
	$current_blog_id = get_current_blog_id();

	// If this isn't a group site, pass through.
	if ( ! bpgsites_is_groupsite( $current_blog_id ) ) {
		return $quicktags;
	}

	// Disallow quicktags by default.
	$quicktags = false;

	// Is this user a member of a showcase group on this blog?
	if ( bpgsites_is_showcase_group_member() ) {

		// Allow quicktags.
		$quicktags = [ 'buttons' => 'strong,em,ul,ol,li,link,close' ];

		// --<
		return $quicktags;

	}

	// --<
	return $quicktags;

}

// Add filter for the above.
add_filter( 'commentpress_rte_quicktags', 'bpgsites_showcase_group_quicktags', 10, 1 );
