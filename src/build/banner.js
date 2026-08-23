'use strict';

const pkg = require('../../package.json');
const year = new Date().getFullYear();

function getBanner() {
	return `/*!
 * ${pkg.name} v${pkg.version} (${pkg.homepage})
 * Copyright ${year} ${pkg.author}
 * Licensed under ${pkg.license}
 */`;
}

module.exports = getBanner;
