<?php
/**
 * Reset REST Endpoint.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\REST;

use OPcacheToolkit\Commands\ResetCommand;

/**
 * Class ResetEndpoint.
 *
 * Handles resetting the OPcache via REST API.
 */
class ResetEndpoint extends BaseEndpoint {

	/**
	 * Reset command instance.
	 *
	 * @var ResetCommand
	 */
	private ResetCommand $command;

	/**
	 * ResetEndpoint constructor.
	 *
	 * @param ResetCommand $command Reset command instance.
	 */
	public function __construct( ResetCommand $command ) {
		$this->command = $command;
	}

	/**
	 * Register the reset route.
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'opcache-toolkit/v1',
			'/reset',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'schema'              => [ $this, 'get_schema' ],
			]
		);
	}

	/**
	 * Handle the reset request.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! $this->verify_nonce( $request ) ) {
			return $this->error_response(
				'rest_forbidden',
				__( 'Invalid security token.', 'opcache-toolkit' ),
				403
			);
		}

		$result = $this->command->execute();

		if ( ! $result->success ) {
			return $this->error_response(
				'opcache_reset_failed',
				$result->message,
				500
			);
		}

		return $this->success_response(
			[
				'message' => $result->message,
			]
		);
	}

	/**
	 * Get the schema for the reset endpoint.
	 *
	 * @return array
	 */
	public function get_schema(): array {
		return [
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'opcache_reset',
			'type'       => 'object',
			'properties' => [
				'success' => [
					'type'        => 'boolean',
					'description' => __( 'Whether the reset was successful.', 'opcache-toolkit' ),
				],
				'message' => [
					'type'        => 'string',
					'description' => __( 'Response message.', 'opcache-toolkit' ),
				],
			],
		];
	}
}
