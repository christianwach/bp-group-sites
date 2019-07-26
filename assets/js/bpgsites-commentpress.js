/*
================================================================================
BP Group Sites CommentPress Javascript
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

This provides compatibility with CommentPress.

--------------------------------------------------------------------------------
*/



// Init vars.
var bpgsites_show_public = '0', bpgsites_settings;

// Test for our localisation object.
if ( 'undefined' !== typeof BpgsitesSettings ) {

	// Get our var.
	bpgsites_show_public = BpgsitesSettings.show_public;

}



/**
 * Set up our state-retaining object.
 *
 * @since 0.2.4
 */
function BP_Group_Sites_Settings() {

   // Selected group ID.
   this.group_id = '';

   /**
    * Group ID getter.
    *
    * @return {Integer} group_id The stored group ID.
    */
   this.get_group_id = function() {
       return this.group_id;
   }

   /**
    * Group ID setter.
    *
    * @param {Integer} group_id The group ID to store.
    */
   this.set_group_id = function( group_id ) {
       this.group_id = group_id;
   }

}

// Let's have an instance.
bpgsites_settings = new BP_Group_Sites_Settings();



/**
 * Set up our elements.
 *
 * @since 0.1
 */
function bpgsites_setup() {

	// Define vars.
	var styles = '';

	// Wrap with js test.
	if ( document.getElementById ) {

		// Open style declaration.
		styles += '<style type="text/css" media="screen">';

		// Avoid flash of hidden elements.
		styles += 'div.bpgsites_group_filter { display: none; } ';

		// Don't show the filter button.
		styles += '#bpgsites_comment_group_submit { display: none; } ';

		// Close style declaration.
		styles += '</style>';

	}

	// Write to page now.
	document.write( styles );

}

// Call setup function.
bpgsites_setup();



/**
 * Check page load.
 *
 * @since 0.1
 */
function bpgsites_page_load() {

	// Define vars.
	var url, comment_id;

	// If there is an anchor in the URL.
	url = document.location.toString();

	// Do we have a comment permalink?
	if ( url.match( '#comment-' ) ) {

		// Get comment ID.
		comment_id = url.split( '#comment-' )[1];

		// Update our dropdown and hide.
		bpgsites_update_select( comment_id );

	}

}



/**
 * Update dropdown and hide it.
 *
 * @since 0.1
 *
 * @param {Integer} comment_id The comment ID.
 */
function bpgsites_update_select( comment_id ) {

	// Define vars.
	var comment_id, comment_classes, spliced, group_id;

	// Get comment in DOM.
	comment = jQuery( '#comment-' + comment_id );

	// Did we get one?
	if ( comment ) {

		// Get reply-to classes as string.
		comment_classes = jQuery( '#li-comment-' + comment_id ).prop( 'class' );

		// Does it contain the identifying string?
		if ( comment_classes.match( 'bpgsites-group-' ) ) {

			// Split at our class name and retain last bit.
			spliced = comment_classes.split( 'bpgsites-group-' )[1];

			// Split again (because our class may not be the last) and retain first bit.
			group_id = spliced.split( ' ' )[0];

			// Set select option.
			jQuery( '#bpgsites-post-in' ).val( group_id );

		} else {

			// Store existing if not empty.
			if ( jQuery( '#bpgsites-post-in' ).val() ) {
				bpgsites_settings.set_group_id( jQuery( '#bpgsites-post-in' ).val() );
			}

			// Clear option.
			jQuery( '#bpgsites-post-in' ).val( '' );

		}

		// Hide enclosing div because the reply is in the same group as the comment.
		jQuery( '#bpgsites-post-in-box' ).hide();

	}

}



/**
 * Initialise elements.
 *
 * @since 0.1
 */
