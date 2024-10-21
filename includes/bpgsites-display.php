<?php
/**
 * BP Group Sites Display Functions.
 *
 * Functions which build markup live here.
 *
 * @package BP_Group_Sites
 * @since 0.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Adds icon to menu in CBOX theme.
 *
 * @since 0.1
 */
function bpgsites_cbox_theme_compatibility() {

	// Is CBOX theme active?
	if ( function_exists( 'cbox_theme_register_widgets' ) ) {

		// Output style in head.
		?>

		<style type="text/css">
		/* <![CDATA[ */
		#nav-<?php echo esc_attr( bpgsites_get_extension_slug() ); ?>:before
		{
			content: "C";
		}
		/* ]]> */
		</style>

		<?php

	}

}

// Add action for the above.
add_action( 'wp_head', 'bpgsites_cbox_theme_compatibility' );
