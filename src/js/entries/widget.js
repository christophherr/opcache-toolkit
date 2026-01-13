/**
 * Dashboard Widget Entry Point.
 *
 * @package
 */

import { CircuitBreaker } from '../services/CircuitBreaker';
import { fetchStatus, fetchHealth } from '../api/status';

document.addEventListener( 'DOMContentLoaded', () => {
	const { statusEndpoint, healthEndpoint, nonce } =
		window.opcacheToolkitWPAdminDashboard || {};
	const container = document.getElementById( 'opcache-toolkit-widget' );
	if ( ! container ) return;

	const breaker = new CircuitBreaker( 5, 60000 );

	async function updateWidget() {
		try {
			const status = await breaker.call( () =>
				fetchStatus( statusEndpoint, nonce )
			);
			const health = await breaker.call( () =>
				fetchHealth( healthEndpoint, nonce )
			);

			if ( ! status.success || ! health.success ) {
				container.innerHTML = `<div class="opcache-toolkit-error">${
					status.message || health.message || 'OPcache is unavailable.'
				}</div>`;
				return;
			}

			const hitRate =
				status.data.opcache_statistics?.opcache_hit_rate.toFixed( 1 ) + '%';
			const usedMem =
				( status.data.memory_usage?.used_memory / 1024 / 1024 ).toFixed( 1 ) +
				'MB';

			container.innerHTML = `
				<div class="opcache-widget-stats">
					<div class="stat"><strong>Hit Rate:</strong> <span>${ hitRate }</span></div>
					<div class="stat"><strong>Memory:</strong> <span>${ usedMem }</span></div>
				</div>
				<div class="opcache-widget-health">
					<strong>Health:</strong> ${
						health.data.issues && health.data.issues.length === 0
							? '✅ OK'
							: '⚠️ Issues Found'
					}
				</div>
			`;
		} catch ( err ) {
			container.innerHTML = `<div class="error">Failed to load status.</div>`;
		}
	}

	updateWidget();
} );
