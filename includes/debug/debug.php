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
	\OPcacheToolkit\Plugin::logger()->log( (string) $message, 'debug' );
}
