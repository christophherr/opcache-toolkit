<?php
/**
 * Integration tests for REST Endpoints.
 *
 * @package OPcacheToolkit
 */

namespace OPcacheToolkit\Tests\Integration\REST;

use OPcacheToolkit\Plugin;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Class RESTIntegrationTest.
 */
class RESTIntegrationTest extends WP_UnitTestCase {

	/**
	 * Set up the test.
	 */
	public function set_up() {
		parent::set_up();

		// Reset Plugin singleton.
		$reflection = new \ReflectionClass( Plugin::class );
		$instance = $reflection->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		$opcache_prop = $reflection->getProperty( 'opcache' );
		$opcache_prop->setAccessible( true );
		$opcache_prop->setValue( null, null );

		// Mock OPcache.
		$opcache = $this->createMock( \OPcacheToolkit\Services\OPcacheService::class );
		$opcache->method( 'is_enabled' )->willReturn( true );
		$opcache->method( 'get_status' )->willReturn( [
			'cache_full'         => false,
			'memory_usage'       => [],
			'opcache_statistics' => [],
		] );
		Plugin::set_opcache( $opcache );

		// Reset and initialize REST API.
		global $wp_rest_server;
		$wp_rest_server = null;

		add_action( 'rest_api_init', [ Plugin::instance(), 'register_rest_endpoints' ], 5 );
		do_action( 'rest_api_init' );

		// Create an admin user.
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
	}

	/**
	 * Tear down the test.
	 */
	public function tear_down() {
		// Reset Plugin static properties.
		$reflection = new \ReflectionClass( Plugin::class );
		foreach ( [ 'instance', 'opcache', 'stats', 'logger' ] as $prop ) {
			$property = $reflection->getProperty( $prop );
			$property->setAccessible( true );
			$property->setValue( null, null );
		}

		parent::tear_down();
	}

	/**
	 * Test GET /opcache-toolkit/v1/status
	 */
	public function test_get_status_endpoint() {
		$request = new WP_REST_Request( 'GET', '/opcache-toolkit/v1/status' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'opcache_enabled', $data['data'] );
	}

	/**
	 * Test GET /opcache-toolkit/v1/chart-data
	 */
	public function test_get_chart_data_endpoint() {
		// Seed some data.
		Plugin::stats()->insert( [
			'recorded_at'    => current_time( 'mysql' ),
			'hit_rate'       => 95.0,
			'cached_scripts' => 100,
			'wasted_memory'  => 0,
		] );

		$request = new WP_REST_Request( 'GET', '/opcache-toolkit/v1/chart-data' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();

		$this->assertTrue( $data['success'] );
		$this->assertCount( 1, $data['data']['labels'] );
	}

	/**
	 * Test POST /opcache-toolkit/v1/reset (Access Denied without Nonce if check_permission checks it)
	 * Note: Our BaseEndpoint checks nonce in handle(), not permission_callback.
	 */
	public function test_post_reset_fails_without_nonce() {
		$request = new WP_REST_Request( 'POST', '/opcache-toolkit/v1/reset' );
		$response = rest_get_server()->dispatch( $request );

		// Should fail with 403 Forbidden due to missing/invalid nonce in handle().
		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test POST /opcache-toolkit/v1/preload
	 */
	public function test_post_preload_fails_without_nonce() {
		$request = new WP_REST_Request( 'POST', '/opcache-toolkit/v1/preload' );
		$request->set_param( 'directories', [ '/tmp' ] );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}

	/**
	 * Test GET /opcache-toolkit/v1/analytics
	 */
	public function test_get_analytics_endpoint() {
		$request = new WP_REST_Request( 'GET', '/opcache-toolkit/v1/analytics' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
	}
}
