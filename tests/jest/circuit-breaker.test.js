/**
 * Tests for CircuitBreaker.
 *
 * @package
 */

import { CircuitBreaker } from '../../src/js/services/CircuitBreaker';

describe('CircuitBreaker', () => {
	let cb;

	beforeEach(() => {
		cb = new CircuitBreaker(3, 1000);
	});

	test('should allow execution when CLOSED', async () => {
		const fn = jest.fn().mockResolvedValue('success');
		const result = await cb.call(fn);

		expect(result).toBe('success');
		expect(cb.state).toBe('CLOSED');
		expect(cb.failureCount).toBe(0);
	});

	test('should open after threshold failures', async () => {
		const fn = jest.fn().mockRejectedValue(new Error('fail'));

		// 3 failures to reach threshold
		await expect(cb.call(fn)).rejects.toThrow('fail');
		await expect(cb.call(fn)).rejects.toThrow('fail');
		await expect(cb.call(fn)).rejects.toThrow('fail');

		expect(cb.state).toBe('OPEN');
		expect(cb.failureCount).toBe(3);
	});

	test('should prevent execution when OPEN', async () => {
		cb.state = 'OPEN';
		cb.nextAttempt = Date.now() + 1000;

		const fn = jest.fn();
		await expect(cb.call(fn)).rejects.toThrow('Circuit breaker is OPEN');
		expect(fn).not.toHaveBeenCalled();
	});

	test('should allow retry after timeout', async () => {
		cb.state = 'OPEN';
		cb.nextAttempt = Date.now() - 1;

		const fn = jest.fn().mockResolvedValue('recovered');
		const result = await cb.call(fn);

		expect(result).toBe('recovered');
		expect(cb.state).toBe('CLOSED');
		expect(cb.failureCount).toBe(0);
	});
});
