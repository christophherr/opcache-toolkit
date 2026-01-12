<?php
/**
 * Warmup Command.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\Commands;

use OPcacheToolkit\Services\CircuitBreaker;
use OPcacheToolkit\Services\Profiler;

/**
 * Class WarmupCommand.
 *
 * Proactively warms up the OPcache by hitting specific URLs.
 */
class WarmupCommand {

	/**
	 * Execute the warmup command.
	 *
	 * @param array $urls List of URLs to warm up.
	 * @return CommandResult
	 */
	public function execute( array $urls ): CommandResult {
		$token = Profiler::start( 'OPcache Warmup', [ 'url_count' => count( $urls ) ] );

		$breaker       = new CircuitBreaker( 'warmup', 3, 300 ); // More restrictive for external hits.
		$success_count = 0;
		$errors        = [];

		foreach ( $urls as $url ) {
			try {
				$response = $breaker->execute(
					function () use ( $url ) {
						$res = wp_remote_get(
							$url,
							[
								'timeout'    => 10,
								'user-agent' => 'OPcacheToolkit-Warmup/1.0',
							]
						);

						if ( is_wp_error( $res ) ) {
							throw new \Exception( esc_html( $res->get_error_message() ) );
						}

						if ( 200 !== wp_remote_retrieve_response_code( $res ) ) {
							throw new \Exception( 'HTTP ' . esc_html( wp_remote_retrieve_response_code( $res ) ) );
						}

						return $res;
					}
				);

				++$success_count;
			} catch ( \Throwable $e ) {
				$errors[ $url ] = $e->getMessage();
				if ( str_contains( $e->getMessage(), 'Circuit breaker' ) ) {
					break; // Stop processing further URLs if breaker is open.
				}
			}
		}

		Profiler::end(
			$token,
			[
				'success_count' => $success_count,
				'error_count'   => count( $errors ),
			]
		);

		return CommandResult::success(
			sprintf(
				/* translators: %d: number of URLs */
				__( 'Warmed up %d URLs.', 'opcache-toolkit' ),
				$success_count
			),
			[
				'success_count' => $success_count,
				'errors'        => $errors,
			]
		);
	}
}
