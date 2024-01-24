<?php
/**
 * Plugin Name: BP Group Sites
 * Description: Creates many-to-many relationships between BuddyPress Groups and WordPress Sites.
 * Version: 0.3.2a
 * Author: Christian Wach
 * Author URI: https://haystack.co.uk
 * Plugin URI: https://github.com/christianwach/bp-group-sites
 * Text Domain: bp-group-sites
 * Domain Path: /languages
 * Network: true
 *
 * @package BP_Group_Sites
 */

// Set our version here.
define( 'BPGSITES_VERSION', '0.3.2a' );

// Store reference to this file.
if ( ! defined( 'BPGSITES_FILE' ) ) {
	define( 'BPGSITES_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'BPGSITES_URL' ) ) {
	define( 'BPGSITES_URL', plugin_dir_url( BPGSITES_FILE ) );
}
// Store PATH to this plugin's directory.
if ( ! defined( 'BPGSITES_PATH' ) ) {
	define( 'BPGSITES_PATH', plugin_dir_path( BPGSITES_FILE ) );
}

// Set site option prefix.
define( 'BPGSITES_PREFIX', 'bpgsites_blog_groups_' );

// Set linked groups option name.
define( 'BPGSITES_LINKED', 'bpgsites_linked_groups' );

// Set linked groups (pending sent) option name.
define( 'BPGSITES_PENDING', 'bpgsites_pending_groups' );

// Set group blogs option name.
define( 'BPGSITES_OPTION', 'bpgsites_group_blogs' );

// Set comment meta key.
define( 'BPGSITES_COMMENT_META_KEY', 'bpgsites_group_id' );

/**
 * BP Group Sites class.
 *
 * A class that encapsulates plugin functionality.
 *
 * @since 0.1
 */
class BP_Group_Sites {

	/**
	 * Admin object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $admin The admin object.
	 */
	public $admin;

	/**
	 * Activity object.
	 *
	 * @since 0.1
	 * @access public
	 * @var object $activity The activity object.
	 */
	public $activity;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Always init admin.
		$this->initialise_admin();

		// Use translation files.
		add_action( 'plugins_loaded', [ $this, 'enable_translation' ] );

		// Add actions for plugin init on BuddyPress init.
		add_action( 'bp_loaded', [ $this, 'initialise' ] );
		add_action( 'bp_loaded', [ $this, 'register_hooks' ] );
		add_action( 'bp_include', [ $this, 'register_theme_hooks' ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Actions to perform on plugin activation.
	 *
	 * @since 0.1
	 */
	public function activate() {

		// Pass through to admin.
		$this->admin->activate();

	}

	/**
	 * Actions to perform on plugin deactivation (NOT deletion).
	 *
	 * @since 0.1
	 */
	public function deactivate() {

		// Pass through to admin.
		$this->admin->deactivate();

	}

	// -------------------------------------------------------------------------

	/**
	 * Load translation files.
	 *
	 * @see http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 *
	 * @since 0.1
	 */
	public function enable_translation() {

		// Enable translation.
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'bp-group-sites', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // Relative path to translation files.
		);

	}

	/**
	 * Initialise the admin object.
	 *
	 * @since 0.1
	 */
	public function initialise_admin() {

		// Load our admin class file.
		require BPGSITES_PATH . 'includes/bpgsites-admin.php';

		// Init object, sending reference to this class.
		$this->admin = new BP_Group_Sites_Admin( $this );

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.1
	 */
	public function initialise() {

		// Load our linkage functions file.
		require BPGSITES_PATH . 'includes/bpgsites-linkage.php';

		// Load our display functions file.
		require BPGSITES_PATH . 'includes/bpgsites-display.php';

		// Load our activity functions file.
		require BPGSITES_PATH . 'includes/bpgsites-activity.php';

		// Init object.
		$this->activity = new BP_Group_Sites_Activity();

		// Load our blogs extension.
		require BPGSITES_PATH . 'includes/bpgsites-blogs.php';

		// Load our group extension.
		require BPGSITES_PATH . 'includes/bpgsites-groups.php';

		// Load our component file.
		require BPGSITES_PATH . 'includes/bp-bpgsites-component.php';

	}

	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.1
	 */
	public function register_hooks() {

		// Hooks that always need to be present.
		$this->admin->register_hooks();
		$this->activity->register_hooks();

		// Register any public styles.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ], 20 );

		// Register any public scripts.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 20 );

		// Add widgets.
		add_action( 'widgets_init', [ $this, 'register_widgets' ] );

		// If the current blog is a group site.
		if ( bpgsites_is_groupsite( get_current_blog_id() ) ) {

			// Register our CommentPress scripts if on front end.
			if ( ! is_admin() ) {
				add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_commentpress_scripts' ], 20 );
			}

		}

	}

	/**
	 * Register theme hooks on bp include.
	 *
	 * @since 0.1
	 */
	public function register_theme_hooks() {

		// Add our templates to the theme compatibility layer.
		add_action( 'bp_register_theme_packages', [ $this, 'theme_compat' ] );

	}

	/**
	 * Add our templates to the theme stack.
	 *
	 * @since 0.1
	 */
	public function theme_compat() {

		// Add templates dir to BuddyPress.
		bp_register_template_stack( 'bpgsites_templates_dir', 16 );

	}

	/**
	 * Add our front-end stylesheets.
	 *
	 * @since 0.1
	 */
	public function enqueue_styles() {

		// If on group admin screen.
		if ( bp_is_group_admin_screen( apply_filters( 'bpgsites_extension_slug', 'group-sites' ) ) ) {

			// Register Select2 styles.
			wp_register_style(
				'bpgsites_select2_css',
				set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css' ),
				null,
				'4.0.13'
			);

			// Enqueue styles.
			wp_enqueue_style( 'bpgsites_select2_css' );

		}

		// Add basic stylesheet.
		wp_enqueue_style(
			'bpgsites_css',
			BPGSITES_URL . 'assets/css/bpgsites.css',
			false,
			BPGSITES_VERSION, // Version.
			'all' // Media.
		);

	}

	/**
	 * Add our front-end Javascripts.
	 *
	 * @since 0.1
	 */
	public function enqueue_scripts() {

		// Only on root blog.
		if ( is_multisite() && bp_is_root_blog() ) {

			// Enqueue activity stream javascript.
			wp_enqueue_script(
				'bpgsites_activity_js',
				BPGSITES_URL . 'assets/js/bpgsites-activity.js',
				[ 'jquery' ],
				BPGSITES_VERSION,
				true
			);

			// If on group admin screen.
			if ( bp_is_group_admin_screen( apply_filters( 'bpgsites_extension_slug', 'group-sites' ) ) ) {

				// Register Select2.
				wp_register_script(
					'bpgsites_select2_js',
					set_url_scheme( 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js' ),
					[ 'jquery' ],
					'4.0.13',
					true
				);

				// Enqueue script.
				wp_enqueue_script( 'bpgsites_select2_js' );

				// Enqueue group admin js.
				wp_enqueue_script(
					'bpgsites_select2_custom_js',
					BPGSITES_URL . 'assets/js/bpgsites-group-admin.js',
					[ 'bpgsites_select2_js' ],
					BPGSITES_VERSION,
					true
				);

				// Localisation array.
				$vars = [
					'localisation' => [],
					'data' => [
						'group_id' => bp_get_current_group_id(),
					],
				];

				// Localise with wp function.
				wp_localize_script(
					'bpgsites_select2_custom_js',
					'BuddypressGroupSitesSettings',
					$vars
				);

			}

		}

	}

	/**
	 * Add our CommentPress-specific scripts.
	 *
	 * @since 0.1
	 */
	public function enqueue_commentpress_scripts() {

		// CommentPress theme compat.
		if ( function_exists( 'commentpress_get_comments_by_para' ) ) {

			// Enqueue common js.
			wp_enqueue_script(
				'bpgsites_cp_js',
				BPGSITES_URL . 'assets/js/bpgsites-commentpress.js',
				[ 'cp_common_js' ],
				BPGSITES_VERSION,
				true
			);

			// Get vars.
			$vars = [
				'show_public' => $this->admin->option_get( 'bpgsites_public' ),
			];

			// Localise with wp function.
			wp_localize_script( 'bpgsites_cp_js', 'BpgsitesSettings', $vars );

		}

	}

	/**
	 * Register widgets for this plugin.
	 *
	 * @since 0.1
	 */
	public function register_widgets() {

		// Include widgets.
		require_once BPGSITES_PATH . 'includes/bpgsites-widgets.php';

	}

}

// Init plugin.
global $bp_groupsites;
$bp_groupsites = new BP_Group_Sites();

// Activation.
register_activation_hook( __FILE__, [ $bp_groupsites, 'activate' ] );

// Deactivation.
register_deactivation_hook( __FILE__, [ $bp_groupsites, 'deactivate' ] );

/*
 * Will use the 'uninstall.php' method.
 *
 * @see: https://codex.wordpress.org/Function_Reference/register_uninstall_hook
 */

/**
 * Returns the path to our templates directory.
 *
 * @since 0.1
 *
 * @return str $path path to this plugin's templates directory.
 */
function bpgsites_templates_dir() {

	/**
	 * Return filterable path to templates.
	 *
	 * @since 0.1
	 *
	 * @param str The default path to this plugin's templates directory.
	 */
	$path = apply_filters( 'bpgsites_templates_dir', BPGSITES_PATH . 'assets/templates' );

	// --<
	return $path;

}
