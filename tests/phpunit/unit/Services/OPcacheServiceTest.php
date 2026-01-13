<?php
/**
 * Unit tests for OPcacheService
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Unit\Services;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\Services\OPcacheService;
use phpmock\phpunit\PHPMock;

/**
 * Class OPcacheServiceTest
 */
class OPcacheServiceTest extends BaseTestCase {
	use PHPMock;

	/**
	 * @var OPcacheService
	 */
	private $service;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = new OPcacheService();
	}

	/**
	 * Test is_enabled returns true when opcache_get_status exists and returns status.
	 */
	public function test_is_enabled_true(): void {
		$function_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'function_exists' );
		$function_exists->expects( $this->any() )->with( 'opcache_get_status' )->willReturn( true );

		$get_status = $this->getFunctionMock( 'OPcacheToolkit\Services', 'opcache_get_status' );
		$get_status->expects( $this->any() )->willReturn( [ 'opcache_enabled' => true ] );

		$this->assertTrue( $this->service->is_enabled() );
	}

	/**
	 * Test is_enabled returns false when opcache_get_status returns false.
	 */
	public function test_is_enabled_false(): void {
		$function_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'function_exists' );
		$function_exists->expects( $this->any() )->with( 'opcache_get_status' )->willReturn( true );

		$get_status = $this->getFunctionMock( 'OPcacheToolkit\Services', 'opcache_get_status' );
		$get_status->expects( $this->any() )->willReturn( false );

		$this->assertFalse( $this->service->is_enabled() );
	}

	/**
	 * Test get_status returns array when successful.
	 */
	public function test_get_status_returns_array(): void {
		$function_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'function_exists' );
		$function_exists->expects( $this->any() )->with( 'opcache_get_status' )->willReturn( true );

		$status_data = [ 'opcache_enabled' => true ];
		$get_status  = $this->getFunctionMock( 'OPcacheToolkit\Services', 'opcache_get_status' );
		$get_status->expects( $this->any() )->with( true )->willReturn( $status_data );

		$this->assertEquals( $status_data, $this->service->get_status( true ) );
	}

	/**
	 * Test reset calls opcache_reset.
	 */
	public function test_reset_calls_function(): void {
		$function_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'function_exists' );
		$function_exists->expects( $this->any() )->with( 'opcache_reset' )->willReturn( true );

		$reset = $this->getFunctionMock( 'OPcacheToolkit\Services', 'opcache_reset' );
		$reset->expects( $this->any() )->willReturn( true );

		$this->assertTrue( $this->service->reset() );
	}

	/**
	 * Test compile_file calls opcache_compile_file.
	 */
	public function test_compile_file_calls_function(): void {
		$path = '/tmp/test.php';

		$function_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'function_exists' );
		$function_exists->expects( $this->any() )->with( 'opcache_compile_file' )->willReturn( true );

		$file_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'file_exists' );
		$file_exists->expects( $this->any() )->with( $path )->willReturn( true );

		$compile = $this->getFunctionMock( 'OPcacheToolkit\Services', 'opcache_compile_file' );
		$compile->expects( $this->any() )->with( $path )->willReturn( true );

		$this->assertTrue( $this->service->compile_file( $path ) );
	}

	/**
	 * Test get_hit_rate returns float.
	 */
	public function test_get_hit_rate(): void {
		$function_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'function_exists' );
		$function_exists->expects( $this->any() )->with( 'opcache_get_status' )->willReturn( true );

		$get_status = $this->getFunctionMock( 'OPcacheToolkit\Services', 'opcache_get_status' );
		$get_status->expects( $this->any() )->willReturn(
			[
				'opcache_statistics' => [
					'opcache_hit_rate' => 98.5,
				],
			]
		);

		$this->assertEquals( 98.5, $this->service->get_hit_rate() );
	}

	/**
	 * Test get_cached_scripts returns script array.
	 */
	public function test_get_cached_scripts(): void {
		$function_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'function_exists' );
		$function_exists->expects( $this->any() )->with( 'opcache_get_status' )->willReturn( true );

		$scripts    = [ '/path/to/script.php' => [] ];
		$get_status = $this->getFunctionMock( 'OPcacheToolkit\Services', 'opcache_get_status' );
		$get_status->expects( $this->any() )->with( true )->willReturn( [ 'scripts' => $scripts ] );

		$this->assertEquals( $scripts, $this->service->get_cached_scripts() );
	}

	/**
	 * Test get_ghost_scripts detects missing files.
	 */
	public function test_get_ghost_scripts(): void {
		$function_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'function_exists' );
		$function_exists->expects( $this->any() )->with( 'opcache_get_status' )->willReturn( true );

		$scripts = [
			'/path/exists.php'  => [ 'hits' => 10 ],
			'/path/missing.php' => [ 'hits' => 5 ],
		];

		$get_status = $this->getFunctionMock( 'OPcacheToolkit\Services', 'opcache_get_status' );
		$get_status->expects( $this->any() )->with( true )->willReturn( [ 'scripts' => $scripts ] );

		$file_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'file_exists' );
		$file_exists->expects( $this->exactly( 2 ) )
			->willReturnMap(
				[
					[ '/path/exists.php', true ],
					[ '/path/missing.php', false ],
				]
			);

		$ghosts = $this->service->get_ghost_scripts();

		$this->assertArrayHasKey( '/path/missing.php', $ghosts );
		$this->assertArrayNotHasKey( '/path/exists.php', $ghosts );
		$this->assertEquals( 5, $ghosts['/path/missing.php']['hits'] );
	}

	/**
	 * Test get_scripts_by_group categorizes and sorts scripts.
	 */
	public function test_get_scripts_by_group(): void {
		$function_exists = $this->getFunctionMock( 'OPcacheToolkit\Services', 'function_exists' );
		$function_exists->expects( $this->any() )->with( 'opcache_get_status' )->willReturn( true );

		$scripts = [
			'/wp-content/plugins/plugin-a/plugin.php' => [ 'memory_consumption' => 1000, 'hits' => 10 ],
			'/wp-content/plugins/plugin-b/plugin.php' => [ 'memory_consumption' => 2000, 'hits' => 5 ],
			'/wp-content/themes/theme-a/style.php'    => [ 'memory_consumption' => 500, 'hits' => 2 ],
			'/wp-includes/functions.php'              => [ 'memory_consumption' => 3000, 'hits' => 20 ],
			'/other/file.php'                         => [ 'memory_consumption' => 100, 'hits' => 1 ],
		];

		$get_status = $this->getFunctionMock( 'OPcacheToolkit\Services', 'opcache_get_status' );
		$get_status->expects( $this->any() )->with( true )->willReturn( [ 'scripts' => $scripts ] );

		$groups = $this->service->get_scripts_by_group();

		// Should be sorted by memory descending.
		$keys = array_keys( $groups );
		$this->assertEquals( 'core:includes', $keys[0] );
		$this->assertEquals( 'plugin:plugin-b', $keys[1] );
		$this->assertEquals( 'plugin:plugin-a', $keys[2] );
		$this->assertEquals( 'theme:theme-a', $keys[3] );
		$this->assertEquals( 'other', $keys[4] );

		$this->assertEquals( 3000, $groups['core:includes']['memory'] );
		$this->assertEquals( 20, $groups['core:includes']['hits'] );
		$this->assertEquals( 1, $groups['core:includes']['count'] );
	}
}
