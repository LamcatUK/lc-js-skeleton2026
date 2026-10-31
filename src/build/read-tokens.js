'use strict';

/**
 * Shared CSS custom-property reader for the token build scripts
 * (generate-theme-json.js, generate-utilities.js). Resolves simple local
 * `@import "file.css";` statements first, then parses every `:root { ... }`
 * block it finds — not just the first one — so one file containing several
 * root blocks (or re-exporting another file's tokens wholesale) still
 * yields everything.
 */

const fs = require('fs');
const path = require('path');

const IMPORT_RE = /@import\s+["']([^"']+)["'];?/g;

function readCssWithImports(filePath, seen = new Set()) {
	const abs = path.resolve(filePath);
	if (seen.has(abs)) return '';
	seen.add(abs);
	const dir = path.dirname(abs);
	return fs
		.readFileSync(abs, 'utf8')
		.replace(IMPORT_RE, (_match, importPath) => readCssWithImports(path.join(dir, importPath), seen));
}

/**
 * Finds every top-level `:root { ... }` block's contents, respecting nested
 * braces (e.g. a `@media` block declared inside `:root`) — a naive
 * non-greedy regex (`:root\s*{([\s\S]*?)}`) stops at the first nested
 * block's own closing brace instead of :root's, silently truncating
 * everything after it.
 *
 * @param {string} cssContent
 * @return {string[]}
 */
function extractRootBlocks(cssContent) {
	const blocks = [];
	const openRegex = /:root\s*{/g;
	let openMatch;
	while ((openMatch = openRegex.exec(cssContent)) !== null) {
		let depth = 1;
		let i = openMatch.index + openMatch[0].length;
		const start = i;
		while (i < cssContent.length && depth > 0) {
			if (cssContent[i] === '{') depth++;
			else if (cssContent[i] === '}') depth--;
			i++;
		}
		blocks.push(cssContent.slice(start, i - 1));
		openRegex.lastIndex = i;
	}
	return blocks;
}

/**
 * Strips /* ... *\/ comments before token parsing — a comment mentioning
 * another token by name (e.g. "real --col-ink: #0D0D0C") otherwise reads as
 * a second, bogus `--name: value;` declaration to the regex-based parser
 * below, which then greedily swallows everything up to its own next `;`.
 *
 * @param {string} cssContent
 * @return {string}
 */
function stripComments(cssContent) {
	return cssContent.replace(/\/\*[\s\S]*?\*\//g, '');
}

function parseRootTokens(cssContent) {
	const tokens = {};
	for (const block of extractRootBlocks(stripComments(cssContent))) {
		const varRegex = /--([\w-]+):\s*([^;]+);/g;
		let match;
		while ((match = varRegex.exec(block)) !== null) {
			tokens[match[1]] = match[2].trim();
		}
	}
	return tokens;
}

/**
 * Reads every `--token: value;` declaration inside any `:root {}` block in
 * the given file, following local @import statements first.
 *
 * @param {string} filePath
 * @return {Record<string, string>}
 */
function readTokensFromFile(filePath) {
	return parseRootTokens(readCssWithImports(filePath));
}

module.exports = { readTokensFromFile, parseRootTokens, readCssWithImports };