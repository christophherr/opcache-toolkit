/**
 * Tests for DOM Utilities.
 *
 * @package
 */

import { formatBytes, smoothScrollTo } from '../../../src/js/utils/dom';

describe( 'DOM Utilities', () => {
	describe( 'formatBytes', () => {
		test( 'should format bytes correctly', () => {
			expect( formatBytes( 0 ) ).toBe( '0 B' );
			expect( formatBytes( 1024 ) ).toBe( '1 KB' );
			expect( formatBytes( 1048576 ) ).toBe( '1 MB' );
			expect( formatBytes( 1073741824 ) ).toBe( '1 GB' );
			expect( formatBytes( 1234567 ) ).toBe( '1.18 MB' );
		} );
	} );

	describe( 'smoothScrollTo', () => {
		test( 'should call window.scrollTo if element exists', () => {
			document.body.innerHTML = '<div id="target"></div>';
			const scrollToSpy = jest
				.spyOn( window, 'scrollTo' )
				.mockImplementation( () => {} );

			// Mock getBoundingClientRect
			const element = document.getElementById( 'target' );
			element.getBoundingClientRect = jest.fn( () => ( { top: 100 } ) );
			window.pageYOffset = 50;

			smoothScrollTo( '#target', 10 );

			expect( scrollToSpy ).toHaveBeenCalledWith( {
				top: 140, // 100 + 50 - 10
				behavior: 'smooth'
			} );
			scrollToSpy.mockRestore();
		} );

		test( 'should not call window.scrollTo if element does not exist', () => {
			document.body.innerHTML = '';
			const scrollToSpy = jest
				.spyOn( window, 'scrollTo' )
				.mockImplementation( () => {} );

			smoothScrollTo( '#non-existent' );

			expect( scrollToSpy ).not.toHaveBeenCalled();
			scrollToSpy.mockRestore();
		} );
	} );
} );
