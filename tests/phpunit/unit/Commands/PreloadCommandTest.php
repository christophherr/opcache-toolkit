<?php
/**
 * Unit tests for PreloadCommand
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Unit\Commands;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Commands\PreloadCommand;
use OPcacheToolkit\Services\OPcacheService;
use Mockery;
use Brain\Monkey;

/**
 * Class PreloadCommandTest
 */
class PreloadCommandTest extends BaseTestCase {

	/**
	 * @var PreloadCommand
	 */
	private $command;

	/**
	 * @var OPcacheService|Mockery\MockInterface
	 */
	private $opcache;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->opcache = Mockery::mock( OPcacheService::class );
		$this->command = new PreloadCommand( $this->opcache );

		// Use global wp_filesystem initialized in BaseTestCase
		global $wp_filesystem;

		Monkey\Functions\expect( 'trailingslashit' )->zeroOrMoreTimes()->andReturnUsing( function( $path ) {
			return rtrim( $path, '/\\' ) . '/';
		} );
	}

	/**
	 * Test execute preloads files correctly.
	 */
	public function test_execute_preloads_files(): void {
		global $wp_filesystem;

		$this->opcache->shouldReceive( 'is_enabled' )->once()->andReturn( true );

		$path = '/app/includes';
		$wp_filesystem->shouldReceive( 'dirlist' )
			->once()
			->with( $path )
			->andReturn( [
				[ 'name' => 'file1.php', 'type' => 'f' ],
				[ 'name' => 'sub', 'type' => 'd' ],
			] );

		$wp_filesystem->shouldReceive( 'dirlist' )
			->once()
			->with( '/app/includes/sub' )
			->andReturn( [
				[ 'name' => 'file2.php', 'type' => 'f' ],
			] );

		$this->opcache->shouldReceive( 'compile_file' )
			->twice()
			->andReturn( true );

		$result = $this->command->execute( [ $path ] );

		$this->assertTrue( $result->success );
		$this->assertEquals( 2, $result->data['compiled_count'] );
	}
}
