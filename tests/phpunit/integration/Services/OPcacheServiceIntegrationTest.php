<?php
/**
 * Integration tests for OPcacheService.
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Integration\Services;

use OPcacheToolkit\Services\OPcacheService;
use WP_UnitTestCase;

/**
 * Class OPcacheServiceIntegrationTest.
 */
class OPcacheServiceIntegrationTest extends WP_UnitTestCase {

	/**
	 * @var OPcacheService
	 */
	private $service;

	/**
	 * Set up the test.
	 */
	public function set_up() {
		parent::set_up();
		$this->service = new OPcacheService();
	}

	/**
	 * Test is_enabled().
	 * Note: In a CLI environment (like tests), OPcache might be disabled.
	 */
	public function test_is_enabled() {
		$enabled = $this->service->is_enabled();
		$this->assertIsBool( $enabled );
	}

	/**
	 * Test get_status().
	 */
	public function test_get_status() {
		$status = $this->service->get_status();
		if ( null !== $status ) {
			$this->assertIsArray( $status );
			$this->assertArrayHasKey( 'opcache_enabled', $status );
		} else {
			$this->assertNull( $status );
		}
	}

	/**
	 * Test get_scripts_by_group().
	 */
	public function test_get_scripts_by_group() {
		$groups = $this->service->get_scripts_by_group();
		$this->assertIsArray( $groups );

		// If there are cached scripts, verify group keys.
		if ( ! empty( $groups ) ) {
			$first_group = reset( $groups );
			$this->assertArrayHasKey( 'count', $first_group );
			$this->assertArrayHasKey( 'memory', $first_group );
		}
	}
}
