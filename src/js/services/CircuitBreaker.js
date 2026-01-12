/**
 * Circuit Breaker implementation for JavaScript.
 *
 * @package
 */

export class CircuitBreaker {
	/**
	 * Constructor.
	 *
	 * @param {number} threshold - Number of failures before opening.
	 * @param {number} timeout   - Time in milliseconds before attempting reset.
	 */
	constructor( threshold = 5, timeout = 60000 ) {
		/** @type {number} */
		this.failureCount = 0;
		/** @type {number} */
		this.threshold = threshold;
		/** @type {number} */
		this.timeout = timeout;
		/** @type {string} */
		this.state = 'CLOSED'; // CLOSED, OPEN, HALF_OPEN
		/** @type {number} */
		this.nextAttempt = Date.now();
	}

	/**
	 * Execute a function through the circuit breaker.
	 *
	 * @param {Function} fn - The function to execute.
	 * @return {Promise<any>} Result of the function.
	 * @throws {Error} If circuit is open or function fails.
	 */
	async call( fn ) {
		if ( this.state === 'OPEN' ) {
			if ( Date.now() < this.nextAttempt ) {
				throw new Error( 'Circuit breaker is OPEN' );
			}
			this.state = 'HALF_OPEN';
		}

		try {
			const result = await fn();
			this.onSuccess();
			return result;
		} catch ( err ) {
			this.onFailure();
			throw err;
		}
	}

	/**
	 * Reset failure count on success.
	 */
	onSuccess() {
		this.failureCount = 0;
		this.state = 'CLOSED';
	}

	/**
	 * Increment failure count on failure and potentially open circuit.
	 */
	onFailure() {
		this.failureCount++;
		if ( this.failureCount >= this.threshold ) {
			this.state = 'OPEN';
			this.nextAttempt = Date.now() + this.timeout;
		}
	}
}
