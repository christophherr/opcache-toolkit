// assets/js/opcache-toolkit-live.js

document.addEventListener('DOMContentLoaded', () => {
	if (!window.opcacheToolkitLive) {
		console.warn('opcacheToolkitLive config missing');
		return;
	}

	const { statusEndpoint, healthEndpoint, preloadEndpoint, nonce, interval } =
		opcacheToolkitLive;

	/* -------------------------------------------------------------
	 * ELEMENT REFERENCES
	 * ------------------------------------------------------------- */
	const hitRateEl = document.querySelector(
		'#opcache-toolkit-card-hit-rate .value'
	);
	const memoryEl = document.querySelector(
		'#opcache-toolkit-card-memory .value'
	);
	const cachedEl = document.querySelector(
		'#opcache-toolkit-card-cached .value'
	);
	const healthList = document.getElementById('opcache-toolkit-health-list');
	const preloadWrap = document.getElementById(
		'opcache-toolkit-preload-progress'
	);
	const preloadBar = preloadWrap
		? preloadWrap.querySelector('.bar-fill')
		: null;
	const preloadText = preloadWrap
		? preloadWrap.querySelector('.progress-text')
		: null;

	const toggleBtn = document.getElementById(
		'opcache-toolkit-toggle-auto-refresh'
	);
	const lastUpdated = document.getElementById('opcache-toolkit-last-updated');
	let retryDelay = 0;
	const maxRetryDelay = 300000; // 5 minutes

	// Error display area
	const errorBox = document.createElement('div');
	errorBox.style.color = '#d63638';
	errorBox.style.marginTop = '10px';
	errorBox.style.fontSize = '12px';
	errorBox.style.display = 'none';
	lastUpdated?.parentNode?.appendChild(errorBox);

	let autoRefresh = true;
	let pollTimer = null;

	/* -------------------------------------------------------------
	 * UTIL: SHOW ERROR MESSAGES IN UI
	 * ------------------------------------------------------------- */
	function showError(msg) {
		if (!errorBox) return;
		errorBox.textContent = msg;
		errorBox.style.display = 'block';
	}

	function clearError() {
		if (!errorBox) return;
		errorBox.style.display = 'none';
		errorBox.textContent = '';
	}

	/* -------------------------------------------------------------
	 * LIVE / PAUSED INDICATOR
	 * ------------------------------------------------------------- */
	function setLiveIndicator(state) {
		if (!window.opcacheToolkitLiveIndicator) {
			window.opcacheToolkitLiveIndicator = document.getElementById(
				'opcache-toolkit-live-indicator'
			);
		}
		if (!window.opcacheToolkitLiveIndicator) return;

		if (state === 'live') {
			window.opcacheToolkitLiveIndicator.textContent = 'Live';
			window.opcacheToolkitLiveIndicator.classList.add('live');
			window.opcacheToolkitLiveIndicator.classList.remove('paused');
		} else {
			window.opcacheToolkitLiveIndicator.textContent = 'Paused';
			window.opcacheToolkitLiveIndicator.classList.add('paused');
			window.opcacheToolkitLiveIndicator.classList.remove('live');
		}
	}

	/* -------------------------------------------------------------
	 * UTIL: UPDATE "LAST UPDATED" LABEL
	 * ------------------------------------------------------------- */
	function setLastUpdated() {
		if (!lastUpdated) return;
		const now = new Date();
		const time = now.toLocaleTimeString();
		lastUpdated.textContent = 'Last updated: ' + time;
	}

	/* -------------------------------------------------------------
	 * FETCH HELPER
	 * ------------------------------------------------------------- */
	async function fetchJson(url) {
		if (!url) return null;

		try {
			const res = await fetch(url, {
				headers: { 'X-WP-Nonce': nonce }
			});

			if (!res.ok) {
				showError(`Error ${res.status}: ${res.statusText}`);
				retryDelay = Math.min(maxRetryDelay, (retryDelay || 5000) * 2);
				return null;
			}

			clearError();
			retryDelay = 0;
			return await res.json();
		} catch (err) {
			showError('Network error: ' + err.message);
			retryDelay = Math.min(maxRetryDelay, (retryDelay || 5000) * 2);
			return null;
		}
	}

	/* -------------------------------------------------------------
	 * UPDATE: LIVE STATUS CARDS
	 * ------------------------------------------------------------- */
	function updateStatusCards(data) {
		if (!data) return;

		const s = data.opcache_statistics;
		const m = data.memory_usage;

		if (s && hitRateEl) {
			hitRateEl.textContent = s.opcache_hit_rate.toFixed(2) + '%';
		}

		if (m && memoryEl) {
			const usedMB = (m.used_memory / 1024 / 1024).toFixed(1);
			memoryEl.textContent = usedMB + ' MB';
		}

		if (s && cachedEl) {
			cachedEl.textContent = s.num_cached_scripts;
		}
	}

	/* -------------------------------------------------------------
	 * UPDATE: HEALTH PANEL
	 * ------------------------------------------------------------- */
	function updateHealthPanel(json) {
		if (!json || !healthList) return;

		healthList.innerHTML = '';

		// Handle error responses
		if (!json.success) {
			const li = document.createElement('li');
			li.textContent = json.message || 'Unable to check system health.';
			li.classList.add('issue');
			healthList.appendChild(li);
			return;
		}

		const { issues, ok } = json.data;

		if (Array.isArray(issues) && issues.length) {
			issues.forEach((msg) => {
				const li = document.createElement('li');
				li.textContent = msg;
				li.classList.add('issue');
				healthList.appendChild(li);
			});
		} else {
			const li = document.createElement('li');
			li.textContent = 'All checks passed.';
			li.classList.add('ok');
			healthList.appendChild(li);
		}
	}

	/* -------------------------------------------------------------
	 * UPDATE: PRELOAD PROGRESS
	 * ------------------------------------------------------------- */
	function updatePreloadProgress(json) {
		if (!json || !preloadBar || !preloadText) return;

		if (!json.success) {
			preloadText.textContent =
				json.message || 'OPcache not available on this server.';
			preloadBar.style.width = '0%';
			return;
		}

		const data = json.data;

		const total = Number(data.total || 0);
		const done = Number(data.done || 0);

		let percent = 0;
		if (total > 0) {
			percent = Math.round((done / total) * 100);
		}

		preloadBar.style.width = percent + '%';
		preloadText.textContent =
			total > 0
				? `${percent}% (${done}/${total} scripts)`
				: 'OPcache not available on this server.';
	}

	/* -------------------------------------------------------------
	 * UPDATE: CHARTS (REUSE SAME POLLING TICK)
	 * ------------------------------------------------------------- */
	function updateChartsFromLiveData(statusData) {
		if (!statusData || !statusData.success) return;
		if (!window.opcacheToolkitCharts || !window.Chart) return;

		const s = statusData.opcache_statistics;
		if (!s) return;

		// Update hit rate chart
		if (window.opcacheToolkitHitRateChart) {
			window.opcacheToolkitHitRateChart.data.labels.push(
				new Date().toLocaleTimeString()
			);
			window.opcacheToolkitHitRateChart.data.datasets[0].data.push(
				s.opcache_hit_rate
			);
			window.opcacheToolkitHitRateChart.update();
		}

		// Update cached scripts chart
		if (window.opcacheToolkitCachedChart) {
			window.opcacheToolkitCachedChart.data.labels.push(
				new Date().toLocaleTimeString()
			);
			window.opcacheToolkitCachedChart.data.datasets[0].data.push(
				s.num_cached_scripts
			);
			window.opcacheToolkitCachedChart.update();
		}

		// Update wasted memory chart
		if (window.opcacheToolkitMemoryChart && statusData.memory_usage) {
			const m = statusData.memory_usage;
			const wastedMB = (m.wasted_memory / 1024 / 1024).toFixed(1);

			window.opcacheToolkitMemoryChart.data.labels.push(
				new Date().toLocaleTimeString()
			);
			window.opcacheToolkitMemoryChart.data.datasets[0].data.push(wastedMB);
			window.opcacheToolkitMemoryChart.update();
		}
	}

	/* -------------------------------------------------------------
	 * ONE POLL TICK: ALL ENDPOINTS
	 * ------------------------------------------------------------- */
	async function pollOnce() {
		if (!autoRefresh) return;
		if (document.hidden) return;

		if (retryDelay > 0) {
			console.warn(`Retrying in ${retryDelay}ms`);
			setTimeout(pollOnce, retryDelay);
			return;
		}

		try {
			const [statusData, healthData, preloadData] = await Promise.all([
				fetchJson(statusEndpoint),
				fetchJson(healthEndpoint),
				fetchJson(preloadEndpoint)
			]);

			updateStatusCards(statusData);
			updateHealthPanel(healthData);
			updatePreloadProgress(preloadData);
			updateChartsFromLiveData(statusData);

			setLastUpdated();
		} catch (err) {
			showError('Polling error: ' + err.message);
		}
	}

	/* -------------------------------------------------------------
	 * AUTO-REFRESH TIMER
	 * ------------------------------------------------------------- */
	function startPolling() {
		if (pollTimer) return;
		const ms = interval || 60000;
		pollTimer = setInterval(pollOnce, ms);
	}

	function stopPolling() {
		if (!pollTimer) return;
		clearInterval(pollTimer);
		pollTimer = null;
	}

	/* -------------------------------------------------------------
	 * PAUSE / RESUME BUTTON
	 * ------------------------------------------------------------- */
	if (toggleBtn) {
		toggleBtn.addEventListener('click', () => {
			autoRefresh = !autoRefresh;

			if (autoRefresh) {
				toggleBtn.textContent = 'Pause Auto-Refresh';
				setLiveIndicator('live'); // ← ADDED
				startPolling();
				pollOnce();
			} else {
				toggleBtn.textContent = 'Resume Auto-Refresh';
				setLiveIndicator('paused'); // ← ADDED
				stopPolling();
			}
		});
	}

	/* -------------------------------------------------------------
	 * INITIAL RUN
	 * ------------------------------------------------------------- */
	setLiveIndicator('live');
	pollOnce();
	startPolling();
});
