<?php /*
================================================================================
BP Group Sites Blogs Template
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

We extend the BuddyPress BP Blogs template class so that we can filter by group
association, whilst retaining useful stuff like pagination.

--------------------------------------------------------------------------------
*/



/*
================================================================================
Class Name
================================================================================
*/

class BPGSites_Blogs_Template extends BP_Blogs_Template {



	/*
	============================================================================
	Properties
	============================================================================
	*/
	
	// need to store this for recalculation
	public $page_arg = 'bpage';
	
	
	
	/** 
	 * @description: initialises this object
	 * @return nothing
	 */
	function __construct( $type, $page, $per_page, $max, $user_id, $search_terms, $page_arg, $group_id ) {
	
		// init parent
		parent::__construct( $type, $page, $per_page, $max, $user_id, $search_terms, $page_arg );
		
		// calculate true total
		$this->_calculate_true_total( $group_id );
		
		// store property for recalculation
		$this->page_arg = $page_arg;
		
		// always exclude groupblogs and the BP root blog
		$this->filter_blogs();

		//print_r( array( $this->blogs, $this->total_blog_count ) ); die();
		
		// add our data to each blog
		$this->blogs = $this->modify_blog_data( $this->blogs );
		
		// filter by group, if requested
		$this->filter_by_group( $group_id );

		/*
		
		At some point, BP_Blogs_Template is bound to go the way of BP_Groups_Template
		and arguments will be passed as an associative array. The following code will
		go some way to dealing with that situation when it arises.
		
		// get passed arguments
		$args = func_get_args();
		
		// did we get any?
		if( is_array( $args ) AND count( $args ) > 1 ) {
			
			// yes, init parent
			parent::__construct( $args );
			
			// modify with our additions
			$this->filter_by_group( $args );

		} else {
			
			// no, init with empty array
			$this->params = array();

		}
		
		*/

	}
	
	
	
	/**
	 * @description exclude groupblogs and the BP root blog
	 */
	public function filter_blogs() {
		
		// if we got some...
		if ( is_array( $this->blogs ) AND count( $this->blogs ) > 0 ) {
		
			// exclude groupblogs and the BP root blog
			$this->blogs = $this->_exclude_groupblogs_and_root( $this->blogs );
		
			// recalculate parameters
			$this->_recalculate();
	
		}
		
	}
	
	
	
	/**
	 * @description wait until after constructor has run to modify parameters
	 * @param array $blogs an array of blogs
	 * @return array modified blogs
	 */
	public function modify_blog_data( $blogs ) {
		
		// if we got some...
		if ( is_array( $blogs ) AND count( $blogs ) > 0 ) {
		
			// loop
			foreach( $blogs AS $blog ) {
			
				// get array of groups and add to blog object
				$blog->blog_groups = bpgsites_get_groups_by_blog_id( $blog->blog_id );
				
			}
		
		}
		
		// --<
		return $blogs;
		
	}
	
	
	
	/**
	 * @description wait until after constructor has run to filter blogs
	 * @param int $group_id the numeric ID of the group
	 */
	public function filter_by_group( $group_id = '' ) {
	
		// sanity check
		if ( $group_id != '' AND is_numeric( $group_id ) ) {
			
			// rebuild array
			$this->blogs = $this->_filter_blogs_by_group_id( $this->blogs, $group_id );
			
			// recalculate parameters
			$this->_recalculate();
	
		}

	}



