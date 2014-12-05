/*
================================================================================
BP Group Sites Global Javascript
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

--------------------------------------------------------------------------------
*/



/**
 * @description: set up our elements
 *
 */
function bpgsites_setup() {

	// define vars
	var styles;

	// init styles
	styles = '';

	// wrap with js test
	if ( document.getElementById ) {

		// open style declaration
		styles += '<style type="text/css" media="screen">';

		// avoid flash of hidden elements
		styles += 'div.bpgsites_group_linkages { display: none; } ';

		// set pointer on headings
		styles += 'h5.bpgsites_group_linkage_heading { cursor: pointer; } ';

		// close style declaration
		styles += '</style>';

	}

	// write to page now
	document.write( styles );

}

// call setup function
bpgsites_setup();



/**
 * @description: define what happens when the page is ready
 *
 */
jQuery(document).ready( function($) {

	/**
	 * @description: activity column headings click
	 *
	 */
	$( 'h5.bpgsites_group_linkage_heading' ).click( function( event ) {

		// define vars
		var wrapper;

		// override event
		event.preventDefault();

		// get form wrapper
		wrapper = $(this).next( 'div.bpgsites_group_linkages' );

		// toggle next paragraph_wrapper
		wrapper.slideToggle( 'fast' );

		// --<
		return false;

	});

});


