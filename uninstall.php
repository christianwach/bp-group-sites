<?php /*
================================================================================
BP Group Sites Uninstaller Version 1.0
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====


--------------------------------------------------------------------------------
*/



// kick out if uninstall not called from WordPress
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit(); }



// delete plugin options
delete_site_option( 'bpgbms_options' );
delete_site_option( 'bpgbms_installed' );



