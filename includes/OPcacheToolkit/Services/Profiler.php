<?php
/**
 * Profiler Service.
 *
 * Lightweight utility to measure operation durations and log via Logger.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\Services;

/**
 * Class Profiler.
 *
 * Provides simple start/end/measure patterns for performance profiling.
 */
class Profiler {

	/**
	 * Start a profiling timer.
	 *
	 * @param string $operation Operation name (e.g., 'OPcache Reset', 'Database Query').
	 * @param array  $context   Optional context (post_id, user_id, etc.).
	 * @return array Token to pass to end().
	 */
	public static function start( string $operation, array $context = array() ): array {
		return array(
			'operation' => $operation,
			'context'   => $context,
			'start'     => microtime( true ),
			'mem_start' => memory_get_usage( true ),
		);
	}

	/**
	 * End a profiling timer and log the result.
	 *
	 * @param array  $token   Token from start().
	 * @param array  $more    Extra context to merge.
	 * @param string $channel Optional channel (default 'Perf').
	 * @return void
	 */
	public static function end( array $token, array $more = array(), string $channel = 'Perf' ): void {
		$start     = isset( $token['start'] ) ? (float) $token['start'] : microtime( true );
		$mem_start = isset( $token['mem_start'] ) ? (int) $token['mem_start'] : 0;
		$operation = isset( $token['operation'] ) ? (string) $token['operation'] : 'unknown';
		$ctx       = isset( $token['context'] ) && is_array( $token['context'] ) ? $token['context'] : array();

		$duration  = ( microtime( true ) - $start ) * 1000; // Convert to milliseconds.
		$mem_end   = memory_get_usage( true );
		$mem_delta = $mem_end - $mem_start;
		$mem_peak  = memory_get_peak_usage( true );

		$context = array_merge(
			$ctx,
			$more,
			array(
				'ms'        => round( $duration, 2 ),
				'mem_start' => $mem_start,
				'mem_end'   => $mem_end,
				'mem_delta' => $mem_delta,
				'mem_peak'  => $mem_peak,
			)
		);

		// Use existing Logger if available, otherwise skip (graceful degradation).
		if ( class_exists( '\\OPcacheToolkit\\Plugin' ) ) {
			\OPcacheToolkit\Plugin::logger()->log(
				$operation,
				'debug',
				$context,
				$channel,
			);
		}
	}

	/**
	 * Convenience: measure a callable's duration and log.
	 *
	 * @param string   $operation Operation name.
	 * @param callable $func      Callable to execute.
	 * @param array    $context   Context for logging.
	 * @param string   $channel   Channel name.
	 * @throws \Throwable If $func() throws an exception.
	 * @return mixed Return value of $func().
	 */
	public static function measure( string $operation, callable $func, array $context = array(), string $channel = 'Perf' ) {
		$token = self::start( $operation, $context );

		try {
			$result = $func();
			self::end( $token, array( 'success' => true ), $channel );
			return $result;
		} catch ( \Throwable $e ) {
			self::end(
				$token,
				array(
					'success' => false,
					'error'   => $e->getMessage(),
					'file'    => $e->getFile(),
					'line'    => $e->getLine(),
				),
				$channel
			);
			throw $e;
		}
	}

	/**
	 * Helper: Get milliseconds elapsed since a start time.
	 *
	 * @param float $start Start time from microtime(true).
	 * @return float Elapsed milliseconds.
	 */
	public static function ms_since( float $start ): float {
		return round( ( microtime( true ) - $start ) * 1000, 2 );
	}
}
