<?php
/**
 * OPcache Toolkit – Settings Page (Tabbed UI)
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( OPCACHE_TOOLKIT_IS_NETWORK ) {
	add_action(
		'network_admin_menu',
		function () {
			add_menu_page(
				esc_html__( 'OPcache Toolkit', 'opcache-toolkit' ),
				esc_html__( 'OPcache Toolkit', 'opcache-toolkit' ),
				'manage_network',
				'opcache-manager',
				'opcache_toolkit_render_settings_page'
			);
		}
	);
} else {
	add_action(
		'admin_menu',
		function () {
			add_options_page(
				esc_html__( 'OPcache Toolkit', 'opcache-toolkit' ),
				esc_html__( 'OPcache Toolkit', 'opcache-toolkit' ),
				'manage_options',
				'opcache-manager',
				'opcache_toolkit_render_settings_page'
			);
		}
	);
}

add_action(
	'admin_init',
	function () {
		register_setting(
			'opcache_toolkit_settings',
			'opcache_toolkit_alert_threshold',
			[
				'type'              => 'number',
				'sanitize_callback' => 'absint',
				'default'           => 90,
			]
		);

		register_setting(
			'opcache_toolkit_settings',
			'opcache_toolkit_alert_email',
			[
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => get_option( 'admin_email' ),
			]
		);

		register_setting(
			'opcache_toolkit_settings',
			'opcache_toolkit_auto_reset',
			[
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			]
		);

		register_setting(
			'opcache_toolkit_settings',
			'opcache_toolkit_retention_days',
			[
				'type'              => 'number',
				'sanitize_callback' => 'absint',
				'default'           => 90,
			]
		);
	}
);

/**
 * Get Setting
 *
 * @param string $key     Option name.
 * @param mixed  $default_value Default value.
 *
 * @return mixed
 */
function opcache_toolkit_get_setting( $key, $default_value = null ) {
	return OPCACHE_TOOLKIT_IS_NETWORK
		? get_site_option( $key, $default_value )
		: get_option( $key, $default_value );
}

/**
 * Render settings page
 *
 * @return void
 */
