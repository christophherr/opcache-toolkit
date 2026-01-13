<?php
/**
 * Statistics Repository.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\Database;

/**
 * Class StatsRepository.
 *
 * Handles database operations for OPcache statistics with caching.
 */
class StatsRepository {

	/**
	 * WordPress database object.
	 *
	 * @var \wpdb
	 */
	private \wpdb $wpdb;

	/**
	 * Statistics table name.
	 *
	 * @var string
	 */
	private string $table;

	/**
	 * Cache key prefix for chart data.
	 */
	private const CACHE_KEY = 'opcache_toolkit_chart_data';

	/**
	 * Cache TTL in seconds (5 minutes).
	 */
	private const CACHE_TTL = 300;

	/**
	 * StatsRepository constructor.
	 *
	 * @param \wpdb $wpdb WordPress database object.
	 */
	public function __construct( \wpdb $wpdb ) {
		$this->wpdb  = $wpdb;
		$this->table = $wpdb->prefix . 'opcache_toolkit_stats';
	}

	/**
	 * Execute a database operation with retry logic.
	 *
	 * @param callable $operation The operation to execute.
	 * @param int      $retries   Number of retries.
	 * @return mixed
	 * @throws \Exception If the operation fails after all retries.
	 */
	private function with_retry( callable $operation, int $retries = 3 ) {
		$last_error = null;

		for ( $i = 0; $i < $retries; $i++ ) {
			try {
				return $operation();
			} catch ( \Throwable $e ) {
				$last_error = $e;
				if ( $i < $retries - 1 ) {
					usleep( 100000 * ( $i + 1 ) ); // Exponential backoff (100ms, 200ms, ...).
				}
			}
		}

		throw new \Exception( 'Database operation failed after ' . (int) $retries . ' retries: ' . esc_html( $last_error->getMessage() ) );
	}

	/**
	 * Get chart data with caching.
	 *
	 * @param int $limit Number of rows to fetch.
	 * @return array
	 */
	public function get_chart_data( int $limit = 180 ): array {
		$cache_key = self::CACHE_KEY . "_{$limit}";
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return (array) $cached;
		}

		try {
			$rows = $this->with_retry(
				function () use ( $limit ) {
					$results = $this->wpdb->get_results(
						$this->wpdb->prepare(
							'SELECT recorded_at, hit_rate, cached_scripts, wasted_memory
							 FROM %i
							 ORDER BY recorded_at ASC
							 LIMIT %d',
							$this->table,
							$limit
						)
					);

					if ( null === $results && ! empty( $this->wpdb->last_error ) ) {
						throw new \Exception( esc_html( $this->wpdb->last_error ) );
					}

					return $results;
				}
			);

			$data = $this->format_chart_data( $rows );
			set_transient( $cache_key, $data, self::CACHE_TTL );
			return $data;
		} catch ( \Throwable $e ) {
			\OPcacheToolkit\Plugin::logger()->log( 'OPcache Toolkit Database Error: ' . $e->getMessage(), 'error' );
			return $this->format_chart_data( [] ); // Return empty format as fallback.
		}
	}

	/**
	 * Insert a new statistics row and invalidate cache.
	 *
	 * @param array $data Statistics data.
	 * @return bool
	 */
	public function insert( array $data ): bool {
		try {
			$result = $this->with_retry(
				function () use ( $data ) {
					$res = $this->wpdb->insert(
						$this->table,
						[
							'recorded_at'    => $data['recorded_at'],
							'hit_rate'       => (float) $data['hit_rate'],
							'cached_scripts' => (int) $data['cached_scripts'],
							'wasted_memory'  => (int) $data['wasted_memory'],
						],
						[ '%s', '%f', '%d', '%d' ]
					);

					if ( false === $res ) {
						throw new \Exception( esc_html( $this->wpdb->last_error ) );
					}

					return $res;
				}
			);

			$this->invalidate_cache();
			return true;
		} catch ( \Throwable $e ) {
			\OPcacheToolkit\Plugin::logger()->log( 'OPcache Toolkit Database Insert Error: ' . $e->getMessage(), 'error' );
			return false;
		}
	}

	/**
	 * Delete statistics older than specified days.
	 *
	 * @param int $days Number of days to retain.
	 * @return int Number of deleted rows.
	 */
	public function delete_older_than( int $days ): int {
		$count = (int) $this->wpdb->query(
			$this->wpdb->prepare(
				'DELETE FROM %i WHERE recorded_at < (NOW() - INTERVAL %d DAY)',
				$this->table,
				$days
			)
		);

		if ( $count > 0 ) {
			$this->invalidate_cache();
		}

		return $count;
	}

	/**
	 * Truncate the entire statistics table.
	 *
	 * @return bool
	 */
	public function truncate(): bool {
		$result = $this->wpdb->query(
			$this->wpdb->prepare( 'TRUNCATE TABLE %i', $this->table )
		);

		$this->invalidate_cache();

		return false !== $result;
	}

	/**
	 * Get all statistics rows (for export).
	 *
	 * @return array
	 */
	public function get_all(): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				'SELECT * FROM %i ORDER BY recorded_at ASC',
				$this->table
			),
			ARRAY_A
		);
	}

	/**
	 * Invalidate chart data caches.
	 *
	 * @return void
	 */
	private function invalidate_cache(): void {
		delete_transient( self::CACHE_KEY . '_180' );
		delete_transient( self::CACHE_KEY . '_30' );
	}

	/**
	 * Format database rows into chart-ready data.
	 *
	 * @param array $rows Database rows.
	 * @return array
	 */
	private function format_chart_data( array $rows ): array {
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

		return $data;
	}

	/**
	 * Predict when OPcache memory might be exhausted based on trends.
	 *
	 * @return array Prediction data.
	 */
	public function get_memory_prediction(): array {
		$rows = $this->get_chart_data( 30 ); // Last 30 points.

		if ( count( $rows['labels'] ) < 2 ) {
			return [ 'status' => 'insufficient_data' ];
		}

		$total_memory = (int) ini_get( 'opcache.memory_consumption' ) * 1024 * 1024;
		$status       = \OPcacheToolkit\Plugin::opcache()->get_status();
		$used_memory  = $status['memory_usage']['used_memory'] ?? 0;

		// Calculate daily growth (very simple linear approximation).
		$first_wasted = $rows['wasted'][0];
		$last_wasted  = end( $rows['wasted'] );
		$growth       = ( $last_wasted - $first_wasted ) / count( $rows['labels'] );

		if ( $growth <= 0 ) {
			return [
				'status' => 'stable',
				'growth' => $growth,
			];
		}

		$remaining_memory = $total_memory - $used_memory;
		$days_remaining   = $growth > 0 ? floor( $remaining_memory / $growth ) : 999;

		return [
			'status'         => $days_remaining < 7 ? 'critical' : ( $days_remaining < 30 ? 'warning' : 'stable' ),
			'days_remaining' => (int) $days_remaining,
			'growth'         => $growth,
		];
	}
}
