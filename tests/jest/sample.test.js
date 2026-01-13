describe( 'Jest Setup', () => {
	it( 'should have opcacheToolkitData defined', () => {
		expect( global.opcacheToolkitData ).toBeDefined();
		expect( global.opcacheToolkitData.restUrl ).toBe(
			'http://example.com/wp-json/'
		);
	} );

	it( 'should have opcacheToolkitLive defined', () => {
		expect( global.opcacheToolkitLive ).toBeDefined();
		expect( global.opcacheToolkitLive.interval ).toBe( 30000 );
	} );

	it( 'should mock __ from @wordpress/i18n', () => {
		const { __ } = require( '@wordpress/i18n' );
		expect( __( 'Hello' ) ).toBe( 'Hello' );
	} );
} );
