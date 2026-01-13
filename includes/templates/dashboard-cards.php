<?php
/**
 * Dashboard cards for OPcache Toolkit.
 *
 * @package OPcacheToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; }

?>
<div class="opcache-toolkit-cards">
	<div class="opcache-toolkit-card" id="opcache-toolkit-card-hit-rate" data-opcache-tooltip="<?php esc_attr_e( 'The percentage of requests served from cache. Higher is better.', 'opcache-toolkit' ); ?>">
		<h3>Hit Rate</h3>
		<p class="value">—</p>
	</div>

	<div class="opcache-toolkit-card" id="opcache-toolkit-card-memory" data-opcache-tooltip="<?php esc_attr_e( 'The amount of OPcache memory currently being used.', 'opcache-toolkit' ); ?>">
		<h3>Memory Usage</h3>
		<p class="value">—</p>
	</div>

	<div class="opcache-toolkit-card" id="opcache-toolkit-card-cached" data-opcache-tooltip="<?php esc_attr_e( 'The number of PHP scripts currently stored in the cache.', 'opcache-toolkit' ); ?>">
		<h3>Cached Scripts</h3>
		<p class="value">—</p>
	</div>
</div>
