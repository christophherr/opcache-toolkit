<?php
/**
 * Analytics REST Endpoint.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\REST;

use OPcacheToolkit\Plugin;

/**
 * Class AnalyticsEndpoint.
 *
 * Provides advanced cache analytics data.
 */
class AnalyticsEndpoint extends BaseEndpoint {

	/**
	 * Register the route.
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'opcache-toolkit/v1',
			'/analytics',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => [ $this, 'check_permission' ],
			]
		);
	}

	/**
	 * Handle the request.
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$check = $this->ensure_opcache_enabled();
		if ( true !== $check ) {
			return $check;
		}

		return new \WP_REST_Response(
			[
				'success'    => true,
				'prediction' => Plugin::stats()->get_memory_prediction(),
				'ghosts'     => Plugin::opcache()->get_ghost_scripts(),
				'groups'     => Plugin::opcache()->get_scripts_by_group(),
			]
		);
	}
}
