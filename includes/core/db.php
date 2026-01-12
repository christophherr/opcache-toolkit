<?php
/**
 * OPcache Toolkit – Database layer
 *
 * - Schema creation & upgrades (using dbDelta)
 * - Stats table helpers
 * - Retention cleanup
 * - Preload report helpers
 * - Maintenance actions (clear stats, export CSV)
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the full table name for OPcache stats.
 *
 * @return string
 */
function opcache_toolkit_get_stats_table_name() {
	global $wpdb;
	return $wpdb->prefix . 'opcache_toolkit_stats';
}

/**
 * Check if the statistics table exists.
 *
 * @return bool
 */
function opcache_toolkit_check_schema() {
	global $wpdb;
	$table = opcache_toolkit_get_stats_table_name();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
}

/**
 * Create or upgrade the OPcache stats table using dbDelta.
 *
 * This does NOT register hooks by itself. Call from your main plugin file:
 * register_activation_hook( OPCACHE_TOOLKIT_FILE, 'opcache_toolkit_install_schema' );
 *
 * @return void
 */
function opcache_toolkit_install_schema() {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$table           = opcache_toolkit_get_stats_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	// Keep this in sync with any future schema changes.
	$sql = "
        CREATE TABLE {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            recorded_at DATETIME NOT NULL,
            hit_rate FLOAT NOT NULL,
            cached_scripts BIGINT(20) UNSIGNED NOT NULL,
            wasted_memory BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY  (id),
            KEY recorded_at (recorded_at)
        ) {$charset_collate};
    ";

	dbDelta( $sql );
}

/**
 * Delete stats older than the configured retention period.
 *
 * Uses 'opcache_toolkit_retention_days' setting (multisite-aware via opcache_toolkit_get_setting).
 *
 * @return void
 */
function opcache_toolkit_cleanup_stats_retention() {
	if ( ! function_exists( 'opcache_toolkit_get_setting' ) ) {
		return;
	}

	$days = (int) opcache_toolkit_get_setting( 'opcache_toolkit_retention_days', 90 );
	if ( $days < 1 ) {
		$days = 1;
	}

	\OPcacheToolkit\Plugin::stats()->delete_older_than( $days );
}

/**
 * Schedule daily retention cleanup.
 *
 * Call this from plugin activation, or lazily check on admin_init.
 *
 * @return void
 */
function opcache_toolkit_schedule_retention_cleanup() {
	if ( ! wp_next_scheduled( 'opcache_toolkit_daily_stats_cleanup' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'opcache_toolkit_daily_stats_cleanup' );
	}
}
add_action( 'opcache_toolkit_daily_stats_cleanup', 'opcache_toolkit_cleanup_stats_retention' );

/**
 * Insert a single stats row into the table.
 *
 * @param string $recorded_at   MySQL datetime.
 * @param float  $hit_rate      Hit rate percentage.
 * @param int    $cached        Cached scripts count.
 * @param int    $wasted        Wasted memory in bytes.
 * @return void
 */
function opcache_toolkit_insert_stats_row( $recorded_at, $hit_rate, $cached, $wasted ) {
	\OPcacheToolkit\Plugin::stats()->insert(
		[
			'recorded_at'    => $recorded_at,
			'hit_rate'       => (float) $hit_rate,
			'cached_scripts' => (int) $cached,
			'wasted_memory'  => (int) $wasted,
		]
	);
}

/**
 * Clear all stats (truncate table).
 *
 * @return void
 */
function opcache_toolkit_clear_stats_table() {
	\OPcacheToolkit\Plugin::stats()->truncate();
}

/**
 * Fetch all stats rows ordered by time (for export, etc.).
 *
 * @return array[] Array of associative rows.
 */
function opcache_toolkit_get_all_stats_rows() {
	return \OPcacheToolkit\Plugin::stats()->get_all();
}

/**
 * Store preload report values.
 *
 * @param int $count Number of files compiled.
 * @return void
 */
function opcache_toolkit_store_preload_report( $count ) {
	$time = time();

	if ( defined( 'OPCACHE_TOOLKIT_IS_NETWORK' ) && OPCACHE_TOOLKIT_IS_NETWORK ) {
		update_site_option( 'opcache_toolkit_preload_time', $time );
		update_site_option( 'opcache_toolkit_preload_count', (int) $count );
	} else {
		update_option( 'opcache_toolkit_preload_time', $time );
		update_option( 'opcache_toolkit_preload_count', (int) $count );
	}
}

