/**
 * Preload API Helpers.
 *
 * @package
 */

/**
 * Fetch current preload progress.
 *
 * @param {string} endpoint - REST API endpoint.
 * @param {string} nonce    - WordPress nonce.
 * @return {Promise<Object>} Progress data.
 * @throws {Error} If fetch fails.
 */
export async function fetchPreloadProgress( endpoint, nonce ) {
	const response = await fetch( endpoint, {
		headers: {
			'X-WP-Nonce': nonce
		}
	} );

	if ( ! response.ok ) {
		throw new Error(
			`Failed to fetch preload progress: ${ response.statusText }`
		);
	}

	return await response.json();
}
