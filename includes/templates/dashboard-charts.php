<?php
/**
 * Dashboard charts for OPcache Toolkit.
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

?>
<h2><?php esc_html_e( 'OPcache Performance Charts', 'opcache-toolkit' ); ?></h2>

<div style="max-width: 900px; margin-top: 30px;" data-opcache-tooltip="<?php esc_attr_e( 'Historical hit rate trend. Stable high rate is ideal.', 'opcache-toolkit' ); ?>">
	<canvas id="opcacheToolkitHitRateChart" height="120"></canvas>
</div>

<div style="max-width: 900px; margin-top: 50px;" data-opcache-tooltip="<?php esc_attr_e( 'Amount of memory wasted due to script updates. High waste may require a reset.', 'opcache-toolkit' ); ?>">
	<canvas id="opcacheToolkitMemoryChart" height="120"></canvas>
</div>

<div style="max-width: 900px; margin-top: 50px;" data-opcache-tooltip="<?php esc_attr_e( 'Total number of scripts cached over time.', 'opcache-toolkit' ); ?>">
	<canvas id="opcacheToolkitCachedChart" height="120"></canvas>
</div>
