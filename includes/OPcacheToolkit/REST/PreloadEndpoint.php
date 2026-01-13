<?php
/**
 * Preload REST Endpoint.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\REST;

use OPcacheToolkit\Commands\PreloadCommand;

/**
 * Class PreloadEndpoint.
 *
 * Handles preloading files into OPcache via REST API.
 */
class PreloadEndpoint extends BaseEndpoint {

	/**
	 * Preload command instance.
	 *
	 * @var PreloadCommand
	 */
	private PreloadCommand $command;

	/**
	 * PreloadEndpoint constructor.
	 *
	 * @param PreloadCommand $command Preload command instance.
	 */
	public function __construct( PreloadCommand $command ) {
		$this->command = $command;
	}

	/**
	 * Register the preload route.
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'opcache-toolkit/v1',
			'/preload',
			[
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'directories' => [
						'type'              => 'array',
						'description'       => __( 'List of absolute paths to directories to preload.', 'opcache-toolkit' ),
						'required'          => true,
						'items'             => [
							'type' => 'string',
						],
						'sanitize_callback' => function ( $value ) {
							return array_map( 'sanitize_text_field', (array) $value );
						},
					],
				],
			]
		);
	}

	/**
	 * Handle the preload request.
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

		$directories = $request->get_param( 'directories' );
		$result      = $this->command->execute( $directories );

		if ( ! $result->success ) {
			return $this->error_response(
				'opcache_preload_failed',
				$result->message,
				500
			);
		}

		return $this->success_response(
			[
				'message' => $result->message,
				'data'    => $result->data,
			]
		);
	}
}
