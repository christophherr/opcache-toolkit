module.exports = {
	testMatch: ['**/tests/jest/**/*.test.[jt]s?(x)'],
	transformIgnorePatterns: ['node_modules/(?!(memize|@wordpress)/)'],
	transform: {
		'^.+\\.(j|t)sx?$': 'babel-jest'
	},
	moduleNameMapper: {
		'\\.(css|less|scss|sass)$': '<rootDir>/tests/jest/mocks/styleMock.js'
	},
	testEnvironment: 'jsdom',
	setupFiles: ['./tests/jest/setup.js']
};
