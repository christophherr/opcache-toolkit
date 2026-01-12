<?php
/**
 * OPcache Toolkit – Admin Dashboard
 *
 * Renders the dashboard page with charts and stats.
 *
 * @package OPcacheToolkit
 */

/**
 * Enqueue scripts and styles for the OPcache Toolkit dashboard.
 */
add_action(
	'admin_enqueue_scripts',
	function ( $hook ) {

		// Only load on OPcache Toolkit dashboard
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
		// 2. Scripts: Chart.js, zoom plugin, charts logic, widgets logic
		// ---------------------------------------------------------------------

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

		// Widgets (collapsible, scroll‑spy, highlight bar, etc.)
		wp_enqueue_script(
			'opcache-toolkit-widgets',
			plugins_url( 'assets/js/opcache-toolkit-widgets.js', OPCACHE_TOOLKIT_FILE ),
			[],
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

		wp_enqueue_style(
			'opcache-toolkit-dashboard',
			plugins_url( 'assets/css/opcache-toolkit-dashboard.css', OPCACHE_TOOLKIT_FILE ),
			[ 'opcache-toolkit-theme' ],
			filemtime( OPCACHE_TOOLKIT_PATH . 'assets/css/opcache-toolkit-dashboard.css' )
		);
	}
);

/**
 * Render the OPcache Toolkit dashboard page.
 */
function opcache_toolkit_render_dashboard_page() {

	if ( ! function_exists( 'opcache_toolkit_user_can_manage_opcache' ) || ! opcache_toolkit_user_can_manage_opcache() ) {
		wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
	}
	?>

	<div class="wrap">
		<h1><?php esc_html_e( 'OPcache Toolkit Dashboard', 'opcache-toolkit' ); ?></h1>

		<div class="opcache-toolkit-layout">

			<!-- Sidebar navigation -->
			<aside class="opcache-toolkit-sidebar">
				<div class="highlight-bar"></div>
				<ul>
					<li><a href="#charts"><?php esc_html_e( 'Charts', 'opcache-toolkit' ); ?></a></li>
					<li><a href="#status"><?php esc_html_e( 'Live Status', 'opcache-toolkit' ); ?></a></li>
					<li><a href="#preload"><?php esc_html_e( 'Preload Progress', 'opcache-toolkit' ); ?></a></li>
					<li><a href="#health"><?php esc_html_e( 'System Health', 'opcache-toolkit' ); ?></a></li>
				</ul>
				<button id="opcache-toolkit-reset-layout" class="button button-secondary">
					<?php esc_html_e( 'Reset Widget Layout', 'opcache-toolkit' ); ?>
				</button>
			</aside>

			<!-- Main content -->
			<main class="opcache-toolkit-main">

				<div class="opcache-toolkit-dashboard">
					<div class="opcache-toolkit-widgets" id="opcache-toolkit-widgets">

						<!-- Charts Widget -->
						<section id="charts" class="opcache-toolkit-widget" data-widget>
							<div class="opcache-toolkit-widget-header">
								<!-- <button class="opcache-toolkit-drag-handle" type="button" aria-label="<?php esc_attr_e( 'Drag widget', 'opcache-toolkit' ); ?>">⋮⋮</button> -->
								<h3><?php esc_html_e( 'OPcache Performance Charts', 'opcache-toolkit' ); ?></h3>
								<button class="opcache-toolkit-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle widget', 'opcache-toolkit' ); ?>">▼</button>
							</div>
							<div class="opcache-toolkit-widget-body">
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
								?>
							</div>
						</section>

						<!-- Preload Progress Widget -->
						<section id="preload" class="opcache-toolkit-widget" data-widget>
							<div class="opcache-toolkit-widget-header">
								<!-- <button class="opcache-toolkit-drag-handle" type="button" aria-label="<?php esc_attr_e( 'Drag widget', 'opcache-toolkit' ); ?>">⋮⋮</button> -->
								<h3><?php esc_html_e( 'Preload Progress', 'opcache-toolkit' ); ?></h3>
								<button class="opcache-toolkit-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle widget', 'opcache-toolkit' ); ?>">▼</button>
							</div>
							<div class="opcache-toolkit-widget-body">
								<?php
								include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-preload.php';
								?>
							</div>
						</section>

						<!-- Live Status Widget -->
						<section id="status" class="opcache-toolkit-widget" data-widget>
							<div class="opcache-toolkit-widget-header">
								<!-- <button class="opcache-toolkit-drag-handle" type="button" aria-label="<?php esc_attr_e( 'Drag widget', 'opcache-toolkit' ); ?>">⋮⋮</button> -->
								<h3><?php esc_html_e( 'Live Status', 'opcache-toolkit' ); ?></h3>
								<button class="opcache-toolkit-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle widget', 'opcache-toolkit' ); ?>">▼</button>
							</div>
							<div class="opcache-toolkit-widget-body">
								<?php
								include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-cards.php';
								?>
							</div>
						</section>

						<!-- System Health Widget -->
						<section id="health" class="opcache-toolkit-widget" data-widget>
							<div class="opcache-toolkit-widget-header">
								<!-- <button class="opcache-toolkit-drag-handle" type="button" aria-label="<?php esc_attr_e( 'Drag widget', 'opcache-toolkit' ); ?>">⋮⋮</button> -->
								<h3><?php esc_html_e( 'System Health', 'opcache-toolkit' ); ?></h3>
								<button class="opcache-toolkit-toggle" type="button" aria-label="<?php esc_attr_e( 'Toggle widget', 'opcache-toolkit' ); ?>">▼</button>
							</div>
							<div class="opcache-toolkit-widget-body">
								<?php
								include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-health.php';
								?>
							</div>
						</section>

					</div><!-- .opcache-toolkit-widgets -->
				</div><!-- .opcache-toolkit-dashboard -->

			</main>

		</div><!-- .opcache-toolkit-layout -->
	</div><!-- .wrap -->

	<?php
}
