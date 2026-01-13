<?php
/**
 * Integration test bootstrap file
 *
 * @package OPcacheToolkit
 */

// 1) Resolve tests dir from env.
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	fwrite( STDERR, "WP_TESTS_DIR is not set.\n" );
	exit( 1 );
}

// 2) Load functions.php FIRST (gives tests_add_filter()).
$functions = $_tests_dir . '/includes/functions.php';
if ( ! file_exists( $functions ) ) {
	fwrite( STDERR, "Missing: {$functions}\n" );
	exit( 1 );
}
require_once $functions;

// 3) Ensure our plugin is loaded during the test bootstrap.
/**
 * Manually load plugins
 *
 * @return void
 */
function _manually_load_plugin() {
	require_once dirname( __DIR__, 3 ) . '/opcache-toolkit.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// 4) Ensure database tables are created.
/**
 * Create plugin tables
 *
 * @return void
 */
function _create_plugin_tables() {
	opcache_toolkit_install_schema();
}
tests_add_filter( 'init', '_create_plugin_tables', 1 );

// 5) NOW include the CORE test bootstrap (this defines ABSPATH and runs installer).
$core_bootstrap = $_tests_dir . '/includes/bootstrap.php';
if ( ! file_exists( $core_bootstrap ) ) {
	fwrite( STDERR, "Missing: {$core_bootstrap}\n" );
	exit( 1 );
}
require_once $core_bootstrap;
