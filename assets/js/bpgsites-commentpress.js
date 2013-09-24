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
 * @description: check page load
 *
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
 * @description: update dropdown and hide it
 *
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
	
		// split at our class name and retain last bit
		spliced = comment_classes.split( 'bpgsites-group-' )[1];
	
		// split again (because our class may not be the last) and retain first bit
		group_id = spliced.split( ' ' )[0];

		// set select option
		jQuery( '#bpgsites-post-in' ).val( group_id );
	
		// hide enclosing div because the reply is in the same group as the comment
		jQuery( '#bpgsites-post-in-box' ).hide();
	
	}
	
}



/** 
 * @description: define what happens when the page is ready
 *
 */
jQuery(document).ready( function($) {
	
	// if we mustn't show public comments...
	if ( bpgsites_show_public == '0' ) {
	
		/** 
		 * @description: hide comments for initially unchecked boxes - HACK!!!
		 *
		 */
		$( 'input.bpgsites_group_checkbox_public' ).each( function(i) {
		
			// define vars
			var group_id, checked;
		
			// get group ID
			group_id = $(this).val();
		
			// get checked/unchecked
			checked = $(this).prop( 'checked' );

			// if checked...
			if ( checked ) {
		
				// show group comments
				$( 'li.bpgsites-group-' + group_id ).addClass( 'bpgsites-shown' );
				$( 'li.bpgsites-group-' + group_id ).show();
		
			} else {
		
				// hide group comments
				$( 'li.bpgsites-group-' + group_id ).removeClass( 'bpgsites-shown' );
				$( 'li.bpgsites-group-' + group_id ).hide();
		
			}
		
			// recalculate headings and para icons
			$( 'a.comment_block_permalink' ).each( function( i ) {
			
				var wrapper, shown, text_sig;
			
				// get wrapper
				wrapper = $(this).parent().next( 'div.paragraph_wrapper' );
			
				// get list items that are not hidden
				shown = wrapper.find( 'li.bpgsites-shown' );
			
				// update heading
				$(this).children( 'span.cp_comment_num' ).text( shown.length );
			
				// get text signature
				text_sig = $(this).parent().prop( 'id' ).split( 'para_heading-' )[1];
			
				// set comment icon text
				$( '#textblock-' + text_sig + ' .commenticonbox small' ).text( shown.length );
		
			});
		
		});
		
	}
	
	
	
	/** 
	 * @description: activity column headings click
	 *
	 */
	$( 'h3.bpgsites_group_filter_heading' ).click( function( event ) {
	
		// define vars
		var form_wrapper;
		
		// override event
		event.preventDefault();
	
		// get form wrapper
		form_wrapper = $(this).next( 'div.bpgsites_group_filter' );
		
		// set width to prevent rendering error
		form_wrapper.css( 'width', $(this).parent().css( 'width' ) );
		
		// toggle next paragraph_wrapper
		form_wrapper.slideToggle( 'slow', function() {
		
			// when finished, reset width to auto
			form_wrapper.css( 'width', 'auto' );
		
		} );
		
		// --<
		return false;

	});
	
	

	/** 
	 * @description: activity column headings click
	 *
	 */
	$( 'a.comment-reply-link' ).click( function( event ) {
		
		// define vars
		var reply_to_classes, spliced, group_id, comment_id, comment_form;
	
		// override event
		event.preventDefault();
		
		// get comment ID
		comment_id = $(this).parent().parent().prop( 'id' ).split( 'comment-' )[1];
		
		// update our dropdown and hide
		bpgsites_update_select( comment_id );
		
		// --<
		return false;

	});
	
	
	
	/** 
	 * @description: activity column headings click
	 *
	 */
	$( 'a#cancel-comment-reply-link' ).click( function( event ) {
		
		// override event
		event.preventDefault();
		
		// show enclosing div
		$( '#bpgsites-post-in-box' ).show();
		
		// --<
		return false;

	});
	


	/** 
	 * @description: activity column headings click
	 *
	 */
	$( 'input.bpgsites_group_checkbox' ).click( function( event ) {
		
		// define vars
		var group_id, checked;
		
		// get group ID
		group_id = $(this).val();
		
		// get checked/unchecked
		checked = $(this).prop( 'checked' );

		// if checked...
		if ( checked ) {
		
			// show group comments
			$( 'li.bpgsites-group-' + group_id ).addClass( 'bpgsites-shown' );
			$( 'li.bpgsites-group-' + group_id ).show();
		
		} else {
		
			// hide group comments
			$( 'li.bpgsites-group-' + group_id ).removeClass( 'bpgsites-shown' );
			$( 'li.bpgsites-group-' + group_id ).hide();
		
		}
		
		// recalculate headings and para icons
		$( 'a.comment_block_permalink' ).each( function( i ) {
			
			var wrapper, shown, text_sig;
			
			// get wrapper
			wrapper = $(this).parent().next( 'div.paragraph_wrapper' );
			
			// get list items that are not hidden
			shown = wrapper.find( 'li.bpgsites-shown' );
			
			// update heading
			$(this).children( 'span.cp_comment_num' ).text( shown.length );
			
			// get text signature
			text_sig = $(this).parent().prop( 'id' ).split( 'para_heading-' )[1];
			
			// set comment icon text
			$( '#textblock-' + text_sig + ' .commenticonbox small' ).text( shown.length );
		
		});
		
	});
	
	
	
	// check page load
	bpgsites_page_load();
	
	
	
});


