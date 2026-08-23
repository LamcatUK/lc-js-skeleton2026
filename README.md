# lc-js-skeleton2026

Standalone base WordPress theme. Check it out, rename it, build on it — this
is a one-time-checkout base, not a live dependency: fixes made here aren't
intended to sync back into projects already forked from it.

Forked from `lc-skeleton2026`, sharing its entire CSS/JS/build system —
the only difference is blocks and site settings use native block.json/edit.js
blocks and a plain Settings API page instead of ACF. See that skeleton's own
docs if a project specifically needs ACF; see this file's "Adding a block"
section below for how blocks work here.

## Starting a new project

```
git clone git@github.com:LamcatUK/lc-js-skeleton2026.git my-new-project
cd my-new-project
./setup.sh
```

`setup.sh` prompts for a theme name, suggests a slug from it (press enter to
accept, or type your own — names and slugs often want to diverge), then
renames every `lc-js-skeleton2026` / `LC JS Skeleton 2026` / `lc_js_skeleton_` /
`LC_JS_Skeleton_` reference across the whole repo to match, and resets git to a
single fresh commit (no skeleton commit history, no GitHub repo created —
that part's manual: `gh repo create LamcatUK/{slug} --public --source=.
--push` when you're ready). Refuses to run on a dirty tree or an
already-renamed project.

## This is Bootstrap-*named*, not Bootstrap

Class names (`container`, `row`, `col-6`, `btn`, `navbar`, `d-flex`, etc.)
follow Bootstrap's conventions for familiarity — but every one of them is
implemented in this theme's own lightweight CSS. There is no Bootstrap
dependency, no Sass, no jQuery, no Popper. Don't assume parity with actual
Bootstrap docs/behaviour, especially for anything not covered below.

## What's in, what's not

- **Layout**: CSS Grid, not flexbox, for `.row`/`.col-*` (`.navbar` itself is
  flexbox — a single-row toolbar is a legitimate flex case). Nested `.row`
  uses `subgrid` where supported, as progressive enhancement only. `.col-*`
  spans are only meaningful relative to their own `.row`'s column count
  (12 by default) — you can't mix a 12-col span and a 5-col span in the same
  `.row`. For "N similar items, however many fit" layouts (card grids,
  feature lists) use `.grid` instead — `repeat(auto-fit, minmax(...))`,
  no fixed column count, no breakpoint classes, tune the minimum item width
  per instance with `style="--grid-min: 10rem"`.
- **Nav**: functional responsive nav + mobile toggle, vanilla JS
  (`src/js/nav-toggle.js`), no Bootstrap JS.
- **Modals**: native `<dialog>` (`src/js/dialog.js`), not a JS component.
- **Forms**: minimal, deliberately unopinionated base — expect to override it.
- **Buttons/cards**: no framework opinion at all — bring your own per project.
- **Tables**: real but optional (`src/css/tables.css`) — uncomment the
  `@import` in `src/css/theme.css` if a project needs one.
- **Icons**: inline SVG, no icon font.
- **No sidebars, no comments, no tags, no author archives, no `archive.php`,
  no `search.php`** — all confirmed rare-to-never in real usage; add them
  per-project if a project genuinely needs one.
- **WooCommerce**: not here at all. Ecommerce projects use the Understrap
  parent/child setup separately — this skeleton is for everything else.

## Design tokens

`src/css/tokens.css` — CSS custom properties (`--col-*`, `--fs-*`, `--space-*`,
etc.), no Sass variables. `src/build/generate-theme-json.js` reads this file
to produce `theme.json` so colors/font sizes show up in the block editor.

Breakpoints are the one exception — they live in `src/build/tokens.config.js`,
not `tokens.css`, because a CSS custom property can't be read inside an
`@media` condition. `src/css/nav.css` hand-duplicates the `lg` breakpoint in
a literal `@media` query for the same reason — keep it in sync with that
file if a breakpoint ever changes.

## Browser support

Modern evergreen only (last 2 versions of Chrome/Firefox/Safari/Edge, see
`.browserslistrc`). No IE11, no legacy fallback layer. Grid, subgrid
(progressive enhancement), `clamp()`, native `<dialog>`, `:has()`, and CSS
nesting are all used without hesitation.

## Build

```
npm install
npm run watch        # rebuilds theme-wide CSS/JS on save
npm run watch-bs     # same, plus browser-sync live reload (proxies localhost/)
npm run blocks:build # compiles every block's src/index.js
npm run blocks:start # watch mode for block JS
npm run dist         # one-off full build: css + js + blocks
npm run generate-theme-json   # regenerate theme.json from tokens.css
```

`npm run css` runs three steps: `generate-utilities.js` (produces
`src/css/utilities.css` — the breakpoint-suffixed grid/utility classes — and
`src/css/blocks.css`, a concatenation of `src/blocks/*.css`), then
PostCSS (`postcss-import` + `postcss-nesting` + `autoprefixer`), then
minification. This is entirely separate from block JS — `blocks:build` is
its own step, run by `@wordpress/scripts` against `webpack.config.js`, which
compiles each `blocks/{slug}/src/index.js` to `blocks/{slug}/build/index.js`.

## Adding a block

```
./add_block.sh
```

Prompts for a block name, then for each field: its name and type (text,
textarea, richtext, image, url, link, number, select, checkbox). Generates
`block.json` (attributes), `src/index.js` + `src/edit.js` (the editing UI,
using WordPress's own field components — no ACF, no `get_field()`), and
`render.php` (front-end template, reading straight from `$attributes`).
No registration step — `inc/blocks.php` auto-loads every `blocks/*/block.json`
it finds. Run `npm run blocks:build` after scaffolding, or the block won't
render correctly until `build/index.js` exists.

Not yet supported by the generator: repeater, gallery, relationship,
post_object, file — add those by hand in `src/edit.js` if a block needs one.

If the block needs custom styles, add `src/blocks/{block-slug}.css`; it's
picked up automatically on the next `npm run css`, no registration step.
Load order for these is alphabetical by filename, not registration order.

`rm_block.sh` removes a block: the whole `blocks/{slug}` directory plus its
`src/blocks/{slug}.css` if present. Again, no registration to undo.
