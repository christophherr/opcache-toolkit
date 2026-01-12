document.addEventListener('DOMContentLoaded', () => {
	const el = document.getElementById('opcache-toolkit-widget');
	if (!el) return;

	const {
		statusEndpoint,
		healthEndpoint,
		preloadEndpoint,
		resetUrl,
		dashboardUrl,
		nonce
	} = opcacheToolkitWPAdminDashboard;

	async function fetchJSON(url) {
		const res = await fetch(url, {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': nonce }
		});

		const json = await res.json();

		if (!json.success) {
			throw new Error(json.message || 'Unknown error');
		}

		return json.data;
	}

	async function loadWidget() {
		try {
			const [status, health, preload] = await Promise.all([
				fetchJSON(statusEndpoint),
				fetchJSON(healthEndpoint),
				fetchJSON(preloadEndpoint)
			]);

			const mem = status.memory;
			const stats = status.stats;

			const hitRate = stats.opcache_hit_rate.toFixed(2);
			const cached = stats.num_cached_scripts;
			const maxCached = stats.max_cached_keys || '—';

			const memUsedPct = Math.round((mem.used_memory / mem.total_memory) * 100);
			const stringsPct = Math.round(
				(status.strings.used_memory / status.strings.buffer_size) * 100
			);

			el.innerHTML = `
                <div class="opcache-toolkit-widget-row">
                    <span class="opcache-toolkit-widget-label">Hit Rate</span>
                    <span>${hitRate}%</span>
                </div>

                <div class="opcache-toolkit-widget-row">
                    <span class="opcache-toolkit-widget-label">Cached Scripts</span>
                    <span>${cached} / ${maxCached}</span>
                </div>

                <div class="opcache-toolkit-widget-row">
                    <span class="opcache-toolkit-widget-label">Memory Used</span>
                    <span>${memUsedPct}%</span>
                </div>
                <div class="opcache-toolkit-widget-bar">
                    <div class="opcache-toolkit-widget-bar-fill" style="width:${memUsedPct}%"></div>
                </div>

                <div class="opcache-toolkit-widget-row">
                    <span class="opcache-toolkit-widget-label">Interned Strings</span>
                    <span>${stringsPct}%</span>
                </div>
                <div class="opcache-toolkit-widget-bar">
                    <div class="opcache-toolkit-widget-bar-fill" style="width:${stringsPct}%"></div>
                </div>

                <div class="opcache-toolkit-widget-row" style="margin-top:10px;">
                    <span class="opcache-toolkit-widget-label">Preload</span>
                    <span>${preload.done} / ${preload.total}</span>
                </div>

                <div class="opcache-toolkit-widget-actions">
                    <a href="${resetUrl}" class="opcache-toolkit-reset-btn">Reset OPcache</a>
                    <a href="${dashboardUrl}" class="opcache-toolkit-dashboard-btn">Full Dashboard</a>
                </div>
            `;
		} catch (err) {
			el.innerHTML = `<p>` + err + `</p>`;
		}
	}

	loadWidget();
});
