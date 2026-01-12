<?php
/**
 * OPcache Toolkit – WP-CLI Commands
 *
 * Provides a complete CLI interface for OPcache management:
 *
 *   wp opcache-toolkit info
 *   wp opcache-toolkit status
 *   wp opcache-toolkit health
 *   wp opcache-toolkit reset
 *   wp opcache-toolkit preload [--async]
 *   wp opcache-toolkit preload report
 *   wp opcache-toolkit warmup
 *   wp opcache-toolkit stats clear
 *   wp opcache-toolkit stats export
 *   wp opcache-toolkit log
 *   wp opcache-toolkit cleanup
 *   wp opcache-toolkit config
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {

	class OPCACHE_TOOLKIT_CLI_Commands {

		/**
		 * Output JSON if --json flag is present.
		 *
		 * @param mixed $data
		 * @param array $assoc_args
		 * @return bool
		 */
		private function maybe_json( $data, $assoc_args ) {
			if ( ! empty( $assoc_args['json'] ) ) {
				WP_CLI::print_value( $data, [ 'format' => 'json' ] );
				return true;
			}
			return false;
		}

		/**
		 * Ensure OPcache is available.
		 */
		private function ensure_opcache_available() {
			if ( ! function_exists( 'opcache_get_status' ) ) {
				WP_CLI::error( esc_html__( 'OPcache is disabled on this server.', 'opcache-toolkit' ) );
			}
		}

		/**
		 * Ensure user has permission.
		 */
		private function ensure_permission() {
			if ( ! opcache_toolkit_user_can_manage_opcache() ) {
				WP_CLI::error( esc_html__( 'You do not have permission to manage OPcache.', 'opcache-toolkit' ) );
			}
		}

		/**
		 * Display raw OPcache information from opcache_get_status().
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON instead of human-readable text.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit info
		 *     wp opcache-toolkit info --json
		 */
		public function info( $args, $assoc_args ) {

			$this->ensure_permission();
			$this->ensure_opcache_available();

			$status = opcache_get_status( false );

			if ( $this->maybe_json( $status, $assoc_args ) ) {
				return;
			}

			$stats = $status['opcache_statistics'];
			$mem   = $status['memory_usage'];

			WP_CLI::line( 'OPcache Information' );
			WP_CLI::line( '-------------------' );
			WP_CLI::line( 'Enabled:          ' . ( $status['opcache_enabled'] ? 'yes' : 'no' ) );
			WP_CLI::line( 'Cached Scripts:   ' . $stats['num_cached_scripts'] );
			WP_CLI::line( 'Hits:             ' . $stats['hits'] );
			WP_CLI::line( 'Misses:           ' . $stats['misses'] );
			WP_CLI::line( 'Hit Rate:         ' . round( $stats['opcache_hit_rate'], 2 ) . '%' );
			WP_CLI::line( 'Memory Used:      ' . size_format( $mem['used_memory'] ) );
			WP_CLI::line( 'Memory Free:      ' . size_format( $mem['free_memory'] ) );
			WP_CLI::line( 'Wasted Memory:    ' . size_format( $mem['wasted_memory'] ) );

			WP_CLI::success( esc_html__( 'OPcache info displayed.', 'opcache-toolkit' ) );
		}

		/**
		 * Display a summarized OPcache status (memory, strings, stats).
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit status
		 *     wp opcache-toolkit status --json
		 */
		public function status( $args, $assoc_args ) {

			$this->ensure_permission();
			$this->ensure_opcache_available();

			$status = opcache_get_status( false );

			$data = [
				'memory'  => $status['memory_usage'],
				'strings' => $status['interned_strings_usage'],
				'stats'   => $status['opcache_statistics'],
			];

			if ( $this->maybe_json( $data, $assoc_args ) ) {
				return;
			}

			WP_CLI::line( 'OPcache Status' );
			WP_CLI::line( '--------------' );
			WP_CLI::line( 'Hit Rate:       ' . round( $data['stats']['opcache_hit_rate'], 2 ) . '%' );
			WP_CLI::line( 'Cached Scripts: ' . $data['stats']['num_cached_scripts'] );
			WP_CLI::line( 'Memory Used:    ' . size_format( $data['memory']['used_memory'] ) );
			WP_CLI::line( 'Strings Used:   ' . size_format( $data['strings']['used_memory'] ) );

			WP_CLI::success( esc_html__( 'OPcache status displayed.', 'opcache-toolkit' ) );
		}

		/**
		 * Show OPcache health indicators (hit rate, memory usage, wasted memory).
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit health
		 *     wp opcache-toolkit health --json
		 */
		public function health( $args, $assoc_args ) {

			$this->ensure_permission();
			$this->ensure_opcache_available();

			$status = opcache_get_status( false );
			$stats  = $status['opcache_statistics'];
			$mem    = $status['memory_usage'];

			$health = [
				'hit_rate'        => round( $stats['opcache_hit_rate'], 2 ),
				'memory_used_pct' => round( ( $mem['used_memory'] / $mem['total_memory'] ) * 100, 2 ),
				'wasted_memory'   => $mem['wasted_memory'],
			];

			if ( $this->maybe_json( $health, $assoc_args ) ) {
				return;
			}

			WP_CLI::line( 'OPcache Health' );
			WP_CLI::line( '--------------' );
			WP_CLI::line( 'Hit Rate:       ' . $health['hit_rate'] . '%' );
			WP_CLI::line( 'Memory Used:    ' . $health['memory_used_pct'] . '%' );
			WP_CLI::line( 'Wasted Memory:  ' . size_format( $health['wasted_memory'] ) );

			WP_CLI::success( esc_html__( 'OPcache health displayed.', 'opcache-toolkit' ) );
		}

		/**
		 * Reset OPcache immediately.
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit reset
		 *     wp opcache-toolkit reset --json
		 */
		public function reset( $args, $assoc_args ) {

			$this->ensure_permission();
			$this->ensure_opcache_available();

			opcache_reset();

			$result = [ 'message' => esc_html__( 'OPcache reset successfully.', 'opcache-toolkit' ) ];

			if ( $this->maybe_json( $result, $assoc_args ) ) {
				return;
			}

			WP_CLI::success( $result['message'] );
		}

		/**
		 * Preload OPcache by compiling all PHP files in plugins and themes.
		 *
		 * ## OPTIONS
		 *
		 * [--async]
		 * : Queue the preload job using Action Scheduler instead of running it now.
		 *
		 * [--json]
		 * : Output machine-readable JSON.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit preload
		 *     wp opcache-toolkit preload --async
		 *     wp opcache-toolkit preload --json
		 */
		public function preload( $args, $assoc_args ) {

			$this->ensure_permission();
			$this->ensure_opcache_available();

			if ( ! empty( $assoc_args['async'] ) ) {

				if ( ! class_exists( 'ActionScheduler' ) ) {
					WP_CLI::error( esc_html__( 'Async preload requires Action Scheduler.', 'opcache-toolkit' ) );
				}

				opcache_toolkit_queue_preload();

				$result = [ 'message' => esc_html__( 'Preload queued (async).', 'opcache-toolkit' ) ];

				if ( $this->maybe_json( $result, $assoc_args ) ) {
					return;
				}

				WP_CLI::success( $result['message'] );
				return;
			}

			// Synchronous preload
			$count = opcache_toolkit_preload_now();
			opcache_toolkit_store_preload_report( $count );

			$result = [
				'compiled' => $count,
				'message'  => esc_html__( 'Preload completed.', 'opcache-toolkit' ),
			];

			if ( $this->maybe_json( $result, $assoc_args ) ) {
				return;
			}

			WP_CLI::success( "Preload completed. Files compiled: $count" );
		}

		/**
		 * Show the last OPcache preload report (files compiled, timestamp).
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit preload report
		 *     wp opcache-toolkit preload report --json
		 */
		public function preload_report( $args, $assoc_args ) {

			$report = opcache_toolkit_get_preload_report();

			if ( $this->maybe_json( $report, $assoc_args ) ) {
				return;
			}

			WP_CLI::line( 'Last Preload Report' );
			WP_CLI::line( '-------------------' );
			WP_CLI::line( 'Files Compiled: ' . $report['count'] );
			WP_CLI::line( 'Last Run:       ' . $report['time'] );

			WP_CLI::success( esc_html__( 'Preload report displayed.', 'opcache-toolkit' ) );
		}

		/**
		 * Warm up OPcache by compiling only uncached PHP files.
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit warmup
		 *     wp opcache-toolkit warmup --json
		 */
		public function warmup( $args, $assoc_args ) {

			$this->ensure_permission();
			$this->ensure_opcache_available();

			$paths = [
				WP_CONTENT_DIR . '/themes',
				WP_CONTENT_DIR . '/plugins',
			];

			$compiled = 0;
			$skipped  = 0;

			foreach ( $paths as $path ) {

				if ( ! is_dir( $path ) ) {
					continue;
				}

				$iterator = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS )
				);

				foreach ( $iterator as $file ) {

					if ( ! $file->isFile() || substr( $file->getFilename(), -4 ) !== '.php' ) {
						continue;
					}

					$real = $file->getPathname();

					if ( opcache_is_script_cached( $real ) ) {
						++$skipped;
						continue;
					}

					opcache_compile_file( $real );
					++$compiled;
				}
			}

			$result = [
				'compiled' => $compiled,
				'skipped'  => $skipped,
				'message'  => esc_html__( 'OPcache warmup completed.', 'opcache-toolkit' ),
			];

			if ( $this->maybe_json( $result, $assoc_args ) ) {
				return;
			}

			WP_CLI::line( "Compiled: $compiled" );
			WP_CLI::line( "Skipped:  $skipped" );
			WP_CLI::success( esc_html__( 'OPcache warmup completed.', 'opcache-toolkit' ) );
		}

		/**
		 * Clear the OPcache statistics database table.
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit stats clear
		 *     wp opcache-toolkit stats clear --json
		 */
		public function stats_clear( $args, $assoc_args ) {

			$this->ensure_permission();

			global $wpdb;
			$table = opcache_toolkit_get_stats_table_name();

			$wpdb->query( "TRUNCATE TABLE {$table}" );

			$result = [ 'message' => esc_html__( 'OPcache statistics cleared.', 'opcache-toolkit' ) ];

			if ( $this->maybe_json( $result, $assoc_args ) ) {
				return;
			}

			WP_CLI::success( $result['message'] );
		}

		/**
		 * Export OPcache statistics as JSON (CSV export must be handled externally).
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON containing all stats rows.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit stats export --json > stats.json
		 */
		public function stats_export( $args, $assoc_args ) {

			$this->ensure_permission();

			global $wpdb;
			$table = opcache_toolkit_get_stats_table_name();

			$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY recorded_at DESC", ARRAY_A );

			if ( $this->maybe_json( $rows, $assoc_args ) ) {
				return;
			}

			WP_CLI::line( esc_html__( 'CSV export is only available via JSON output.', 'opcache-toolkit' ) );
		}

		/**
		 * Run the daily OPcache log job immediately.
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit log
		 *     wp opcache-toolkit log --json
		 */
		public function log( $args, $assoc_args ) {

			$this->ensure_permission();

			opcache_toolkit_handle_daily_log();

			$result = [ 'message' => esc_html__( 'Daily log executed.', 'opcache-toolkit' ) ];

			if ( $this->maybe_json( $result, $assoc_args ) ) {
				return;
			}

			WP_CLI::success( $result['message'] );
		}

		/**
		 * Run the OPcache statistics retention cleanup immediately.
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit cleanup
		 *     wp opcache-toolkit cleanup --json
		 */
		public function cleanup( $args, $assoc_args ) {

			$this->ensure_permission();

			opcache_toolkit_cleanup_stats_retention();

			$result = [ 'message' => esc_html__( 'Retention cleanup completed.', 'opcache-toolkit' ) ];

			if ( $this->maybe_json( $result, $assoc_args ) ) {
				return;
			}

			WP_CLI::success( $result['message'] );
		}

		/**
		 * Display OPcache configuration (ini settings).
		 *
		 * ## OPTIONS
		 *
		 * [--json]
		 * : Output machine-readable JSON.
		 *
		 * ## EXAMPLES
		 *
		 *     wp opcache-toolkit config
		 *     wp opcache-toolkit config --json
		 */
		public function config( $args, $assoc_args ) {

			$this->ensure_permission();
			$this->ensure_opcache_available();

			$config = ini_get_all( 'opcache' );

			if ( $this->maybe_json( $config, $assoc_args ) ) {
				return;
			}

			WP_CLI::line( 'OPcache Configuration' );
			WP_CLI::line( '---------------------' );

			foreach ( $config as $key => $value ) {
				WP_CLI::line( "$key: " . $value['local_value'] );
			}

			WP_CLI::success( esc_html__( 'OPcache configuration displayed.', 'opcache-toolkit' ) );
		}
	}

	WP_CLI::add_command( 'opcache-toolkit', 'OPCACHE_TOOLKIT_CLI_Commands' );
}
