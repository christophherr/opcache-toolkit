<?php
/**
 * Logger Service.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\Services;

/**
 * Class Logger.
 *
 * Handles structured file-based logging for the plugin.
 */
class Logger {

	/**
	 * Log directory.
	 *
	 * @var string
	 */
	private string $log_dir;

	/**
	 * Maximum log file size (5MB) before rotation.
	 *
	 * @var int
	 */
	private const MAX_LOG_SIZE = 5242880;

	/**
	 * Logger constructor.
	 */
	public function __construct() {
		$this->init_log_dir();
	}

	/**
	 * Initialize log directory using WP_Filesystem.
	 *
	 * @return void
	 */
	private function init_log_dir(): void {
		$upload_dir    = wp_upload_dir();
		$this->log_dir = $upload_dir['basedir'] . '/opcache-toolkit-logs';

		$wp_filesystem = $this->get_filesystem();
		if ( $wp_filesystem && ! $wp_filesystem->is_dir( $this->log_dir ) ) {
			$wp_filesystem->mkdir( $this->log_dir );

			// Prevent directory listing.
			$wp_filesystem->put_contents(
				$this->log_dir . '/index.php',
				'<?php // Silence is golden.'
			);

			// Deny direct access.
			$wp_filesystem->put_contents(
				$this->log_dir . '/.htaccess',
				"Order deny,allow\nDeny from all\n"
			);
		}
	}

	/**
	 * Log a message.
	 *
	 * @param string $message The message to log.
	 * @param string $level   The log level (info, error, warning, debug).
	 * @param array  $context Additional data to log.
	 * @param string $source  Log source (php or js).
	 * @return void
	 */
	public function log( string $message, string $level = 'info', array $context = [], string $source = 'php' ): void {
		$level = strtoupper( $level );

		// Skip DEBUG level logs unless WP_DEBUG is enabled or debug mode is on.
		if ( 'DEBUG' === $level && ! $this->is_debug_enabled() ) {
			return;
		}

		// Build log entry.
		$log_entry = sprintf(
			"[%s] [%s] %s: %s (User: %s, Memory: %s)\n",
			current_time( 'Y-m-d H:i:s' ),
			strtoupper( $source ),
			$level,
			$message,
			get_current_user_id() ? get_current_user_id() : 'guest',
			'php' === $source ? round( memory_get_usage() / 1024 / 1024, 2 ) . 'MB' : 'N/A'
		);

		// Add context if provided.
		if ( ! empty( $context ) ) {
			$log_entry .= 'Context: ' . wp_json_encode( $context, JSON_PRETTY_PRINT ) . "\n";
		}

		// Add stack trace for errors (when debug enabled).
		if ( $this->is_debug_enabled() && 'ERROR' === $level && 'php' === $source ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_wp_debug_backtrace_summary
			$log_entry .= 'Stack Trace: ' . wp_debug_backtrace_summary() . "\n";
		}

		$log_entry .= str_repeat( '-', 80 ) . "\n";

		$this->write_to_file( $log_entry, $source );
	}

	/**
	 * Write log entry to file.
	 *
	 * @param string $entry  The log entry to write.
	 * @param string $source Log source.
	 * @return void
	 */
	private function write_to_file( string $entry, string $source ): void {
		$filename = ( 'js' === $source ) ? 'js.log' : 'plugin.log';
		$file     = $this->log_dir . '/' . $filename;

		$wp_filesystem = $this->get_filesystem();
		if ( ! $wp_filesystem ) {
			return;
		}

		// Rotate file if it exceeds MAX_LOG_SIZE.
		if ( $wp_filesystem->exists( $file ) && $wp_filesystem->size( $file ) > self::MAX_LOG_SIZE ) {
			$wp_filesystem->move( $file, $file . '.bak', true );
		}

		// Append to file.
		$existing_content = $wp_filesystem->exists( $file ) ? $wp_filesystem->get_contents( $file ) : '';
		$wp_filesystem->put_contents( $file, $existing_content . $entry );
	}

	/**
	 * Check if debug mode is enabled.
	 *
	 * @return bool
	 */
	public function is_debug_enabled(): bool {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return true;
		}

		return (bool) get_option( 'opcache_toolkit_debug_mode', false );
	}

	/**
	 * Get the log directory path.
	 *
	 * @return string
	 */
	public function get_log_dir(): string {
		return $this->log_dir;
	}

	/**
	 * Get a log file path.
	 *
	 * @param string $source Log source (php or js).
	 * @return string
	 */
	public function get_log_file( string $source = 'php' ): string {
		$filename = ( 'js' === $source ) ? 'js.log' : 'plugin.log';
		return $this->log_dir . '/' . $filename;
	}

	/**
	 * Delete a log file.
	 *
	 * @param string $source Log source.
	 * @return bool
	 */
	public function delete_log( string $source = 'php' ): bool {
		$file          = $this->get_log_file( $source );
		$wp_filesystem = $this->get_filesystem();
		if ( $wp_filesystem && $wp_filesystem->exists( $file ) ) {
			return $wp_filesystem->delete( $file );
		}
		return false;
	}

	/**
	 * Get WordPress filesystem object.
	 *
	 * @return object|null
	 */
	public function get_filesystem(): ?object {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem ? $wp_filesystem : null;
	}

	/**
	 * Cleanup old log files.
	 *
	 * @param int $retention_days Number of days to keep logs.
	 * @return void
	 */
	public function cleanup( int $retention_days = 30 ): void {
		$wp_filesystem = $this->get_filesystem();
		if ( ! $wp_filesystem || ! $wp_filesystem->is_dir( $this->log_dir ) ) {
			return;
		}

		$files = $wp_filesystem->dirlist( $this->log_dir );
		if ( ! $files ) {
			return;
		}

		$threshold = time() - ( $retention_days * DAY_IN_SECONDS );

		foreach ( $files as $file ) {
			if ( 'index.php' === $file['name'] || '.htaccess' === $file['name'] ) {
				continue;
			}

			if ( $file['lastmodunix'] < $threshold ) {
				$wp_filesystem->delete( $this->log_dir . '/' . $file['name'] );
			}
		}
	}
}
