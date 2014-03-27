<?php

/**
 * BP Group Sites - Group Sites Directory
 *
 * @package BuddyPress
 * @subpackage bp-default
 */

get_header( 'buddypress' );

?>

<!-- bpgsites/index.php -->
	<?php do_action( 'bp_before_directory_blogs_page' ); ?>

	<div id="content" role="main" class="<?php do_action( 'content_class' ); ?>">
		<div class="padder">

		<?php do_action( 'bp_before_directory_blogs' ); ?>

		<form action="" method="post" id="bpgsites-directory-form" class="dir-form">

			<h3><?php 
			
			// show title
			echo sprintf( 
				__( '%s Directory', 'bpgsites' ), 
				apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bpgsites' ) )
			);

			?></h3>

			<?php do_action( 'bp_before_directory_blogs_content' ); ?>

			<div id="blog-dir-search" class="dir-search" role="search">

				<?php bp_directory_blogs_search_form(); ?>

			</div><!-- #blog-dir-search -->

			<div class="item-list-tabs" role="navigation">
				<ul>
					<li class="selected" id="bpgsites-all"><a href="<?php bp_root_domain(); ?>/<?php bpgsites_root_slug(); ?>"><?php 
						
						// filter subnav title
						printf( 
							__( 'All %1$s <span>%2$s</span>', 'bpgsites' ), 
							apply_filters( 'bpgsites_extension_plural', __( 'Group Sites', 'bpgsites' ) ),
							bpgsites_get_total_blog_count()
						); 
							
					?></a></li>

					<?php 
					
					/*
					------------------------------------------------------------
					What would we mean by "My Texts"?
					------------------------------------------------------------
					*/
					
					/*
					if ( is_user_logged_in() && bp_get_total_blog_count_for_user( bp_loggedin_user_id() ) ) : ?>

						<li id="bpgsites-personal"><a href="<?php echo bp_loggedin_user_domain() . bp_get_blogs_slug(); ?>"><?php printf( __( 'My Group Sites <span>%s</span>', 'bpgsites' ), bp_get_total_blog_count_for_user( bp_loggedin_user_id() ) ); ?></a></li>

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

						<label for="bpgsites-order-by"><?php _e( 'Order By:', 'bpgsites' ); ?></label>
						<select id="bpgsites-order-by">
							<option value="active"><?php _e( 'Last Active', 'bpgsites' ); ?></option>
							<option value="newest"><?php _e( 'Newest', 'bpgsites' ); ?></option>
							<option value="alphabetical"><?php _e( 'Alphabetical', 'bpgsites' ); ?></option>

							<?php do_action( 'bp_blogs_directory_order_options' ); ?>

						</select>
					</li>
				</ul>
			</div>

			<div id="bpgsites-dir-list" class="bpgsites dir-list">

				<?php bp_locate_template( array( 'bpgsites/bpgsites-loop.php' ), true, false ); ?>

			</div><!-- #bpgsites-dir-list -->

			<?php do_action( 'bp_directory_blogs_content' ); ?>

			<?php wp_nonce_field( 'directory_bpgsites', '_wpnonce-bpgsites-filter' ); ?>

			<?php do_action( 'bp_after_directory_blogs_content' ); ?>

		</form><!-- #bpgsites-directory-form -->

		<?php do_action( 'bp_after_directory_blogs' ); ?>

		</div><!-- .padder -->
	</div><!-- #content -->

	<?php do_action( 'bp_after_directory_blogs_page' ); ?>

<?php get_sidebar( 'buddypress' ); ?>
<?php get_footer( 'buddypress' ); ?>