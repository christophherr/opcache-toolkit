<?php
/**
 * Debug logging for OPcache Toolkit.
 *
 * Provides a lightweight file-based logger that writes entries
 * to wp-content/opcache-manager-debug.log when WP_DEBUG is enabled.
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access.
}

/**
 * Write a message to the OPcache Toolkit debug log.
 *
 * This function is intentionally silent when WP_DEBUG is disabled,
 * ensuring no accidental logging in production environments.
 *
 * @param string $message The message to log.
 * @return void
 */
function opcache_toolkit_log( $message ) {

	// Only log when WP_DEBUG is enabled
	if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
		return;
	}

	// Ensure message is a string
	$message = (string) $message;

	// Log file location
	$file = WP_CONTENT_DIR . '/opcache-manager-debug.log';

	// Timestamped entry
	$timestamp = date( 'Y-m-d H:i:s' );
	$entry     = "[$timestamp] $message\n";

	// Write to log
	error_log( $entry, 3, $file );
}
