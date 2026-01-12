<?php
/**
 * Circuit Breaker Service.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\Services;

/**
 * Class CircuitBreaker.
 *
 * Implements the Circuit Breaker pattern to prevent cascading failures.
 */
class CircuitBreaker {

	/**
	 * State constants.
	 */
	private const STATE_CLOSED    = 'CLOSED';
	private const STATE_OPEN      = 'OPEN';
	private const STATE_HALF_OPEN = 'HALF_OPEN';

	/**
	 * Storage key prefix.
	 */
	private const OPTION_PREFIX = 'opcache_toolkit_cb_';

	/**
	 * Configuration.
	 *
	 * @var int
	 */
	private int $threshold;

	/**
	 * Timeout
	 *
	 * @var int
	 */
	private int $timeout;

	/**
	 * Service name
	 *
	 * @var string
	 */
	private string $service_name;

	/**
	 * Constructor.
	 *
	 * @param string $service_name Unique name for the service being protected.
	 * @param int    $threshold    Number of failures before opening.
	 * @param int    $timeout      Time in seconds before attempting reset.
	 */
	public function __construct( string $service_name, int $threshold = 5, int $timeout = 60 ) {
		$this->service_name = $service_name;
		$this->threshold    = $threshold;
		$this->timeout      = $timeout;
	}

	/**
	 * Execute a callable through the circuit breaker.
	 *
	 * @param callable $callback The function to execute.
	 * @return mixed
	 *
	 * @throws \Exception If the circuit is open or execution fails.
	 * @throws \Throwable Propagates exceptions from the callback.
	 */
	public function execute( callable $callback ) {
		$state = $this->get_state();

		if ( self::STATE_OPEN === $state ) {
			if ( time() < $this->get_next_attempt() ) {
				throw new \Exception( esc_html( "Circuit breaker for {$this->service_name} is OPEN." ) );
			}
			$this->set_state( self::STATE_HALF_OPEN );
		}

		try {
			$result = $callback();
			$this->on_success();
			return $result;
		} catch ( \Throwable $e ) {
			$this->on_failure();
			throw $e;
		}
	}

	/**
	 * Record a success and close the circuit.
	 *
	 * @return void
	 */
	private function on_success(): void {
		$this->set_failure_count( 0 );
		$this->set_state( self::STATE_CLOSED );
	}

	/**
	 * Record a failure and potentially open the circuit.
	 *
	 * @return void
	 */
	private function on_failure(): void {
		$count = $this->get_failure_count() + 1;
		$this->set_failure_count( $count );

		if ( $count >= $this->threshold ) {
			$this->set_state( self::STATE_OPEN );
			$this->set_next_attempt( time() + $this->timeout );
		}
	}

	/**
	 * Get current state.
	 *
	 * @return string
	 */
	private function get_state(): string {
		return get_option( self::OPTION_PREFIX . $this->service_name . '_state', self::STATE_CLOSED );
	}

	/**
	 * Set current state.
	 *
	 * @param string $state New state.
	 *
	 * @return void
	 */
	private function set_state( string $state ): void {
		update_option( self::OPTION_PREFIX . $this->service_name . '_state', $state );
	}

	/**
	 * Get failure count.
	 *
	 * @return int
	 */
	private function get_failure_count(): int {
		return (int) get_option( self::OPTION_PREFIX . $this->service_name . '_failures', 0 );
	}

	/**
	 * Set failure count.
	 *
	 * @param int $count New failure count.
	 *
	 * @return void
	 */
	private function set_failure_count( int $count ): void {
		update_option( self::OPTION_PREFIX . $this->service_name . '_failures', $count );
	}

	/**
	 * Get next attempt timestamp.
	 *
	 * @return int
	 */
	private function get_next_attempt(): int {
		return (int) get_option( self::OPTION_PREFIX . $this->service_name . '_next_attempt', 0 );
	}

	/**
	 * Set next attempt timestamp.
	 *
	 * @param int $timestamp Timestamp for next attempt.
	 *
	 * @return void
	 */
	private function set_next_attempt( int $timestamp ): void {
		update_option( self::OPTION_PREFIX . $this->service_name . '_next_attempt', $timestamp );
	}
}
