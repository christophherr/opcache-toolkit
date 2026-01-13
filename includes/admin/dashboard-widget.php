<?php
/**
 * OPcache Toolkit – Modern Dashboard Widget
 *
 * Lightweight summary widget using REST API data.
 * Matches the new dashboard UI and uses shared CSS variables.
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the OPcache dashboard widget.
 *
 * @return void
 */
function opcache_toolkit_register_dashboard_widget() {

	// Multisite: only show on main site.
	if ( OPCACHE_TOOLKIT_IS_NETWORK && ! is_main_site() ) {
		return;
	}

	wp_add_dashboard_widget(
		'opcache_toolkit_widget',
		esc_html__( 'OPcache Status', 'opcache-toolkit' ),
		'opcache_toolkit_widget_render'
	);

	add_action(
		'admin_enqueue_scripts',
		function ( $hook ) {
			if ( 'index.php' !== $hook ) {
				return;
			}

			wp_enqueue_style(
				'opcache-toolkit-theme',
				plugins_url( 'assets/css/opcache-toolkit-theme.css', OPCACHE_TOOLKIT_FILE ),
				[],
				filemtime( OPCACHE_TOOLKIT_PATH . 'assets/css/opcache-toolkit-theme.css' )
			);

			wp_enqueue_style(
				'opcache-toolkit-wpadmin-dashboard',
				plugins_url( 'assets/css/opcache-toolkit-wpadmin-dashboard.css', OPCACHE_TOOLKIT_FILE ),
				[ 'opcache-toolkit-theme' ],
				filemtime( OPCACHE_TOOLKIT_PATH . 'assets/css/opcache-toolkit-wpadmin-dashboard.css' )
			);

			$script_path = 'assets/js/widget.js';
			$asset_file  = OPCACHE_TOOLKIT_PATH . 'assets/js/widget.asset.php';
			$asset       = file_exists( $asset_file ) ? include $asset_file : [
				'dependencies' => [],
				'version'      => OPCACHE_TOOLKIT_VERSION,
			];

			wp_enqueue_script(
				'opcache-toolkit-wpadmin-dashboard',
				plugins_url( $script_path, OPCACHE_TOOLKIT_FILE ),
				$asset['dependencies'],
				$asset['version'],
				true
			);

			wp_localize_script(
				'opcache-toolkit-wpadmin-dashboard',
				'opcacheToolkitWPAdminDashboard',
				[
					'statusEndpoint'  => rest_url( 'opcache-toolkit/v1/status' ),
					'healthEndpoint'  => rest_url( 'opcache-toolkit/v1/health' ),
					'preloadEndpoint' => rest_url( 'opcache-toolkit/v1/preload-progress' ),
					'resetUrl'        => wp_nonce_url(
						opcache_toolkit_admin_url( 'admin-post.php?action=opcache_toolkit_clear' ),
						'opcache_toolkit_clear'
					),
					'nonce'           => wp_create_nonce( 'wp_rest' ),
					'dashboardUrl'    => opcache_toolkit_admin_url( 'admin.php?page=opcache-toolkit' ),
				]
			);
		}
	);
}
add_action( 'wp_dashboard_setup', 'opcache_toolkit_register_dashboard_widget' );


/**
 * Render the widget container.
 *
 * The actual content is injected by JS using REST API data.
 *
 * @return void
 */
function opcache_toolkit_widget_render() {
	?>
	<div id="opcache-toolkit-widget" class="opcache-toolkit-widget">
		<div class="opcache-toolkit-widget-loading">
			<?php esc_html_e( 'Loading OPcache data…', 'opcache-toolkit' ); ?>
		</div>
	</div>
	<?php
}
