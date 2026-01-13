<?php
/**
 * WP-CLI Commands.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\CLI;

use OPcacheToolkit\Commands\PreloadCommand;
use OPcacheToolkit\Commands\ResetCommand;
use OPcacheToolkit\Plugin;
use OPcacheToolkit\Commands\CommandResult;
use WP_CLI;
use function WP_CLI\Utils\format_items;

/**
 * Class Commands.
 *
 * Provides WP-CLI commands for managing OPcache.
 */
class Commands {

	/**
	 * Output data as JSON if requested.
	 *
	 * @param mixed $data       Data to output.
	 * @param array $assoc_args Associative arguments from WP-CLI.
	 * @return bool True if JSON was output, false otherwise.
	 */
	private function maybe_json( $data, array $assoc_args ): bool {
		if ( isset( $assoc_args['json'] ) && $assoc_args['json'] ) {
			WP_CLI::log( \wp_json_encode( $data ) );
			return true;
		}
		return false;
	}

	/**
	 * Ensure OPcache is available.
	 *
	 * @throws \Exception If OPcache is not available.
	 * @return void
	 */
	private function ensure_opcache_available(): void {
		if ( ! Plugin::opcache()->is_enabled() ) {
			WP_CLI::error( \esc_html__( 'OPcache is not loaded or enabled on this server.', 'opcache-toolkit' ) );
		}
	}

	/**
	 * Display general OPcache information.
	 *
	 * ## OPTIONS
	 *
	 * [--json]
	 * : Output machine-readable JSON.
	 *
	 * ## EXAMPLES
	 *
	 *     wp opcache-toolkit info
	 *
	 * @param array $args  Command arguments.
	 * @param array $assoc_args  Command associative arguments.
	 *
	 * @return void
	 * @throws \Exception If OPcache is not available.
	 */
	public function info( array $args, array $assoc_args ): void {
		$this->ensure_opcache_available();

		$status = Plugin::opcache()->get_status();
		$config = Plugin::opcache()->get_configuration();

		$data = [
			'enabled'      => true,
			'version'      => $config['zend_extension_version'] ?? 'unknown',
			'scripts'      => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
			'hit_rate'     => Plugin::opcache()->get_hit_rate(),
			'memory_usage' => Plugin::opcache()->get_memory_usage(),
		];

		if ( $this->maybe_json( $data, $assoc_args ) ) {
			return;
		}

		WP_CLI::line( 'OPcache Status: Enabled' );
		WP_CLI::line( 'Cached Scripts: ' . $data['scripts'] );
		WP_CLI::line( 'Hit Rate: ' . \round( $data['hit_rate'], 2 ) . '%' );
		WP_CLI::line( 'Memory Used: ' . \size_format( $data['memory_usage']['used_memory'] ) );
	}

	/**
	 * Reset the OPcache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp opcache-toolkit reset
	 *
	 * @param  array $args        Command arguments.
	 * @param  array $assoc_args  Command associative arguments.
	 *
	 * @return void
	 * @throws \Exception If OPcache is not available.
	 */
	public function reset( array $args, array $assoc_args ): void {
		$this->ensure_opcache_available();

		$command = new ResetCommand( Plugin::opcache() );
		$result  = $command->execute();

		if ( $result->success ) {
			WP_CLI::success( $result->message );
		} else {
			WP_CLI::error( $result->message );
		}
	}

	/**
	 * Preload files into OPcache.
	 *
	 * ## OPTIONS
	 *
	 * <directories>...
	 * : One or more absolute paths to directories to preload.
	 *
	 * ## EXAMPLES
	 *
	 *     wp opcache-toolkit preload /var/www/html/wp-content/plugins
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 * @return void
	 * @throws \Exception If OPcache is not available.
	 */
	public function preload( array $args, array $assoc_args ): void {
		$this->ensure_opcache_available();

		if ( empty( $args ) ) {
			WP_CLI::error( \esc_html__( 'Please specify at least one directory to preload.', 'opcache-toolkit' ) );
		}

		$command = new PreloadCommand( Plugin::opcache() );
		$result  = $command->execute( $args );

		if ( $result->success ) {
			WP_CLI::success( $result->message );
		} else {
			WP_CLI::error( $result->message );
		}
	}

	/**
	 * Run system diagnostics.
	 *
	 * ## EXAMPLES
	 *
	 *     wp opcache-toolkit doctor
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command associative arguments.
	 * @return void
	 */
	public function doctor( array $args, array $assoc_args ): void {
		WP_CLI::line( \esc_html__( 'Running diagnostics...', 'opcache-toolkit' ) );

		$status = Plugin::opcache()->get_status();

		$checks = [
			'PHP Version'     => PHP_VERSION,
			'WP Version'      => \get_bloginfo( 'version' ),
			'Plugin Version'  => OPCACHE_TOOLKIT_VERSION,
			'OPcache Enabled' => \extension_loaded( 'Zend OPcache' ) ? '✓ Yes' : '✗ No',
			'OPcache Memory'  => \ini_get( 'opcache.memory_consumption' ) . 'MB',
			'Hit Rate'        => \number_format( Plugin::opcache()->get_hit_rate(), 2 ) . '%',
			'Cached Scripts'  => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
			'DB Schema'       => \opcache_toolkit_check_schema() ? '✓ OK' : '✗ Missing',
			'Cron Jobs'       => \wp_next_scheduled( 'opcache_toolkit_daily_log' ) ? '✓ Scheduled' : '✗ Missing',
			'Debug Mode'      => \get_option( 'opcache_toolkit_debug_mode' ) ? 'Enabled' : 'Disabled',
		];

		$table_data = [];
		foreach ( $checks as $check => $result ) {
			$table_data[] = [
				'check'  => $check,
				'result' => $result,
			];
		}

		format_items( 'table', $table_data, [ 'check', 'result' ] );
		WP_CLI::success( \__( 'Diagnostics complete', 'opcache-toolkit' ) );
	}
}
