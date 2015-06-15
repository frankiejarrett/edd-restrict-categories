<?php
/**
 * Plugin Name: EDD Restrict Categories
 * Plugin URI: https://easydigitaldownloads.com/downloads/restrict-categories/
 * Description:
 * Version: 1.0.0
 * Author: Easy Digital Downloads
 * Author URI: http://easydigitaldownloads.com/
 * Developer: Frankie Jarrett
 * Developer URI: http://frankiejarrett.com/
 * Depends: Easy Digital Downlods
 * Text Domain: edd-restrict-categories
 * Domain Path: /languages
 *
 * License: GNU General Public License v2.0
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Plugin Class
 *
 *
 *
 * @version 1.0.0
 * @package Easy Digital Downloads
 * @author  Frankie Jarrett
 */
class EDD_Restrict_Categories {

	/**
	 * Hold class instance
	 *
	 * @access public
	 * @static
	 *
	 * @var EDD_Restrict_Categories
	 */
	public static $instance;

	/**
	 * Hold the taxonomy slugs where restriction is allowed
	 *
	 * @access public
	 * @static
	 *
	 * @var array
	 */
	public static $taxonomies;

	/**
	 * Hold the post types where taxonomy restrictions should be honored
	 *
	 * @access public
	 * @static
	 *
	 * @var array
	 */
	public static $post_types;

	/**
	 * Plugin version number
	 *
	 * @const string
	 */
	const VERSION = '1.0.0';

	/**
	 * Unique key prefix
	 *
	 * @const string
	 */
	const PREFIX = 'eddrc_';

	/**
	 * Class constructor
	 *
	 * @access private
	 */
	private function __construct() {
		if ( ! $this->edd_exists() ) {
			return;
		}

		define( 'EDD_RESTRICT_CATEGORIES_URL', plugins_url( '/', __FILE__ ) );
		define( 'EDD_RESTRICT_CATEGORIES_DIR', plugin_dir_path( __FILE__ ) );
		define( 'EDD_RESTRICT_CATEGORIES_INC_DIR', EDD_RESTRICT_CATEGORIES_DIR . 'includes/' );

		add_action( 'plugins_loaded', function() {
			foreach ( glob( EDD_RESTRICT_CATEGORIES_INC_DIR . '*.php' ) as $include ) {
				if ( is_readable( $include ) ) {
					require_once $include;
				}
			}
		} );

		/**
		 * Register the taxonomy slugs where restriction will be allowed
		 *
		 * @since 1.0.0
		 *
		 * @param array $taxonomies
		 *
		 * @return array
		 */
		self::$taxonomies = (array) apply_filters( 'eddrc_taxonomies', array( 'download_category', 'download_tag' ) );

		/**
		 * Register the post types where taxonomy restrictions should be honored
		 *
		 * @since 1.0.0
		 *
		 * @param array $post_types
		 *
		 * @return array
		 */
		self::$post_types = (array) apply_filters( 'eddrc_post_types', array( 'download' ) );

		// Enqueue scripts and styles in the admin
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Return an active instance of this class
	 *
	 * @access public
	 * @since 1.0.0
	 * @static
	 *
	 * @return EDD_Restrict_Categories
	 */
	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns true if EDD exists
	 *
	 * Looks at the active list of plugins on the site to
	 * determine if EDD is installed and activated.
	 *
	 * @access private
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function edd_exists() {
		return in_array( 'easy-digital-downloads/easy-digital-downloads.php', (array) apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}

	/**
	 * Enqueue scripts and styles in the admin
	 *
	 * @action admin_enqueue_scripts
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) ) {
			return;
		}

		$screen_ids = array();

		foreach ( self::$taxonomies as $taxonomy ) {
			$screen_ids[] = 'edit-' . $taxonomy;
		}

		$screen_ids = implode( ' ', $screen_ids );

		if ( false === strpos( $screen_ids, $screen->id ) ) {
			return;
		}

		// Scripts
		wp_register_script( 'eddrc-select2', EDD_RESTRICT_CATEGORIES_URL . 'ui/js/select2.full.min.js', array( 'jquery' ), '4.0.0' );
		wp_enqueue_script( 'eddrc-admin', EDD_RESTRICT_CATEGORIES_URL . 'ui/js/admin.min.js', array( 'jquery', 'eddrc-select2' ), self::VERSION );

		// Styles
		wp_register_style( 'eddrc-select2', EDD_RESTRICT_CATEGORIES_URL . 'ui/css/select2.min.css', array(), '4.0.0' );
		wp_enqueue_style( 'eddrc-admin', EDD_RESTRICT_CATEGORIES_URL . 'ui/css/admin.min.css', array( 'eddrc-select2' ), self::VERSION );
	}

	/**
	 * Return an array of user role labels
	 *
	 * @access public
	 * @since 1.0.0
	 * @static
	 *
	 * @return array
	 */
	public static function get_role_labels() {
		$roles  = array();
		$_roles = new WP_Roles();

		foreach ( $_roles->get_names() as $role => $label ) {
			$roles[ $role ] = translate_user_role( $label );
		}

		return (array) $roles;
	}

	/**
	 * Return a particular taxonomy label with fallback support
	 *
	 * @access public
	 * @since 1.0.0
	 * @static
	 *
	 * @param string $taxonomy
	 * @param string $label (optional)
	 *
	 * @return string
	 */
	public static function get_tax_label( $taxonomy, $label = 'name' ) {
		if ( false === ( $taxonomy = get_taxonomy( $taxonomy ) ) ) {
			return (string) $taxonomy;
		}

		$labels = get_taxonomy_labels( $taxonomy );
		$output = ! empty( $labels->$label ) ? $labels->$label : ( ! empty( $labels->name ) ? $labels->name : $taxonomy );

		return (string) $output;
	}

}

/**
 * Instantiate the plugin instance
 *
 * @global EDD_Restrict_Categories
 */
$GLOBALS['edd_restrict_categories'] = EDD_Restrict_Categories::get_instance();
