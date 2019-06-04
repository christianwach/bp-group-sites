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
 * Create global namespace.
 *
 * @since 0.1
 */
var BuddypressGroupSites = BuddypressGroupSites || {};



/* -------------------------------------------------------------------------- */



/**
 * Create settings class.
 *
 * @since 0.1
 *
 * Unused at present, but kept as a useful template.
 */
BuddypressGroupSites.settings = new function() {

	// Store object refs.
	var me = this,
		$ = jQuery.noConflict();

	// Init group ID.
	this.group_id = false;

	// Override if we have our localisation object.
	if ( 'undefined' !== typeof BuddypressGroupSitesSettings ) {
		me.group_id = BuddypressGroupSitesSettings.data.group_id;
	}

	/**
	 * Setter for group ID.
	 */
	this.set_group_id = function( val ) {
		me.group_id = val;
	};

	/**
	 * Getter for group ID.
	 */
	this.get_group_id = function() {
		return me.group_id;
	};

};



/* -------------------------------------------------------------------------- */



/**
 * Create "Read With" class.
 *
 * @since 0.1
 */
BuddypressGroupSites.readwith = new function() {

	// Store object refs.
	var me = this,
		$ = jQuery.noConflict();

	/**
	 * Initialise "Read With".
	 *
	 * This method should only be called once.
	 *
	 * @since 0.1
	 */
	this.init = function() {

		// Write to head.
		me.head();

	};

	/**
	 * Write to <head> element.
	 *
	 * @since 0.1
	 */
	this.head = function() {

		// Define vars.
		var styles;

		// Init styles.
		styles = '';

		// Wrap with js test.
		if ( document.getElementById ) {

			// Open style declaration.
			styles += '<style type="text/css" media="screen">';

			// Avoid flash of hidden elements.
			styles += 'div.bpgsites_group_linkage_reveal { display: none; } ';

			// Set pointer on headings.
			styles += 'h5.bpgsites_group_linkage_heading { cursor: pointer; } ';

			// Close style declaration.
			styles += '</style>';

		}

		// Write to page now.
		document.write( styles );

	};

	/**
	 * Do setup when jQuery reports that the DOM is ready.
	 *
	 * This method should only be called once.
	 *
	 * @since 0.1
	 */
	this.dom_ready = function() {

		/**
		 * Activity column headings click.
		 *
		 * @since 0.1
		 *
		 * @param {Object} event The jQuery event.
		 * @return false
		 */
		$('h5.bpgsites_group_linkage_heading').click( function( event ) {

			// Define vars.
			var wrapper, target, button;

			// Override event.
			event.preventDefault();

			// Get form wrapper.
			wrapper = $(this).next( 'div.bpgsites_group_linkage_reveal' );

			// Find select2 target in wrapper.
			target = wrapper.find( '.bpgsites_group_linkages_invite_select' );

			// Init select2.
			me.select2.init( target );

			// Find submit button in wrapper.
			button = wrapper.find( '.bpgsites_invite_actions' );

			// Hide it.
			button.hide();

			// Toggle next paragraph_wrapper.
			wrapper.slideToggle( 'fast' );

		});

	};

};



/* -------------------------------------------------------------------------- */



/**
 * Create "Read With" Select2 class.
 *
 * @since 0.1
 */
BuddypressGroupSites.readwith.select2 = new function() {

	// Store object refs.
	var me = this,
		$ = jQuery.noConflict();

	/**
	 * Select2 init.
	 *
	 * @since 0.1
	 *
	 * @param {Object} target The targeted element.
	 */
	this.init = function( target ) {

		// Declare vars.
		var current_blog_id;

		// Get blog ID.
		current_blog_id = target.closest( '.bpgsites_group_linkages_invite' )
						  .prop( 'id' ).split( '-' )[1];

		/**
		 * Select2 init.
		 *
		 * @since 0.1
		 */
		target.select2({

			// Action.
			ajax: {
				method: 'POST',
				url: ajaxurl,
				dataType: 'json',
				delay: 250,
				data: function( params ) {
					return {
						s: params.term, // Search term.
						action: 'bpgsites_get_groups',
						page: params.page,
						group_id: BuddypressGroupSites.settings.get_group_id(),
						blog_id: current_blog_id
					};
				},
				processResults: function( data, page ) {
					// Parse the results into the format expected by Select2.
					// Since we are using custom formatting functions we do not need to
					// alter the remote JSON data.
					return {
						results: data
					};
				},
				cache: true
			},

			// Settings.
			escapeMarkup: function( markup ) { return markup; }, // Let our custom formatter work.
			minimumInputLength: 3,
			templateResult: me.format_result,
			templateSelection: me.format_response

		});

		// Bind event listeners.
		me.events_bind( target );

	};

	/**
	 * Select2 format results for display in dropdown.
	 *
	 * @since 0.1
	 *
	 * @param {Object} data The results data.
	 * @return {String} markup The results markup.
	 */
	this.format_result = function( data ) {

		// Bail if still loading.
		if ( data.loading ) return data.name;

		// Declare vars.
		var markup;

		// Construct basic group info.
		markup = '<div style="clear:both;">' +
		'<div class="select2_results_group_avatar" style="float:left;margin-right:8px;">' + data.avatar + '</div>' +
		'<div class="select2_results_group_name"><span style="font-weight:600;">' + data.name + '</span> <em>(' + data.type + ')</em></div>';

		// Add group description, if available.
		if (data.description) {
			markup += '<div class="select2_results_group_description" style="font-size:.9em;line-height:1.4;">' + data.description + '</div>';
		}

		// Close.
		markup += '</div>';

		// --<
		return markup;

	}

	/**
	 * Select2 format response.
	 *
	 * @since 0.1
	 *
	 * @param {Object} data The results data.
	 * @return {String} The expected response.
	 */
	this.format_response = function( data ) {
		return data.name || data.text;
	}

	/**
	 * Select2 events.
	 *
	 * @since 0.1
	 *
	 * @param {Object} target The targeted element.
	 */
	this.events_bind = function( target ) {

		/**
		 * Set up Select2 listener.
		 */
		target.on( 'select2:select', function( event ) {

			// Find corresponding submit button and show.
			$(this).closest( '.bpgsites_group_linkages_invite' )
				.find( '.bpgsites_invite_actions' )
				.show();

		});

	}

	/**
	 * Clear Select2 listeners.
	 *
	 * @since 0.1
	 */
	this.events_unbind = function() {
		$('.bpgsites_group_linkages_invite_select').unbind( 'select2:select' );
	}

};



/* -------------------------------------------------------------------------- */



// Do immediate actions
BuddypressGroupSites.readwith.init();



/* -------------------------------------------------------------------------- */



/**
 * Define what happens when the page is ready.
 *
 * @since 0.1
 *
 * @param {Object} $ The jQuery reference.
 */
jQuery(document).ready( function($) {

	// Document ready!
	BuddypressGroupSites.readwith.dom_ready();

}); // End document.ready.



