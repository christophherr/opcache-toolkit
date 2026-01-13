<?php
/**
 * OPcache Toolkit – Admin Menu
 *
 * Provides a top-level menu with Dashboard + Settings subpages.
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register top-level OPcache Toolkit menu.
 *
 * - Single-site: appears in wp-admin
 * - Multisite: appears in Network Admin
 *
 * @return void
 */
function opcache_toolkit_register_admin_menu() {

	$cap         = is_multisite() ? 'manage_network' : 'manage_options';
	$parent_slug = 'opcache-toolkit';

	// Top-level menu.
	add_menu_page(
		esc_html__( 'OPcache Toolkit', 'opcache-toolkit' ),
		esc_html__( 'OPcache Toolkit', 'opcache-toolkit' ),
		$cap,
		$parent_slug,
		'opcache_toolkit_render_dashboard_page',
		'dashicons-performance',
		65
	);

	// Dashboard subpage (same callback as top-level).
	add_submenu_page(
		$parent_slug,
		esc_html__( 'Dashboard', 'opcache-toolkit' ),
		esc_html__( 'Dashboard', 'opcache-toolkit' ),
		$cap,
		$parent_slug,
		'opcache_toolkit_render_dashboard_page'
	);

	// Settings subpage.
	add_submenu_page(
		$parent_slug,
		esc_html__( 'Settings', 'opcache-toolkit' ),
		esc_html__( 'Settings', 'opcache-toolkit' ),
		$cap,
		'opcache-toolkit-settings',
		'opcache_toolkit_render_settings_page'
	);

	// System Report subpage.
	add_submenu_page(
		$parent_slug,
		esc_html__( 'System Report', 'opcache-toolkit' ),
		esc_html__( 'System Report', 'opcache-toolkit' ),
		$cap,
		'opcache-toolkit-report',
		'opcache_toolkit_render_system_report'
	);

	// Setup Wizard (Hidden from menu).
	// We use 'index.php' as parent to register the page without showing it in our main menu.
	// Using null causes PHP Deprecated warnings in some WordPress versions because it calls plugin_basename(null).
	add_submenu_page(
		'index.php',
		esc_html__( 'Setup Wizard', 'opcache-toolkit' ),
		esc_html__( 'Setup Wizard', 'opcache-toolkit' ),
		$cap,
		'opcache-toolkit-wizard',
		'opcache_toolkit_render_wizard_page'
	);
}

if ( is_multisite() ) {
	add_action( 'network_admin_menu', 'opcache_toolkit_register_admin_menu' );
} else {
	add_action( 'admin_menu', 'opcache_toolkit_register_admin_menu' );
}