	/** 
	 * @description: filter blogs by their group associations
	 * @param array $blogs an array of blogs
	 * @return array filtered blogs
	 */
	protected function _exclude_groupblogs_and_root( $blogs ) {

		// init return
		$filtered_blogs = array();
		$filtered_blogs['blogs'] = array();

		// if we have some blogs...
		if ( is_array( $blogs ) AND count( $blogs ) > 0 ) {
		
			// let's look at them
			foreach( $blogs AS $blog ) {
			
				// is it the BP root blog?
				if ( $blog->blog_id != bp_get_root_blog_id() ) {
				
					// is it a groupblog?
					if ( ! bpgsites_is_groupblog( $blog->blog_id ) ) {
				
						// okay, none of those - add it
						$filtered_blogs['blogs'][] = $blog;
				
					}
					
				}
			
			}
	
		}
	
		// total blog count is calculated by _calculate_true_total()
		$filtered_blogs['total'] = $this->total_blog_count;
	
		/*
		// DIE!!!!!!
		print_r( array(
			'blogs' => $blogs, 
			'group_id' => $group_id,
			'filtered_blogs' => $filtered_blogs,
			'total_blog_count' => $this->total_blog_count
		) ); die();
		*/
	
		// --<
		return $filtered_blogs;
	
	}



	/** 
	 * @description: filter blogs by their group associations
	 * @param array $blogs an array of blogs
	 * @param int $group_id the numeric ID of the group
	 * @return array filtered blogs
	 */
	protected function _filter_blogs_by_group_id( $blogs, $group_id ) {

		// init return
		$filtered_blogs = array();
		$filtered_blogs['blogs'] = array();

		// if we have some blogs...
		if ( is_array( $blogs ) AND count( $blogs ) > 0 ) {
		
			// let's look at them
			foreach( $blogs AS $blog ) {
			
				// is it associated with this group?
				if ( in_array( $group_id, $blog->blog_groups ) ) {

					// add all for now
					$filtered_blogs['blogs'][] = $blog;
				
				}
			
			}
	
		}
	
		// total blog count is calculated by _calculate_true_total()
		$filtered_blogs['total'] = $this->total_blog_count;
	
		/*
		// DIE!!!!!!
		print_r( array(
			'blogs' => $blogs, 
			'group_id' => $group_id,
			'filtered_blogs' => $filtered_blogs 
		) ); die();
		*/
	
		// --<
		return $filtered_blogs;
	
	}



	/** 
	 * @description: recalculate properties
	 */
	protected function _recalculate() {

		// recalculate and reassign
		//$this->total_blog_count = (int) $this->blogs['total'];
		$this->blogs = $this->blogs['blogs'];
		$this->blog_count = count( $this->blogs );
		
		//print_r( $this ); die();
		
		// rebuild pagination with new blog counts
		if ( (int) $this->total_blog_count && (int) $this->pag_num ) {
	
			$this->pag_links = paginate_links( array(
		
				'base'      => add_query_arg( $this->page_arg, '%#%' ),
				'format'    => '',
				'total'     => ceil( (int) $this->total_blog_count / (int) $this->pag_num ),
				'current'   => (int) $this->pag_page,
				'prev_text' => _x( '&larr;', 'Blog pagination previous text', 'bpgsites' ),
				'next_text' => _x( '&rarr;', 'Blog pagination next text', 'bpgsites' ),
				'mid_size'  => 1
			
			) );
		
		}
	
	}



	/**
	 * @description calculate true total of filtered blogs
	 * @param int $group_id the numeric ID of the group
	 */
	protected function _calculate_true_total( $group_id = '' ) {
	
		// get all blogs first
		$all = bp_blogs_get_all_blogs();
		
		// filter out root blog and group blogs
		$filtered = $this->_exclude_groupblogs_and_root( $all['blogs'] );
		
		// add our data to each blog
		$filtered['blogs'] = $this->modify_blog_data( $filtered['blogs'] );
		
		// optionally filter by group ID
		if ( $group_id != '' AND is_numeric( $group_id ) ) {
			$filtered = $this->_filter_blogs_by_group_id( $filtered['blogs'], $group_id );
		}
		
		// store total
		$this->total_blog_count = count( $filtered['blogs'] );
		
	}



} // class ends



/** 
 * @description: group-aware modification of bp_has_blogs
 * @return boolean true when there are blogs, false when not
 */
