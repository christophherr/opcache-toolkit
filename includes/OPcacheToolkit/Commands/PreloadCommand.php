<?php
/**
 * Preload Command.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\Commands;

use OPcacheToolkit\Services\OPcacheService;
use OPcacheToolkit\Services\CircuitBreaker;
use OPcacheToolkit\Services\Profiler;

/**
 * Class PreloadCommand.
 *
 * Handles the logic for preloading PHP files into OPcache.
 */
class PreloadCommand {

	/**
	 * OPcache service instance.
	 *
	 * @var OPcacheService
	 */
	private OPcacheService $opcache;

	/**
	 * Circuit breaker instance.
	 *
	 * @var CircuitBreaker
	 */
	private CircuitBreaker $breaker;

	/**
	 * PreloadCommand constructor.
	 *
	 * @param OPcacheService $opcache OPcache service instance.
	 */
	public function __construct( OPcacheService $opcache ) {
		$this->opcache = $opcache;
		$this->breaker = new CircuitBreaker( 'preload', 10, 300 );
	}

	/**
	 * Execute the preload command.
	 *
	 * @param array $directories List of absolute paths to directories to preload.
	 * @return CommandResult
	 */
	public function execute( array $directories ): CommandResult {
		if ( ! $this->opcache->is_enabled() ) {
			return CommandResult::failure(
				__( 'OPcache is not enabled or available on this server.', 'opcache-toolkit' )
			);
		}

		$token = Profiler::start( 'OPcache Preload', [ 'directory_count' => count( $directories ) ] );

		try {
			$result = $this->breaker->execute(
				function () use ( $directories ) {
					global $wp_filesystem;
					if ( empty( $wp_filesystem ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
						WP_Filesystem();
					}

					$total_compiled = 0;
					$failed_files   = [];

					foreach ( $directories as $directory ) {
						$total_compiled += $this->process_directory( $directory, $failed_files );
					}

					return CommandResult::success(
						sprintf(
							/* translators: %d: number of files */
							__( 'Successfully preloaded %d files into OPcache.', 'opcache-toolkit' ),
							$total_compiled
						),
						[
							'compiled_count' => $total_compiled,
							'failed_files'   => $failed_files,
						]
					);
				}
			);

			Profiler::end(
				$token,
				[
					'compiled_count' => $result->data['compiled_count'] ?? 0,
					'failed_count'   => count( $result->data['failed_files'] ?? [] ),
				]
			);

			return $result;
		} catch ( \Throwable $e ) {
			Profiler::end( $token, [ 'error' => $e->getMessage() ] );
			return CommandResult::failure( $e->getMessage() );
		}
	}

	/**
	 * Recursively process a directory and compile PHP files.
	 *
	 * @param string $path         Absolute path to the directory.
	 * @param array  $failed_files Reference to an array to track failures.
	 * @return int Number of successfully compiled files.
	 */
	private function process_directory( string $path, array &$failed_files ): int {
		global $wp_filesystem;

		$count = 0;
		$list  = $wp_filesystem->dirlist( $path );

		if ( empty( $list ) ) {
			return 0;
		}

		foreach ( $list as $item ) {
			$full_path = trailingslashit( $path ) . $item['name'];

			if ( 'd' === $item['type'] ) {
				$count += $this->process_directory( $full_path, $failed_files );
				continue;
			}

			if ( str_ends_with( $item['name'], '.php' ) ) {
				if ( $this->opcache->compile_file( $full_path ) ) {
					++$count;
				} else {
					$failed_files[] = $full_path;
				}
			}
		}

		return $count;
	}
}
