<?php
/**
 * BP Group Sites - Group Sites Directory.
 *
 * @package BuddyPress
 * @subpackage BP_Group_Sites
 */

get_header( 'buddypress' );

?>

<!-- theme/bpgsites/index.php -->
	<?php do_action( 'bp_before_directory_groupsites_page' ); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

			<article id="post-0" class="post-0 page type-page status-publish hentry">

				<header class="entry-header">
					<h1 class="entry-title"><?php

					// Show title.
					echo sprintf(
						/* translators: %s: The plural name for Group Sites. */
						__( '%s Directory', 'bp-group-sites' ),
						apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) )
					);

					?></h1>
				</header><!-- .entry-header -->

				<div class="entry-content">

					<div id="buddypress">

						<?php do_action( 'bp_before_directory_groupsites' ); ?>

						<?php do_action( 'bp_before_directory_groupsites_content' ); ?>

						<div id="blog-dir-search" class="dir-search" role="search">

							<?php bp_directory_blogs_search_form(); ?>

						</div><!-- #blog-dir-search -->

						<form action="" method="post" id="bpgsites-directory-form" class="dir-form">

							<div class="item-list-tabs" role="navigation">
								<ul>
									<li class="selected" id="bpgsites-all"><a href="<?php bp_root_domain(); ?>/<?php bpgsites_root_slug(); ?>"><?php

									// Filter subnav title.
									printf(
										/* translators: %s: The plural name for Group Sites. */
										__( 'All %1$s <span>%2$s</span>', 'bp-group-sites' ),
										apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bp-group-sites' ) ),
										bpgsites_get_total_blog_count()
									);

									?></a></li>

									<?php

									/*
									------------------------------------------------------------
									What would we mean by "My Texts"?
									------------------------------------------------------------
									if ( is_user_logged_in() && bp_get_total_blog_count_for_user( bp_loggedin_user_id() ) ) : ?>

										<li id="bpgsites-personal"><a href="<?php echo bp_loggedin_user_domain() . bp_get_blogs_slug(); ?>"><?php printf( __( 'My Group Sites <span>%s</span>', 'bp-group-sites' ), bp_get_total_blog_count_for_user( bp_loggedin_user_id() ) ); ?></a></li>

									<?php endif;
									*/

									?>

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

				</div><!-- .entry-content -->

			</article><!-- #post-## -->

		</main><!-- #main -->
	</div><!-- #primary -->

	<?php do_action( 'bp_after_directory_groupsites_page' ); ?>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>
