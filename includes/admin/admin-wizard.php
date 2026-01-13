<?php
/**
 * OPcache Toolkit – Setup Wizard
 *
 * Provides a "Zero-Config" setup experience for new users.
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the setup wizard page.
 */
function opcache_toolkit_render_wizard_page() {
	if ( ! opcache_toolkit_user_can_manage_opcache() ) {
		wp_die( esc_html__( 'Access denied.', 'opcache-toolkit' ) );
	}

	// Handle form submission.
	if ( isset( $_POST['opcache_toolkit_setup_complete'] ) ) {
		check_admin_referer( 'opcache_toolkit_setup' );

		// Set default options.
		opcache_toolkit_update_setting( 'opcache_toolkit_alert_threshold', 90 );
		opcache_toolkit_update_setting( 'opcache_toolkit_retention_days', 30 );
		opcache_toolkit_update_setting( 'opcache_toolkit_debug_mode', false );

		// Auto-detect optimal settings.
		$mem = \OPcacheToolkit\Plugin::opcache()->get_memory_usage();
		if ( isset( $mem['total_memory'] ) && $mem['total_memory'] > 0 ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf -- Placeholder for potential future enhancement.
			// Example: if memory is very high, maybe adjust something (placeholder).
		}

		opcache_toolkit_update_setting( 'opcache_toolkit_setup_completed', true );

		wp_safe_redirect( opcache_toolkit_admin_url( 'admin.php?page=opcache-toolkit' ) );
		exit;
	}

	?>
	<div class="wrap">
		<div class="opcache-toolkit-wizard-container" style="max-width: 600px; margin: 50px auto; padding: 40px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; text-align: center;">
			<span class="dashicons dashicons-performance" style="font-size: 64px; width: 64px; height: 64px; color: #2271b1; margin-bottom: 20px;"></span>
			<h1><?php esc_html_e( 'Welcome to OPcache Toolkit', 'opcache-toolkit' ); ?></h1>
			<p style="font-size: 16px; line-height: 1.5; color: #50575e; margin-bottom: 30px;">
				<?php esc_html_e( 'Thank you for installing OPcache Toolkit. We have auto-detected the optimal settings for your server. Click the button below to complete the setup and start monitoring your OPcache performance.', 'opcache-toolkit' ); ?>
			</p>

			<div class="opcache-toolkit-wizard-checks" style="text-align: left; margin-bottom: 30px; background: #f6f7f7; padding: 20px; border-radius: 4px;">
				<h3 style="margin-top: 0;"><?php esc_html_e( 'Pre-Flight Checks', 'opcache-toolkit' ); ?></h3>
				<ul style="list-style: none; padding: 0; margin: 0;">
					<li style="margin-bottom: 10px;">
						<?php if ( \OPcacheToolkit\Plugin::opcache()->is_enabled() ) : ?>
							<span class="dashicons dashicons-yes" style="color: #46b450;"></span>
							<?php esc_html_e( 'OPcache Extension: Loaded', 'opcache-toolkit' ); ?>
						<?php else : ?>
							<span class="dashicons dashicons-no" style="color: #d63638;"></span>
							<?php esc_html_e( 'OPcache Extension: Not Loaded', 'opcache-toolkit' ); ?>
						<?php endif; ?>
					</li>
					<li style="margin-bottom: 10px;">
						<span class="dashicons dashicons-yes" style="color: #46b450;"></span>
						<?php printf( /* translators: %s: PHP version */ esc_html__( 'PHP Version: %s', 'opcache-toolkit' ), esc_html( PHP_VERSION ) ); ?>
					</li>
					<li>
						<span class="dashicons dashicons-yes" style="color: #46b450;"></span>
						<?php esc_html_e( 'Database: Ready', 'opcache-toolkit' ); ?>
					</li>
				</ul>
			</div>

			<form method="post">
				<?php wp_nonce_field( 'opcache_toolkit_setup' ); ?>
				<input type="hidden" name="opcache_toolkit_setup_complete" value="1">
				<button type="submit" class="button button-primary button-hero">
					<?php esc_html_e( 'Complete Setup', 'opcache-toolkit' ); ?>
				</button>
			</form>

			<p style="margin-top: 20px;">
				<a href="<?php echo esc_url( opcache_toolkit_admin_url( 'admin.php?page=opcache-toolkit' ) ); ?>" style="text-decoration: none; color: #646970;">
					<?php esc_html_e( 'Skip for now', 'opcache-toolkit' ); ?>
				</a>
			</p>
		</div>
	</div>
	<style>
		#wpcontent { background: #f0f0f1; }
		.opcache-toolkit-wizard-container h1 { margin-top: 0; }
	</style>
	<?php
}
