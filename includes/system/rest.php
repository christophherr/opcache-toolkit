<?php
/**
 * OPcache Toolkit – REST API Endpoints
 *
 * Provides REST access to OPcache status, preload, and reset actions.
 *
 * Endpoints:
 *   GET  /wp-json/opcache-toolkit/v1/status
 *   POST /wp-json/opcache-toolkit/v1/preload
 *   POST /wp-json/opcache-toolkit/v1/reset
 *
 * Unified Response Format:
 * [
 *   'success' => true|false,
 *   'code'    => string|null,
 *   'message' => string|null,
 *   'data'    => mixed|null,
 * ]
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register OPcache REST API routes.
 *
 * @return void
 */
function opcache_toolkit_register_rest_routes() {

	register_rest_route(
		'opcache-toolkit/v1',
		'/status',
		[
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'opcache_toolkit_rest_get_status',
			'permission_callback' => 'opcache_toolkit_rest_can_access',
			'schema'              => 'opcache_toolkit_rest_get_status_schema',
		]
	);

	register_rest_route(
		'opcache-toolkit/v1',
		'/preload',
		[
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'opcache_toolkit_rest_preload',
			'permission_callback' => 'opcache_toolkit_rest_can_access',
			'schema'              => 'opcache_toolkit_rest_get_preload_schema',
		]
	);

	register_rest_route(
		'opcache-toolkit/v1',
		'/reset',
		[
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'opcache_toolkit_rest_reset',
			'permission_callback' => 'opcache_toolkit_rest_can_access',
			'schema'              => 'opcache_toolkit_rest_get_reset_schema',
		]
	);

	register_rest_route(
		'opcache-toolkit/v1',
		'/chart-data',
		[
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => 'opcache_toolkit_rest_can_access',
			'callback'            => 'opcache_toolkit_rest_get_chart_data',
		]
	);

	register_rest_route(
		'opcache-toolkit/v1',
		'/preload-progress',
		[
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => 'opcache_toolkit_rest_can_access',
			'callback'            => function () {
				$progress = get_option(
					'opcache_toolkit_preload_progress',
					[
						'total' => 0,
						'done'  => 0,
					]
				);

				if ( ! extension_loaded( 'Zend OPcache' ) ) {
					return opcache_toolkit_rest_response(
						false,
						'opcache_disabled',
						esc_html__( 'OPcache is not loaded on this server.', 'opcache-toolkit' ),
						null
					);
				}

				return opcache_toolkit_rest_response(
					true,
					null,
					null,
					$progress
				);
			},
		]
	);

	register_rest_route(
		'opcache-toolkit/v1',
		'/health',
		[
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => 'opcache_toolkit_rest_can_access',
			'callback'            => function () {

				// If OPcache is not loaded at all
				if ( ! extension_loaded( 'Zend OPcache' ) ) {
					return opcache_toolkit_rest_response(
						false,
						'opcache_disabled',
						esc_html__( 'OPcache is not loaded on this server.', 'opcache-toolkit' ),
						null,
						200
					);
				}

				$cfg = ini_get_all( 'opcache' );

				if ( ! is_array( $cfg ) ) {
					return opcache_toolkit_rest_response(
						false,
						'opcache_unavailable',
						esc_html__( 'Unable to read OPcache configuration.', 'opcache-toolkit' ),
						null,
						200
					);
				}

				$issues = [];

				// Treat missing opcache.enable as disabled
				if (
				! isset( $cfg['opcache.enable'] ) ||
				empty( $cfg['opcache.enable']['local_value'] )
				) {
					$issues[] = esc_html__( 'OPcache is disabled.', 'opcache-toolkit' );
				}

				if (
				isset( $cfg['opcache.memory_consumption']['local_value'] ) &&
				$cfg['opcache.memory_consumption']['local_value'] < 128
				) {
					$issues[] = esc_html__( 'Memory consumption is too low.', 'opcache-toolkit' );
				}

				if (
				isset( $cfg['opcache.validate_timestamps']['local_value'] ) &&
				! $cfg['opcache.validate_timestamps']['local_value']
				) {
					$issues[] = esc_html__( 'Timestamp validation is disabled.', 'opcache-toolkit' );
				}

				return opcache_toolkit_rest_response(
					true,
					null,
					null,
					[
						'ok'     => empty( $issues ),
						'issues' => $issues,
					]
				);
			},
		]
	);
}
add_action( 'rest_api_init', 'opcache_toolkit_register_rest_routes' );