function bpgsites_has_blogs( $args = '' ) {
	global $blogs_template;

	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$type         = 'active';
	$user_id      = 0;
	$search_terms = null;
	$group_id = false;

	// User filtering
	if ( bp_displayed_user_id() )
		$user_id = bp_displayed_user_id();

	$defaults = array(
		'type'         => $type,
		'page'         => 1,
		'per_page'     => 1,
		'max'          => false,

		'page_arg'     => 'bpage',        // See https://buddypress.trac.wordpress.org/ticket/3679

		'user_id'      => $user_id,       // Pass a user_id to limit to only blogs this user has higher than subscriber access to
		'search_terms' => $search_terms,  // Pass search terms to filter on the blog title or description.
		'group_id'     => $group_id       // Pass a group ID to show only blogs associated with that group
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	if ( is_null( $search_terms ) ) {
		if ( isset( $_REQUEST['s'] ) && !empty( $_REQUEST['s'] ) )
			$search_terms = $_REQUEST['s'];
		else
			$search_terms = false;
	}

	if ( $max ) {
		if ( $per_page > $max ) {
			$per_page = $max;
		}
	}

	$blogs_template = new BPGSites_Blogs_Template( $type, $page, $per_page, $max, $user_id, $search_terms, $page_arg, $group_id );
	return apply_filters( 'bpgsites_has_blogs', $blogs_template->has_blogs(), $blogs_template );
	
}



/*
================================================================================
Functions which may only be used in the loop
================================================================================
*/



/**
 * @description: for a blog in the loop, check if it is associated with the current group
 */
function bpgsites_is_blog_in_group() {

	// access object
	global $blogs_template;
	
	// init return
	$return = false;
	
	// sanity check
	if ( 
	
		is_array( $blogs_template->blog->blog_groups ) AND 
		count( $blogs_template->blog->blog_groups ) > 0 
	
	) {
		
		// is the current group in the array?
		if ( in_array( bp_get_current_group_id(), $blogs_template->blog->blog_groups ) ) {
			$return = true;
		}

	}
	
	// --<
	return apply_filters( 'bpgsites_is_blog_in_group', $return );
	
}



/** 
 * @description: get the text value of a submit button
 */
function bpgsites_admin_button_value() {

	// is this blog already associated?
	if ( bpgsites_is_blog_in_group() ) {
		echo __( 'Remove', 'bpgsites' );
	} else {
		echo __( 'Add', 'bpgsites' );
	}
	
}



/** 
 * @description: get the action of a submit button
 */
function bpgsites_admin_button_action() {

	// is this blog already associated?
	if ( bpgsites_is_blog_in_group() ) {
		echo 'remove';
	} else {
		echo 'add';
	}
	
}



/** 
 * @description: copied from bp_blogs_pagination_count() and amended
 */
function bpgsites_blogs_pagination_count() {
	global $blogs_template;

	$start_num = intval( ( $blogs_template->pag_page - 1 ) * $blogs_template->pag_num ) + 1;
	$from_num  = bp_core_number_format( $start_num );
	$to_num    = bp_core_number_format( ( $start_num + ( $blogs_template->pag_num - 1 ) > $blogs_template->total_blog_count ) ? $blogs_template->total_blog_count : $start_num + ( $blogs_template->pag_num - 1 ) );
	$total     = bp_core_number_format( $blogs_template->total_blog_count );
	
	// get singular name
	$singular = strtolower( apply_filters( 'bpgsites_extension_name', __( 'site', 'bpgsites' ) ) );
	
	// get plural name
	$plural = strtolower( apply_filters( 'bpgsites_extension_plural', __( 'sites', 'bpgsites' ) ) );
	
	// we need to override the singular name
	echo sprintf( 
		__( 'Viewing %1$s %2$s to %3$s (of %4$s %5$s)', 'buddypress' ), 
		$singular,
		$from_num, 
		$to_num, 
		$total,
		$plural
	);
	
}

