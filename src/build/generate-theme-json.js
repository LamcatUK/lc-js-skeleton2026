// generate-theme-json.js
// Node.js script to parse CSS custom-property tokens and generate theme.json
// for the WordPress block editor.
//
// Usage:
//   npm run generate-theme-json
//
// Reads the :root custom properties from src/css/tokens.css and generates
// theme.json with a color palette and font sizes for Gutenberg.
//
// Looks for:
// - Color variables: --col-* (excluding aliases like --col-primary that just
//   point at another color — only "real" palette colors get listed)
// - Font size variables: --fs-*

const fs = require('fs');
const path = require('path');

const tokensFile = path.join(__dirname, '../css/tokens.css');
const themeJsonFile = path.join(__dirname, '../../theme.json');

function parseCssVariables(cssContent) {
	const rootBlockMatch = cssContent.match(/:root\s*{([\s\S]*?)}/);
	if (!rootBlockMatch) return {};
	const rootContent = rootBlockMatch[1];
	const varRegex = /--([\w-]+):\s*([^;]+);/g;
	const tokens = {};
	let match;
	while ((match = varRegex.exec(rootContent)) !== null) {
		tokens[match[1]] = match[2].trim();
	}
	return tokens;
}

function buildThemeJson(tokens) {
	// Palette colors: --col-{slug} whose value is a literal color, not a
	// var() alias (aliases like --col-primary: var(--col-blue-700) point at
	// one of the literal ones, so they'd just be a duplicate palette entry).
	const colors = Object.entries(tokens)
		.filter(([key, value]) => key.startsWith('col-') && !value.startsWith('var('))
		.map(([key, value]) => ({
			name: key.replace('col-', ''),
			slug: key.replace('col-', ''),
			color: value,
		}));

	const fontSizes = Object.entries(tokens)
		.filter(([key]) => key.startsWith('fs-'))
		.map(([key, value]) => {
			const slug = key.replace('fs-', '');
			return { name: slug, slug, size: value };
		});

	return {
		version: 2,
		settings: {
			appearanceTools: true,
			color: {
				defaultPalette: false,
				palette: colors.map((color) => ({ ...color, origin: 'theme' })),
			},
			typography: { fontSizes },
		},
	};
}

function main() {
	if (!fs.existsSync(tokensFile)) {
		console.error('Tokens file not found:', tokensFile);
		process.exit(1);
	}
	const cssContent = fs.readFileSync(tokensFile, 'utf8');
	const tokens = parseCssVariables(cssContent);
	const themeJson = buildThemeJson(tokens);
	fs.writeFileSync(themeJsonFile, JSON.stringify(themeJson, null, 2));
	console.log('theme.json generated successfully.');
}

main();
