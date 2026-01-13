<?php
/**
 * Log REST Endpoint.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\REST;

use OPcacheToolkit\Plugin;

/**
 * Class LogEndpoint.
 *
 * Handles receiving logs from the JavaScript frontend.
 */
class LogEndpoint extends BaseEndpoint {

	/**
	 * Register the route.
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'opcache-toolkit/v1',
			'/log',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'logs' => [
						'type'     => 'array',
						'required' => true,
						'items'    => [
							'type'       => 'object',
							'properties' => [
								'level'   => [ 'type' => 'string' ],
								'message' => [ 'type' => 'string' ],
								'context' => [ 'type' => 'object' ],
							],
						],
					],
				],
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
		if ( ! $this->verify_nonce( $request ) ) {
			return $this->error_response( 'invalid_nonce', __( 'Invalid security token', 'opcache-toolkit' ), 403 );
		}

		$logs = $request->get_param( 'logs' );

		foreach ( $logs as $log ) {
			$level   = $log['level'] ?? 'info';
			$message = $log['message'] ?? '';
			$context = $log['context'] ?? [];

			Plugin::logger()->log( $message, $level, $context, 'js' );
		}

		return new \WP_REST_Response( [ 'success' => true ] );
	}
}
