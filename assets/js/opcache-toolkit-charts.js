// assets/js/opcache-toolkit-charts.js

document.addEventListener('DOMContentLoaded', () => {
	/* -------------------------------------------------------------
	 * 1. INITIAL CHART SETUP (using localized data)
	 * ------------------------------------------------------------- */

	const ctxHitRate = document.getElementById('opcacheToolkitHitRateChart');
	const ctxMemory = document.getElementById('opcacheToolkitMemoryChart');
	const ctxCached = document.getElementById('opcacheToolkitCachedChart');

	const hitRateChart = new Chart(ctxHitRate, {
		type: 'line',
		data: {
			labels: opcacheToolkitCharts.labels,
			datasets: [
				{
					label: 'Hit Rate',
					data: opcacheToolkitCharts.hitRate,
					borderColor: '#2271b1',
					tension: 0.2
				}
			]
		}
	});

	const memoryChart = new Chart(ctxMemory, {
		type: 'line',
		data: {
			labels: opcacheToolkitCharts.labels,
			datasets: [
				{
					label: 'Wasted Memory',
					data: opcacheToolkitCharts.wasted,
					borderColor: '#e55353',
					tension: 0.2
				}
			]
		}
	});

	const cachedChart = new Chart(ctxCached, {
		type: 'line',
		data: {
			labels: opcacheToolkitCharts.labels,
			datasets: [
				{
					label: 'Cached Scripts',
					data: opcacheToolkitCharts.cached,
					borderColor: '#38c172',
					tension: 0.2
				}
			]
		}
	});

	/* -------------------------------------------------------------
	 * 2. FETCH NEW DATA FROM REST ENDPOINT
	 * ------------------------------------------------------------- */

	async function fetchChartData() {
		const url = opcacheToolkitCharts.endpoint;

		const response = await fetch(url, {
			headers: {
				'X-WP-Nonce': opcacheToolkitCharts.nonce
			}
		});

		if (!response.ok) {
			console.error('Failed to fetch chart data');
			return null;
		}

		return await response.json();
	}

	/* -------------------------------------------------------------
	 * 3. UPDATE CHARTS WITH NEW DATA
	 * ------------------------------------------------------------- */

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

	/* -------------------------------------------------------------
	 * 4. MANUAL REFRESH BUTTON
	 * ------------------------------------------------------------- */

	const refreshBtn = document.getElementById('opcache-toolkit-refresh-charts');

	if (refreshBtn) {
		refreshBtn.addEventListener('click', async () => {
			refreshBtn.disabled = true;
			refreshBtn.textContent = 'Refreshing…';

			const data = await fetchChartData();
			updateCharts(data);

			refreshBtn.disabled = false;
			refreshBtn.textContent = 'Refresh Data';
		});
	}

	/* -------------------------------------------------------------
	 * 5. AUTO‑POLLING (LIVE REFRESH)
	 * ------------------------------------------------------------- */

	const POLL_INTERVAL = 60 * 1000; // 60 seconds

	setInterval(async () => {
		const data = await fetchChartData();
		updateCharts(data);
	}, POLL_INTERVAL);
});
