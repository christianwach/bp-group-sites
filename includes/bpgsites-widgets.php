<?php /*
================================================================================
BP Group Sites Widgets
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES
=====

Widgets defined here.

--------------------------------------------------------------------------------
*/



/**
 * Creates a custom Widget for displaying a list of Group Sites.
 *
 * @since 0.2.1
 */
class BP_Group_Sites_List_Widget extends WP_Widget {



	/**
	 * Constructor registers widget with WordPress.
	 *
	 * @since 0.2.1
	 */
	function __construct() {

		// init parent
		parent::__construct(

			// base ID
			'bpgsites_list_widget',

			// name
			sprintf(
				__( 'List of %s', 'bp-group-sites' ),
				apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) )
			),

			// args
			array(
				'description' => sprintf(
					__( 'Use this widget to show a list of %s.', 'bp-group-sites' ),
					apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) )
				),
			)

		);

	}



	/**
	 * Outputs the HTML for this widget.
	 *
	 * @since 0.2.1
	 * @param array $args An array of standard parameters for widgets in this theme
	 * @param array $instance An array of settings for this widget instance
	 * @return void Echoes its output
	 */
	public function widget( $args, $instance ) {

		// get group sites
		if ( bpgsites_has_blogs( $args ) ) { ?>

			<ul id="blogs-list" class="item-list">

			<?php while ( bp_blogs() ) : bp_the_blog(); ?>

				<li>
					<div class="item-avatar">
						<a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_avatar( 'type=thumb' ); ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_name(); ?></a></div>
					</div>

					<div class="clear"></div>
				</li>

			<?php endwhile; ?>

			</ul>

			<?php

		}

	}



	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 * @since 0.2.1
	 *
	 * @param array $instance Previously saved values from database.
	 * @return void Echoes its output
	 */
	public function form( $instance ) {

		//print_r( $instance ); die();

		// get title
		if ( isset( $instance['title'] ) ) {
			$title = $instance['title'];
		} else {
			$title = sprintf(
				__( 'List of %s', 'bp-group-sites' ),
				apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) )
			);
		}

		?>

		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bp-group-sites' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<?php

	}



	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 * @since 0.2.1
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 * @return array $instance Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {

		// never lose a value
		$instance = wp_parse_args( $new_instance, $old_instance );

		// --<
		return $instance;

	}



} // ends class BP_Group_Sites_List_Widget



// register this widget
register_widget( 'BP_Group_Sites_List_Widget' );



