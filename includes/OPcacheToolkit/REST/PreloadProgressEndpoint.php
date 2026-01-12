<?php
/**
 * Preload Progress REST Endpoint.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\REST;

/**
 * Class PreloadProgressEndpoint.
 *
 * Provides real-time preloading progress.
 */
class PreloadProgressEndpoint extends BaseEndpoint {

	/**
	 * Register the route.
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'opcache-toolkit/v1',
			'/preload-progress',
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

		$progress = get_option(
			'opcache_toolkit_preload_progress',
			[
				'total' => 0,
				'done'  => 0,
			]
		);

		return $this->success_response( $progress );
	}
}
