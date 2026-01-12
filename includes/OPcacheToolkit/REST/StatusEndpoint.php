<?php
/**
 * Status REST Endpoint.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\REST;

use OPcacheToolkit\Services\OPcacheService;

/**
 * Class StatusEndpoint.
 *
 * Provides real-time OPcache status via REST API.
 */
class StatusEndpoint extends BaseEndpoint {

	/**
	 * OPcache service instance.
	 *
	 * @var OPcacheService
	 */
	private OPcacheService $opcache;

	/**
	 * StatusEndpoint constructor.
	 *
	 * @param OPcacheService $opcache OPcache service instance.
	 */
	public function __construct( OPcacheService $opcache ) {
		$this->opcache = $opcache;
	}

	/**
	 * Register the status route.
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'opcache-toolkit/v1',
			'/status',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'schema'              => [ $this, 'get_schema' ],
			]
		);
	}

	/**
	 * Handle the status request.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! $this->opcache->is_enabled() ) {
			return $this->error_response(
				'opcache_disabled',
				__( 'OPcache is not loaded or enabled on this server.', 'opcache-toolkit' ),
				503
			);
		}

		$status = $this->opcache->get_status( true );

		if ( null === $status ) {
			return $this->error_response(
				'opcache_status_failed',
				__( 'Failed to retrieve OPcache status.', 'opcache-toolkit' ),
				500
			);
		}

		return $this->success_response(
			[
				'opcache_enabled'    => true,
				'cache_full'         => $status['cache_full'] ?? false,
				'memory_usage'       => $status['memory_usage'] ?? [],
				'opcache_statistics' => $status['opcache_statistics'] ?? [],
			]
		);
	}

	/**
	 * Get the schema for the status endpoint.
	 *
	 * @return array
	 */
	public function get_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'opcache_status',
			'type'       => 'object',
			'properties' => [
				'opcache_enabled'    => [
					'type'        => 'boolean',
					'description' => __( 'Whether OPcache is enabled.', 'opcache-toolkit' ),
				],
				'cache_full'         => [
					'type'        => 'boolean',
					'description' => __( 'Whether the OPcache is full.', 'opcache-toolkit' ),
				],
				'memory_usage'       => [
					'type'        => 'object',
					'description' => __( 'OPcache memory usage statistics.', 'opcache-toolkit' ),
				],
				'opcache_statistics' => [
					'type'        => 'object',
					'description' => __( 'OPcache performance statistics.', 'opcache-toolkit' ),
				],
			],
		];
	}
}
