/**
 * Tests for Charts API.
 *
 * @package
 */

import { fetchChartData } from '../../../src/js/api/charts';

describe( 'Charts API', () => {
	const endpoint = 'http://example.com/wp-json/opcache-toolkit/v1/chart-data';
	const nonce = 'mock-nonce';

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'fetchChartData should call fetch with correct parameters', async () => {
		const mockData = { labels: [], datasets: [] };
		global.fetch.mockResolvedValueOnce( {
			ok: true,
			json: () => Promise.resolve( mockData )
		} );

		const result = await fetchChartData( endpoint, nonce );

		expect( global.fetch ).toHaveBeenCalledWith( endpoint, {
			headers: {
				'X-WP-Nonce': nonce
			}
		} );
		expect( result ).toEqual( mockData );
	} );

	test( 'fetchChartData should throw error on failure', async () => {
		global.fetch.mockResolvedValueOnce( {
			ok: false,
			statusText: 'Server Error'
		} );

		await expect( fetchChartData( endpoint, nonce ) ).rejects.toThrow(
			'Failed to fetch chart data: Server Error'
		);
	} );
} );
