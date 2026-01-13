/**
 * OPcache Toolkit JavaScript Logger.
 *
 * @package
 */

export class Logger {
	/**
	 * Constructor.
	 *
	 * @param {string} endpoint - REST API endpoint for logging.
	 * @param {string} nonce    - WordPress nonce for authentication.
	 */
	constructor( endpoint, nonce ) {
		/** @type {Array} */
		this.logs = [];
		/** @type {number} */
		this.batchSize = 10;
		/** @type {number} */
		this.batchInterval = 5000; // 5 seconds
		/** @type {string} */
		this.endpoint = endpoint;
		/** @type {string} */
		this.nonce = nonce;

		if ( this.endpoint && this.nonce ) {
			this.startInterval();
		}
	}

	/**
	 * Log a message with a specific level.
	 *
	 * @param {string} level   - Log level (info, warning, error, debug).
	 * @param {string} message - Log message.
	 * @param {Object} context - Additional context.
	 * @return {void}
	 */
	log( level, message, context = {} ) {
		this.logs.push( {
			level,
			message,
			context,
			timestamp: new Date().toISOString()
		} );

		if ( this.logs.length >= this.batchSize ) {
			this.flush();
		}
	}

	/**
	 * Log info message.
	 *
	 * @param {string} message - Log message.
	 * @param {Object} context - Additional context.
	 * @return {void}
	 */
	info( message, context = {} ) {
		this.log( 'info', message, context );
	}

	/**
	 * Log warning message.
	 *
	 * @param {string} message - Log message.
	 * @param {Object} context - Additional context.
	 * @return {void}
	 */
	warn( message, context = {} ) {
		this.log( 'warning', message, context );
	}

	/**
	 * Log error message.
	 *
	 * @param {string} message - Log message.
	 * @param {Object} context - Additional context.
	 * @return {void}
	 */
	error( message, context = {} ) {
		this.log( 'error', message, context );
	}

	/**
	 * Log debug message.
	 *
	 * @param {string} message - Log message.
	 * @param {Object} context - Additional context.
	 * @return {void}
	 */
	debug( message, context = {} ) {
		this.log( 'debug', message, context );
	}

	/**
	 * Flush batched logs to the server.
	 *
	 * @return {Promise<void>}
	 */
	async flush() {
		if ( this.logs.length === 0 ) return;

		const logsToSend = [ ...this.logs ];
		this.logs = [];

		try {
			const response = await fetch( this.endpoint, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': this.nonce
				},
				body: JSON.stringify( { logs: logsToSend } )
			} );

			if ( ! response.ok ) {
				// eslint-disable-next-line no-console
				console.error( 'Failed to send logs to server', response.statusText );
			}
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.error( 'Error sending logs to server', err );
		}
	}

	/**
	 * Start the automatic flush interval.
	 */
	startInterval() {
		setInterval( () => this.flush(), this.batchInterval );
	}

	/**
	 * Register global error and promise rejection handlers.
	 */
	registerGlobalHandlers() {
		window.addEventListener( 'error', ( event ) => {
			this.error( 'Global JS Error', {
				message: event.message,
				filename: event.filename,
				lineno: event.lineno,
				colno: event.colno,
				error: event.error ? event.error.stack : null
			} );
		} );

		window.addEventListener( 'unhandledrejection', ( event ) => {
			this.error( 'Unhandled Promise Rejection', {
				reason: event.reason ? event.reason.stack || event.reason : 'Unknown'
			} );
		} );
	}
}