function opcache_toolkit_render_settings_page() {

	$cap = OPCACHE_TOOLKIT_IS_NETWORK ? 'manage_network' : 'manage_options';
	if ( ! current_user_can( $cap ) ) {
		wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
	}

	// Load settings.
	$threshold  = opcache_toolkit_get_setting( 'opcache_toolkit_alert_threshold', 90 );
	$email      = opcache_toolkit_get_setting( 'opcache_toolkit_alert_email', get_option( 'admin_email' ) );
	$auto_reset = opcache_toolkit_get_setting( 'opcache_toolkit_auto_reset', false );
	$retention  = opcache_toolkit_get_setting( 'opcache_toolkit_retention_days', 90 );

	// Handle saving network settings.
	if ( OPCACHE_TOOLKIT_IS_NETWORK && isset( $_POST['opcache_toolkit_save_network_settings'] ) ) {
		check_admin_referer( 'opcache_toolkit_network_settings' );

		$threshold  = isset( $_POST['opcache_toolkit_alert_threshold'] ) ? (float) $_POST['opcache_toolkit_alert_threshold'] : 90;
		$email      = isset( $_POST['opcache_toolkit_alert_email'] ) ? sanitize_email( wp_unslash( $_POST['opcache_toolkit_alert_email'] ) ) : get_option( 'admin_email' );
		$auto_reset = isset( $_POST['opcache_toolkit_auto_reset'] ) ? 1 : 0;
		$retention  = isset( $_POST['opcache_toolkit_retention_days'] ) ? (int) $_POST['opcache_toolkit_retention_days'] : 90;

		update_site_option( 'opcache_toolkit_alert_threshold', $threshold );
		update_site_option( 'opcache_toolkit_alert_email', $email );
		update_site_option( 'opcache_toolkit_auto_reset', $auto_reset );
		update_site_option( 'opcache_toolkit_retention_days', $retention );

		echo '<div class="updated"><p>' . esc_html__( 'Network settings saved.', 'opcache-toolkit' ) . '</p></div>';
	}

	// Preload report.
	$report = opcache_toolkit_get_preload_report();

	wp_enqueue_style(
		'opcache-toolkit-theme',
		plugins_url( 'assets/css/opcache-toolkit-theme.css', OPCACHE_TOOLKIT_FILE ),
		[],
		filemtime( OPCACHE_TOOLKIT_PATH . 'assets/css/opcache-toolkit-theme.css' )
	);

	wp_enqueue_style(
		'opcache-toolkit-settings',
		plugins_url( 'assets/css/opcache-toolkit-settings.css', OPCACHE_TOOLKIT_FILE ),
		[ 'opcache-toolkit-theme' ],
		filemtime( OPCACHE_TOOLKIT_PATH . 'assets/css/opcache-toolkit-settings.css' )
	);

	// Settings JS using modular build.
	$script_path = 'assets/js/settings.js';
	$asset_file  = OPCACHE_TOOLKIT_PATH . 'assets/js/settings.asset.php';
	$asset       = file_exists( $asset_file ) ? include $asset_file : [
		'dependencies' => [],
		'version'      => OPCACHE_TOOLKIT_VERSION,
	];

	wp_enqueue_script(
		'opcache-toolkit-settings',
		plugins_url( $script_path, OPCACHE_TOOLKIT_FILE ),
		$asset['dependencies'],
		$asset['version'],
		true
	);
	?>

	<div class="wrap opcache-toolkit-settings-wrap">
		<h1><?php esc_html_e( 'OPcache Toolkit Settings', 'opcache-toolkit' ); ?></h1>

		<!-- Tabs -->
		<nav class="opcache-toolkit-tabs">
			<a href="#" data-tab="general" class="active">General</a>
			<a href="#" data-tab="preload">Preload</a>
			<a href="#" data-tab="advanced">Advanced</a>
		</nav>

		<!-- GENERAL TAB -->
		<section id="opcache-toolkit-tab-general" class="opcache-toolkit-tab active">
			<?php if ( OPCACHE_TOOLKIT_IS_NETWORK ) : ?>
				<form method="post" action="">
					<?php wp_nonce_field( 'opcache_toolkit_network_settings' ); ?>
					<input type="hidden" name="opcache_toolkit_save_network_settings" value="1">
			<?php else : ?>
				<form method="post" action="options.php">
					<?php settings_fields( 'opcache_toolkit_settings' ); ?>
			<?php endif; ?>

				<h2><?php esc_html_e( 'Email Alerts', 'opcache-toolkit' ); ?></h2>

				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Hit Rate Threshold', 'opcache-toolkit' ); ?></th>
						<td>
							<input type="number" id="opcache_toolkit_alert_threshold"
									name="opcache_toolkit_alert_threshold"
									value="<?php echo esc_attr( $threshold ); ?>"
									min="0" max="100" step="1">
							%
							<p class="description">
								<?php esc_html_e( 'Send an alert if OPcache hit rate drops below this value.', 'opcache-toolkit' ); ?>
							</p>
							<p class="opcache-toolkit-error" id="opcache_toolkit_alert_threshold_error"></p>
						</td>
					</tr>

					<tr>
						<th><?php esc_html_e( 'Alert Email', 'opcache-toolkit' ); ?></th>
						<td>
							<input type="email" id="opcache_toolkit_alert_email"
									name="opcache_toolkit_alert_email"
									value="<?php echo esc_attr( $email ); ?>"
									class="regular-text">
							<p class="opcache-toolkit-error" id="opcache_toolkit_alert_email_error"></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Automatic OPcache Reset', 'opcache-toolkit' ); ?></h2>

				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Auto Reset', 'opcache-toolkit' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="opcache_toolkit_auto_reset" value="1"
									<?php checked( $auto_reset, 1 ); ?>>
								<?php esc_html_e( 'Reset OPcache after plugin/theme updates', 'opcache-toolkit' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Data Retention', 'opcache-toolkit' ); ?></h2>

				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Retention Days', 'opcache-toolkit' ); ?></th>
						<td>
							<input type="number" id="opcache_toolkit_retention_days"
									name="opcache_toolkit_retention_days"
									value="<?php echo esc_attr( $retention ); ?>"
									min="1" step="1">
							<p class="opcache-toolkit-error" id="opcache_toolkit_retention_days_error"></p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</section>

		<!-- PRELOAD TAB -->
		<section id="opcache-toolkit-tab-preload" class="opcache-toolkit-tab">
			<h2><?php esc_html_e( 'OPcache Preload Report', 'opcache-toolkit' ); ?></h2>

			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Last Preload Run', 'opcache-toolkit' ); ?></th>
					<td><?php echo esc_html( $report['time'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Files Compiled', 'opcache-toolkit' ); ?></th>
					<td><?php echo esc_html( $report['count'] ); ?></td>
				</tr>
			</table>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="opcache_toolkit_preload">
				<?php submit_button( esc_html__( 'Run Preload Now', 'opcache-toolkit' ) ); ?>
			</form>
		</section>

		<!-- ADVANCED TAB -->
		<section id="opcache-toolkit-tab-advanced" class="opcache-toolkit-tab">
			<h2><?php esc_html_e( 'Advanced Tools', 'opcache-toolkit' ); ?></h2>

			<h3><?php esc_html_e( 'Reset OPcache', 'opcache-toolkit' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'opcache_toolkit_clear' ); ?>
				<input type="hidden" name="action" value="opcache_toolkit_clear">
				<?php submit_button( esc_html__( 'Reset OPcache Now', 'opcache-toolkit' ), 'delete' ); ?>
			</form>

			<hr>

			<h3><?php esc_html_e( 'Clear OPcache Statistics', 'opcache-toolkit' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'opcache_toolkit_clear_stats' ); ?>
				<input type="hidden" name="action" value="opcache_toolkit_clear_stats">
				<?php submit_button( esc_html__( 'Clear Statistics', 'opcache-toolkit' ), 'delete' ); ?>
			</form>

			<hr>

			<h3><?php esc_html_e( 'Export OPcache Statistics', 'opcache-toolkit' ); ?></h3>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'opcache_toolkit_export_stats' ); ?>
				<input type="hidden" name="action" value="opcache_toolkit_export_stats">
				<?php submit_button( esc_html__( 'Download CSV', 'opcache-toolkit' ) ); ?>
			</form>

			<hr>

			<h3><?php esc_html_e( 'Debug Information', 'opcache-toolkit' ); ?></h3>
			<?php
			if ( function_exists( 'opcache_get_configuration' ) ) {
				// Debug output is intentionally unescaped inside <pre>.
				?>
				<pre class="opcache-toolkit-debug">
				<?php
				print_r( opcache_get_configuration() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- intentional
				?>
				</pre>
				<?php
			} else {
				esc_html_e( 'OPcache configuration is not available on this server.', 'opcache-toolkit' );
			}
			?>
		</section>
	</div>

	<?php
}
