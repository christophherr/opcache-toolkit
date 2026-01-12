/**
 * Tests for Status API.
 *
 * @package
 */

import { fetchStatus, fetchHealth } from '../../../src/js/api/status';

describe('Status API', () => {
	const endpoint = 'http://example.com/wp-json/opcache-toolkit/v1/status';
	const nonce = 'mock-nonce';

	beforeEach(() => {
		jest.clearAllMocks();
	});

	test('fetchStatus should call fetch with correct parameters', async () => {
		const mockData = { enabled: true };
		global.fetch.mockResolvedValueOnce({
			ok: true,
			json: () => Promise.resolve(mockData)
		});

		const result = await fetchStatus(endpoint, nonce);

		expect(global.fetch).toHaveBeenCalledWith(endpoint, {
			headers: {
				'X-WP-Nonce': nonce
			}
		});
		expect(result).toEqual(mockData);
	});

	test('fetchStatus should throw error on failure', async () => {
		global.fetch.mockResolvedValueOnce({
			ok: false,
			statusText: 'Forbidden'
		});

		await expect(fetchStatus(endpoint, nonce)).rejects.toThrow(
			'Failed to fetch status: Forbidden'
		);
	});

	test('fetchHealth should call fetch with correct parameters', async () => {
		const mockData = { status: 'ok' };
		global.fetch.mockResolvedValueOnce({
			ok: true,
			json: () => Promise.resolve(mockData)
		});

		const result = await fetchHealth(endpoint, nonce);

		expect(global.fetch).toHaveBeenCalledWith(endpoint, {
			headers: {
				'X-WP-Nonce': nonce
			}
		});
		expect(result).toEqual(mockData);
	});
});
