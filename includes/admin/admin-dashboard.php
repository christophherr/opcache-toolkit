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

		// Screen ID for this page is: toplevel_page_opcache-toolkit.
		if ( 'toplevel_page_opcache-toolkit' !== $hook ) {
			return;
		}

		// 1. Collect data for charts (ensure these variables exist).
		// Pull chart data directly from the repository.
		$data     = \OPcacheToolkit\Plugin::stats()->get_chart_data( 180 );
		$hit_rate = $data['hitRate'] ?? [];
		$labels   = $data['labels'] ?? [];
		$cached   = $data['cached'] ?? [];
		$wasted   = $data['wasted'] ?? [];

		wp_enqueue_script( 'postbox' );
		wp_enqueue_script( 'dashboard' );

		// Main Dashboard Bundle (includes Chart.js, Live Polling, Logger, etc).
		$script_path = 'assets/js/dashboard.js';
		$asset_file  = OPCACHE_TOOLKIT_PATH . 'assets/js/dashboard.asset.php';
		$asset       = file_exists( $asset_file ) ? include $asset_file : [
			'dependencies' => [ 'wp-i18n', 'jquery' ],
			'version'      => OPCACHE_TOOLKIT_VERSION,
		];

		wp_enqueue_script(
			'opcache-toolkit-dashboard',
			plugins_url( $script_path, OPCACHE_TOOLKIT_FILE ),
			array_merge( $asset['dependencies'], [ 'postbox', 'dashboard' ] ),
			$asset['version'],
			true
		);

		// Localize dashboard data.
		wp_localize_script(
			'opcache-toolkit-dashboard',
			'opcacheToolkitCharts',
			[
				'labels'   => $labels,
				'hitRate'  => $hit_rate,
				'cached'   => $cached,
				'wasted'   => $wasted,
				'endpoint' => rest_url( 'opcache-toolkit/v1/chart-data' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
			]
		);

		wp_localize_script(
			'opcache-toolkit-dashboard',
			'opcacheToolkitData',
			[
				'restUrl' => rest_url(),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);

		wp_localize_script(
			'opcache-toolkit-dashboard',
			'opcacheToolkitLive',
			[
				'statusEndpoint'  => rest_url( 'opcache-toolkit/v1/status' ),
				'healthEndpoint'  => rest_url( 'opcache-toolkit/v1/health' ),
				'preloadEndpoint' => rest_url( 'opcache-toolkit/v1/preload-progress' ),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'interval'        => 30000,
			]
		);

		// Widgets Bundle (Scroll-spy, Tooltips).
		$widgets_path       = 'assets/js/widgets.js';
		$widgets_asset_file = OPCACHE_TOOLKIT_PATH . 'assets/js/widgets.asset.php';
		$widgets_asset      = file_exists( $widgets_asset_file ) ? include $widgets_asset_file : [
			'dependencies' => [],
			'version'      => OPCACHE_TOOLKIT_VERSION,
		];

		wp_enqueue_script(
			'opcache-toolkit-widgets',
			plugins_url( $widgets_path, OPCACHE_TOOLKIT_FILE ),
			$widgets_asset['dependencies'],
			$widgets_asset['version'],
			true
		);

		wp_enqueue_style(
			'opcache-toolkit-theme',
			plugins_url( 'assets/css/opcache-toolkit-theme.css', OPCACHE_TOOLKIT_FILE ),
			[],
			filemtime( OPCACHE_TOOLKIT_PATH . 'assets/css/opcache-toolkit-theme.css' )
		);

		// You can keep a light dashboard CSS file for internal layout/colors if needed.
		wp_enqueue_style(
			'opcache-toolkit-dashboard',
			plugins_url( 'assets/css/opcache-toolkit-dashboard.css', OPCACHE_TOOLKIT_FILE ),
			[ 'opcache-toolkit-theme' ],
			filemtime( OPCACHE_TOOLKIT_PATH . 'assets/css/opcache-toolkit-dashboard.css' )
		);

		// Inline JS to init postboxes on this screen.
		wp_add_inline_script(
			'postbox',
			"jQuery(document).ready(function($){
                // Initialize postboxes for the dashboard screen.
                postboxes.add_postbox_toggles('toplevel_page_opcache-toolkit');

                // Resilient toggle handler for meta boxes.
                // We target only the header and use e.stopImmediatePropagation() to prevent double firing.
                $(document).on('click', '.postbox .postbox-header', function(e) {
                    // Ignore clicks on buttons or links inside the header.
                    if ($(e.target).filter('button, a, input, select, textarea').length > 0) {
                        return;
                    }

                    e.stopImmediatePropagation();

                    const postbox = $(this).closest('.postbox');
                    const isClosed = postbox.hasClass('closed');

                    if (isClosed) {
                        postbox.removeClass('closed');
                        postbox.find('.handlediv').attr('aria-expanded', 'true');
                    } else {
                        postbox.addClass('closed');
                        postbox.find('.handlediv').attr('aria-expanded', 'false');
                    }

                    // Manually trigger save state.
                    postboxes.save_state('toplevel_page_opcache-toolkit');

                    return false;
                });
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

		// Left column (normal).
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

		// Right column (side).
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
 *
 * @return void
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
 *
 * @return void
 */
function opcache_toolkit_mb_preload_callback() {
	include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-preload.php';
}

/**
 * Meta box callback: Live Status.
 *
 * @return void
 */
function opcache_toolkit_mb_status_callback() {
	include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-cards.php';
}

/**
 * Meta box callback: System Health.
 *
 * @return void
 */
function opcache_toolkit_mb_health_callback() {
	include OPCACHE_TOOLKIT_PATH . 'includes/templates/dashboard-health.php';
}

/**
 * Render the OPcache Toolkit dashboard page.
 *
 * @return void
 */
function opcache_toolkit_render_dashboard_page() {

	if ( ! opcache_toolkit_user_can_manage_opcache() ) {
		wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
	}

	// We must register the postboxes to ensure their state can be saved.
	// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	do_action( 'add_meta_boxes_toplevel_page_opcache-toolkit', null );

	?>

	<div class="wrap" id="opcache-toolkit-dashboard">
	<h1><?php esc_html_e( 'OPcache Toolkit Dashboard', 'opcache-toolkit' ); ?></h1>
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
				<form method="post" action="">
					<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce' ); ?>
					<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce' ); ?>
				</form>
				<?php do_meta_boxes( 'toplevel_page_opcache-toolkit', 'normal', null ); ?>
			</div>
			<div id="postbox-container-2" class="postbox-container">
				<?php do_meta_boxes( 'toplevel_page_opcache-toolkit', 'side', null ); ?>
			</div>
		</div> <!-- #post-body -->
	</div> <!-- #poststuff -->
		</main>

	</div><!-- .opcache-toolkit-layout -->
</div>
	<?php
}