/**
 * Unified REST response wrapper.
 *
 * @param bool        $success
 * @param string|null $code
 * @param string|null $message
 * @param mixed|null  $data
 * @param int         $status
 *
 * @return WP_REST_Response
 */
function opcache_toolkit_rest_response( $success, $code = null, $message = null, $data = null, $status = 200 ) {
	return new WP_REST_Response(
		[
			'success' => $success,
			'code'    => $code,
			'message' => $message,
			'data'    => $data,
		],
		$status
	);
}


/**
 * Permission check for OPcache REST endpoints.
 *
 * @return bool
 */
function opcache_toolkit_rest_can_access() {
	return opcache_toolkit_user_can_manage_opcache();
}

/**
 * GET /status
 *
 * @return WP_REST_Response|WP_Error
 */
function opcache_toolkit_rest_get_status() {

	if ( ! function_exists( 'opcache_get_status' ) ) {
		return opcache_toolkit_rest_response(
			false,
			'opcache_disabled',
			esc_html__( 'OPcache is disabled on this server.', 'opcache-toolkit' ),
			null,
			200
		);
	}

	$status = opcache_get_status( false );

	if ( ! $status ) {
		return opcache_toolkit_rest_response(
			false,
			'opcache_unavailable',
			esc_html__( 'Unable to retrieve OPcache status.', 'opcache-toolkit' ),
			null,
			500
		);
	}

	return opcache_toolkit_rest_response(
		true,
		null,
		null,
		$status
	);
}

/**
 * POST /preload
 *
 * @return WP_REST_Response|WP_Error
 */
function opcache_toolkit_rest_preload() {

	$allowed = opcache_toolkit_rest_rate_limit( 'preload', 60 );
	if ( is_wp_error( $allowed ) ) {
		return opcache_toolkit_rest_response(
			false,
			$allowed->get_error_code(),
			$allowed->get_error_message(),
			null,
			$allowed->get_error_data( 'status' ) ?? 429
		);
	}

	if ( class_exists( 'ActionScheduler' ) ) {
		opcache_toolkit_queue_preload();

		return opcache_toolkit_rest_response(
			true,
			null,
			esc_html__( 'OPcache preload has been queued.', 'opcache-toolkit' ),
			[ 'queued' => true ]
		);
	}

	$count = opcache_toolkit_preload_now();

	return opcache_toolkit_rest_response(
		true,
		null,
		esc_html__( 'OPcache has been preloaded.', 'opcache-toolkit' ),
		[
			'queued'   => false,
			'compiled' => $count,
		]
	);
}


/**
 * POST /reset
 *
 * @return WP_REST_Response|WP_Error
 */
function opcache_toolkit_rest_reset() {

	$allowed = opcache_toolkit_rest_rate_limit( 'reset', 30 );
	if ( is_wp_error( $allowed ) ) {
		return opcache_toolkit_rest_response(
			false,
			$allowed->get_error_code(),
			$allowed->get_error_message(),
			null,
			$allowed->get_error_data( 'status' ) ?? 429
		);
	}

	if ( ! function_exists( 'opcache_reset' ) ) {
		return opcache_toolkit_rest_response(
			false,
			'opcache_reset_missing',
			esc_html__( 'The opcache_reset() function is not available.', 'opcache-toolkit' ),
			null,
			500
		);
	}

	if ( ! opcache_reset() ) {
		return opcache_toolkit_rest_response(
			false,
			'opcache_reset_failed',
			esc_html__( 'OPcache reset failed.', 'opcache-toolkit' ),
			null,
			500
		);
	}

	return opcache_toolkit_rest_response(
		true,
		null,
		esc_html__( 'OPcache has been reset.', 'opcache-toolkit' ),
		[ 'success' => true ]
	);
}

/**
 * Return historical OPcache stats for charts. *
 *
 * @return WP_REST_Response
 */
