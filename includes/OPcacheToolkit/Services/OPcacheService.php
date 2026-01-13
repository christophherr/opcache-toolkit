<?php
/**
 * OPcache Service Wrapper.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

namespace OPcacheToolkit\Services;

/**
 * Class OPcacheService.
 *
 * Provides a testable wrapper for PHP's opcache_* functions.
 */
class OPcacheService {

	/**
	 * Check if OPcache is enabled and functional.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return function_exists( 'opcache_get_status' )
			&& false !== opcache_get_status();
	}

	/**
	 * Get OPcache status.
	 *
	 * @param bool $scripts Whether to include script-specific state.
	 * @return array|null
	 */
	public function get_status( bool $scripts = false ): ?array {
		if ( ! function_exists( 'opcache_get_status' ) ) {
			return null;
		}

		$status = opcache_get_status( $scripts );
		return false !== $status ? $status : null;
	}

	/**
	 * Get OPcache configuration.
	 *
	 * @return array|null
	 */
	public function get_configuration(): ?array {
		$config = ini_get_all( 'opcache' );
		return is_array( $config ) ? $config : null;
	}

	/**
	 * Reset the entire OPcache.
	 *
	 * @return bool
	 */
	public function reset(): bool {
		return function_exists( 'opcache_reset' ) && opcache_reset();
	}

	/**
	 * Compile a PHP file into OPcache.
	 *
	 * @param string $path Absolute path to the file.
	 * @return bool
	 */
	public function compile_file( string $path ): bool {
		if ( ! function_exists( 'opcache_compile_file' ) ) {
			return false;
		}

		if ( ! file_exists( $path ) ) {
			return false;
		}

		try {
			return opcache_compile_file( $path );
		} catch ( \Throwable $e ) {
			\OPcacheToolkit\Plugin::logger()->log( 'OPcache compile error: ' . $e->getMessage(), 'error' );
			return false;
		}
	}

	/**
	 * Get current OPcache hit rate.
	 *
	 * @return float
	 */
	public function get_hit_rate(): float {
		$status = $this->get_status();
		return $status['opcache_statistics']['opcache_hit_rate'] ?? 0.0;
	}

	/**
	 * Get current memory usage statistics.
	 *
	 * @return array
	 */
	public function get_memory_usage(): array {
		$status = $this->get_status();
		return $status['memory_usage'] ?? [];
	}

	/**
	 * Get detailed information about cached scripts.
	 *
	 * @return array
	 */
	public function get_cached_scripts(): array {
		$status = $this->get_status( true );
		return $status['scripts'] ?? [];
	}

	/**
	 * Get scripts in cache that no longer exist on disk.
	 *
	 * @return array
	 */
	public function get_ghost_scripts(): array {
		$scripts = $this->get_cached_scripts();
		$ghosts  = [];

		foreach ( $scripts as $path => $data ) {
			if ( ! file_exists( $path ) ) {
				$ghosts[ $path ] = $data;
			}
		}

		return $ghosts;
	}

	/**
	 * Group cached scripts by their base directory.
	 *
	 * Useful for identifying which plugins or sites use the most memory.
	 *
	 * @return array
	 */
	public function get_scripts_by_group(): array {
		$scripts = $this->get_cached_scripts();
		$groups  = [];

		foreach ( $scripts as $path => $data ) {
			$group = 'other';

			if ( str_contains( $path, 'wp-content/plugins/' ) ) {
				$parts = explode( 'wp-content/plugins/', $path );
				$sub   = explode( '/', $parts[1] );
				$group = 'plugin:' . $sub[0];
			} elseif ( str_contains( $path, 'wp-content/themes/' ) ) {
				$parts = explode( 'wp-content/themes/', $path );
				$sub   = explode( '/', $parts[1] );
				$group = 'theme:' . $sub[0];
			} elseif ( str_contains( $path, 'wp-includes/' ) ) {
				$group = 'core:includes';
			} elseif ( str_contains( $path, 'wp-admin/' ) ) {
				$group = 'core:admin';
			}

			if ( ! isset( $groups[ $group ] ) ) {
				$groups[ $group ] = [
					'count'  => 0,
					'memory' => 0,
					'hits'   => 0,
				];
			}

			++$groups[ $group ]['count'];
			$groups[ $group ]['memory'] += $data['memory_consumption'] ?? 0;
			$groups[ $group ]['hits']   += $data['hits'] ?? 0;
		}

		uasort(
			$groups,
			function ( $a, $b ) {
				return $b['memory'] <=> $a['memory'];
			}
		);

		return $groups;
	}
}
