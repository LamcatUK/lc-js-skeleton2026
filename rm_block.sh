#!/bin/bash

# Removes a native block: the whole blocks/{slug} directory (block.json,
# src/, render.php, build/) plus its src/blocks/{slug}.css if present. No
# registration to undo — inc/blocks.php auto-loads whatever's left in blocks/.

blocks_dir="./blocks"
if [ ! -d "$blocks_dir" ]; then
  echo "Blocks directory not found: $blocks_dir"
  exit 1
fi

mapfile -t block_slugs < <(find "$blocks_dir" -mindepth 1 -maxdepth 1 -type d -exec test -f '{}/block.json' \; -print | xargs -n 1 basename 2>/dev/null | sort)

if [ ${#block_slugs[@]} -eq 0 ]; then
  echo "No blocks found in $blocks_dir"
  exit 1
fi

echo ""
echo "Available blocks:"
echo ""
for i in "${!block_slugs[@]}"; do
  printf "%2d) %s\n" $((i+1)) "${block_slugs[$i]}"
done
echo ""

read -p "Enter block number to remove (or 'q' to quit): " selection

if [ "$selection" == "q" ] || [ "$selection" == "Q" ]; then
  echo "Cancelled."
  exit 0
fi

if ! [[ "$selection" =~ ^[0-9]+$ ]] || [ "$selection" -lt 1 ] || [ "$selection" -gt ${#block_slugs[@]} ]; then
  echo "Invalid selection."
  exit 1
fi

selected_index=$((selection-1))
block_kebab="${block_slugs[$selected_index]}"

block_dir="./blocks/${block_kebab}"
css_file="./src/blocks/${block_kebab}.css"

echo ""
echo "This will remove the following:"
echo "  - Block directory: $block_dir (block.json, src/, render.php, build/)"
echo "  - CSS file (if present): $css_file"
echo ""
read -p "Are you sure you want to remove this block? (y/n): " confirm

if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
  echo "Cancelled."
  exit 0
fi

rm -rf "$block_dir"
echo "Removed: $block_dir"

if [ -f "$css_file" ]; then
  rm "$css_file"
  echo "Removed: $css_file"
else
  echo "No CSS file to remove (block had none): $css_file"
fi

echo ""
echo "Block removal complete!"
