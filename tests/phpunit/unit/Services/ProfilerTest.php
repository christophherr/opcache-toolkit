<?php
/**
 * Unit tests for Profiler
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Unit\Services;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Services\Profiler;
use OPcacheToolkit\Plugin;
use Mockery;
use Brain\Monkey;

/**
 * Class ProfilerTest
 */
class ProfilerTest extends BaseTestCase {

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'OPCACHE_TOOLKIT_PATH' ) ) {
			define( 'OPCACHE_TOOLKIT_PATH', dirname( __DIR__, 3 ) . DIRECTORY_SEPARATOR );
		}

		if ( ! function_exists( 'opcache_toolkit_user_can_manage_opcache' ) ) {
			require_once OPCACHE_TOOLKIT_PATH . 'opcache-toolkit.php';
		}
	}

	/**
	 * Test start returns valid token.
	 */
	public function test_start_returns_valid_token(): void {
		$token = Profiler::start( 'Test Op', [ 'key' => 'val' ] );

		$this->assertIsArray( $token );
		$this->assertEquals( 'Test Op', $token['operation'] );
		$this->assertEquals( [ 'key' => 'val' ], $token['context'] );
		$this->assertArrayHasKey( 'start', $token );
		$this->assertArrayHasKey( 'mem_start', $token );
	}

	/**
	 * test_measure_logs_success
	 */
	public function test_measure_logs_success(): void {
		$logger = Mockery::mock( 'OPcacheToolkit\Services\Logger' );
		$logger->shouldReceive( 'log' )->once()->with(
			'Test Op',
			'debug',
			Mockery::on( function( $context ) {
				return isset( $context['ms'] ) && isset( $context['success'] ) && $context['success'] === true;
			} ),
			'Perf'
		);

		// Inject into Plugin singleton
		$reflection = new \ReflectionClass( Plugin::class );
		$property   = $reflection->getProperty( 'logger' );
		$property->setAccessible( true );
		$property->setValue( null, $logger );

		$result = Profiler::measure( 'Test Op', function() {
			return 'success';
		} );

		$this->assertEquals( 'success', $result );

		// Reset singleton
		$property->setValue( null, null );
	}

	/**
	 * test_measure_logs_error_and_rethrows
	 */
	public function test_measure_logs_error_and_rethrows(): void {
		$logger = Mockery::mock( 'OPcacheToolkit\Services\Logger' );
		$logger->shouldReceive( 'log' )->once()->with(
			'Fail Op',
			'debug',
			Mockery::on( function( $context ) {
				return isset( $context['success'] ) && $context['success'] === false && $context['error'] === 'Oops';
			} ),
			'Perf'
		);

		// Inject into Plugin singleton
		$reflection = new \ReflectionClass( Plugin::class );
		$property   = $reflection->getProperty( 'logger' );
		$property->setAccessible( true );
		$property->setValue( null, $logger );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Oops' );

		try {
			Profiler::measure( 'Fail Op', function() {
				throw new \Exception( 'Oops' );
			} );
		} finally {
			// Reset singleton
			$property->setValue( null, null );
		}
	}
}
