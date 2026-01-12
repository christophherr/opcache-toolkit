<?php
/**
 * OPcache Toolkit – OPcache Reset Hooks
 *
 * Handles:
 * - Manual OPcache reset (admin-post action)
 * - Automatic OPcache reset after plugin/theme updates
 * - Optional logging hooks
 *
 * This file centralizes all OPcache reset logic so it is not scattered
 * across the plugin. It is loaded on every admin request.
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle the "Reset OPcache Now" admin-post action.
 *
 * This action is triggered when the user clicks the "Reset OPcache Now"
 * button in the Advanced Tools tab of the settings page, or when the
 * admin bar "Clear OPcache" link is clicked.
 *
 * Security:
 * - Requires `manage_options` capability.
 * - Protected by a nonce (`opcache_toolkit_clear`).
 *
 * Behavior:
 * - Calls `opcache_reset()` if available.
 * - Redirects back to the referring page.
 *
 * @return void
 */
add_action(
	'admin_post_opcache_toolkit_clear',
	function () {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
		}

		check_admin_referer( 'opcache_toolkit_clear' );

		if ( function_exists( 'opcache_reset' ) ) {
			opcache_reset();

			\OPcacheToolkit\Plugin::logger()->log( 'OPcache manually reset via admin-post action.' );
		}

		wp_safe_redirect( wp_get_referer() );
		exit;
	}
);


/**
 * Automatically reset OPcache after plugin or theme updates.
 *
 * This hook fires after the WordPress upgrader finishes installing or
 * updating plugins/themes. It checks the `opcache_toolkit_auto_reset` setting and,
 * if enabled, resets OPcache to ensure updated PHP files are recompiled.
 *
 * Security:
 * - Only runs for users with `manage_options`.
 *
 * Behavior:
 * - Only triggers for plugin/theme updates (not core updates).
 * - Calls `opcache_reset()` if available.
 *
 * @param WP_Upgrader $upgrader   The upgrader instance.
 * @param array       $hook_extra Additional context about the upgrade.
 *
 * @return void
 */
add_action(
	'upgrader_process_complete',
	function ( $upgrader, $hook_extra ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$auto_reset = OPCACHE_TOOLKIT_IS_NETWORK
		? get_site_option( 'opcache_toolkit_auto_reset', 0 )
		: get_option( 'opcache_toolkit_auto_reset', 0 );

		if ( ! $auto_reset ) {
			return;
		}

		if ( ! isset( $hook_extra['type'] ) ) {
			return;
		}

		if ( ! in_array( $hook_extra['type'], [ 'plugin', 'theme' ], true ) ) {
			return;
		}

		if ( function_exists( 'opcache_reset' ) ) {
			opcache_reset();

			\OPcacheToolkit\Plugin::logger()->log( 'OPcache auto-reset after ' . $hook_extra['type'] . ' update.' );
		}
	},
	10,
	2
);
