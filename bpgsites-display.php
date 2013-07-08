<?php /*
================================================================================
BP Group Sites Display Functions
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

Throw any functions which build markup in here.

--------------------------------------------------------------------------------
*/



/** 
 * @description: adds icon to menu in CBOX theme
 */
function bpgsites_cbox_theme_compatibility() {
	
	// is CBOX theme active?
	if ( function_exists( 'cbox_theme_register_widgets' ) ) {

		// output style in head
		?>
		
		<style type="text/css">
		/* <![CDATA[ */
		#nav-<?php echo apply_filters( 'bpgsites_extension_slug', 'group-sites' ) ?>:before 
		{
			content: "C";
		}
		/* ]]> */
		</style>

		<?php
		
	}

}

// add action for the above
add_action( 'wp_head', 'bpgsites_cbox_theme_compatibility' );



