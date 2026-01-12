<?php
/**
 * OPcache Toolkit – Admin Dashboard (Meta Box version)
 *
 * Renders the dashboard page with charts and stats using WordPress meta boxes.
 *
 * @package OPcacheToolkit
 */

/**
 * Enqueue scripts and styles for the OPcache Toolkit dashboard.
 */
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {

		// Screen ID for this page is: toplevel_page_opcache-toolkit
		if ( $hook !== 'toplevel_page_opcache-toolkit' ) {
			return;
		}

		// ---------------------------------------------------------------------
		// 1. Collect data for charts (ensure these variables exist)
		// ---------------------------------------------------------------------

		// Pull chart data directly from the REST callback.
		$response = opcache_toolkit_rest_get_chart_data();
		$data     = $response instanceof WP_REST_Response ? $response->get_data() : [];

		$labels  = $data['labels'] ?? [];
		$hitRate = $data['hitRate'] ?? [];
		$cached  = $data['cached'] ?? [];
		$wasted  = $data['wasted'] ?? [];

		// ---------------------------------------------------------------------
		// 2. Core scripts: postboxes (drag + toggle), Chart.js, etc.
		// ---------------------------------------------------------------------

		// WordPress core postboxes (handles drag, collapse, persistence)
		wp_enqueue_script( 'postbox' );

		// Chart.js core
		wp_enqueue_script(
			'chartjs',
			plugins_url( 'assets/js/chart.js', OPCACHE_TOOLKIT_FILE ),
			[],
			null,
			true
		);

		// Chart.js zoom plugin (optional)
		wp_enqueue_script(
			'chartjs-zoom',
			plugins_url( 'assets/js/chartjs-plugin-zoom.js', OPCACHE_TOOLKIT_FILE ),
			[ 'chartjs' ],
			null,
			true
		);

		// Dashboard chart logic
		wp_enqueue_script(
			'opcache-toolkit-charts',
			plugins_url( 'assets/js/opcache-toolkit-charts.js', OPCACHE_TOOLKIT_FILE ),
			[ 'chartjs', 'chartjs-zoom' ],
			filemtime( OPCACHE_TOOLKIT_PATH . 'assets/js/opcache-toolkit-charts.js' ),
			true
		);

		// Localize chart data
		wp_localize_script(
			'opcache-toolkit-charts',
			'opcacheToolkitCharts',
			[
				'labels'   => $labels,
				'hitRate'  => $hitRate,
				'cached'   => $cached,
				'wasted'   => $wasted,
				'endpoint' => rest_url( 'opcache-toolkit/v1/chart-data' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			]
		);

		// Sidebar scroll-spy, highlight bar, smooth scrolling
		wp_enqueue_script(
			'opcache-toolkit-widgets',
			plugins_url( 'assets/js/opcache-toolkit-widgets.js', OPCACHE_TOOLKIT_FILE ),
			[], // No dependencies needed
			filemtime( OPCACHE_TOOLKIT_PATH . 'assets/js/opcache-toolkit-widgets.js' ),
			true
		);

		// Live polling (status cards, health, preload)
		wp_enqueue_script(
			'opcache-toolkit-live',
			plugins_url( 'assets/js/opcache-toolkit-live.js', OPCACHE_TOOLKIT_FILE ),
			[],
			filemtime( OPCACHE_TOOLKIT_PATH . 'assets/js/opcache-toolkit-live.js' ),
			true
		);

		wp_localize_script(
			'opcache-toolkit-live',
			'opcacheToolkitLive',
			[
				'statusEndpoint'  => rest_url( 'opcache-toolkit/v1/status' ),
				'healthEndpoint'  => rest_url( 'opcache-toolkit/v1/health' ),
				'preloadEndpoint' => rest_url( 'opcache-toolkit/v1/preload-progress' ),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'interval'        => 30000, // 30 seconds; adjust if you want 60s
			]
		);

		// ---------------------------------------------------------------------
		// 3. Styles
		// ---------------------------------------------------------------------
		wp_enqueue_style(
			'opcache-toolkit-theme',
			plugins_url( 'assets/css/opcache-toolkit-theme.css', OPCACHE_TOOLKIT_FILE ),
			[],
			filemtime( OPCACHE_TOOLKIT_PATH . 'assets/css/opcache-toolkit-theme.css' )
		);

		// You can keep a light dashboard CSS file for internal layout/colors if needed
		wp_enqueue_style(
			'opcache-toolkit-dashboard',
			plugins_url( 'assets/css/opcache-toolkit-dashboard.css', OPCACHE_TOOLKIT_FILE ),
			[ 'opcache-toolkit-theme' ],
			filemtime( OPCACHE_TOOLKIT_PATH . 'assets/css/opcache-toolkit-dashboard.css' )
		);

		// Inline JS to init postboxes on this screen
		wp_add_inline_script(
			'postbox',
			"jQuery(document).ready(function($){
            postboxes.add_postbox_toggles('toplevel_page_opcache-toolkit');
        });"
		);
	}
);

