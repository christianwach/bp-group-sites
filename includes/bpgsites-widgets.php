<?php
/**
 * BP Group Sites Widgets.
 *
 * Widgets defined here.
 *
 * @package BP_Group_Sites
 * @since 0.2.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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
	public function __construct() {

		// Define widget name.
		$name = sprintf(
			/* translators: %s: The plural name of Group Sites. */
			__( 'List of %s', 'bp-group-sites' ),
			bpgsites_get_extension_plural()
		);

		// Define widget args.
		$args = [
			'description' => sprintf(
				/* translators: %s: The plural name of Group Sites. */
				__( 'Use this widget to show a list of %s.', 'bp-group-sites' ),
				bpgsites_get_extension_plural()
			),
		];

		// Init parent.
		parent::__construct(
			'bpgsites_list_widget', // Base ID.
			$name,
			$args
		);

	}

	/**
	 * Outputs the HTML for this widget.
	 *
	 * @since 0.2.1
	 * @param array $args An array of standard parameters for widgets in this theme.
	 * @param array $instance An array of settings for this widget instance.
	 */
	public function widget( $args, $instance ) {

		// Get filtered title.
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );

		// Show widget prefix.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo isset( $args['before_widget'] ) ? $args['before_widget'] : '';

		// Show title if there is one.
		if ( ! empty( $title ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo isset( $args['before_title'] ) ? $args['before_title'] : '';
			echo esc_html( $title );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo isset( $args['after_title'] ) ? $args['after_title'] : '';
		}

		// Set default max blogs if absent.
		if ( empty( $instance['max_blogs'] ) || ! is_numeric( $instance['max_blogs'] ) ) {
			$instance['max_blogs'] = 5;
		}

		// Set up params.
		$params = [
			'max'      => $instance['max_blogs'],
			'per_page' => $instance['max_blogs'],
		];

		// Get group sites.
		if ( bpgsites_has_blogs( $params ) ) { ?>

			<ul class="item-list bpgsites-list">

			<?php while ( bp_blogs() ) : ?>
				<?php bp_the_blog(); ?>

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

		// Show widget suffix.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo isset( $args['after_widget'] ) ? $args['after_widget'] : '';

	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 * @since 0.2.1
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		// Get title.
		if ( isset( $instance['title'] ) ) {
			$title = wp_strip_all_tags( $instance['title'] );
		} else {
			$title = sprintf(
				/* translators: %s: The plural name of Group Sites. */
				__( 'List of %s', 'bp-group-sites' ),
				bpgsites_get_extension_plural()
			);
		}

		// Get max blogs.
		if ( isset( $instance['max_blogs'] ) ) {
			$max_blogs = wp_strip_all_tags( $instance['max_blogs'] );
		} else {
			$max_blogs = 5;
		}

		?>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'bp-group-sites' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>"></label>
		</p>

		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'max_blogs' ) ); ?>"><?php esc_html_e( 'Max number to show:', 'bp-group-sites' ); ?> <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'max_blogs' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'max_blogs' ) ); ?>" type="text" value="<?php echo esc_attr( $max_blogs ); ?>" style="width: 30%" /></label>
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

		// Never lose a value.
		$instance = wp_parse_args( $new_instance, $old_instance );

		// --<
		return $instance;

	}

}

// Register this widget.
register_widget( 'BP_Group_Sites_List_Widget' );
