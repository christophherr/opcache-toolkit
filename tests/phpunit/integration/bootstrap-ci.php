<?php
/**
 * CI integration test bootstrap for wp-env.
 */

$integration_dir = __DIR__;
$plugin_dir      = dirname( $integration_dir, 3 );
$wp_root         = dirname( $plugin_dir, 3 );

if ( ! file_exists( $wp_root . '/wp-load.php' ) ) {
    fwrite( STDERR, "Could not find wp-load.php at: {$wp_root}/wp-load.php\n" );
    exit( 1 );
}

require_once $wp_root . '/wp-load.php';

$plugin_main = $plugin_dir . '/opcache-toolkit.php';
if ( ! file_exists( $plugin_main ) ) {
    fwrite( STDERR, "Could not find plugin main file at: {$plugin_main}\n" );
    exit( 1 );
}

require_once $plugin_main;

if ( function_exists( 'opcache_toolkit_install_schema' ) ) {
    opcache_toolkit_install_schema();
}
