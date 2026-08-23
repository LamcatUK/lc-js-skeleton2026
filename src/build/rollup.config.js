'use strict';

const path = require('path');
const { babel } = require('@rollup/plugin-babel');
const banner = require('./banner.js');

module.exports = {
	input: path.resolve(__dirname, '../js/theme.js'),
	output: [
		{
			file: path.resolve(__dirname, '../../js/theme.js'),
			format: 'iife',
			banner: banner(),
		},
		{
			file: path.resolve(__dirname, '../../js/theme.min.js'),
			format: 'iife',
			banner: banner(),
		},
	],
	plugins: [
		babel({
			babelHelpers: 'bundled',
			configFile: path.resolve(__dirname, 'babel.config.js'),
		}),
	],
};
