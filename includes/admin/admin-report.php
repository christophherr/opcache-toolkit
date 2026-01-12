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
		<textarea readonly style="width:100%;height:400px;font-family:monospace;"><?php echo esc_textarea( opcache_toolkit_generate_system_report() ); ?></textarea>
		<p>
			<button class="button button-primary" onclick="navigator.clipboard.writeText(this.parentElement.previousElementSibling.value).then(() => alert('<?php esc_attr_e( 'Copied to clipboard!', 'opcache-toolkit' ); ?>'))">
				<?php esc_html_e( 'Copy to Clipboard', 'opcache-toolkit' ); ?>
			</button>
		</p>
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
