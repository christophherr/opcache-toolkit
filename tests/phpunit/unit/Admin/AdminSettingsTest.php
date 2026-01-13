<?php
/**
 * Unit tests for Admin Settings Helpers
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Unit\Admin;

use OPcacheToolkit\Tests\Unit\BaseTestCase;
use Brain\Monkey;

/**
 * Class AdminSettingsTest
 */
class AdminSettingsTest extends BaseTestCase {

	/**
	 * Set up the test.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Load the real functions.
		if ( ! defined( 'OPCACHE_TOOLKIT_PATH' ) ) {
			define( 'OPCACHE_TOOLKIT_PATH', dirname( __DIR__, 4 ) . DIRECTORY_SEPARATOR );
		}
		if ( ! function_exists( 'opcache_toolkit_get_setting' ) ) {
			require_once OPCACHE_TOOLKIT_PATH . 'includes/admin/admin-settings.php';
		}

		// Mock the WordPress functions they call.
		$options = &$this->options;
		Monkey\Functions\when( 'get_option' )->alias(
			function ( $n, $d = false ) use ( &$options ) {
				return $options[ $n ] ?? $d;
			}
		);
		Monkey\Functions\when( 'update_option' )->alias(
			function ( $n, $v ) use ( &$options ) {
				$options[ $n ] = $v;
				return true;
			}
		);
		Monkey\Functions\when( 'get_site_option' )->alias(
			function ( $n, $d = false ) use ( &$options ) {
				return $options[ $n ] ?? $d;
			}
		);
		Monkey\Functions\when( 'update_site_option' )->alias(
			function ( $n, $v ) use ( &$options ) {
				$options[ $n ] = $v;
				return true;
			}
		);
		Monkey\Functions\when( 'admin_url' )->alias(
			function ( $p = '' ) {
				return 'http://example.com/wp-admin/' . $p;
			}
		);
		Monkey\Functions\when( 'network_admin_url' )->alias(
			function ( $p = '' ) {
				return 'http://example.com/network-admin/' . $p;
			}
		);
	}

	/**
	 * Test opcache_toolkit_get_setting in single site.
	 */
	public function test_opcache_toolkit_get_setting_single_site(): void {
		$this->options['my_option'] = 'site_value';

		$result = \opcache_toolkit_get_setting( 'my_option', 'default' );
		$this->assertEquals( 'site_value', $result );
	}

	/**
	 * Test opcache_toolkit_get_setting default value.
	 */
	public function test_opcache_toolkit_get_setting_default(): void {
		$result = \opcache_toolkit_get_setting( 'non_existent', 'default' );
		$this->assertEquals( 'default', $result );
	}

	/**
	 * Test opcache_toolkit_update_setting in single site.
	 */
	public function test_opcache_toolkit_update_setting_single_site(): void {
		$result = \opcache_toolkit_update_setting( 'my_option', 'new_value' );
		$this->assertTrue( $result );
		$this->assertEquals( 'new_value', $this->options['my_option'] );
	}

	/**
	 * Test opcache_toolkit_admin_url in single site.
	 */
	public function test_opcache_toolkit_admin_url_single_site(): void {
		$result = \opcache_toolkit_admin_url( 'test-path' );
		$this->assertEquals( 'http://example.com/wp-admin/test-path', $result );
	}

	/**
	 * Test multisite behavior.
	 *
	 * @group multisite
	 */
	public function test_multisite_helpers(): void {
		if ( ! OPCACHE_TOOLKIT_IS_NETWORK ) {
			$this->markTestSkipped( 'OPCACHE_TOOLKIT_IS_NETWORK is false. Run with --group multisite and OPCACHE_TOOLKIT_TEST_MULTISITE=true.' );
		}

		$this->options['net_option'] = 'net_value';
		$this->assertEquals( 'net_value', \opcache_toolkit_get_setting( 'net_option' ) );

		\opcache_toolkit_update_setting( 'new_net_option', 'new_net_value' );
		$this->assertEquals( 'new_net_value', $this->options['new_net_option'] );

		$this->assertEquals( 'http://example.com/network-admin/test', \opcache_toolkit_admin_url( 'test' ) );
	}
}
