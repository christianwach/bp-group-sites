/*
================================================================================
BP Group Sites CommentPress Javascript
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

This provides compatibility with CommentPress

--------------------------------------------------------------------------------
*/



// init var
var bpgsites_show_public = '0';

// test for our localisation object
if ( 'undefined' !== typeof BpgsitesSettings ) {

	// get our var
	bpgsites_show_public = BpgsitesSettings.show_public;

}



/**
 * Set up our elements.
 *
 * @since 0.1
 */
function bpgsites_setup() {

	// define vars
	var styles = '';

	// wrap with js test
	if ( document.getElementById ) {

		// open style declaration
		styles += '<style type="text/css" media="screen">';

		// avoid flash of hidden elements
		styles += 'div.bpgsites_group_filter { display: none; } ';

		// don't show the filter button
		styles += '#bpgsites_comment_group_submit { display: none; } ';

		// close style declaration
		styles += '</style>';

	}

	// write to page now
	document.write( styles );

}

// call setup function
bpgsites_setup();



/**
 * Check page load.
 *
 * @since 0.1
 */
function bpgsites_page_load() {

	// define vars
	var url, comment_id;

	// if there is an anchor in the URL...
	url = document.location.toString();

	// do we have a comment permalink?
	if ( url.match( '#comment-' ) ) {

		// get comment ID
		comment_id = url.split( '#comment-' )[1];

		// update our dropdown and hide
		bpgsites_update_select( comment_id );

	}

}



/**
 * Update dropdown and hide it.
 *
 * @since 0.1
 */
function bpgsites_update_select( comment_id ) {

	// define vars
	var comment_id, comment_classes, spliced, group_id;

	// get comment in DOM
	comment = jQuery( '#comment-' + comment_id );

	// did we get one?
	if ( comment ) {

		// get reply-to classes as string
		comment_classes = jQuery( '#li-comment-' + comment_id ).prop( 'class' );

		// does it contain the identifying string?
		if ( comment_classes.match( 'bpgsites-group-' ) ) {

			// split at our class name and retain last bit
			spliced = comment_classes.split( 'bpgsites-group-' )[1];

			// split again (because our class may not be the last) and retain first bit
			group_id = spliced.split( ' ' )[0];

			// set select option
			jQuery( '#bpgsites-post-in' ).val( group_id );

		} else {

			// clear select option
			jQuery( '#bpgsites-post-in' ).val( '' );

		}

		// hide enclosing div because the reply is in the same group as the comment
		jQuery( '#bpgsites-post-in-box' ).hide();

	}

}



/**
 * Initialise elements.
 *
 * @since 0.1
 */
function bpgsites_init_elements() {

	// if we mustn't show public comments...
	if ( bpgsites_show_public == '0' ) {

		/**
		 * Hide comments for initially unchecked boxes.
		 *
		 * This is apparently something of a hack (though I didn't note down why)
		 *
		 * @since 0.1
		 */
		jQuery( 'input.bpgsites_group_checkbox_public' ).each( function(i) {

			// define vars
			var group_id, checked;

			// get group ID
			group_id = jQuery(this).val();

			// get checked/unchecked
			checked = jQuery(this).prop( 'checked' );

			// if checked...
			if ( checked ) {

				// show group comments
				jQuery( 'li.bpgsites-group-' + group_id ).addClass( 'bpgsites-shown' );
				jQuery( 'li.bpgsites-group-' + group_id ).show();

			} else {

				// hide group comments
				jQuery( 'li.bpgsites-group-' + group_id ).removeClass( 'bpgsites-shown' );
				jQuery( 'li.bpgsites-group-' + group_id ).hide();

			}

			/**
			 * Recalculate headings and para icons.
			 *
			 * @since 0.1
			 */
			jQuery( 'a.comment_block_permalink' ).each( function( i ) {

				var wrapper, shown, text_sig;

				// get wrapper
				wrapper = jQuery(this).parent().next( 'div.paragraph_wrapper' );

				// get list items that are not hidden
				shown = wrapper.find( 'li.bpgsites-shown' );

				// update heading
				jQuery(this).children( 'span.cp_comment_num' ).text( shown.length );

				// get text signature
				text_sig = jQuery(this).parent().prop( 'id' ).split( 'para_heading-' )[1];

				// set comment icon text
				jQuery( '#textblock-' + text_sig + ' .commenticonbox small' ).text( shown.length );

			});

		});

	}



	/**
	 * Toggle the appearance of "Filter Comments by Group" panel.
	 *
	 * @since 0.1
	 */
	jQuery( 'h3.bpgsites_group_filter_heading' ).click( function( event ) {

		// define vars
		var form_wrapper;

		// override event
		event.preventDefault();

		// get form wrapper
		form_wrapper = jQuery(this).next( 'div.bpgsites_group_filter' );

		// set width to prevent rendering error
		form_wrapper.css( 'width', jQuery(this).parent().css( 'width' ) );

		// toggle next paragraph_wrapper
		form_wrapper.slideToggle( 'slow', function() {

			// when finished, reset width to auto
			form_wrapper.css( 'width', 'auto' );

		} );

	});



	/**
	 * Intercept clicks on "Reply to Comment" links and update group selector.
	 *
	 * @since 0.1
	 */
	jQuery( 'a.comment-reply-link' ).click( function( event ) {

		// define vars
		var comment_id;

		// override event
		event.preventDefault();

		// get comment ID
		comment_id = jQuery(this).parent().parent().prop( 'id' ).split( 'comment-' )[1];

		// update our dropdown and hide
		bpgsites_update_select( comment_id );

	});



	/**
	 * Intercept clicks on "Cancel" reply link in the comment form.
	 *
	 * @since 0.1
	 *
	 * @return false
	 */
	jQuery( 'a#cancel-comment-reply-link' ).click( function( event ) {

		var groups = [];

		// override event
		event.preventDefault();

		// if not empty, we can skip setting a value
		if ( jQuery( '#bpgsites-post-in' ).val() ) {

			// skip

		} else {

			// set to first item in select

			// grab all items
			jQuery('#bpgsites-post-in option').each( function() {
				groups.push( jQuery(this).val() );
			});


			// set selected if we have any
			if ( groups.length > 0 ) {
				jQuery( '#bpgsites-post-in' ).val( groups[0] );
			}

		}

		// show enclosing div
		jQuery( '#bpgsites-post-in-box' ).show();

	});



	/**
	 * Listen to clicks on group-filtering checkboxes.
	 *
	 * @since 0.1
	 */
	jQuery( 'input.bpgsites_group_checkbox' ).click( function( event ) {

		// define vars
		var group_id, checked;

		// get group ID
		group_id = jQuery(this).val();

		// get checked/unchecked
		checked = jQuery(this).prop( 'checked' );

		// do the action for this checkbox
		bpgsites_do_checkbox_action( checked, group_id );

		// save state
		bpgsites_save_state();

	});



	/**
	 * Listen to clicks on toggle checkbox for the "Filter Comments by Group" panel.
	 *
	 * @since 0.1
	 */
	jQuery( 'input.bpgsites_group_checkbox_toggle' ).click( function( event ) {

		// get checked/unchecked
		var checked = jQuery(this).prop( 'checked' );

		// check all when this element is checked - and vice versa
		jQuery( 'input.bpgsites_group_checkbox' ).each( function( i ) {

			var group_id;

			// set element check state
			jQuery(this).prop( 'checked', checked );

			// get group ID
			group_id = jQuery(this).val();

			// do the action for this checkbox
			bpgsites_do_checkbox_action( checked, group_id );

		});

		// save state
		bpgsites_save_state();

	});



}



