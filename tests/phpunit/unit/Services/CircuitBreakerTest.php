<?php
/**
 * Unit tests for CircuitBreaker
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Unit\Services;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Services\CircuitBreaker;
use Brain\Monkey;
use phpmock\phpunit\PHPMock;

/**
 * Class CircuitBreakerTest
 */
class CircuitBreakerTest extends BaseTestCase {
	use PHPMock;

	/**
	 * Test that the circuit breaker allows execution when CLOSED.
	 */
	public function test_execute_allows_when_closed(): void {
		$cb = new CircuitBreaker( 'test', 2, 60 );

		$failures_reset = false;
		$state_closed = false;

		Monkey\Functions\when( 'get_option' )->alias( function( $key, $default ) {
			if ( $key === 'opcache_toolkit_cb_test_state' ) {
				return 'CLOSED';
			}
			return $default;
		} );

		Monkey\Functions\when( 'update_option' )->alias( function( $key, $val ) use ( &$failures_reset, &$state_closed ) {
			if ( $key === 'opcache_toolkit_cb_test_failures' && $val === 0 ) {
				$failures_reset = true;
			}
			if ( $key === 'opcache_toolkit_cb_test_state' && $val === 'CLOSED' ) {
				$state_closed = true;
			}
			return true;
		} );

		$result = $cb->execute( function() {
			return 'success';
		} );

		$this->assertEquals( 'success', $result );
		$this->assertTrue( $failures_reset );
		$this->assertTrue( $state_closed );
	}

	/**
	 * Test that the circuit breaker opens after threshold failures.
	 */
	public function test_execute_opens_after_failures(): void {
		$cb = new CircuitBreaker( 'test', 2, 60 );

		$state = 'CLOSED';
		$failures = 0;

		Monkey\Functions\when( 'get_option' )->alias( function( $key, $default ) use ( &$state, &$failures ) {
			if ( $key === 'opcache_toolkit_cb_test_state' ) {
				return $state;
			}
			if ( $key === 'opcache_toolkit_cb_test_failures' ) {
				return $failures;
			}
			return $default;
		} );

		Monkey\Functions\when( 'update_option' )->alias( function( $key, $val ) use ( &$state, &$failures ) {
			if ( $key === 'opcache_toolkit_cb_test_state' ) {
				$state = $val;
			}
			if ( $key === 'opcache_toolkit_cb_test_failures' ) {
				$failures = $val;
			}
			return true;
		} );

		// 1. First failure.
		try {
			$cb->execute( function() {
				throw new \Exception( 'fail' );
			} );
		} catch ( \Exception $e ) {
			$this->assertEquals( 'fail', $e->getMessage() );
		}

		$this->assertEquals( 1, $failures );
		$this->assertEquals( 'CLOSED', $state );

		// 2. Second failure should open the circuit.
		try {
			$cb->execute( function() {
				throw new \Exception( 'fail' );
			} );
		} catch ( \Exception $e ) {
			$this->assertEquals( 'fail', $e->getMessage() );
		}

		$this->assertEquals( 2, $failures );
		$this->assertEquals( 'OPEN', $state );
	}

	/**
	 * Test that the circuit breaker prevents execution when OPEN.
	 */
	public function test_execute_prevents_when_open(): void {
		$cb = new CircuitBreaker( 'test', 2, 60 );

		Monkey\Functions\when( 'get_option' )->alias( function( $key, $default ) {
			if ( $key === 'opcache_toolkit_cb_test_state' ) {
				return 'OPEN';
			}
			if ( $key === 'opcache_toolkit_cb_test_next_attempt' ) {
				return time() + 100;
			}
			return $default;
		} );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Circuit breaker for test is OPEN' );

		$cb->execute( function() {
			return 'should not run';
		} );
	}

	/**
	 * Test that the circuit breaker allows retry after timeout.
	 */
	public function test_execute_allows_retry_after_timeout(): void {
		$cb = new CircuitBreaker( 'test', 2, 60 );

		$state = 'OPEN';
		$failures_reset = false;

		Monkey\Functions\when( 'get_option' )->alias( function( $key, $default ) use ( &$state ) {
			if ( $key === 'opcache_toolkit_cb_test_state' ) {
				return $state;
			}
			if ( $key === 'opcache_toolkit_cb_test_next_attempt' ) {
				return time() - 1;
			}
			return $default;
		} );

		Monkey\Functions\when( 'update_option' )->alias( function( $key, $val ) use ( &$state, &$failures_reset ) {
			if ( $key === 'opcache_toolkit_cb_test_state' ) {
				$state = $val;
			}
			if ( $key === 'opcache_toolkit_cb_test_failures' && $val === 0 ) {
				$failures_reset = true;
			}
			return true;
		} );

		$result = $cb->execute( function() {
			return 'recovered';
		} );

		$this->assertEquals( 'recovered', $result );
		$this->assertEquals( 'CLOSED', $state );
		$this->assertTrue( $failures_reset );
	}
}
