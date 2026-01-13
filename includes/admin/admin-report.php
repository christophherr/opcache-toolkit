<?php
/**
 * System Report Page.
 *
 * @package OPcacheToolkit
 */

declare( strict_types=1 );

/**
 * Render the System Report page.
 *
 * @return void
 */
function opcache_toolkit_render_system_report(): void {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'System Report', 'opcache-toolkit' ); ?></h1>
		<p><?php esc_html_e( 'Copy and paste this report when requesting support.', 'opcache-toolkit' ); ?></p>
		<textarea style="width: 35%; height:400px;" readonly style="width:100%;height:400px;font-family:monospace;"><?php echo esc_textarea( opcache_toolkit_generate_system_report() ); ?></textarea>
		<p>
			<button class="button button-primary" onclick="navigator.clipboard.writeText(this.parentElement.previousElementSibling.value).then(() => alert('<?php esc_attr_e( 'Copied to clipboard!', 'opcache-toolkit' ); ?>'))">
				<?php esc_html_e( 'Copy to Clipboard', 'opcache-toolkit' ); ?>
			</button>
		</p>
	</div>
	<div class="wrap">
		<h2><?php esc_html_e( 'Error Log', 'opcache-toolkit' ); ?></h2>
		<?php
		$logger   = \OPcacheToolkit\Plugin::logger();
		$log_file = $logger->get_log_file( 'php' );
		$fs       = $logger->get_filesystem();
		$log_data = '';

		if ( $fs && $fs->exists( $log_file ) ) {
			$log_data = $fs->get_contents( $log_file );
			// Show only last 500 lines if log is huge.
			$lines = explode( "\n", $log_data );
			if ( count( $lines ) > 500 ) {
				$lines    = array_slice( $lines, -500 );
				$log_data = implode( "\n", $lines );
			}
		} else {
			$log_data = __( 'No log file found.', 'opcache-toolkit' );
		}
		?>
		<textarea readonly style="width:100%;height:400px;font-family:monospace;background:#f0f0f1;"><?php echo esc_textarea( $log_data ); ?></textarea>

		<div style="margin-top: 15px;">
			<form method="post" action="<?php echo esc_url( opcache_toolkit_admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;">
				<?php wp_nonce_field( 'opcache_toolkit_download_log' ); ?>
				<input type="hidden" name="action" value="opcache_toolkit_download_log">
				<button type="submit" class="button button-secondary">
					<?php esc_html_e( 'Download Log File', 'opcache-toolkit' ); ?>
				</button>
			</form>

			<form method="post" action="<?php echo esc_url( opcache_toolkit_admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block; margin-left: 10px;">
				<?php wp_nonce_field( 'opcache_toolkit_delete_log' ); ?>
				<input type="hidden" name="action" value="opcache_toolkit_delete_log">
				<button type="submit" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete the log file?', 'opcache-toolkit' ); ?>')">
					<?php esc_html_e( 'Delete Log File', 'opcache-toolkit' ); ?>
				</button>
			</form>
		</div>
	</div>
	<?php
}

/**
 * Generate the system report text.
 *
 * @return string
 */
function opcache_toolkit_generate_system_report(): string {
	$report = [];

	$report[] = '### OPcache Toolkit System Report';
	$report[] = 'Generated: ' . current_time( 'mysql' );
	$report[] = '';

	$report[] = '### Environment';
	$report[] = 'PHP Version: ' . PHP_VERSION;
	$report[] = 'WordPress Version: ' . get_bloginfo( 'version' );
	$report[] = 'Plugin Version: ' . OPCACHE_TOOLKIT_VERSION;
	$report[] = 'Multisite: ' . ( is_multisite() ? 'Yes' : 'No' );
	$report[] = 'WP_DEBUG: ' . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled' );
	$report[] = '';

	$report[] = '### OPcache Status';
	$status   = \OPcacheToolkit\Plugin::opcache()->get_status();
	$report[] = 'Enabled: ' . ( null !== $status ? 'Yes' : 'No' );
	if ( $status ) {
		$report[] = 'Hit Rate: ' . number_format( \OPcacheToolkit\Plugin::opcache()->get_hit_rate(), 2 ) . '%';
		$report[] = 'Cached Scripts: ' . ( $status['opcache_statistics']['num_cached_scripts'] ?? 0 );
		$report[] = 'Memory Used: ' . size_format( $status['memory_usage']['used_memory'] ?? 0 );
		$report[] = 'Wasted Memory: ' . size_format( $status['memory_usage']['wasted_memory'] ?? 0 );
	}
	$report[] = '';

	$report[] = '### OPcache Configuration';
	$config   = \OPcacheToolkit\Plugin::opcache()->get_configuration();
	if ( is_array( $config ) ) {
		foreach ( $config as $key => $value ) {
			$report[] = sprintf( '%s: %s', $key, $value['local_value'] ?? 'N/A' );
		}
	}
	$report[] = '';

	return implode( "\n", $report );
}

/**
 * Handle Download Log File action.
 *
 * @return void
 */
add_action(
	'admin_post_opcache_toolkit_download_log',
	function () {
		if ( ! opcache_toolkit_user_can_manage_opcache() ) {
			wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
		}

		check_admin_referer( 'opcache_toolkit_download_log' );

		$logger   = \OPcacheToolkit\Plugin::logger();
		$log_file = $logger->get_log_file( 'php' );
		$fs       = $logger->get_filesystem();

		if ( ! $fs || ! $fs->exists( $log_file ) ) {
			wp_die( esc_html__( 'Log file not found.', 'opcache-toolkit' ) );
		}

		$content = $fs->get_contents( $log_file );

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="opcache-toolkit-plugin.log"' );
		header( 'Content-Length: ' . strlen( $content ) );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
);

/**
 * Handle Delete Log File action.
 *
 * @return void
 */
add_action(
	'admin_post_opcache_toolkit_delete_log',
	function () {
		if ( ! opcache_toolkit_user_can_manage_opcache() ) {
			wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
		}

		check_admin_referer( 'opcache_toolkit_delete_log' );

		\OPcacheToolkit\Plugin::logger()->delete_log( 'php' );

		wp_safe_redirect( opcache_toolkit_admin_url( 'admin.php?page=opcache-toolkit-report' ) );
		exit;
	}
);
