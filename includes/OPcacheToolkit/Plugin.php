<?php
/**
 * Plugin Registry and Bootstrapper.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit;

/**
 * Class Plugin.
 *
 * Main entry point for the OPcache Toolkit plugin.
 * Handles service instantiation and procedural file loading.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * OPcache service instance.
	 *
	 * @var Services\OPcacheService|null
	 */
	private static ?Services\OPcacheService $opcache = null;

	/**
	 * Stats repository instance.
	 *
	 * @var Database\StatsRepository|null
	 */
	private static ?Database\StatsRepository $stats = null;

	/**
	 * Logger service instance.
	 *
	 * @var Services\Logger|null
	 */
	private static ?Services\Logger $logger = null;

	/**
	 * Set the OPcache service.
	 *
	 * @param Services\OPcacheService $service OPcache service instance.
	 * @return void
	 */
	public static function set_opcache( Services\OPcacheService $service ): void {
		self::$opcache = $service;
	}

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the OPcache service.
	 *
	 * @return Services\OPcacheService
	 */
	public static function opcache(): Services\OPcacheService {
		if ( null === self::$opcache ) {
			self::$opcache = new Services\OPcacheService();
		}
		return self::$opcache;
	}

	/**
	 * Get the stats repository.
	 *
	 * @return Database\StatsRepository
	 */
	public static function stats(): Database\StatsRepository {
		if ( null === self::$stats ) {
			global $wpdb;
			self::$stats = new Database\StatsRepository( $wpdb );
		}
		return self::$stats;
	}

	/**
	 * Get the logger service.
	 *
	 * @return Services\Logger
	 */
	public static function logger(): Services\Logger {
		if ( null === self::$logger ) {
			self::$logger = new Services\Logger();
		}
		return self::$logger;
	}

	/**
	 * Bootstrap the plugin.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->load_procedural_files();

		add_action( 'rest_api_init', [ $this, 'register_rest_endpoints' ] );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'opcache-toolkit', CLI\Commands::class );
		}
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @return void
	 */
	public function register_rest_endpoints(): void {
		( new REST\StatusEndpoint( self::opcache() ) )->register();
		( new REST\ChartDataEndpoint( self::stats() ) )->register();

		$reset_command = new Commands\ResetCommand( self::opcache() );
		( new REST\ResetEndpoint( $reset_command ) )->register();

		$preload_command = new Commands\PreloadCommand( self::opcache() );
		( new REST\PreloadEndpoint( $preload_command ) )->register();
		( new REST\LogEndpoint() )->register();
		( new REST\AnalyticsEndpoint() )->register();
	}

	/**
	 * Load procedural PHP files.
	 *
	 * @return void
	 */
	private function load_procedural_files(): void {
		$files = [
			'core/db.php',
			'debug/debug.php',
			'admin/admin-menu.php',
			'admin/admin-report.php',
			'admin/admin-settings.php',
			'admin/admin-dashboard.php',
			'admin/admin-bar.php',
			'admin/dashboard-widget.php',
			'core/cron.php',
			'system/alerts.php',
			'core/preload.php',
			'core/preload-async.php',
			'core/opcache-reset.php',
			'system/notices.php',
		];

		foreach ( $files as $file ) {
			$path = OPCACHE_TOOLKIT_PATH . 'includes/' . $file;
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}
	}

	/**
	 * Constructor is private for singleton.
	 */
	private function __construct() {
		// No-op.
	}
}
