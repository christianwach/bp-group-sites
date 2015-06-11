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
 * Create global namespace
 */
var BuddypressGroupSites = BuddypressGroupSites || {};



/* -------------------------------------------------------------------------- */



/**
 * Create settings class
 *
 * Unused at present, but kept as a useful template.
 */
BuddypressGroupSites.settings = new function() {

	// store object refs
	var me = this,
		$ = jQuery.noConflict();

	// init group ID
	this.group_id = false;

	// override if we have our localisation object
	if ( 'undefined' !== typeof BuddypressGroupSitesSettings ) {
		this.group_id = BuddypressGroupSitesSettings.data.group_id;
	}

	/**
	 * Setter for group ID
	 */
	this.set_group_id = function( val ) {
		this.group_id = val;
	};

	/**
	 * Getter for group ID
	 */
	this.get_group_id = function() {
		return this.group_id;
	};

};



/* -------------------------------------------------------------------------- */



/**
 * Create "Read With" class
 */
BuddypressGroupSites.readwith = new function() {

	// store object refs
	var me = this,
		$ = jQuery.noConflict();

	/**
	 * Initialise "Read With".
	 *
	 * This method should only be called once.
	 *
	 * @return void
	 */
	this.init = function() {

		// write to head
		me.head();

	};

	/**
	 * Write to <head> element
	 *
	 * @return void
	 */
	this.head = function() {

		// define vars
		var styles;

		// init styles
		styles = '';

		// wrap with js test
		if ( document.getElementById ) {

			// open style declaration
			styles += '<style type="text/css" media="screen">';

			// avoid flash of hidden elements
			styles += 'div.bpgsites_group_linkage_reveal { display: none; } ';

			// set pointer on headings
			styles += 'h5.bpgsites_group_linkage_heading { cursor: pointer; } ';

			// close style declaration
			styles += '</style>';

		}

		// write to page now
		document.write( styles );

	};

	/**
	 * Do setup when jQuery reports that the DOM is ready.
	 *
	 * This method should only be called once.
	 *
	 * @return void
	 */
	this.dom_ready = function() {

		/**
		 * Activity column headings click
		 *
		 * @return false
		 */
		$('h5.bpgsites_group_linkage_heading').click( function( event ) {

			// define vars
			var wrapper, target, button;

			// override event
			event.preventDefault();

			// get form wrapper
			wrapper = $(this).next( 'div.bpgsites_group_linkage_reveal' );

			// find select2 target in wrapper
			target = wrapper.find( '.bpgsites_group_linkages_invite_select' );

			// init select2
			me.select2.init( target );

			// find submit button in wrapper
			button = wrapper.find( '.bpgsites_invite_actions' );

			// hide it
			button.hide();

			// toggle next paragraph_wrapper
			wrapper.slideToggle( 'fast' );

		});

	};

};



/* -------------------------------------------------------------------------- */



/**
 * Create "Read With" Select2 class
 */
BuddypressGroupSites.readwith.select2 = new function() {

	// store object refs
	var me = this,
		$ = jQuery.noConflict();

	/**
	 * Select2 init
	 */
	this.init = function( target ) {

		// declare vars
		var current_blog_id;

		// get blog ID
		current_blog_id = target.closest( '.bpgsites_group_linkages_invite' )
						  .prop( 'id' ).split( '-' )[1];

		/**
		 * Select2 init
		 */
		target.select2({

			// action
			ajax: {
				method: 'POST',
				url: ajaxurl,
				dataType: 'json',
				delay: 250,
				data: function( params ) {
					return {
						s: params.term, // search term
						action: 'bpgsites_get_groups',
						page: params.page,
						group_id: BuddypressGroupSites.settings.get_group_id(),
						blog_id: current_blog_id
					};
				},
				processResults: function( data, page ) {
					// parse the results into the format expected by Select2.
					// since we are using custom formatting functions we do not need to
					// alter the remote JSON data
					return {
						results: data
					};
				},
				cache: true
			},

			// settings
			escapeMarkup: function( markup ) { return markup; }, // let our custom formatter work
			minimumInputLength: 3,
			templateResult: me.format_result,
			templateSelection: me.format_response

		});

		// bind event listeners
		me.events_bind( target );

	};

	/**
	 * Select2 format results for display in dropdown
	 */
	this.format_result = function(data) {

		// bail if still loading
		if (data.loading) return data.name;

		// declare vars
		var markup;

		// construct basic group info
		markup = '<div style="clear:both;">' +
		'<div class="select2_results_group_avatar" style="float:left;margin-right:8px;">' + data.avatar + '</div>' +
		'<div class="select2_results_group_name"><span style="font-weight:600;">' + data.name + '</span> <em>(' + data.type + ')</em></div>';

		// add group description, if available
		if (data.description) {
			markup += '<div class="select2_results_group_description" style="font-size:.9em;line-height:1.4;">' + data.description + '</div>';
		}

		// close
		markup += '</div>';

		// --<
		return markup;

	}

	/**
	 * Select2 format response
	 */
	this.format_response = function( data ) {
		return data.name || data.text;
	}

	/**
	 * Select2 events
	 */
	this.events_bind = function( target ) {

		/**
		 * Set up Select2 listener
		 */
		target.on( 'select2:select', function( event ) {

			// find corresponding submit button and show
			$(this).closest( '.bpgsites_group_linkages_invite' )
				.find('.bpgsites_invite_actions')
				.show();

		});

	}

	/**
	 * Clear Select2 listeners
	 */
	this.events_unbind = function() {
		$('.bpgsites_group_linkages_invite_select').unbind( 'select2:select' );
	}

};



/* -------------------------------------------------------------------------- */



// do immediate actions
BuddypressGroupSites.readwith.init();



/* -------------------------------------------------------------------------- */



/**
 * Define what happens when the page is ready
 *
 * @return void
 */
jQuery(document).ready( function($) {

	// document ready!
	BuddypressGroupSites.readwith.dom_ready();

}); // end document.ready



