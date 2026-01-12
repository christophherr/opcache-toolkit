<?php
/**
 * Health REST Endpoint.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\REST;

use OPcacheToolkit\Plugin;

/**
 * Class HealthEndpoint.
 *
 * Provides system health checks for OPcache.
 */
class HealthEndpoint extends BaseEndpoint {

	/**
	 * Register the health route.
	 *
	 * @return void
	 */
	public function register(): void {
		register_rest_route(
			'opcache-toolkit/v1',
			'/health',
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => [ $this, 'check_permission' ],
			]
		);
	}

	/**
	 * Handle the health request.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$check = $this->ensure_opcache_enabled();
		if ( true !== $check ) {
			return $check;
		}

		$issues = [];
		$status = Plugin::opcache()->get_status();

		// Check memory usage.
		$mem = $status['memory_usage'] ?? [];
		if ( ! empty( $mem ) ) {
			$used_pct = ( $mem['used_memory'] / ( $mem['used_memory'] + $mem['free_memory'] ) ) * 100;
			if ( $used_pct > 90 ) {
				$issues[] = sprintf(
					/* translators: %s: percentage of memory used */
					__( 'OPcache memory usage is very high (%s%%). Consider increasing opcache.memory_consumption.', 'opcache-toolkit' ),
					round( $used_pct, 1 )
				);
			}
		}

		// Check hit rate.
		$hit_rate = Plugin::opcache()->get_hit_rate();
		if ( $hit_rate < 80 ) {
			$issues[] = sprintf(
				/* translators: %s: hit rate percentage */
				__( 'OPcache hit rate is low (%s%%). This may indicate frequent script changes or insufficient cache size.', 'opcache-toolkit' ),
				round( $hit_rate, 1 )
			);
		}

		// Check wasted memory.
		if ( ( $mem['wasted_memory'] ?? 0 ) > ( 1024 * 1024 * 5 ) ) { // 5MB
			$issues[] = __( 'Significant wasted memory detected. A manual reset might be beneficial if opcache.max_wasted_percentage is not being met.', 'opcache-toolkit' );
		}

		return $this->success_response(
			[
				'issues' => $issues,
			]
		);
	}
}
