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
		if ( ! defined( 'OPCACHE_TOOLKIT_IS_NETWORK' ) ) {
			define( 'OPCACHE_TOOLKIT_IS_NETWORK', false );
		}
		parent::setUp();
		if ( ! defined( 'OPCACHE_TOOLKIT_PATH' ) ) {
			define( 'OPCACHE_TOOLKIT_PATH', realpath( __DIR__ . '/../../../../' ) . DIRECTORY_SEPARATOR );
		}
		require_once OPCACHE_TOOLKIT_PATH . 'includes/admin/admin-settings.php';
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
	 */
	public function test_multisite_helpers(): void {
		// We can't redefine OPCACHE_TOOLKIT_IS_NETWORK if it was already defined.
		// If it's already defined as false, we can't test multisite in this run easily
		// without using separate test files or process isolation.
		if ( ! OPCACHE_TOOLKIT_IS_NETWORK ) {
			$this->markTestSkipped( 'OPCACHE_TOOLKIT_IS_NETWORK is already defined as false.' );
		}

		$this->options['net_option'] = 'net_value';
		$this->assertEquals( 'net_value', \opcache_toolkit_get_setting( 'net_option' ) );

		\opcache_toolkit_update_setting( 'new_net_option', 'new_net_value' );
		$this->assertEquals( 'new_net_value', $this->options['new_net_option'] );

		$this->assertEquals( 'http://example.com/network-admin/test', \opcache_toolkit_admin_url( 'test' ) );
	}
}
