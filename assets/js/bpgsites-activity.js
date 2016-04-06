/*
================================================================================
BP Group Sites Activity Stream Javascript
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

This provides compatibility with BuddyPress Activity Streams

--------------------------------------------------------------------------------
*/



/**
 * Define what happens when the page is ready
 *
 * @since 0.1
 */
jQuery(document).ready( function($) {

	/**
	 * Remove activity class on 'new_groupsite_comment' items
	 *
	 * This is done because BuddyPress attaches a listener to this class which
	 * shows the activity comment form when clicked. We want links to actually
	 * go to their target destination.
	 */
	$('li.new_groupsite_comment a.button.bp-primary-action').removeClass( 'acomment-reply' );

});


