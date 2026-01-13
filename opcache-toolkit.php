<?php
/**
 * Plugin Name: OPcache Toolkit
 * Plugin URI: https://github.com/christophherr/opcache-manager
 * Description: A full OPcache monitoring and management suite with charts, alerts, preloading, REST API, AJAX live stats, and multisite support.
 * Version: 1.0.0
 * Contributor: christophherr
 * Author: Christoph Herr
 * Author URI: https://www.christophherr.com
 * Text Domain: opcache-manager
 * Domain Path: /languages
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * License: GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access.
}

/**
 * Define plugin paths.
 */
define( 'OPCACHE_TOOLKIT_FILE', __FILE__ );
define( 'OPCACHE_TOOLKIT_PATH', plugin_dir_path( __FILE__ ) );
define( 'OPCACHE_TOOLKIT_URL', plugin_dir_url( __FILE__ ) );
define( 'OPCACHE_TOOLKIT_IS_NETWORK', is_multisite() );
define( 'OPCACHE_TOOLKIT_VERSION', '1.0.0' );

/**
 * Load plugin text domain for translations.
 */
add_action(
	'plugins_loaded',
	function () {
		load_plugin_textdomain(
			'opcache-manager',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
);

/**
 * Include Autoloader and Bootstrap Plugin.
 */
require_once OPCACHE_TOOLKIT_PATH . 'vendor/autoload.php';
\OPcacheToolkit\Plugin::instance()->boot();

/**
 * Initialize Error Monitor for automatic PHP error capture.
 */
\OPcacheToolkit\Services\ErrorMonitor::init();

register_activation_hook(
	__FILE__,
	function () {
		// Create or upgrade database schema.
		opcache_toolkit_install_schema();

		// Schedule daily OPcache log.
		opcache_toolkit_schedule_daily_event();

		// Schedule retention cleanup.
		opcache_toolkit_schedule_retention_cleanup();

		// Mark for redirect to wizard.
		opcache_toolkit_update_setting( 'opcache_toolkit_show_wizard', true );
	}
);

add_action(
	'admin_init',
	function () {
		if ( opcache_toolkit_get_setting( 'opcache_toolkit_show_wizard' ) ) {
			opcache_toolkit_update_setting( 'opcache_toolkit_show_wizard', false );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check for redirection.
			if ( ! isset( $_GET['activate-multi'] ) && opcache_toolkit_user_can_manage_opcache() ) {
				wp_safe_redirect( admin_url( 'admin.php?page=opcache-toolkit-wizard' ) );
				exit;
			}
		}
	}
);

register_deactivation_hook(
	__FILE__,
	function () {

		// Clear Action Scheduler jobs.
		if ( class_exists( 'ActionScheduler' ) ) {
			as_unschedule_all_actions( 'opcache_toolkit_daily_log', [], 'opcache-toolkit' );
			as_unschedule_all_actions( 'opcache_toolkit_daily_stats_cleanup', [], 'opcache-toolkit' );
		}

		// Clear WP-Cron jobs.
		wp_clear_scheduled_hook( 'opcache_toolkit_daily_log' );
		wp_clear_scheduled_hook( 'opcache_toolkit_daily_stats_cleanup' );
	}
);


/**
 * Determine whether the current user can manage OPcache.
 *
 * @return bool
 */
function opcache_toolkit_user_can_manage_opcache() {

	if ( is_multisite() ) {
		return current_user_can( 'manage_network' );
	}

	return current_user_can( 'manage_options' );
}
