<?php
/**
 * Unit tests for ErrorMonitor
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Unit\Services;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Services\ErrorMonitor;
use OPcacheToolkit\Plugin;
use Mockery;

/**
 * Class ErrorMonitorTest
 */
class ErrorMonitorTest extends BaseTestCase {

	/**
	 * test_handle_error_logs_and_returns_false
	 */
	public function test_handle_error_logs_and_returns_false(): void {
		$logger = Mockery::mock( 'OPcacheToolkit\Services\Logger' );
		$logger->shouldReceive( 'log' )->once()->with(
			'Test error',
			'error',
			Mockery::on( function( $context ) {
				return $context['code'] === E_WARNING && $context['file'] === 'test.php';
			} ),
			'php'
		);

		// Inject into Plugin singleton
		$reflection = new \ReflectionClass( Plugin::class );
		$property   = $reflection->getProperty( 'logger' );
		$property->setAccessible( true );
		$property->setValue( null, $logger );

		$result = ErrorMonitor::handle_error( E_WARNING, 'Test error', 'test.php', 10 );

		$this->assertFalse( $result );

		// Reset singleton
		$property->setValue( null, null );
	}

	/**
	 * test_handle_exception_logs
	 */
	public function test_handle_exception_logs(): void {
		$logger = Mockery::mock( 'OPcacheToolkit\Services\Logger' );
		$logger->shouldReceive( 'log' )->once()->with(
			'Test exception',
			'error',
			Mockery::on( function( $context ) {
				return $context['type'] === 'EXCEPTION' && $context['class'] === 'Exception';
			} ),
			'php'
		);

		// Inject into Plugin singleton
		$reflection = new \ReflectionClass( Plugin::class );
		$property   = $reflection->getProperty( 'logger' );
		$property->setAccessible( true );
		$property->setValue( null, $logger );

		ErrorMonitor::handle_exception( new \Exception( 'Test exception' ) );

		$this->addToAssertionCount( 1 );

		// Reset singleton
		$property->setValue( null, null );
	}
}