/**
 * Register meta boxes for the OPcache Toolkit dashboard.
 */
add_action(
	'add_meta_boxes_toplevel_page_opcache-toolkit',
	function () {

		// Left column (normal)
		add_meta_box(
			'opcache_toolkit_mb_charts',
			__( 'OPcache Performance Charts', 'opcache-toolkit' ),
			'opcache_toolkit_mb_charts_callback',
			'toplevel_page_opcache-toolkit',
			'normal',
			'high'
		);

		add_meta_box(
			'opcache_toolkit_mb_preload',
			__( 'Preload Progress', 'opcache-toolkit' ),
			'opcache_toolkit_mb_preload_callback',
			'toplevel_page_opcache-toolkit',
			'normal',
			'default'
		);

		// Right column (side)
		add_meta_box(
			'opcache_toolkit_mb_status',
			__( 'Live Status', 'opcache-toolkit' ),
			'opcache_toolkit_mb_status_callback',
			'toplevel_page_opcache-toolkit',
			'side',
			'high'
		);

		add_meta_box(
			'opcache_toolkit_mb_health',
			__( 'System Health', 'opcache-toolkit' ),
			'opcache_toolkit_mb_health_callback',
			'toplevel_page_opcache-toolkit',
			'side',
			'default'
		);
	}
);

/**
 * Meta box callback: Charts.
 */
function opcache_toolkit_mb_charts_callback() {
	?>
	<div class="opcache-toolkit-chart-controls">
		<button id="opcache-toolkit-refresh-charts" class="button button-secondary">
			<?php esc_html_e( 'Refresh Data', 'opcache-toolkit' ); ?>
		</button>

		<button id="opcache-toolkit-toggle-auto-refresh" class="button button-secondary">
			<?php esc_html_e( 'Pause Auto-Refresh', 'opcache-toolkit' ); ?>
		</button>

		<span id="opcache-toolkit-last-updated" style="margin-left:10px; font-size:12px; opacity:0.8;">
			<?php esc_html_e( 'Last updated: —', 'opcache-toolkit' ); ?>
		</span>
		<span id="opcache-toolkit-live-indicator" style="margin-left:10px; font-size:12px; opacity:0.8;">
			<?php esc_html_e( 'Live', 'opcache-toolkit' ); ?>
		</span>
	</div>

	<?php
	include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-charts.php';
	include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-export-buttons.php';
}

/**
 * Meta box callback: Preload Progress.
 */
function opcache_toolkit_mb_preload_callback() {
	include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-preload.php';
}

/**
 * Meta box callback: Live Status.
 */
function opcache_toolkit_mb_status_callback() {
	include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-cards.php';
}

/**
 * Meta box callback: System Health.
 */
function opcache_toolkit_mb_health_callback() {
	include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-health.php';
}

/**
 * Render the OPcache Toolkit dashboard page.
 */
function opcache_toolkit_render_dashboard_page() {

	if ( ! function_exists( 'opcache_toolkit_user_can_manage_opcache' ) || ! opcache_toolkit_user_can_manage_opcache() ) {
		wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
	}

	do_action( 'add_meta_boxes_toplevel_page_opcache-toolkit', null );

	?>

	<div class="wrap">
	<h1><?php esc_html_e( 'OPcache Toolkit Dashboard', 'opcache-toolkit' ); ?></h1>
	<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>

	<div class="opcache-toolkit-layout">

		<!-- Sidebar navigation -->
		<aside class="opcache-toolkit-sidebar">
			<div class="highlight-bar"></div>
			<ul>
				<li><a href="#opcache_toolkit_mb_charts"><?php esc_html_e( 'Charts', 'opcache-toolkit' ); ?></a></li>
				<li><a href="#opcache_toolkit_mb_status"><?php esc_html_e( 'Live Status', 'opcache-toolkit' ); ?></a></li>
				<li><a href="#opcache_toolkit_mb_preload"><?php esc_html_e( 'Preload Progress', 'opcache-toolkit' ); ?></a></li>
				<li><a href="#opcache_toolkit_mb_health"><?php esc_html_e( 'System Health', 'opcache-toolkit' ); ?></a></li>
			</ul>
		</aside>

		<!-- Main content -->
		<main class="opcache-toolkit-main">
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="postbox-container-1" class="postbox-container">
						<?php do_meta_boxes( 'toplevel_page_opcache-toolkit', 'normal', null ); ?>
					</div>
					<div id="postbox-container-2" class="postbox-container">
						<?php do_meta_boxes( 'toplevel_page_opcache-toolkit', 'side', null ); ?>
					</div>
				</div>
			</div>
		</main>

	</div><!-- .opcache-toolkit-layout -->
</div>


	<?php
}
