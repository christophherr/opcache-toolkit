<?php
/**
 * OPcache Toolkit – Email Alerts
 *
 * Sends an email notification when the OPcache hit rate
 * falls below the configured threshold.
 *
 * Multisite-aware via opcache_toolkit_get_setting().
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Triggered by the daily cron job.
 *
 * @param float $hit_rate The current OPcache hit rate.
 * @return void
 */
add_action(
	'opcache_toolkit_check_alerts',
	function ( $hit_rate ) {

		// Fetch threshold
		$threshold = (float) opcache_toolkit_get_setting( 'opcache_toolkit_alert_threshold', 90 );

		// Fetch email
		$email = opcache_toolkit_get_setting( 'opcache_toolkit_alert_email', get_option( 'admin_email' ) );

		// Allow customization
		$email   = apply_filters( 'opcache_toolkit_alert_email', $email );
		$subject = apply_filters( 'opcache_toolkit_alert_subject', esc_html__( 'OPcache Hit Rate Alert', 'opcache-toolkit' ) );

		$body = apply_filters(
			'opcache_toolkit_alert_body',
			sprintf(
				esc_html__( "Your OPcache hit rate has dropped to %.2f%%.\n\nThis may indicate memory pressure, plugin bloat, or excessive file changes.", 'opcache-toolkit' ),
				$hit_rate
			)
		);

		// Only send alert if hit rate is below threshold
		if ( $hit_rate < $threshold ) {

			wp_mail( $email, $subject, $body );

			if ( function_exists( 'opcache_toolkit_log' ) ) {
				opcache_toolkit_log( "Alert sent: hit rate {$hit_rate}% < threshold {$threshold}% to {$email}" );
			}
		}
	}
);
