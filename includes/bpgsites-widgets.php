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

		// get filtered title
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		// show widget prefix
		echo ( isset( $args['before_widget'] ) ? $args['before_widget'] : '' );

		// show title if there is one
		if ( ! empty( $title ) ) {
			echo ( isset( $args['before_title'] ) ? $args['before_title'] : '' );
			echo $title;
			echo ( isset( $args['after_title'] ) ? $args['after_title'] : '' );
		}

		// set default max blogs if absent
		if ( empty( $instance['max_blogs'] ) OR ! is_numeric( $instance['max_blogs'] ) ) {
			$instance['max_blogs'] = 5;
		}

		// set up params
		$params = array(
			'max' => $instance['max_blogs'],
			'per_page' => $instance['max_posts'],
		);

		// get group sites
		if ( bpgsites_has_blogs( $params ) ) { ?>

			<ul class="item-list bpgsites-list">

			<?php while ( bp_blogs() ) : bp_the_blog(); ?>

				<li>
					<div class="item-avatar">
						<a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_avatar( 'type=thumb' ); ?></a>
					</div>

					<div class="item">
						<div class="item-title"><a href="<?php bp_blog_permalink(); ?>"><?php bp_blog_name(); ?></a></div>
						<div class="item-meta"><span class="activity"><?php bp_blog_last_active(); ?></span></div>
					</div>

					<div class="clear"></div>
				</li>

			<?php endwhile; ?>

			</ul>

			<?php

		}

		// show widget suffix
		echo ( isset( $args['after_widget'] ) ? $args['after_widget'] : '' );

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

		// get title
		if ( isset( $instance['title'] ) ) {
			$title = strip_tags( $instance['title'] );
		} else {
			$title = sprintf(
				__( 'List of %s', 'bp-group-sites' ),
				apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) )
			);
		}

		// get max blogs
		if ( isset( $instance['max_blogs'] ) ) {
			$max_blogs = strip_tags( $instance['max_blogs'] );
		} else {
			$max_blogs = 5;
		}

		?>

		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'bp-group-sites' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"></label>
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'max_blogs' ); ?>"><?php _e( 'Max number to show:', 'bp-group-sites' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_blogs' ); ?>" name="<?php echo $this->get_field_name( 'max_blogs' ); ?>" type="text" value="<?php echo esc_attr( $max_blogs ); ?>" style="width: 30%" /></label>
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



