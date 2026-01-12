<?php
/**
 * OPcache Toolkit – Preload Request Handler
 *
 * Handles the admin-post request to trigger OPcache preloading.
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access.
}

/**
 * Handle the OPcache preload admin action.
 *
 * If Action Scheduler is available, the preload is queued to run
 * in the background. Otherwise, it runs synchronously in the request.
 *
 * @return void
 */
add_action(
	'admin_post_opcache_toolkit_preload',
	function () {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
		}

		// Async path via Action Scheduler
		if ( class_exists( 'ActionScheduler' ) ) {

			opcache_toolkit_queue_preload();

			wp_redirect(
				admin_url( 'options-general.php?page=opcache-manager&opcache_toolkit_notice=preload_queued' )
			);
			exit;
		}

		// Fallback: synchronous preload.
		$count = opcache_toolkit_preload_now();
		opcache_toolkit_store_preload_report( $count );

		// You might log this as well.
		if ( function_exists( 'opcache_toolkit_log' ) ) {
			opcache_toolkit_log( "Synchronous preload completed. Files compiled: {$count}" );
		}

		wp_redirect(
			admin_url( 'options-general.php?page=opcache-manager&opcache_toolkit_notice=preloaded' )
		);
		exit;
	}
);
