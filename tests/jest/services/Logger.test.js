/**
 * Tests for Logger Service.
 *
 * @package
 */

import { Logger } from '../../../src/js/services/Logger';

describe( 'Logger', () => {
	let logger;
	const endpoint = 'http://example.com/wp-json/opcache-toolkit/v1/log';
	const nonce = 'mock-nonce';

	beforeEach( () => {
		jest.clearAllMocks();
		jest.useFakeTimers();
		logger = new Logger( endpoint, nonce );
	} );

	afterEach( () => {
		jest.useRealTimers();
	} );

	test( 'should initialize with correct properties', () => {
		expect( logger.endpoint ).toBe( endpoint );
		expect( logger.nonce ).toBe( nonce );
		expect( logger.logs ).toEqual( [] );
	} );

	test( 'should batch logs before flushing', () => {
		logger.info( 'Test message' );
		expect( logger.logs.length ).toBe( 1 );
		expect( global.fetch ).not.toHaveBeenCalled();

		// Add 9 more logs to reach batchSize (10)
		for ( let i = 0; i < 9; i++ ) {
			logger.info( `Message ${ i }` );
		}

		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
		expect( logger.logs.length ).toBe( 0 );
	} );

	test( 'should flush on interval', () => {
		logger.info( 'Test message' );
		expect( global.fetch ).not.toHaveBeenCalled();

		jest.advanceTimersByTime( 5000 );

		expect( global.fetch ).toHaveBeenCalledTimes( 1 );
		expect( logger.logs.length ).toBe( 0 );
	} );

	test( 'should handle fetch errors gracefully', async () => {
		const consoleSpy = jest
			.spyOn( console, 'error' )
			.mockImplementation( () => {} );
		global.fetch.mockRejectedValueOnce( new Error( 'Network error' ) );

		// Fill batch to trigger flush
		for ( let i = 0; i < 10; i++ ) {
			logger.info( `Message ${ i }` );
		}

		// Wait for promise resolution in flush
		await Promise.resolve();
		await Promise.resolve();

		expect( consoleSpy ).toHaveBeenCalledWith(
			'Error sending logs to server',
			expect.any( Error )
		);
		consoleSpy.mockRestore();
	} );

	test( 'should register global handlers', () => {
		const addEventListenerSpy = jest.spyOn( window, 'addEventListener' );
		logger.registerGlobalHandlers();

		expect( addEventListenerSpy ).toHaveBeenCalledWith(
			'error',
			expect.any( Function )
		);
		expect( addEventListenerSpy ).toHaveBeenCalledWith(
			'unhandledrejection',
			expect.any( Function )
		);
		addEventListenerSpy.mockRestore();
	} );

	test( 'should log different levels correctly', () => {
		logger.info( 'info' );
		logger.warn( 'warn' );
		logger.error( 'error' );
		logger.debug( 'debug' );

		expect( logger.logs[ 0 ].level ).toBe( 'info' );
		expect( logger.logs[ 1 ].level ).toBe( 'warning' );
		expect( logger.logs[ 2 ].level ).toBe( 'error' );
		expect( logger.logs[ 3 ].level ).toBe( 'debug' );
	} );
} );
