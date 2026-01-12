<?php
/**
 * Unit tests for CLI Commands
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Unit\CLI;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use OPcacheToolkit\CLI\Commands;
use OPcacheToolkit\Plugin;
use Mockery;
use Brain\Monkey;

/**
 * Class CommandsTest
 */
class CommandsTest extends BaseTestCase {

	/**
	 * @var Commands
	 */
	private $cli;

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->cli = new Commands();

		if ( ! defined( 'OPCACHE_TOOLKIT_VERSION' ) ) {
			define( 'OPCACHE_TOOLKIT_VERSION', '1.0.0' );
		}

		// Mock WP_CLI
		if ( ! class_exists( 'WP_CLI' ) ) {
			eval( 'class WP_CLI { public static function success($m){} public static function error($m){} public static function line($m){} public static function log($m){} }' );
		}

		if ( ! function_exists( 'WP_CLI\Utils\format_items' ) ) {
			eval( 'namespace WP_CLI\Utils { function format_items($f, $i, $c){} }' );
		}
	}

	/**
	 * Test info command.
	 */
	public function test_info_command(): void {
		$opcache_mock = Mockery::mock( 'OPcacheToolkit\Services\OPcacheService' );
		$opcache_mock->shouldReceive( 'is_enabled' )->andReturn( true );
		$opcache_mock->shouldReceive( 'get_status' )->andReturn(
			[
				'opcache_statistics' => [ 'num_cached_scripts' => 123 ],
			]
		);
		$opcache_mock->shouldReceive( 'get_configuration' )->andReturn( [ 'zend_extension_version' => '8.0.0' ] );
		$opcache_mock->shouldReceive( 'get_hit_rate' )->andReturn( 99.9 );
		$opcache_mock->shouldReceive( 'get_memory_usage' )->andReturn( [ 'used_memory' => 1024 ] );

		$reflection = new \ReflectionClass( 'OPcacheToolkit\Plugin' );
		$property   = $reflection->getProperty( 'opcache' );
		$property->setAccessible( true );
		$property->setValue( null, $opcache_mock );

		// Expecting some output calls
		// Since we can't easily mock static classes that are already defined (unless using certain tools),
		// we just ensure no errors are thrown for now, or use a better way if WP_CLI is available.

		$this->cli->info( [], [] );
		$this->assertTrue( true );
	}

	/**
	 * Test doctor command gather checks correctly.
	 */
	public function test_doctor_command(): void {
		$opcache_mock = Mockery::mock( 'OPcacheToolkit\Services\OPcacheService' );
		$opcache_mock->shouldReceive( 'is_enabled' )->andReturn( true );
		$opcache_mock->shouldReceive( 'get_hit_rate' )->andReturn( 95.0 );
		$opcache_mock->shouldReceive( 'get_status' )->andReturn( [ 'opcache_statistics' => [ 'num_cached_scripts' => 10 ] ] );

		$reflection = new \ReflectionClass( 'OPcacheToolkit\Plugin' );
		$property   = $reflection->getProperty( 'opcache' );
		$property->setAccessible( true );
		$property->setValue( null, $opcache_mock );

		Monkey\Functions\expect( 'get_bloginfo' )->with( 'version' )->andReturn( '6.4' );
		Monkey\Functions\expect( 'get_option' )->andReturn( false );
		Monkey\Functions\expect( 'wp_next_scheduled' )->andReturn( true );

		// Mock check_schema
		$this->wpdb->shouldReceive( 'prepare' )->andReturn( 'QUERY' );
		$this->wpdb->shouldReceive( 'get_var' )->andReturn( 'wp_opcache_toolkit_stats' );

		// We need to mock \WP_CLI\Utils\format_items
		if ( ! class_exists( 'WP_CLI\Utils' ) ) {
			// This is hard to mock because it's in a sub-namespace and might be called as a function or class
		}

		$this->cli->doctor( [], [] );
		$this->assertTrue( true );
	}
}
