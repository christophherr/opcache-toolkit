<?php
/**
 * OPcache Toolkit – Preloading (Sync + Async)
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preload OPcache by compiling PHP files in themes and plugins.
 *
 * @return int Number of PHP files successfully compiled.
 */
function opcache_toolkit_preload_now() {

	$paths = [
		WP_CONTENT_DIR . '/themes',
		WP_CONTENT_DIR . '/plugins',
	];

	$compiled = 0;
	$total    = 0;

	// Count PHP files
	foreach ( $paths as $path ) {
		if ( ! is_dir( $path ) ) {
			continue;
		}

		try {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS )
			);
		} catch ( UnexpectedValueException $e ) {
			if ( function_exists( 'opcache_toolkit_log' ) ) {
				opcache_toolkit_log( 'Preload error: ' . $e->getMessage() );
			}
			continue;
		}

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && substr( $file->getFilename(), -4 ) === '.php' ) {
				++$total;
			}
		}
	}

	// Initialize progress
	update_option(
		'opcache_toolkit_preload_progress',
		[
			'total' => $total,
			'done'  => 0,
		]
	);

	// Compile files
	foreach ( $paths as $path ) {
		if ( ! is_dir( $path ) ) {
			continue;
		}

		try {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS )
			);
		} catch ( UnexpectedValueException $e ) {
			if ( function_exists( 'opcache_toolkit_log' ) ) {
				opcache_toolkit_log( 'Preload error: ' . $e->getMessage() );
			}
			continue;
		}

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}
			if ( substr( $file->getFilename(), -4 ) !== '.php' ) {
				continue;
			}

			opcache_compile_file( $file->getPathname() );
			++$compiled;

			update_option(
				'opcache_toolkit_preload_progress',
				[
					'total' => $total,
					'done'  => $compiled,
				]
			);
		}
	}

	// Finalize report (use unified helper)
	if ( function_exists( 'opcache_toolkit_store_preload_report' ) ) {
		opcache_toolkit_store_preload_report( $compiled );
	}

	// Mark progress complete
	update_option(
		'opcache_toolkit_preload_progress',
		[
			'total' => $total,
			'done'  => $total,
		]
	);

	if ( function_exists( 'opcache_toolkit_log' ) ) {
		opcache_toolkit_log( "Preload completed. Files compiled: {$compiled}" );
	}

	return $compiled;
}

/**
 * Queue a background preload job using Action Scheduler.
 */
function opcache_toolkit_queue_preload() {
	if ( ! class_exists( 'ActionScheduler' ) ) {
		return;
	}

	if ( ! as_next_scheduled_action( 'opcache_toolkit_preload_async' ) ) {
		as_enqueue_async_action( 'opcache_toolkit_preload_async' );
	}
}

add_action(
	'opcache_toolkit_preload_async',
	function () {
		opcache_toolkit_preload_now();
	}
);
