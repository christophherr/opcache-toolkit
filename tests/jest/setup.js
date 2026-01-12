global.opcacheToolkitData = {
	restUrl: 'http://example.com/wp-json/',
	nonce: '12345'
};

global.opcacheToolkitLive = {
	statusEndpoint: 'http://example.com/wp-json/opcache-toolkit/v1/status',
	healthEndpoint: 'http://example.com/wp-json/opcache-toolkit/v1/health',
	preloadEndpoint:
		'http://example.com/wp-json/opcache-toolkit/v1/preload-progress',
	nonce: '12345',
	interval: 30000
};

global.opcacheToolkitCharts = {
	labels: [],
	hitRate: [],
	cached: [],
	wasted: [],
	endpoint: 'http://example.com/wp-json/opcache-toolkit/v1/chart-data',
	nonce: '12345'
};

global.opcacheToolkitWPAdminDashboard = {
	statusEndpoint: 'http://example.com/wp-json/opcache-toolkit/v1/status',
	healthEndpoint: 'http://example.com/wp-json/opcache-toolkit/v1/health',
	preloadEndpoint:
		'http://example.com/wp-json/opcache-toolkit/v1/preload-progress',
	resetUrl:
		'http://example.com/wp-admin/admin-post.php?action=opcache_toolkit_clear&_wpnonce=12345',
	nonce: '12345',
	dashboardUrl: 'http://example.com/wp-admin/admin.php?page=opcache-manager'
};

global.window.confirm = jest.fn( () => true );
global.URL.createObjectURL = jest.fn();
global.URL.revokeObjectURL = jest.fn();

// Mock fetch globally
global.fetch = jest.fn( () =>
	Promise.resolve( {
		ok: true,
		json: () => Promise.resolve( {} )
	} )
);

global.ResizeObserver = jest.fn().mockImplementation( () => ( {
	observe: jest.fn(),
	unobserve: jest.fn(),
	disconnect: jest.fn()
} ) );

Object.defineProperty( window, 'matchMedia', {
	writable: true,
	value: jest.fn().mockImplementation( ( query ) => ( {
		matches: false,
		media: query,
		onchange: null,
		addListener: jest.fn(), // deprecated
		removeListener: jest.fn(), // deprecated
		addEventListener: jest.fn(),
		removeEventListener: jest.fn(),
		dispatchEvent: jest.fn()
	} ) )
} );

jest.mock(
	'@wordpress/i18n',
	() => ( {
		__: ( val ) => val,
		_x: ( val ) => val,
		_n: ( s, p, n ) => ( n > 1 ? p : s ),
		_nx: ( s, p, n ) => ( n > 1 ? p : s ),
		isRTL: () => false
	} ),
	{ virtual: true }
);

// Mock CSS imports if identity-obj-proxy is not working or missing
jest.mock( 'identity-obj-proxy', () => ( {} ), { virtual: true } );

// Start MSW server for network mocking is temporarily disabled due to runner conflicts.
/*
try {
	const { server } = require('./msw');
	beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
	afterEach(() => server.resetHandlers());
	afterAll(() => server.close());
} catch (err) {
	console.error('MSW Setup Error:', err);
}
*/
