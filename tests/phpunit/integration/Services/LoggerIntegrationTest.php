<?php
/**
 * Integration tests for Logger service log management.
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Integration\Services;

use OPcacheToolkit\Plugin;
use WP_UnitTestCase;

/**
 * Class LoggerIntegrationTest
 */
class LoggerIntegrationTest extends WP_UnitTestCase {

	/**
	 * Test log file paths and directory.
	 */
	public function test_log_paths() {
		$logger = Plugin::logger();
		$dir    = $logger->get_log_dir();

		$this->assertStringContainsString( 'opcache-toolkit-logs', $dir );

		$php_log = $logger->get_log_file( 'php' );
		$this->assertStringEndsWith( 'plugin.log', $php_log );

		$js_log = $logger->get_log_file( 'js' );
		$this->assertStringEndsWith( 'js.log', $js_log );
	}

	/**
	 * Test log deletion.
	 */
	public function test_log_deletion() {
		$logger = Plugin::logger();
		$logger->log( 'Test message' );

		$php_log = $logger->get_log_file( 'php' );
		$fs      = $logger->get_filesystem();

		$this->assertTrue( $fs->exists( $php_log ) );

		$logger->delete_log( 'php' );

		$this->assertFalse( $fs->exists( $php_log ) );
	}
}