/**
 * Get preload report (formatted + raw values).
 *
 * @return array{time:string,count:int,raw_time:int,raw_count:int}
 */
function opcache_toolkit_get_preload_report() {

	$get = ( defined( 'OPCACHE_TOOLKIT_IS_NETWORK' ) && OPCACHE_TOOLKIT_IS_NETWORK )
		? 'get_site_option'
		: 'get_option';

	$raw_time  = (int) $get( 'opcache_toolkit_preload_time', 0 );
	$raw_count = (int) $get( 'opcache_toolkit_preload_count', 0 );

	if ( $raw_time > 0 ) {
		$time = date_i18n(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			$raw_time
		);
	} else {
		$time = '—';
	}

	return [
		'time'      => $time,
		'count'     => $raw_count,
		'raw_time'  => $raw_time,
		'raw_count' => $raw_count,
	];
}

/**
 * Handle "Clear OPcache Statistics" admin-post action.
 *
 * @return void
 */
add_action(
	'admin_post_opcache_toolkit_clear_stats',
	function () {
		$cap = ( defined( 'OPCACHE_TOOLKIT_IS_NETWORK' ) && OPCACHE_TOOLKIT_IS_NETWORK ) ? 'manage_network' : 'manage_options';
		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
		}

		check_admin_referer( 'opcache_toolkit_clear_stats' );

		opcache_toolkit_clear_stats_table();

		wp_safe_redirect( wp_get_referer() );
		exit;
	}
);

/**
 * Handle "Export OPcache Statistics" admin-post action (CSV).
 *
 * Uses WP_Filesystem + a temporary file for portability.
 *
 * @return void
 */
add_action(
	'admin_post_opcache_toolkit_export_stats',
	function () {
		$cap = ( defined( 'OPCACHE_TOOLKIT_IS_NETWORK' ) && OPCACHE_TOOLKIT_IS_NETWORK )
			? 'manage_network'
			: 'manage_options';

		if ( ! current_user_can( $cap ) ) {
			wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
		}

		check_admin_referer( 'opcache_toolkit_export_stats' );

		global $wp_filesystem;

		// Ensure WP_Filesystem is initialized.
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( empty( $wp_filesystem ) ) {
			wp_die( esc_html__( 'Could not initialize filesystem API.', 'opcache-toolkit' ) );
		}

		$rows = opcache_toolkit_get_all_stats_rows();

		if ( empty( $rows ) ) {
			wp_die( esc_html__( 'No data available for export.', 'opcache-toolkit' ) );
		}

		// Generate CSV in memory.
		$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( false === $handle ) {
			wp_die( esc_html__( 'Unable to create temporary memory stream.', 'opcache-toolkit' ) );
		}

		// Header row.
		fputcsv( $handle, array_keys( $rows[0] ), ',', '"', '\\' );

		// Data rows.
		foreach ( $rows as $row ) {
			fputcsv( $handle, $row, ',', '"', '\\' );
		}

		rewind( $handle );
		$csv_output = stream_get_contents( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		if ( false === $csv_output ) {
			wp_die( esc_html__( 'Unable to generate CSV content.', 'opcache-toolkit' ) );
		}

		// Create temp file and write CSV via WP_Filesystem.
		$tmp = wp_tempnam( 'opcache_stats.csv' );
		if ( ! $tmp ) {
			wp_die( esc_html__( 'Unable to create temporary file.', 'opcache-toolkit' ) );
		}

		$chmod = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;

		if ( ! $wp_filesystem->put_contents( $tmp, $csv_output, $chmod ) ) {
			wp_die( esc_html__( 'Unable to write CSV via Filesystem API.', 'opcache-toolkit' ) );
		}

		// Send file to browser.
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="opcache_stats.csv"' );
		header( 'Content-Length: ' . $wp_filesystem->size( $tmp ) );

		echo $wp_filesystem->get_contents( $tmp ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		// Cleanup.
		$wp_filesystem->delete( $tmp );
		exit;
	}
);
