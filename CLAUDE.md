# lc-js-skeleton2026

Read this in full before touching anything — several decisions here are
deliberate and not obvious from the code alone. If you're about to reach for
Sass, Bootstrap, jQuery, an icon font, or flexbox-for-everything, stop and
re-read the relevant section below first.

## What this is

A standalone WordPress theme skeleton — no parent theme, no framework
dependency. It exists to be checked out per client project, renamed, and
built on. It is **not** a live dependency: this repo is a one-time-checkout
base. Fixes made here are not expected to sync back into projects that have
already forked from it, and there is no submodule/subtree relationship to
maintain.

It replaces an earlier workflow built on Understrap (a Bootstrap 5 WordPress
starter theme used as a WP parent/child theme pair). That workflow is still
used for the rare WooCommerce project — this skeleton is deliberately for
everything else, which is the large majority of projects.

**This is a fork of `lc-skeleton2026`, kept deliberately separate rather than
merged in.** Both skeletons share the same CSS/JS/build system verbatim — the
only difference is how blocks and site-wide settings are built: `lc-skeleton2026`
uses ACF (ACF Blocks, ACF options page), this one uses native
block.json/edit.js/render.php blocks and a plain Settings API page, with no
ACF dependency at all. The split exists because ACF Blocks stopped reliably
saving field edits under WP 7.1's now-mandatory iframed block canvas — ACF's
own block-editor JS bridge only wires up field-change listeners on a block's
very first mount and doesn't re-arm when a block's canvas swaps from its
static preview into the editable form on selection, so typed edits can be
silently lost. Native blocks don't have this problem: field state flows
through React's own `setAttributes()`, which Gutenberg's iframe was built to
support correctly. Both skeletons are maintained in parallel on the chance
WP reverts or LTS's a pre-7.1 release — if that never happens, this one is
likely to become the only one going forward.

## The one rule that explains most of the file layout

**Bootstrap-style class names, zero Bootstrap.** `.container`, `.row`,
`.col-6`, `.btn`, `.navbar`, `.d-flex`, etc. all exist and are used exactly
like they'd look in a Bootstrap project — but every one of them is defined
in this theme's own CSS. There is no Bootstrap package, no Bootstrap Sass,
no Bootstrap JS, no Popper. The naming was kept on purpose for muscle-memory
continuity (for the theme author and any devs who've worked in Bootstrap
before) — **do not assume behavioural parity with actual Bootstrap.**
Anything not explicitly implemented here (most Bootstrap components) simply
doesn't exist, no matter how standard it looks.

## Architecture decisions and why

