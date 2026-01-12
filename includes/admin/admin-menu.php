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

	// Top-level menu
	add_menu_page(
		esc_html__( 'OPcache Toolkit', 'opcache-toolkit' ),
		esc_html__( 'OPcache Toolkit', 'opcache-toolkit' ),
		$cap,
		$parent_slug,
		'opcache_toolkit_render_dashboard_page',
		'dashicons-performance',
		65
	);

	// Dashboard subpage (same callback as top-level)
	add_submenu_page(
		$parent_slug,
		esc_html__( 'Dashboard', 'opcache-toolkit' ),
		esc_html__( 'Dashboard', 'opcache-toolkit' ),
		$cap,
		$parent_slug,
		'opcache_toolkit_render_dashboard_page'
	);

	// Settings subpage
	add_submenu_page(
		$parent_slug,
		esc_html__( 'Settings', 'opcache-toolkit' ),
		esc_html__( 'Settings', 'opcache-toolkit' ),
		$cap,
		'opcache-toolkit-settings',
		'opcache_toolkit_render_settings_page'
	);
}

if ( is_multisite() ) {
	add_action( 'network_admin_menu', 'opcache_toolkit_register_admin_menu' );
} else {
	add_action( 'admin_menu', 'opcache_toolkit_register_admin_menu' );
}
