<?php
/**
 * OPcache Toolkit – Admin Bar Integration
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
-------------------------------------------------------------------------
 * CLEAR OPCACHE BUTTON
 * ------------------------------------------------------------------------- */

add_action(
	'admin_bar_menu',
	function ( $admin_bar ) {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$admin_bar->add_menu(
			[
				'id'    => 'opcache-toolkit-clear',
				'title' => esc_html__( 'Clear OPcache', 'opcache-toolkit' ),
				'href'  => wp_nonce_url(
					admin_url( 'admin-post.php?action=opcache_toolkit_clear' ),
					'opcache_toolkit_clear'
				),
				'meta'  => [ 'title' => esc_html__( 'Clear OPcache', 'opcache-toolkit' ) ],
			]
		);

		// Optional: Add preload button
		$admin_bar->add_menu(
			[
				'id'    => 'opcache-toolkit-preload',
				'title' => esc_html__( 'Run Preload', 'opcache-toolkit' ),
				'href'  => wp_nonce_url(
					admin_url( 'admin-post.php?action=opcache_toolkit_preload_now' ),
					'opcache_toolkit_preload_now'
				),
				'meta'  => [ 'title' => esc_html__( 'Run Preload Now', 'opcache-toolkit' ) ],
			]
		);
	},
	100
);

/*
-------------------------------------------------------------------------
 * HANDLE CLEAR OPCACHE
 * ------------------------------------------------------------------------- */

add_action(
	'admin_post_opcache_toolkit_clear',
	function () {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
		}

		check_admin_referer( 'opcache_toolkit_clear' );

		if ( function_exists( 'opcache_reset' ) ) {
			opcache_reset();
		}

		wp_redirect( wp_get_referer() );
		exit;
	}
);

/*
-------------------------------------------------------------------------
 * HANDLE PRELOAD NOW
 * ------------------------------------------------------------------------- */

add_action(
	'admin_post_opcache_toolkit_preload_now',
	function () {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
		}

		check_admin_referer( 'opcache_toolkit_preload_now' );

		opcache_toolkit_preload_now();

		wp_redirect( wp_get_referer() );
		exit;
	}
);