function bpgsites_init_elements() {

	// If we mustn't show public comments.
	if ( bpgsites_show_public == '0' ) {

		/**
		 * Hide comments for initially unchecked boxes.
		 *
		 * This is apparently something of a hack - though I didn't note down why.
		 *
		 * @since 0.1
		 *
		 * @param {Integer} i The number of the element iterated on.
		 */
		jQuery( 'input.bpgsites_group_checkbox_public' ).each( function( i ) {

			// Define vars.
			var group_id, checked;

			// Get group ID.
			group_id = jQuery(this).val();

			// Get checked/unchecked.
			checked = jQuery(this).prop( 'checked' );

			// If checked.
			if ( checked ) {

				// Show group comments.
				jQuery( 'li.bpgsites-group-' + group_id ).addClass( 'bpgsites-shown' );
				jQuery( 'li.bpgsites-group-' + group_id ).show();

			} else {

				// Hide group comments.
				jQuery( 'li.bpgsites-group-' + group_id ).removeClass( 'bpgsites-shown' );
				jQuery( 'li.bpgsites-group-' + group_id ).hide();

			}

			/**
			 * Recalculate headings and para icons.
			 *
			 * @since 0.1
			 *
			 * @param {Integer} i The number of the element iterated on.
			 */
			jQuery( 'a.comment_block_permalink' ).each( function( i ) {

				var wrapper, shown, text_sig;

				// Get wrapper.
				wrapper = jQuery(this).parent().next( 'div.paragraph_wrapper' );

				// Get list items that are not hidden.
				shown = wrapper.find( 'li.bpgsites-shown' );

				// Update heading.
				jQuery(this).children( 'span.cp_comment_num' ).text( shown.length );

				// Get text signature.
				text_sig = jQuery(this).parent().prop( 'id' ).split( 'para_heading-' )[1];

				// Set comment icon text.
				jQuery( '#textblock-' + text_sig + ' .commenticonbox small' ).text( shown.length );

			});

		});

	}



	/**
	 * Toggle the appearance of "Filter Comments by Group" panel.
	 *
	 * @since 0.1
	 *
	 * @param {Object} event The jQuery event.
	 */
	jQuery( 'h3.bpgsites_group_filter_heading' ).click( function( event ) {

		// Define vars.
		var form_wrapper;

		// Override event.
		event.preventDefault();

		// Get form wrapper.
		form_wrapper = jQuery(this).next( 'div.bpgsites_group_filter' );

		// Set width to prevent rendering error.
		form_wrapper.css( 'width', jQuery(this).parent().css( 'width' ) );

		// Toggle next paragraph_wrapper.
		form_wrapper.slideToggle( 'slow', function() {

			// When finished, reset width to auto.
			form_wrapper.css( 'width', 'auto' );

		} );

	});



	/**
	 * Intercept clicks on "Reply to Comment" links and update group selector.
	 *
	 * @since 0.1
	 *
	 * @param {Object} event The jQuery event.
	 */
	jQuery( 'a.comment-reply-link' ).click( function( event ) {

		// Define vars.
		var comment_id;

		// Override event.
		event.preventDefault();

		// Get comment ID.
		comment_id = jQuery(this).parent().parent().prop( 'id' ).split( 'comment-' )[1];

		// Update our dropdown and hide.
		bpgsites_update_select( comment_id );

	});



	/**
	 * Intercept clicks on "Cancel" reply link in the comment form.
	 *
	 * @since 0.1
	 *
	 * @param {Object} event The jQuery event.
	 * @return false
	 */
	jQuery( 'a#cancel-comment-reply-link' ).click( function( event ) {

		var groups = [], previous;

		// Override event.
		event.preventDefault();

		// If not empty, we can skip setting a value.
		if ( jQuery( '#bpgsites-post-in' ).val() ) {

			// Skip.

		} else {

			// Store existing if not empty.
			if ( jQuery( '#bpgsites-post-in' ).val() ) {
				bpgsites_update_select.set_group_id( jQuery( '#bpgsites-post-in' ).val() );
			}

			// Get prior value.
			previous = bpgsites_settings.get_group_id();

			// Reset to prior value if we have one.
			if ( previous ) {
				jQuery( '#bpgsites-post-in' ).val( previous );
			}

		}

		// Show enclosing div.
		jQuery( '#bpgsites-post-in-box' ).show();

	});



	/**
	 * Listen to clicks on group-filtering checkboxes.
	 *
	 * @since 0.1
	 *
	 * @param {Object} event The jQuery event.
	 */
	jQuery( 'input.bpgsites_group_checkbox' ).click( function( event ) {

		// Define vars.
		var group_id, checked;

		// Get group ID.
		group_id = jQuery(this).val();

		// Get checked/unchecked.
		checked = jQuery(this).prop( 'checked' );

		// Do the action for this checkbox.
		bpgsites_do_checkbox_action( checked, group_id );

		// Save state.
		bpgsites_save_state();

	});



	/**
	 * Listen to clicks on toggle checkbox for the "Filter Comments by Group" panel.
	 *
	 * @since 0.1
	 *
	 * @param {Object} event The jQuery event.
	 */
	jQuery( 'input.bpgsites_group_checkbox_toggle' ).click( function( event ) {

		// Get checked/unchecked.
		var checked = jQuery(this).prop( 'checked' );

		// Check all when this element is checked - and vice versa.
		jQuery( 'input.bpgsites_group_checkbox' ).each( function( i ) {

			var group_id;

			// Set element check state.
			jQuery(this).prop( 'checked', checked );

			// Get group ID.
			group_id = jQuery(this).val();

			// Do the action for this checkbox.
			bpgsites_do_checkbox_action( checked, group_id );

		});

		// Save state.
		bpgsites_save_state();

	});



	/**
	 * Hook into CommentPress comment edit trigger.
	 *
	 * @since 0.2.8
	 *
	 * @param {Array} data The array of comment data.
	 */
	jQuery( document ).on( 'commentpress-ajax-comment-callback', function( event, data ) {

		// Sanity check.
		if ( ! data.id ) {
			return;
		}

		// Set select option.
		if ( data.bpgsites_group_id ) {
			jQuery( '#bpgsites-post-in' ).val( data.bpgsites_group_id );
		}

		// Hide dropdown if comment is a reply or show if not.
		if ( parseInt( data.parent ) == 0 ) {
			jQuery( '#bpgsites-post-in-box' ).show();
		} else {
			jQuery( '#bpgsites-post-in-box' ).hide();
		}

	});



}



