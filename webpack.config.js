const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = {
	...defaultConfig,
	entry: {
		dashboard: './src/js/entries/dashboard.js',
		logger: './src/js/entries/logger-init.js',
		widgets: './src/js/entries/widgets.js',
		widget: './src/js/entries/widget.js', // Dashboard widget
		settings: './src/js/entries/settings.js'
	},
	output: {
		path: path.resolve(__dirname, 'assets/js'),
		filename: '[name].js',
		clean: false // Don't clean assets/js yet as it contains legacy files we still need
	}
};
