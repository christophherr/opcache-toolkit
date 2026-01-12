<?php
/**
 * Logger Test.
 *
 * @package OPcacheToolkit\Tests\Unit
 */

namespace OPcacheToolkit\Tests\Unit\Services;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Services\Logger;
use Brain\Monkey;

/**
 * Class LoggerTest.
 */
class LoggerTest extends BaseTestCase {

	/**
	 * Test logger initialization.
	 *
	 * @return void
	 */
	public function test_initialization(): void {
		Monkey\Functions\when( 'wp_upload_dir' )
			->alias( function() {
				return [ 'basedir' => '/tmp' ];
			} );

		// Mock filesystem.
		$wp_filesystem = \Mockery::mock( \WP_Filesystem_Direct::class );
		$wp_filesystem->shouldReceive( 'is_dir' )->andReturn( false );
		$wp_filesystem->shouldReceive( 'mkdir' )->once();
		$wp_filesystem->shouldReceive( 'put_contents' )->twice();

		$GLOBALS['wp_filesystem'] = $wp_filesystem;

		$logger = new Logger();
		$this->assertInstanceOf( Logger::class, $logger );
	}

	/**
	 * Test logging a message.
	 *
	 * @return void
	 */
	public function test_log(): void {
		Monkey\Functions\when( 'wp_upload_dir' )
			->alias( function() {
				return [ 'basedir' => '/tmp' ];
			} );

		Monkey\Functions\when( 'current_time' )->justReturn( '2026-01-12 12:00:00' );
		Monkey\Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$wp_filesystem = \Mockery::mock( \WP_Filesystem_Direct::class );
		$wp_filesystem->shouldReceive( 'is_dir' )->andReturn( true );
		$wp_filesystem->shouldReceive( 'exists' )->andReturn( false );
		$wp_filesystem->shouldReceive( 'put_contents' )->once()->with(
			'/tmp/opcache-toolkit-logs/plugin.log',
			\Mockery::on( function( $content ) {
				return str_contains( $content, '[2026-01-12 12:00:00] [PHP] INFO: Test message' );
			} )
		);

		$GLOBALS['wp_filesystem'] = $wp_filesystem;

		$logger = new Logger();
		$logger->log( 'Test message' );
		$this->assertTrue( true );
	}
}
