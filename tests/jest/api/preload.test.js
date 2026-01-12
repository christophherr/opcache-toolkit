/**
 * Tests for Preload API.
 *
 * @package
 */

import { fetchPreloadProgress } from '../../../src/js/api/preload';

describe('Preload API', () => {
	const endpoint =
		'http://example.com/wp-json/opcache-toolkit/v1/preload-progress';
	const nonce = 'mock-nonce';

	beforeEach(() => {
		jest.clearAllMocks();
	});

	test('fetchPreloadProgress should call fetch with correct parameters', async () => {
		const mockData = { progress: 50 };
		global.fetch.mockResolvedValueOnce({
			ok: true,
			json: () => Promise.resolve(mockData)
		});

		const result = await fetchPreloadProgress(endpoint, nonce);

		expect(global.fetch).toHaveBeenCalledWith(endpoint, {
			headers: {
				'X-WP-Nonce': nonce
			}
		});
		expect(result).toEqual(mockData);
	});

	test('fetchPreloadProgress should throw error on failure', async () => {
		global.fetch.mockResolvedValueOnce({
			ok: false,
			statusText: 'Not Found'
		});

		await expect(fetchPreloadProgress(endpoint, nonce)).rejects.toThrow(
			'Failed to fetch preload progress: Not Found'
		);
	});
});
