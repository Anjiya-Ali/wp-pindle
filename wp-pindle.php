<?php
/**
 * Plugin Name:       WP Pindle
 * Description:       Pull events from Pindle and display them via shortcode
 * Version:           2.0.0
 * Requires at least: 5.5.4
 * Requires PHP:      7.3
 * Author:            Anjiya
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wp-pindle
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_PINDLE', '2.0.0' );

class WP_Pindle {
	/**
	 * The single instance of the queue.
	 *
	 * @var WP_Pindle|null
	 */
	protected static $instance = null;

	/**
	 * @var WP_Pindle_Settings|null
	 */
	public $settings;

	/**
	 * @var WP_Pindle_Shortcodes|null
	 */
	public $shortcodes;

	/**
	 * @var WP_Pindle_Sync|null
	 */
	public $sync_api;

	/**
	 * @var WP_Pindle_Actions|null
	 */
	public $actions;

	/**
	 * Single instance
	 *
	 * @return WP_Pindle
	 */
	final public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new WP_Pindle();
		}

		return self::$instance;
	}

	private function __construct() {
		// Register translations
		load_plugin_textdomain( 'wp-pindle', false, basename( dirname( __FILE__ ) ) . '/languages/' );

		define( 'WP_PINDLE_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'WP_PINDLE_URL', plugin_dir_url( __FILE__ ) );

		add_action( 'admin_notices', [ $this, 'notices' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'settings_link' ], 10, 1 );

		require_once 'includes/lib/action-scheduler/action-scheduler.php';

		// load classes
		require_once 'includes/functions.php';
		require_once 'includes/class-wp-pindle-sync.php';
		require_once 'includes/class-wp-pindle-actions.php';
		require_once 'includes/class-wp-pindle-settings.php';
		require_once 'includes/class-wp-pindle-shortcodes.php';

		$this->settings   = new WP_Pindle_Settings;
		$this->shortcodes = new WP_Pindle_Shortcodes;
		$this->sync_api   = new WP_Pindle_Sync;
		$this->actions    = new WP_Pindle_Actions;

		return $this;
	}

	public function settings_link( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=' . WP_Pindle_Settings::SLUG ) . '">' . __( 'Settings', 'wp-pindle' ) . '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	public function notices() {
		$notices = get_transient( '_pindle_notices' );
		if ( empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $type => $notice ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p><strong>%2$s:</strong> %3$s</p></div>', esc_attr( $type ), 'WP Pindle', esc_html( $notice ) );
		}

		delete_transient( '_pindle_notices' );
	}
}

/**
 * @return WP_Pindle|null
 */
function wp_pindle() {
	$message = wp_pindle_dependencies_checks();

	// All good
	if ( empty( $message ) ) {
		return WP_Pindle::instance();
	}

	// Something unmatched
	add_action(
		'admin_notices',
		function () use ( $message ) {
			$class = 'notice notice-error';
			printf( '<div class="%1$s"><p><strong>%2$s:</strong> %3$s</p></div>', esc_attr( $class ), 'WP Pindle', esc_html( $message ) );
		}
	);

	return null;
}

// Fire up!
add_action( 'plugins_loaded', 'wp_pindle' );

/**
 * @return string
 */
function wp_pindle_dependencies_checks() {
	global $wp_version;
	$message = '';

	if ( version_compare( PHP_VERSION, '7.3', '<' ) ) {
		$message = sprintf(
			__( 'You need to upgrade your PHP to atleast version %s. Currently %s is used. To resolve the upgrade please contact your server host.', 'wp-pindle' ),
			'7.4',
			PHP_VERSION
		);
	} elseif ( version_compare( $wp_version, '5.5.4', '<' ) ) {
		$message = sprintf(
			__( 'You need to update your WordPress version to atleast version %s. Current version %s is being used.', 'wp-pindle' ),
			'5.5.4',
			$wp_version
		);
	}

	return $message;
}

/**
 * Activation hook
 */
function wp_pindle_activation_hook() {
	$message = wp_pindle_dependencies_checks();

	if ( ! empty( $message ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		die( sprintf( '<strong>%1$s:</strong> %2$s', 'WP Pindle', esc_html( $message ) ) );
	}

	update_option( 'wp_pindle_version', WP_PINDLE );
}

register_activation_hook( __FILE__, 'wp_pindle_activation_hook' );