/**
 * Perform the action for a filter checkbox.
 *
 * @since 0.2.1
 *
 * @param {String} checked The current checked value of the input.
 * @param {Integer} group_id The numerical group ID.
 */
function bpgsites_do_checkbox_action( checked, group_id ) {

	// If checked.
	if ( checked ) {

		// Show group comments.
		jQuery( 'li.bpgsites-group-' + group_id ).addClass( 'bpgsites-shown' );
		jQuery( 'li.bpgsites-group-' + group_id ).show();

	} else {

		// Hide group comments.
		jQuery( 'li.bpgsites-group-' + group_id ).removeClass( 'bpgsites-shown' );
		jQuery( 'li.bpgsites-group-' + group_id ).hide();

	}

	// Recalculate headings and para icons.
	jQuery( 'a.comment_block_permalink' ).each( function( i ) {

		var wrapper, shown, text_sig;

		// Get wrapper.
		wrapper = jQuery(this).parent().next( 'div.paragraph_wrapper' );

		// Get list items that are not hidden.
		shown = wrapper.find( 'li.bpgsites-shown' );

		// Update heading.
		jQuery(this).children( 'span.cp_comment_num' ).text( shown.length );

		// Get text signature.
		text_sig = jQuery(this).parent().prop( 'id' ).split( 'para_heading-' )[1];

		// Set comment icon text.
		jQuery( '#textblock-' + text_sig + ' .commenticonbox small' ).text( shown.length );

	});

}



/**
 * Save the state of the filter checkboxes in a cookie.
 *
 * @since 0.2.2
 */
function bpgsites_save_state() {

	// Declare vars.
	var state = [], states = '';

	// Get the state of all checkboxes.
	jQuery( 'input.bpgsites_group_checkbox' ).each( function( i ) {

		var group_id, checked;

		// Get checked/unchecked state.
		checked = jQuery(this).prop( 'checked' );

		// Get group ID.
		group_id = jQuery(this).val();

		// Add to the array if checked.
		if ( checked ) {
			state.push( group_id );
		}

	});

	// Convert to string.
	states = state.join( ',' );

	// Set cookie.
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

	// Get cookie.
	var states = jQuery.cookie( 'bpgsites_checkboxes' ),
		state = [],
		group_id,
		checked;

	// Bail if we don't have one.
	if ( 'undefined' === typeof states || states === null ) {
		return;
	}

	// Create array.
	state = states.split( ',' );

	// Get the state of all checkboxes.
	jQuery( 'input.bpgsites_group_checkbox' ).each( function( i ) {

		// Get group ID.
		group_id = jQuery(this).val();

		// Check or uncheck depending on presence in array.
		if ( jQuery.inArray( group_id, state ) !== -1 ) {
			checked = true;
		} else {
			checked = false;
		}

		// Do action for this checkbox.
		bpgsites_do_checkbox_action( checked, group_id )

		// Set element check state.
		jQuery(this).prop( 'checked', checked );

	});

}



/**
 * Define what happens when the page is ready.
 *
 * @since 0.1
 *
 * @param {Object} $ The jQuery reference.
 */
jQuery(document).ready( function($) {

	// Recall state if set.
	bpgsites_recall_state();

	// Init elements.
	bpgsites_init_elements();

	// Check page load.
	bpgsites_page_load();

});


