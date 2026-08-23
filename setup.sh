#!/bin/bash
set -e

# One-time bootstrap: run this straight after checking out and renaming the
# skeleton directory for a new project. Renames every lc-js-skeleton2026 /
# LC JS Skeleton 2026 / lc_js_skeleton_ / LC_JS_SKELETON_ / LC_JS_Skeleton_ reference to
# your new theme, then resets git to a fresh history so this project doesn't
# drag the skeleton's own commit log around. Leaves GitHub itself to you
# (gh repo create + push) — deliberately not automated, see README.

old_slug="lc-js-skeleton2026"
old_name="LC JS Skeleton 2026"
old_prefix="lc_js_skeleton"
old_prefix_upper="LC_JS_SKELETON"
old_prefix_pascal="LC_JS_Skeleton"
old_nav_walker_file="inc/class-lc-skeleton-nav-walker.php"

# Idempotency guard — refuse to run twice against an already-renamed project.
if ! grep -rq "$old_slug" style.css 2>/dev/null; then
  echo "No '$old_slug' references found in style.css — this looks like it's already been renamed."
  echo "Refusing to run again (would double-rename). Delete setup.sh if you don't need it anymore."
  exit 1
fi

# Refuse to run on a dirty tree — this is meant to run on a fresh checkout,
# not part-way through real work.
if [ -d .git ] && [ -n "$(git status --short 2>/dev/null)" ]; then
  echo "Working tree has uncommitted changes. setup.sh resets git history entirely —"
  echo "commit, stash, or discard first, then re-run."
  exit 1
fi

read -p "New theme name (e.g. \"LC New Client 2026\"): " new_name
if [ -z "$new_name" ]; then
  echo "No theme name provided."
  exit 1
fi

# Suggest a slug from the name, but name and slug often want to diverge
# (a cleaner/shorter slug than the marketing name) — offer it as a default,
# not a fait accompli.
suggested_slug=$(echo "$new_name" | tr '[:upper:]' '[:lower:]' | tr ' ' '-')
read -p "Theme slug [$suggested_slug]: " slug_input
new_slug="${slug_input:-$suggested_slug}"

# browser-sync's proxy target isn't derived from the slug/name, so it needs
# its own prompt — defaults to the team's usual "{slug}.local" convention.
read -p "Local dev URL for browser-sync [${new_slug}.local]: " local_url_input
new_local_url="${local_url_input:-${new_slug}.local}"

# Header pattern: flat is [logo][primary nav] in one row (the current
# default header.php); stacked is [logo][top row] then [logo][primary nav]
# as two rows. Only header.php's marked header/nav section is affected —
# no CSS is generated for either, that's still per-project work.
read -p "Header style — flat or stacked [flat]: " header_style_input
header_style="${header_style_input:-flat}"
while [ "$header_style" != "flat" ] && [ "$header_style" != "stacked" ]; do
  read -p "Please enter 'flat' or 'stacked' [flat]: " header_style_input
  header_style="${header_style_input:-flat}"
done

# Lowercase/uppercase PHP fn/const prefixes derived from the *confirmed*
# slug, not the raw name, so they can't silently diverge from it.
new_prefix=$(echo "$new_slug" | tr '-' '_')
new_prefix_upper=$(echo "$new_prefix" | tr '[:lower:]' '[:upper:]')

# The editor-chrome CSS classes (.lc-js-skeleton-editor-block, etc., in
# css/editor.css, src/css/editor.css, and add_block.sh's generated markup)
# use the kebab form of old_prefix directly — NOT old_slug, which has a
# trailing "2026" these class names never carried. Needs its own rule or
# it silently survives every rename.
old_prefix_kebab=$(echo "$old_prefix" | tr '_' '-')
new_prefix_kebab=$(echo "$new_prefix" | tr '_' '-')

# The one class name (LC_JS_Skeleton_Nav_Walker) uses mixed case, so it's
# derived from the name instead, word by word — already-uppercase words
# (acronyms like "LC") are preserved as typed rather than forced to "Lc".
new_prefix_pascal=""
for word in $new_name; do
  if [[ "$word" =~ ^[A-Z0-9]+$ ]]; then
    part="$word"
  else
    part="$(echo "${word:0:1}" | tr '[:lower:]' '[:upper:]')$(echo "${word:1}" | tr '[:upper:]' '[:lower:]')"
  fi
  new_prefix_pascal="${new_prefix_pascal:+${new_prefix_pascal}_}${part}"
done

# WPCS (WordPress.Files.FileName.InvalidClassFileName) requires a class's
# filename to be "class-" + the full class name in kebab-case — since the
# nav walker's class name changes per project, its filename has to change
# with it, not just its contents.
new_nav_walker_slug=$(echo "$new_prefix_pascal" | tr '[:upper:]' '[:lower:]' | tr '_' '-')
new_nav_walker_file="inc/class-${new_nav_walker_slug}-nav-walker.php"

echo ""
echo "This will replace, across every tracked file:"
echo "  \"$old_name\"           -> \"$new_name\""
echo "  $old_slug               -> $new_slug"
echo "  ${old_prefix}_ (PHP fn/const)  -> ${new_prefix}_"
echo "  ${old_prefix_pascal}_ (class name)  -> ${new_prefix_pascal}_"
echo "  ${old_prefix_kebab}- (editor CSS classes)  -> ${new_prefix_kebab}-"
echo "  $old_nav_walker_file -> $new_nav_walker_file"
echo "  browser-sync proxy: localhost/ -> ${new_local_url}/"
echo "  header.php header/nav section -> templates/header-${header_style}.php"
echo ""
echo "...then delete .git and start a fresh history (first commit only —"
echo "GitHub repo creation and push are left to you)."
echo ""
read -p "Proceed? (y/n): " confirm
if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
  echo "Cancelled."
  exit 0
