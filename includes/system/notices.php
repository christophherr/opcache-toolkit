<?php
/**
 * OPcache Toolkit – Admin Notices
 *
 * Displays contextual admin notices triggered by actions such as:
 * - Clearing OPcache
 * - Preloading OPcache
 * - Clearing statistics
 * - Exporting statistics
 * - Logging debug entries
 *
 * Notices are triggered by appending `?opcache_toolkit_notice=key` to the redirect URL.
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render OPcache Toolkit admin notices.
 *
 * @return void
 */
function opcache_toolkit_render_admin_notices() {

	if ( ! isset( $_GET['opcache_toolkit_notice'] ) ) {
		return;
	}

	$type = sanitize_text_field( $_GET['opcache_toolkit_notice'] );

	/**
	 * Notice definitions
	 *
	 * Format:
	 *   key => [ css_class, message ]
	 */
	$messages = [

		// OPcache actions
		'opcache_cleared' => [
			'success',
			esc_html__( 'OPcache cleared successfully.', 'opcache-toolkit' ),
		],

		// Preload actions
		'preload_queued'  => [
			'success',
			esc_html__( 'OPcache preload has been queued and will run in the background.', 'opcache-toolkit' ),
		],
		'preload_done'    => [
			'success',
			esc_html__( 'OPcache has been preloaded successfully.', 'opcache-toolkit' ),
		],

		// Stats actions
		'stats_cleared'   => [
			'success',
			esc_html__( 'OPcache statistics have been cleared.', 'opcache-toolkit' ),
		],
		'stats_exported'  => [
			'success',
			esc_html__( 'OPcache statistics exported successfully.', 'opcache-toolkit' ),
		],

		// Debug
		'logged'          => [
			'success',
			esc_html__( 'Debug log entry recorded.', 'opcache-toolkit' ),
		],

		// Generic error
		'error'           => [
			'error',
			esc_html__( 'An error occurred while processing your request.', 'opcache-toolkit' ),
		],
	];

	if ( ! isset( $messages[ $type ] ) ) {
		return;
	}

	[$class, $text] = $messages[ $type ];

	printf(
		"<div class='notice notice-%s is-dismissible'><p>%s</p></div>",
		esc_attr( $class ),
		esc_html( $text )
	);
}
add_action( 'admin_notices', 'opcache_toolkit_render_admin_notices' );