- **No Sass.** Native CSS nesting (browsers handle it natively at this
  project's browser baseline — see below) replaces the one thing Sass was
  doing that mattered. Don't add Sass back in "just for tokens" or "just for
  mixins" — there was a deliberate decision to not need a preprocessor at
  all here.
- **CSS Grid, not flexbox, for layout.** `.row`/`.col-*` are Grid-based (see
  "How the grid actually works" below) — this was chosen over flexbox
  because the person building this genuinely prefers Grid's mental model,
  and it sets up cleanly for `subgrid`. `.navbar` itself is flexbox — a
  single-row toolbar is a legitimate flex use case; Grid isn't a mandate for
  every layout, just the default for anything row/column/page-structure
  shaped.
- **Design tokens are CSS custom properties**, not Sass variables — see
  `src/css/tokens.css`. This is also what makes color/font-size options
  show up in the Gutenberg editor (`src/build/generate-theme-json.js` reads
  this file to produce `theme.json`).
- **Breakpoints are the one exception to "tokens live in tokens.css."**
  A CSS custom property can't be read inside an `@media` condition, so
  breakpoints live in `src/build/tokens.config.js` instead. `src/css/nav.css`
  hardcodes the `lg` breakpoint (992px) directly in a plain `@media` query
  for the same reason, in a comment noting it must stay in sync with that
  file. If you change a breakpoint, grep for the old pixel value across
  `src/css/*.css` — there is no single source of truth enforced by tooling,
  only by convention.
- **No Bootstrap JS, no jQuery.** `src/js/nav-toggle.js` is a ~20-line
  vanilla replacement for Bootstrap's Collapse component (mobile nav toggle).
  `src/js/dialog.js` wires up the native `<dialog>` element (`showModal()`/
  `close()`) as the modal solution — not a JS component library.
- **No icon font.** Icons are inline SVG (see `header.php`'s nav toggle
  button for the pattern). Don't add Font Awesome or similar back in.
- **Buttons and cards have zero framework opinion.** `.btn` in
  `src/css/forms.css` is a bare minimal base — the theme author designs
  buttons and cards per-project rather than using a framework's look, so
  don't build out an opinionated button/card system here without being
  asked.
- **Tables are real but opt-in.** `src/css/tables.css` exists and is
  genuinely styled, but is not imported by default in `src/css/theme.css` —
  uncomment the `@import` if a project needs one.
- **Deliberately absent, don't add back without being asked:** sidebars/
  widget areas, comments, tags, author archives, `archive.php`, `search.php`.
  All confirmed rare-to-never in real usage across ~40 projects/year. If a
  specific project needs one, add it there, not here.
- **Site-Wide Settings is core, not per-project.** `inc/options.php` — a plain Settings API page (no ACF), storing one serialized array option (`lc_js_skeleton_site_settings`). Read values elsewhere with `lc_js_skeleton_get_setting( $key )`. "Crucial to every theme" per the person building this. GA/GTM only fire for logged-out visitors (`inc/head-tags.php`) so the team's own traffic doesn't skew analytics — a real, previously-known gap in the old `lc-iology2025` theme (fires for everyone there), fixed here from the start rather than retrofitted. GTM's noscript fallback is on `wp_body_open`, not buried in the footer — that's where Google's own docs say it belongs. The SVG icon-upload tab the ACF version of this page had is deliberately not ported here — deferred to a future plugin, not rebuilt as part of dropping ACF. `get_icon()`/`get_icon_choices()` (`inc/utilities.php`) still work exactly as before; just drop an .svg into `img/icons/` by hand instead of uploading it through wp-admin.
- **Native blocks, not ACF.** Each block is a directory under `blocks/{slug}/`:
  `block.json` (name/title/attributes/supports), `src/index.js` +
  `src/edit.js` (the editing UI — React, using WordPress's own field
  components), and `render.php` (front-end template, reading `$attributes`
  directly — no `get_field()`, there's no ACF to call). `inc/blocks.php` globs
  `blocks/*/block.json` and registers every one automatically — no per-block
  PHP registration code, unlike the marker-comment insertion the ACF version
  used. Fields never render as a click-to-reveal preview or get shunted into
  the sidebar — they're just always-live inputs in the canvas, because that's
  how native blocks work, not something forced on top.

## Browser support baseline

Modern evergreen only — see `.browserslistrc` (last 2 versions of Chrome/
Firefox/Safari/Edge, no IE11). This is why CSS Grid, `subgrid` (used as
progressive enhancement via `@supports`, not depended on), `clamp()`,
native `<dialog>`, `:has()`, and CSS nesting are all used without fallback
layers. Don't add polyfills or fallback CSS for older browsers unless
explicitly asked — it would be working against a deliberate decision.

## How the grid actually works

`.row { display: grid; grid-template-columns: repeat(var(--grid-columns), 1fr); }`
and `.col-N { grid-column: span N; }` — `--grid-columns` defaults to 12
(`src/css/tokens.css`). **The span number is only meaningful relative to
whatever `grid-template-columns` its own `.row` ancestor has.** You cannot
mix "some children spanning against a 12-col row" with "other children
spanning against a 5-col row" inside the *same* `.row` — a `.col-*` class is
not portable across different column-count contexts.

If a project needs a genuinely different column count (the theme author's
example: five equal columns), don't repurpose `.col-*` classes for it.
`.grid` (`src/css/layout.css`) is the built solution: `display: grid;
grid-template-columns: repeat(auto-fit, minmax(var(--grid-min), 1fr));`
— no column count is declared at all, the browser fits as many as the
minimum item width (`--grid-min`, default `16rem` in `tokens.css`)
allows, and reflows automatically as the viewport changes. Tune it per
instance with an inline `style="--grid-min: 10rem"` rather than adding
a new class per layout.

This is deliberately *not* a replacement for `.row`/`.col-*` — use `.row`
when you need deliberate, exact spans (page structure); use `.grid`
when items just need to be "roughly N up, however many fit" (card grids,
feature lists). A row-modifier approach (overriding `--grid-columns` for one
specific `.row` with its own `.col-*-of-5`-style classes) was considered and
rejected in favour of `.grid` for this use case — more bookkeeping for
less benefit when the real need is "N similar items," not exact spans.

Nested `.row`s use `subgrid` for their columns where the browser supports it
(`@supports (grid-template-columns: subgrid)` in `src/css/layout.css`),
falling back to their own independent 12-column grid otherwise.

## File layout

```
style.css              Theme header — no `Template:` line, this is standalone
functions.php           Requires inc/*.php, nothing else
inc/
  setup.php             add_theme_support, register_nav_menus
  enqueue.php           Enqueues css/theme.min.css + js/theme.min.js, filemtime-versioned
  class-nav-walker.php  Lightweight Walker_Nav_Menu — nav-link/dropdown-menu classes, no JS
  blocks.php            Globs blocks/*/block.json and register_block_type()s each one — no per-block code
  options.php           Site-Wide Settings page (plain Settings API, one array option) + lc_js_skeleton_get_setting()
  head-tags.php          Font preload (fonts/*.woff2 glob) + GA/GTM (logged-out only) + Google/Bing verification, reading from the options page
  block-usage.php        [block_usage_table] shortcode — QA utility, lists every block against the published pages/posts using it
  utilities.php          Reusable, project-agnostic functions (parse_phone, pluralise, estimate_reading_time_in_minutes, get_icon/get_icon_choices) — safe to lift verbatim into any project on this skeleton. Project-specific helpers go in inc/helpers.php instead, created only when needed, not scaffolded here.
header.php / footer.php / index.php / page.php / single.php / 404.php
                        Deliberately minimal — most real page layouts are built from blocks, not these
blocks/                 One directory per block (add_block.sh scaffolds here)
  {slug}/
    block.json          Name/title/attributes/supports — "editorScript": "file:./build/index.js", "render": "file:./render.php"
    src/index.js        registerBlockType(), imports edit.js + block.json
    src/edit.js          The editing UI — React, WordPress's own field components (TextControl, RichText, MediaUpload, ...)
    render.php           Front-end template — reads $attributes directly, no get_field()
    build/               GENERATED by wp-scripts, see webpack.config.js — do not hand-edit or commit assumptions about its contents surviving a clean checkout
  _shared/               Reusable React components any block's edit.js can import — NOT its own
                        block, not globbed/registered by inc/blocks.php, just bundled into whichever
                        block(s) `import` it. add_block.sh does not write to this directory or know
                        these components exist; wire them into a generated edit.js by hand.
    RepeaterField.js     Generic repeater UI for a block attribute holding an array of row objects —
                        ACF repeater's native-blocks equivalent. Deliberately one array attribute on
                        a single block edited through this shared component, not an InnerBlocks/
                        child-block pattern (a parent block's save() needs an InnerBlocks.Content
                        placeholder or new rows silently fail to persist, and any wrapper save() adds
                        becomes literal $content in the dynamic block's render.php, breaking CSS that
                        assumes children are direct flex/grid children — real bugs hit once already).
                        Takes `label`, `value`
                        (rows array), `onChange`, `fields` ([ { name, label, type: 'text'|'textarea'|
                        'image'|'file'|'link', help, mimeTypes, linkTarget } ]), `emptyRow` (shape of a
                        freshly-added row), and `layout` ('row', default — sub-fields side by side with
                        shared column headers; or 'column' — stacked, each with its own visible label).
                        Renders its own add/move-up/move-down/remove controls — no native Gutenberg UI
                        exists for this outside InnerBlocks. Matching CSS in src/css/editor.css
                        (.lc-js-skeleton-repeater-field*).
    PostTypePicker.js    Generic single-post picker — the block-editor equivalent of ACF's single
                        `post_object` field (`return_format: object`). Search-as-you-type via
                        `ComboboxControl`, backed by `@wordpress/core-data` rather than a custom REST
                        call. Takes `label`, `postType` (slug to search, e.g. 'product'), `value`
                        (selected post ID or 0), `onChange( id )`, `help`. No styling of its own to
                        port — `ComboboxControl` is unstyled beyond what @wordpress/components already
                        provides.
fonts/                  Drop .woff2 files here — preloaded automatically, no registration step
src/
  css/                  Theme-wide CSS. theme.css is the @import entry point.
    tokens.css          Design tokens as CSS custom properties (colors, spacing, type)
    base.css            Reboot-equivalent element reset
    layout.css           .container / .row / subgrid
    nav.css             .navbar, mobile toggle, dropdown submenus
    forms.css           Minimal form base + .btn
    tables.css          Opt-in, not imported by default
    utilities.css        GENERATED — do not hand-edit, see generate-utilities.js
    blocks.css           GENERATED — concatenation of src/blocks/*.css
  blocks/               Block-specific CSS, separate from theme-wide src/css/.
                        {block-slug}.css — add_block.sh does NOT create this file;
                        drop one in here and it's picked up automatically on next
                        build (glob, alphabetical order, no registration step).
  js/
    theme.js            Entry point, imports the two below
    nav-toggle.js        Mobile nav collapse — vanilla JS replacement for Bootstrap Collapse
    dialog.js            Native <dialog> wiring — vanilla JS replacement for Bootstrap Modal
  build/
    tokens.config.js     Breakpoints + utility/grid definitions — source of truth for generate-utilities.js
    generate-utilities.js  Generates src/css/utilities.css and src/css/blocks.css
    generate-theme-json.js Generates theme.json from src/css/tokens.css
    postcss.config.js    postcss-import + postcss-nesting + autoprefixer
    rollup.config.js / babel.config.js / terser.config.json / banner.js
                        JS bundling — no nodeResolve/commonjs, there are no npm JS deps to bundle
    browser-sync.config.js
css/ , js/              Compiled output — committed to git (not gitignored), same convention as
                        the Understrap-based themes this replaced
add_block.sh            Prompts for a block name, then loops prompting for each field's name and
                        type (text/textarea/richtext/image/url/link/number/select/checkbox),
                        generating block.json's attributes, the matching src/edit.js control, and
                        a render.php $attributes stub for each one. Not yet supported by the
                        generator: repeater, gallery, relationship, post_object, file — add those
                        by hand in src/edit.js. Does not create a CSS file — see src/blocks/ above.
                        Reminds you to run `npm run blocks:build` afterward — the block won't work
                        until build/index.js exists.
rm_block.sh             Removes a block: the whole blocks/{slug} directory, and
                        src/blocks/{slug}.css if present. No registration to undo.
setup.sh                One-time bootstrap for a NEW project checked out from this skeleton —
                        prompts for a theme name + slug, renames every lc-js-skeleton2026 /
                        LC JS Skeleton 2026 / lc_js_skeleton_ / LC_JS_Skeleton_ reference, resets git to
                        a fresh single commit. Refuses to run twice (idempotency guard) or on a
                        dirty tree. Does NOT create a GitHub repo — that's a deliberate manual
                        step (gh repo create), not automated. See README.md.
theme.json              GENERATED by generate-theme-json.js — do not hand-edit
```

## Build commands

```
npm install
npm run watch        # rebuild theme-wide CSS/JS on save
npm run watch-bs     # same + browser-sync live reload, proxies localhost/
npm run blocks:build # one-off compile of every blocks/*/src/index.js
npm run blocks:start # watch mode for block JS, wp-scripts' own watcher
npm run dist         # one-off full build: css + js + blocks:build
npm run generate-theme-json
./add_block.sh
```

`npm run css` = `generate-utilities.js` (writes `utilities.css` + `blocks.css`)
→ PostCSS (`postcss-import`, `postcss-nesting`, `autoprefixer`) → minify.
`npm run js` = rollup (bundles `src/js/theme.js`) → terser. Both are the
theme-wide build, entirely separate from blocks — they don't touch or know
about `blocks/*/src/`.

`npm run blocks:build`/`blocks:start` run `@wordpress/scripts` (`wp-scripts`)
against `webpack.config.js`, which globs `blocks/*/src/index.js` and compiles
each one to `blocks/{slug}/build/index.js` — co-located with that block's own
`block.json`, not wp-scripts' single top-level `build/` default.
**`webpack.config.js` explicitly disables webpack's `output.clean`** — the
default config cleans `output.path` before every build, and since
`output.path` here is the shared `blocks/` directory (so `[name]/build/...`
resolves per block), an enabled clean would wipe every block's
`block.json`/`render.php`/`src/` alongside the compiled output. Don't remove
that `clean: false` without replacing it with something that can't do that.

## Working conventions carried over from the previous (Understrap) themes

- Compiled `css/`/`js/` output is committed to git, not gitignored. The same
  applies to each block's `build/` output — commit it, don't gitignore it,
  same reasoning.
- No ACF anywhere in this theme — see `lc-skeleton2026` (the sibling
  ACF-based skeleton this one forked from) if a project specifically needs
  ACF instead of native blocks.