fi

# Text replacement — every tracked file, skipping binaries/build tooling
# that shouldn't be touched.
files=$(git ls-files 2>/dev/null || find . -type f -not -path "./node_modules/*" -not -path "./.git/*")

for f in $files; do
  [ -f "$f" ] || continue
  case "$f" in
    *.png|*.jpg|*.jpeg|*.gif|*.woff|*.woff2|*.map) continue ;;
  esac
  sed -i \
    -e "s/${old_name}/${new_name}/g" \
    -e "s/${old_slug}/${new_slug}/g" \
    -e "s/${old_prefix_upper}/${new_prefix_upper}/g" \
    -e "s/${old_prefix_pascal}/${new_prefix_pascal}/g" \
    -e "s/${old_prefix_kebab}/${new_prefix_kebab}/g" \
    -e "s/${old_prefix}/${new_prefix}/g" \
    "$f"
done

echo "Replaced references across $(echo "$files" | wc -l) files."

# browser-sync's proxy/host targets are literal placeholders, not one of the
# old_name/old_slug/old_prefix tokens above, so they get their own replacement.
browser_sync_config="src/build/browser-sync.config.js"
if [ -f "$browser_sync_config" ]; then
  sed -i \
    -e "s#proxy: 'localhost/'#proxy: '${new_local_url}/'#" \
    -e "s#host: 'localhost'#host: '${new_local_url}'#" \
    "$browser_sync_config"
  echo "Set browser-sync proxy/host to ${new_local_url} in $browser_sync_config"
else
  echo "Warning: $browser_sync_config not found — skipping browser-sync proxy/host update."
fi

# Rename the nav walker file to match its now-renamed class (see above —
# WPCS requires this, and the content substitution above doesn't touch
# filenames).
if [ -f "$old_nav_walker_file" ]; then
  mv "$old_nav_walker_file" "$new_nav_walker_file"
  sed -i "s#${old_nav_walker_file}#${new_nav_walker_file}#g" functions.php
  echo "Renamed $old_nav_walker_file -> $new_nav_walker_file"
else
  echo "Warning: $old_nav_walker_file not found — skipping nav walker filename rename."
fi

# Write the chosen header partial (already through the rename pass above,
# so it references the new nav walker class) into header.php's marked
# section. Marker comments stay in place — they just bound the region a
# future header-style swap would need to replace.
header_partial="templates/header-${header_style}.php"
if [ -f "$header_partial" ] && [ -f "header.php" ]; then
  tmp_header=$(mktemp)
  awk -v partial="$header_partial" '
    /<!-- HEADER-NAV:START -->/ { print; while ((getline line < partial) > 0) print line; close(partial); skip=1; next }
    /<!-- HEADER-NAV:END -->/ { skip=0 }
    skip { next }
    { print }
  ' header.php > "$tmp_header"
  # mktemp defaults to 0600, owner-only — mv would otherwise leave header.php
  # unreadable by the webserver user, a 500 error the moment this runs on a
  # real site. Match the original file's permissions instead of the temp file's.
  chmod --reference=header.php "$tmp_header" 2>/dev/null || chmod 664 "$tmp_header"
  mv "$tmp_header" header.php
  echo "Wrote $header_partial into header.php"
else
  echo "Warning: $header_partial or header.php not found — skipping header style setup."
fi

# The unused partial (and templates/ itself, once empty) isn't needed once
# a project has its header.php written — this is a one-time setup choice,
# not an ongoing generated-file relationship.
rm -f templates/header-flat.php templates/header-stacked.php
rmdir templates 2>/dev/null || true

# Reset git to a fresh history.
rm -rf .git
git init -q
git add -A
git commit -q -m "Initial commit from ${old_slug} skeleton"

# Rename the checkout's own directory to match the new slug — everything
# above rewrites file contents, but the folder you `git clone`d into keeps
# its original name (e.g. "lc-js-skeleton2026") unless done explicitly.
# Done last, after every other file operation, since renaming the directory
# out from under a running script is only safe once nothing after it still
# needs to resolve paths relative to the old name.
old_dir="$(pwd)"
parent_dir="$(dirname "$old_dir")"
new_dir="${parent_dir}/${new_slug}"

if [ "$old_dir" = "$new_dir" ]; then
  : # Already checked out under the new slug — nothing to rename.
elif [ -e "$new_dir" ]; then
  echo ""
  echo "Warning: $new_dir already exists — leaving this folder named $(basename "$old_dir")."
  echo "Rename it yourself once that's resolved: mv \"$old_dir\" \"$new_dir\""
else
  cd "$parent_dir"
  mv "$(basename "$old_dir")" "$new_slug"
  cd "$new_dir"
  echo ""
  echo "Renamed folder: $(basename "$old_dir") -> $new_slug"
fi

echo ""
echo "Done. New theme: \"$new_name\" ($new_slug)."
echo ""
echo "Next steps:"
echo "  cd ../$new_slug   # if your shell is still in the old folder path"
echo "  npm install"
echo "  gh repo create LamcatUK/$new_slug --public --source=. --push   # when you're ready"
