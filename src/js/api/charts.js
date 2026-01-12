/**
 * Charts API Helpers.
 *
 * @package
 */

/**
 * Fetch historical chart data.
 *
 * @param {string} endpoint - REST API endpoint.
 * @param {string} nonce    - WordPress nonce.
 * @return {Promise<Object>} Chart data.
 * @throws {Error} If fetch fails.
 */
export async function fetchChartData(endpoint, nonce) {
	const response = await fetch(endpoint, {
		headers: {
			'X-WP-Nonce': nonce
		}
	});

	if (!response.ok) {
		throw new Error(`Failed to fetch chart data: ${response.statusText}`);
	}

	return await response.json();
}
