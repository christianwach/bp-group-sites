<?php
/**
 * Settings Page template.
 *
 * Handles markup for the Settings Page.
 *
 * @package BP_Group_Sites
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<!-- assets/admin/page-settings.php -->
<div class="wrap" id="bpgsites_admin_wrapper">

	<h2><?php esc_html_e( 'BP Group Sites Settings', 'bp-group-sites' ); ?></h2>

	<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
	<?php if ( isset( $_GET['updated'] ) ) : ?>
		<div id="message" class="updated"><p><?php esc_html_e( 'Options saved.', 'bp-group-sites' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( $submit_url ); ?>">

		<?php wp_nonce_field( 'bpgsites_admin_action', 'bpgsites_nonce' ); ?>

		<div id="bpgsites_admin_options">

			<h3><?php echo esc_html_e( 'Global Options', 'bp-group-sites' ); ?></h3>

			<table class="form-table">

				<tr valign="top">
					<th scope="row">
						<label for="bpgsites_public">
							<?php esc_html_e( 'Should comments in public groups be visible to readers who are not members of those groups?', 'bp-group-sites' ); ?>
						</label>
					</th>
					<td>
						<input id="bpgsites_public" name="bpgsites_public" value="1" type="checkbox" <?php checked( 1, $public ); ?> />
					</td>
				</tr>

			</table>

			<h3><?php echo esc_html_e( 'Naming Options', 'bp-group-sites' ); ?></h3>

			<table class="form-table">

				<tr valign="top">
					<th scope="row">
						<label for="bpgsites_overrides"><?php esc_html_e( 'Enable name changes?', 'bp-group-sites' ); ?></label>
					</th>
					<td>
						<input id="bpgsites_overrides" name="bpgsites_overrides" value="1" type="checkbox" <?php checked( 1, $overrides ); ?> />
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="bpgsites_overrides_title"><?php esc_html_e( 'Component Title', 'bp-group-sites' ); ?></label>
					</th>
					<td>
						<input id="bpgsites_overrides_title" name="bpgsites_overrides_title" value="<?php echo esc_attr( $title ); ?>" type="text" />
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="bpgsites_overrides_name"><?php esc_html_e( 'Singular name for a Group Site', 'bp-group-sites' ); ?></label>
					</th>
					<td>
						<input id="bpgsites_overrides_name" name="bpgsites_overrides_name" value="<?php echo esc_attr( $name ); ?>" type="text" />
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="bpgsites_overrides_plural"><?php esc_html_e( 'Plural name for Group Sites', 'bp-group-sites' ); ?></label>
					</th>
					<td>
						<input id="bpgsites_overrides_plural" name="bpgsites_overrides_plural" value="<?php echo esc_attr( $plural ); ?>" type="text" />
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
						<label for="bpgsites_overrides_button"><?php esc_html_e( 'Visit Group Site button text', 'bp-group-sites' ); ?></label>
					</th>
					<td>
						<input id="bpgsites_overrides_button" name="bpgsites_overrides_button" value="<?php echo esc_attr( $button ); ?>" type="text" />
					</td>
				</tr>

			</table>

		</div>

		<p class="submit">
			<input type="submit" name="bpgsites_submit" value="<?php esc_attr_e( 'Save Changes', 'bp-group-sites' ); ?>" class="button-primary" />
		</p>

	</form>

</div>
