<?php /*
================================================================================
BP Group Sites Uninstaller
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====
--------------------------------------------------------------------------------
*/

// Kick out if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit(); }

// Delete plugin options.
delete_site_option( 'bpgsites_options' );
delete_site_option( 'bpgsites_installed' );
delete_site_option( 'bpgsites_auth_groups' );

