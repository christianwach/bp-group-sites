<?php
/**
 * BP Group Sites Uninstaller.
 *
 * @package BP_Group_Sites
 */

// Bail if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Delete plugin options.
delete_site_option( 'bpgsites_options' );
delete_site_option( 'bpgsites_installed' );
delete_site_option( 'bpgsites_auth_groups' );
