<?php
/**
 * OPcache Toolkit – Daily Logging Handler
 *
 * Handles:
 * - Daily OPcache statistics snapshot
 * - Retention cleanup
 * - Alert checks
 *
 * This file is multisite-aware and uses the unified database helpers
 * defined in includes/db.php.
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle the daily OPcache logging event.
 *
 * This function:
 * - Retrieves OPcache status safely
 * - Inserts a daily snapshot using opcache_toolkit_insert_stats_row()
 * - Runs retention cleanup
 * - Triggers alert checks
 *
 * @return void
 */
function opcache_toolkit_handle_daily_log() {

	// OPcache unavailable
	if ( ! function_exists( 'opcache_get_status' ) ) {
		if ( function_exists( 'opcache_toolkit_log' ) ) {
			opcache_toolkit_log( 'Daily log skipped: opcache_get_status() unavailable.' );
		}
		return;
	}

	$status = opcache_get_status( false );

	// Status retrieval failed
	if ( ! $status || ! isset( $status['opcache_statistics'], $status['memory_usage'] ) ) {
		if ( function_exists( 'opcache_toolkit_log' ) ) {
			opcache_toolkit_log( 'Daily log skipped: invalid OPcache status.' );
		}
		return;
	}

	$stats = $status['opcache_statistics'];
	$mem   = $status['memory_usage'];

	// Insert daily stats using unified helper
	opcache_toolkit_insert_stats_row(
		current_time( 'mysql' ),
		$stats['opcache_hit_rate'],
		$stats['num_cached_scripts'],
		$mem['wasted_memory']
	);

	// Run retention cleanup
	if ( function_exists( 'opcache_toolkit_cleanup_stats_retention' ) ) {
		opcache_toolkit_cleanup_stats_retention();
	}

	// Trigger alert checks (hit rate only)
	do_action( 'opcache_toolkit_check_alerts', $stats['opcache_hit_rate'] );

	if ( function_exists( 'opcache_toolkit_log' ) ) {
		opcache_toolkit_log( 'Daily OPcache stats logged successfully.' );
	}
}
add_action( 'opcache_toolkit_daily_log', 'opcache_toolkit_handle_daily_log' );


/**
 * Schedule the daily logging event.
 *
 * Uses Action Scheduler if available, otherwise falls back to WP-Cron.
 *
 * @return void
 */
function opcache_toolkit_schedule_daily_event() {

	// Prefer Action Scheduler
	if ( class_exists( 'ActionScheduler' ) ) {

		if ( ! as_next_scheduled_action( 'opcache_toolkit_daily_log', [], 'opcache-toolkit' ) ) {
			as_schedule_recurring_action(
				time(),
				DAY_IN_SECONDS,
				'opcache_toolkit_daily_log',
				[],
				'opcache-manager'
			);
		}

		return;
	}

	// Fallback: WP-Cron
	if ( ! wp_next_scheduled( 'opcache_toolkit_daily_log' ) ) {
		wp_schedule_event( time(), 'daily', 'opcache_toolkit_daily_log' );
	}
}
