/**
 * Status API Helpers.
 *
 * @package
 */

/**
 * Fetch current OPcache status.
 *
 * @param {string} endpoint - REST API endpoint.
 * @param {string} nonce    - WordPress nonce.
 * @return {Promise<Object>} Status data.
 * @throws {Error} If fetch fails.
 */
export async function fetchStatus( endpoint, nonce ) {
	const response = await fetch( endpoint, {
		headers: {
			'X-WP-Nonce': nonce
		}
	} );

	if ( ! response.ok ) {
		throw new Error( `Failed to fetch status: ${ response.statusText }` );
	}

	return await response.json();
}

/**
 * Fetch system health data.
 *
 * @param {string} endpoint - REST API endpoint.
 * @param {string} nonce    - WordPress nonce.
 * @return {Promise<Object>} Health data.
 * @throws {Error} If fetch fails.
 */
export async function fetchHealth( endpoint, nonce ) {
	const response = await fetch( endpoint, {
		headers: {
			'X-WP-Nonce': nonce
		}
	} );

	if ( ! response.ok ) {
		throw new Error( `Failed to fetch health: ${ response.statusText }` );
	}

	return await response.json();
}
