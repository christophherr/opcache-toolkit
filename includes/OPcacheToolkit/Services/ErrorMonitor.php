<?php
/**
 * ErrorMonitor Service.
 *
 * Intercepts PHP errors and exceptions, mirroring them into plugin logs.
 * Designed to be safe and minimal: does not interfere with WordPress' own
 * error logging (debug.log/php_errorlog). We only mirror for convenience.
 *
 * Enable/disable via OPCACHE_TOOLKIT_CAPTURE_PHP_ERRORS constant (default true).
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\Services;

/**
 * Class ErrorMonitor.
 *
 * Captures PHP errors, warnings, notices, and fatal errors for logging.
 */
class ErrorMonitor {

	/**
	 * Initialized flag.
	 *
	 * @var bool
	 */
	private static bool $initialized = false;

	/**
	 * Initialize error handlers once.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}
		self::$initialized = true;

		$enabled = defined( 'OPCACHE_TOOLKIT_CAPTURE_PHP_ERRORS' )
			? (bool) OPCACHE_TOOLKIT_CAPTURE_PHP_ERRORS
			: true;

		if ( ! $enabled ) {
			return;
		}

		// Set custom handlers that mirror to Logger and then defer to PHP's internal handling by returning false.
		set_error_handler( array( __CLASS__, 'handle_error' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Custom error viewer.
		set_exception_handler( array( __CLASS__, 'handle_exception' ) );
		register_shutdown_function( array( __CLASS__, 'handle_shutdown' ) );
	}

	/**
	 * Error handler: mirror notices/warnings/errors to Logger and allow default behavior by returning false.
	 *
	 * @param  int         $errno   Error number.
	 * @param  string      $errstr  Error message.
	 * @param  string|null $errfile Error file.
	 * @param  int|null    $errline Error line.
	 * @return bool
	 */
	public static function handle_error( int $errno, string $errstr, ?string $errfile = null, ?int $errline = null ): bool {
		$severity = self::severity_label( $errno );
		$context  = array(
			'type'  => $severity,
			'code'  => $errno,
			'file'  => (string) $errfile,
			'line'  => (int) $errline,
			'trace' => self::safe_trace(),
		);

		// Use existing Logger if available.
		if ( class_exists( '\\OPcacheToolkit\\Plugin' ) ) {
			\OPcacheToolkit\Plugin::logger()->log(
				$errstr,
				'error',
				$context,
				'php'
			);
		}

		// Returning false lets PHP/WordPress continue its normal error handling, including writing to debug.log.
		return false;
	}

	/**
	 * Exception handler: log uncaught exceptions.
	 *
	 * @param \Throwable $e Exception.
	 * @return void
	 */
	public static function handle_exception( \Throwable $e ): void {
		$context = array(
			'type'  => 'EXCEPTION',
			'class' => get_class( $e ),
			'file'  => $e->getFile(),
			'line'  => $e->getLine(),
			'code'  => $e->getCode(),
			'trace' => self::safe_trace( $e->getTrace() ),
		);

		// Use existing Logger if available.
		if ( class_exists( '\\OPcacheToolkit\\Plugin' ) ) {
			\OPcacheToolkit\Plugin::logger()->log(
				$e->getMessage(),
				'error',
				$context,
				'php'
			);
		}

		// Let default handler run if display_errors etc. (cannot rethrow here). Nothing else to do.
	}

	/**
	 * Shutdown handler: capture fatal errors that bypass set_error_handler.
	 *
	 * @return void
	 */
	public static function handle_shutdown(): void {
		$last = error_get_last();
		if ( is_array( $last ) && isset( $last['type'] ) && self::is_fatal( (int) $last['type'] ) ) {
			$context = array(
				'type' => self::severity_label( (int) $last['type'] ),
				'file' => (string) ( $last['file'] ?? '' ),
				'line' => (int) ( $last['line'] ?? 0 ),
			);

			// Use existing Logger if available.
			if ( class_exists( '\\OPcacheToolkit\\Plugin' ) ) {
				\OPcacheToolkit\Plugin::logger()->log(
					(string) ( $last['message'] ?? 'Fatal error' ),
					'error',
					$context,
					'php'
				);
			}
		}
	}

	/**
	 * Check if error type is fatal.
	 *
	 * @param int $type Error type.
	 * @return bool
	 */
	private static function is_fatal( int $type ): bool {
		return in_array( $type, array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true );
	}

	/**
	 * Convert error number to severity label.
	 *
	 * @param int $errno Error number.
	 * @return string
	 */
	private static function severity_label( int $errno ): string {
		return match ( $errno ) {
			E_ERROR, E_USER_ERROR, E_CORE_ERROR, E_COMPILE_ERROR => 'ERROR',
			E_WARNING, E_USER_WARNING, E_COMPILE_WARNING => 'WARNING',
			E_NOTICE, E_USER_NOTICE => 'NOTICE',
			E_DEPRECATED, E_USER_DEPRECATED => 'DEPRECATED',
			default => 'INFO',
		};
	}

	/**
	 * Return a sanitized backtrace without huge args/objects.
	 *
	 * @param array|null $trace Backtrace.
	 * @return array
	 */
	private static function safe_trace( ?array $trace = null ): array {
		$trace = $trace ?? debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace -- Custom error viewer.
		$clean = array();

		foreach ( $trace as $frame ) {
			$clean[] = array(
				'file'  => isset( $frame['file'] ) ? (string) $frame['file'] : null,
				'line'  => isset( $frame['line'] ) ? (int) $frame['line'] : null,
				'fn'    => isset( $frame['function'] ) ? (string) $frame['function'] : null,
				'class' => isset( $frame['class'] ) ? (string) $frame['class'] : null,
			);
		}

		return $clean;
	}
}
