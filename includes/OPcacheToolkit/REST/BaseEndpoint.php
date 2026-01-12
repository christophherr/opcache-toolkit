<?php
/**
 * Base REST Endpoint.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\REST;

/**
 * Class BaseEndpoint.
 *
 * Provides common functionality and security for all REST endpoints.
 */
abstract class BaseEndpoint {

	/**
	 * Register the route with WordPress.
	 *
	 * @return void
	 */
	abstract public function register(): void;

	/**
	 * Check if user has permission to manage OPcache.
	 *
	 * @return bool
	 */
	public function check_permission(): bool {
		return opcache_toolkit_user_can_manage_opcache();
	}

	/**
	 * Verify nonce for state-changing operations.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return bool
	 */
	protected function verify_nonce( \WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( empty( $nonce ) ) {
			return false;
		}

		return (bool) wp_verify_nonce( $nonce, 'wp_rest' );
	}

	/**
	 * Return a standardized success response.
	 *
	 * @param mixed $data Optional data payload.
	 * @return \WP_REST_Response
	 */
	protected function success_response( $data = null ): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'success' => true,
				'data'    => $data,
			],
			200
		);
	}

	/**
	 * Return a standardized error response.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param int    $status  HTTP status code.
	 * @return \WP_REST_Response
	 */
	protected function error_response( string $code, string $message, int $status = 400 ): \WP_REST_Response {
		return new \WP_REST_Response(
			[
				'success' => false,
				'code'    => $code,
				'message' => $message,
			],
			$status
		);
	}
}