/**
 * Perform the action for a filter checkbox.
 *
 * @since 0.2.1
 *
 * @param {String} checked The current checked value of the input
 * @param {Integer} group_id The numerical group ID
 */
function bpgsites_do_checkbox_action( checked, group_id ) {

	// if checked...
	if ( checked ) {

		// show group comments
		jQuery( 'li.bpgsites-group-' + group_id ).addClass( 'bpgsites-shown' );
		jQuery( 'li.bpgsites-group-' + group_id ).show();

	} else {

		// hide group comments
		jQuery( 'li.bpgsites-group-' + group_id ).removeClass( 'bpgsites-shown' );
		jQuery( 'li.bpgsites-group-' + group_id ).hide();

	}

	// recalculate headings and para icons
	jQuery( 'a.comment_block_permalink' ).each( function( i ) {

		var wrapper, shown, text_sig;

		// get wrapper
		wrapper = jQuery(this).parent().next( 'div.paragraph_wrapper' );

		// get list items that are not hidden
		shown = wrapper.find( 'li.bpgsites-shown' );

		// update heading
		jQuery(this).children( 'span.cp_comment_num' ).text( shown.length );

		// get text signature
		text_sig = jQuery(this).parent().prop( 'id' ).split( 'para_heading-' )[1];

		// set comment icon text
		jQuery( '#textblock-' + text_sig + ' .commenticonbox small' ).text( shown.length );

	});

}



/**
 * Save the state of the filter checkboxes in a cookie.
 *
 * @since 0.2.2
 */
function bpgsites_save_state() {

	// declare vars
	var state = [], states = '';

	// get the state of all checkboxes
	jQuery( 'input.bpgsites_group_checkbox' ).each( function( i ) {

		var group_id, checked;

		// get checked/unchecked state
		checked = jQuery(this).prop( 'checked' );

		// get group ID
		group_id = jQuery(this).val();

		// add to the array if checked
		if ( checked ) {
			state.push( group_id );
		}

	});

	// convert to string
	states = state.join( ',' );

	// set cookie
	jQuery.cookie(
		'bpgsites_checkboxes',
		states,
		{ expires: 28, path: cp_cookie_path }
	);

}



/**
 * Recall the state of the filter checkboxes from a cookie.
 *
 * @since 0.2.2
 */
function bpgsites_recall_state() {

	// get cookie
	var states = jQuery.cookie( 'bpgsites_checkboxes' ),
		state = [],
		group_id,
		checked;

	// bail if we don't have one
	if ( 'undefined' === typeof states || states === null ) {
		return;
	}

	// create array
	state = states.split( ',' );

	// get the state of all checkboxes
	jQuery( 'input.bpgsites_group_checkbox' ).each( function( i ) {

		// get group ID
		group_id = jQuery(this).val();

		// check or uncheck depending on presence in array
		if ( jQuery.inArray( group_id, state ) !== -1 ) {
			checked = true;
		} else {
			checked = false;
		}

		// do action for this checkbox
		bpgsites_do_checkbox_action( checked, group_id )

		// set element check state
		jQuery(this).prop( 'checked', checked );

	});

}



/**
 * Define what happens when the page is ready.
 *
 * @since 0.1
 */
jQuery(document).ready( function($) {

	// recall state if set
	bpgsites_recall_state();

	// init elements
	bpgsites_init_elements();

	// check page load
	bpgsites_page_load();

});


