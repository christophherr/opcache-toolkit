/**
 * Dashboard Entry Point.
 *
 * @package
 */

import { CircuitBreaker } from '../services/CircuitBreaker';
import { Logger } from '../services/Logger';
import { fetchStatus, fetchHealth } from '../api/status';
import { fetchChartData } from '../api/charts';
import { initSmoothScrolling, initScrollSpy, initTooltips } from '../utils/dom';
import Chart from 'chart.js/auto';
import zoomPlugin from 'chartjs-plugin-zoom';

Chart.register(zoomPlugin);

document.addEventListener('DOMContentLoaded', () => {
	/* -------------------------------------------------------------
	 * 1. INITIALIZATION
	 * ------------------------------------------------------------- */
	const { statusEndpoint, healthEndpoint, nonce, interval } =
		window.opcacheToolkitLive || {};
	const chartConfig = window.opcacheToolkitCharts || {};

	// Initialize Logger.
	const loggerEndpoint =
		window.opcacheToolkitData?.restUrl + 'opcache-toolkit/v1/log';
	const loggerNonce = window.opcacheToolkitData?.nonce;
	const logger = new Logger(loggerEndpoint, loggerNonce);
	logger.registerGlobalHandlers();
	window.opcacheToolkitLogger = logger;

	// Initialize Circuit Breaker.
	const breaker = new CircuitBreaker(5, 60000);
	window.opcacheToolkitCircuitBreaker = breaker;

	/* -------------------------------------------------------------
	 * 2. DOM ELEMENT REFERENCES
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
	const lastUpdated = document.getElementById('opcache-toolkit-last-updated');
	const refreshBtn = document.getElementById('opcache-toolkit-refresh-charts');
	const highlightBar = document.querySelector(
		'.opcache-toolkit-sidebar .highlight-bar'
	);

	/* -------------------------------------------------------------
	 * 3. CHART SETUP
	 * ------------------------------------------------------------- */
	const chartOptions = {
		responsive: true,
		maintainAspectRatio: false,
		animation: { duration: 800, easing: 'easeInOutQuart' },
		scales: { y: { beginAtZero: true } },
		plugins: { legend: { display: false } }
	};

	const hitRateChart = new Chart(
		document.getElementById('opcacheToolkitHitRateChart'),
		{
			type: 'line',
			data: {
				labels: chartConfig.labels,
				datasets: [
					{
						label: 'Hit Rate (%)',
						data: chartConfig.hitRate,
						borderColor: '#2271b1',
						backgroundColor: 'rgba(34, 113, 177, 0.1)',
						fill: true,
						tension: 0.3,
						pointRadius: 2
					}
				]
			},
			options: chartOptions
		}
	);

	const memoryChart = new Chart(
		document.getElementById('opcacheToolkitMemoryChart'),
		{
			type: 'line',
			data: {
				labels: chartConfig.labels,
				datasets: [
					{
						label: 'Wasted Memory (Bytes)',
						data: chartConfig.wasted,
						borderColor: '#e55353',
						backgroundColor: 'rgba(229, 83, 83, 0.1)',
						fill: true,
						tension: 0.3,
						pointRadius: 2
					}
				]
			},
			options: chartOptions
		}
	);

	const cachedChart = new Chart(
		document.getElementById('opcacheToolkitCachedChart'),
		{
			type: 'line',
			data: {
				labels: chartConfig.labels,
				datasets: [
					{
						label: 'Cached Scripts',
						data: chartConfig.cached,
						borderColor: '#38c172',
						backgroundColor: 'rgba(56, 193, 114, 0.1)',
						fill: true,
						tension: 0.3,
						pointRadius: 2
					}
				]
			},
			options: chartOptions
		}
	);

	/* -------------------------------------------------------------
	 * 4. UI UPDATE FUNCTIONS
	 * ------------------------------------------------------------- */
	function updateStatusCards(data) {
		if (!data) return;
		const s = data.opcache_statistics;
		const m = data.memory_usage;
		if (s && hitRateEl)
			hitRateEl.textContent = s.opcache_hit_rate.toFixed(2) + '%';
		if (m && memoryEl)
			memoryEl.textContent = (m.used_memory / 1024 / 1024).toFixed(1) + ' MB';
		if (s && cachedEl) cachedEl.textContent = s.num_cached_scripts;
	}

	function updateHealthPanel(json) {
		if (!json || !healthList) return;
		healthList.innerHTML = '';
		if (!json.success) {
			const li = document.createElement('li');
			li.textContent = json.message || 'Unable to check system health.';
			li.classList.add('issue');
			healthList.appendChild(li);
			return;
		}
		const { issues } = json.data;
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

	function updateCharts(data) {
		if (!data) return;
		hitRateChart.data.labels = data.labels;
		hitRateChart.data.datasets[0].data = data.hitRate;
		hitRateChart.update();
		memoryChart.data.labels = data.labels;
		memoryChart.data.datasets[0].data = data.wasted;
		memoryChart.update();
		cachedChart.data.labels = data.labels;
		cachedChart.data.datasets[0].data = data.cached;
		cachedChart.update();
	}

	function setLastUpdated() {
		if (lastUpdated)
			lastUpdated.textContent =
				'Last updated: ' + new Date().toLocaleTimeString();
	}

	/* -------------------------------------------------------------
	 * 5. POLLING & EVENTS
	 * ------------------------------------------------------------- */
	async function poll() {
		try {
			const status = await breaker.call(() =>
				fetchStatus(statusEndpoint, nonce)
			);
			updateStatusCards(status);

			const health = await breaker.call(() =>
				fetchHealth(healthEndpoint, nonce)
			);
			updateHealthPanel(health);

			const charts = await breaker.call(() =>
				fetchChartData(chartConfig.endpoint, chartConfig.nonce)
			);
			updateCharts(charts);

			setLastUpdated();
		} catch (err) {
			logger.error('Polling failed:', { error: err.message });
		}
	}

	if (statusEndpoint) {
		setInterval(poll, interval || 30000);
		poll();
	}

	if (refreshBtn) {
		refreshBtn.addEventListener('click', async () => {
			refreshBtn.disabled = true;
			const oldText = refreshBtn.textContent;
			refreshBtn.textContent = 'Refreshing…';
			await poll();
			refreshBtn.disabled = false;
			refreshBtn.textContent = oldText;
		});
	}

	/* -------------------------------------------------------------
	 * 6. UTILS (Sidebar, Tooltips)
	 * ------------------------------------------------------------- */
	initSmoothScrolling('.opcache-toolkit-sidebar a');
	initScrollSpy('.postbox[id]', '.opcache-toolkit-sidebar a', (link) => {
		if (!highlightBar) return;
		const rect = link.getBoundingClientRect();
		const sidebarRect = link
			.closest('.opcache-toolkit-sidebar')
			.getBoundingClientRect();
		highlightBar.style.top = rect.top - sidebarRect.top + 'px';
		highlightBar.style.height = rect.height + 'px';
	});
	initTooltips('[data-opcache-tooltip]');
});