function opcache_toolkit_rest_get_chart_data() {
	global $wpdb;

	$table = $wpdb->prefix . 'opcache_toolkit_stats';
	$rows  = $wpdb->get_results(
		"
        SELECT recorded_at, hit_rate, cached_scripts, wasted_memory
        FROM {$table}
        ORDER BY recorded_at ASC
        LIMIT 180
    "
	);

	$data = [
		'labels'  => [],
		'hitRate' => [],
		'cached'  => [],
		'wasted'  => [],
	];

	foreach ( $rows as $row ) {
		$data['labels'][]  = $row->recorded_at;
		$data['hitRate'][] = (float) $row->hit_rate;
		$data['cached'][]  = (int) $row->cached_scripts;
		$data['wasted'][]  = (int) $row->wasted_memory;
	}

	if ( ! extension_loaded( 'Zend OPcache' ) ) {
		return opcache_toolkit_rest_response(
			false,
			'opcache_disabled',
			esc_html__( 'OPcache is not loaded on this server.', 'opcache-toolkit' ),
			null
		);
	}

	return opcache_toolkit_rest_response( true, null, null, $data );
}

/**
 * Basic rate limiting for OPcache REST actions.
 *
 * @param string $action_slug Unique action identifier (e.g., 'preload', 'reset').
 * @param int    $interval    Minimum seconds between calls.
 * @return true|WP_Error True if allowed, WP_Error if rate-limited.
 */
function opcache_toolkit_rest_rate_limit( $action_slug, $interval = 60 ) {

	$user = wp_get_current_user();

	if ( ! $user || 0 === $user->ID ) {
		// For app passwords / non-user contexts, skip rate limiting here,
		// or implement IP-based logic if you really want to.
		return true;
	}

	$meta_key  = 'opcache_toolkit_last_' . $action_slug . '_time';
	$last_time = intval( get_user_meta( $user->ID, $meta_key, true ) );
	$now       = time();

	if ( $last_time && ( $now - $last_time ) < $interval ) {
		$remaining = $interval - ( $now - $last_time );

		return new WP_Error(
			'opcache_toolkit_rate_limited',
			sprintf(
				/* translators: %d is the number of seconds remaining. */
				esc_html__( 'Rate limit exceeded. Please wait %d seconds before trying again.', 'opcache-toolkit' ),
				$remaining
			),
			[ 'status' => 429 ]
		);
	}

	update_user_meta( $user->ID, $meta_key, $now );

	return true;
}


/**
 * JSON schema for the /status endpoint.
 *
 * @return array
 */
function opcache_toolkit_rest_get_status_schema() {

	return [
		'$schema'    => 'http://json-schema.org/draft-04/schema#',
		'title'      => 'opcache_status',
		'type'       => 'object',
		'properties' => [
			'opcache_enabled'    => [
				'type'        => 'boolean',
				'description' => esc_html__( 'Whether OPcache is enabled.', 'opcache-toolkit' ),
			],
			'cache_full'         => [
				'type'        => 'boolean',
				'description' => esc_html__( 'Whether the OPcache is full.', 'opcache-toolkit' ),
			],
			'memory_usage'       => [
				'type'        => 'object',
				'description' => esc_html__( 'OPcache memory usage statistics.', 'opcache-toolkit' ),
			],
			'opcache_statistics' => [
				'type'        => 'object',
				'description' => esc_html__( 'OPcache performance statistics.', 'opcache-toolkit' ),
			],
		],
	];
}


function opcache_toolkit_rest_get_preload_schema() {
	return [
		'type'       => 'object',
		'properties' => [
			'queued'   => [
				'type'        => 'boolean',
				'description' => esc_html__( 'Whether the preload was queued (async) or run immediately.', 'opcache-toolkit' ),
			],
			'compiled' => [
				'type'        => 'integer',
				'description' => esc_html__( 'Number of files compiled during synchronous preload.', 'opcache-toolkit' ),
			],
			'message'  => [
				'type'        => 'string',
				'description' => esc_html__( 'Human-readable status message.', 'opcache-toolkit' ),
			],
		],
	];
}

function opcache_toolkit_rest_get_reset_schema() {
	return [
		'type'       => 'object',
		'properties' => [
			'success' => [
				'type'        => 'boolean',
				'description' => esc_html__( 'Whether the OPcache reset was successful.', 'opcache-toolkit' ),
			],
			'message' => [
				'type'        => 'string',
				'description' => esc_html__( 'Human-readable status message.', 'opcache-toolkit' ),
			],
		],
	];
}
