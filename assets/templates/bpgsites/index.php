<?php
/**
 * BP Group Sites - Group Sites Directory.
 *
 * @package BuddyPress
 * @subpackage BP_Group_Sites
 */

get_header( 'buddypress' );

?>

<!-- bpgsites/index.php -->
	<?php do_action( 'bp_before_directory_groupsites_page' ); ?>

	<div id="content" role="main" class="<?php do_action( 'content_class' ); ?>">
		<div id="buddypress">

		<?php do_action( 'bp_before_directory_groupsites' ); ?>

		<form action="" method="post" id="bpgsites-directory-form" class="dir-form">

			<h3>
				<?php

				echo sprintf(
					/* translators: %s: The plural name for Group Sites. */
					esc_html__( '%s Directory', 'bp-group-sites' ),
					apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) )
				);

				?>
			</h3>

			<?php do_action( 'bp_before_directory_groupsites_content' ); ?>

			<div id="blog-dir-search" class="dir-search" role="search">

				<?php bp_directory_blogs_search_form(); ?>

			</div><!-- #blog-dir-search -->

			<div class="item-list-tabs" role="navigation">
				<ul>
					<li class="selected" id="bpgsites-all"><a href="<?php bp_root_domain(); ?>/<?php bpgsites_root_slug(); ?>">
						<?php

						// Filter subnav title.
						printf(
							/* translators: 1: The plural name for Group Sites, 2: The number of Group Sites. */
							esc_html__( 'All %1$s <span>%2$s</span>', 'bp-group-sites' ),
							apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) ),
							bpgsites_get_total_blog_count()
						);

						?>
					</a></li>

					<?php do_action( 'bp_blogs_directory_blog_types' ); ?>

				</ul>
			</div><!-- .item-list-tabs -->

			<div class="item-list-tabs" id="subnav" role="navigation">
				<ul>

					<?php do_action( 'bp_blogs_directory_blog_sub_types' ); ?>

					<li id="bpgsites-order-select" class="last filter">

						<label for="bpgsites-order-by"><?php esc_html_e( 'Order By:', 'bp-group-sites' ); ?></label>
						<select id="bpgsites-order-by">
							<option value="active"><?php esc_html_e( 'Last Active', 'bp-group-sites' ); ?></option>
							<option value="newest"><?php esc_html_e( 'Newest', 'bp-group-sites' ); ?></option>
							<option value="alphabetical"><?php esc_html_e( 'Alphabetical', 'bp-group-sites' ); ?></option>

							<?php do_action( 'bp_blogs_directory_order_options' ); ?>

						</select>
					</li>
				</ul>
			</div>

			<div id="bpgsites-dir-list" class="bpgsites dir-list">

				<?php bp_locate_template( [ 'bpgsites/bpgsites-loop.php' ], true, false ); ?>

			</div><!-- #bpgsites-dir-list -->

			<?php do_action( 'bp_directory_groupsites_content' ); ?>

			<?php wp_nonce_field( 'directory_bpgsites', '_wpnonce-bpgsites-filter' ); ?>

			<?php do_action( 'bp_after_directory_groupsites_content' ); ?>

		</form><!-- #bpgsites-directory-form -->

		<?php do_action( 'bp_after_directory_groupsites' ); ?>

		</div><!-- .buddypress -->
	</div><!-- #content -->

	<?php do_action( 'bp_after_directory_groupsites_page' ); ?>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>
