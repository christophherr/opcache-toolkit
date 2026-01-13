<?php
/**
 * PHPUnit bootstrap for unit tests.
 *
 * Unit tests use Brain Monkey to mock WordPress functions.
 * No actual WordPress installation required.
 *
 * @package OPcacheToolkit\Tests
 */

// Composer autoloader (loads Brain Monkey, Mockery, PHPUnit, and plugin classes)
require_once dirname( __DIR__, 3 ) . '/vendor/autoload.php';

// Define WordPress constants that code might check
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

// Show all errors during tests
error_reporting( E_ALL );
ini_set( 'display_errors', '1' );
