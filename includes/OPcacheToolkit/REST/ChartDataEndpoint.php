<?php
/**
 * Chart Data REST Endpoint.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\REST;

use OPcacheToolkit\Database\StatsRepository;

/**
 * Class ChartDataEndpoint.
 *
 * Provides historical statistics for dashboard charts via REST API.
 */
class ChartDataEndpoint extends BaseEndpoint {

	/**
	 * Stats repository instance.
	 *
	 * @var StatsRepository
	 */
	private StatsRepository $stats;

	/**
	 * ChartDataEndpoint constructor.
	 *
	 * @param StatsRepository $stats Stats repository instance.
	 */
	public function __construct( StatsRepository $stats ) {
		$this->stats = $stats;
	}

	/**
	 * Register the chart data route.
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'opcache-toolkit/v1',
			'/chart-data',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'limit' => [
						'type'              => 'integer',
						'description'       => __( 'Number of data points to retrieve.', 'opcache-toolkit' ),
						'default'           => 180,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Handle the chart data request.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$check = $this->ensure_opcache_enabled();
		if ( true !== $check ) {
			return $check;
		}

		$limit = (int) $request->get_param( 'limit' );
		$data  = $this->stats->get_chart_data( $limit );

		return $this->success_response( $data );
	}
}
