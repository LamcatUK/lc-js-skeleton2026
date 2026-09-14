#!/bin/bash

# Scaffolds a native block: block.json, src/index.js, src/edit.js, render.php.
# Prompts for each field's name and type, generating the block.json attribute,
# the matching edit.js control, and a render.php variable stub for each one —
# same "run a script, answer prompts, get working files" idea as the old
# ACF-based add_block.sh, just generating a JS/PHP block instead of an ACF
# field group.
#
# No CSS file is created here — same as before, drop one in
# src/blocks/{slug}.css and it's picked up automatically on the next build.
#
# No registration step either: inc/blocks.php globs blocks/*/block.json and
# registers every one it finds, so a new block just needs its files in place.

set -e

read -p "Enter block name: " block_name

if [ -z "$block_name" ]; then
  echo "No block name provided."
  exit 1
fi

block_kebab=$(echo "$block_name" | tr '[:upper:]' '[:lower:]' | tr ' ' '-' | tr -cd 'a-z0-9-')
block_dir="./blocks/${block_kebab}"

if [ -d "$block_dir" ]; then
  echo "Block already exists: $block_dir"
  exit 1
fi

# Theme text domain, used as the block namespace and translation domain.
style_file="./style.css"
theme_slug=$(grep "Text Domain:" "$style_file" | sed 's/.*Text Domain:[ ]*//' | tr -d '\r')

echo ""
echo "Add fields one at a time. Leave the field name blank to finish."
echo "Supported types: text, textarea, richtext, image, url, link, number, select, checkbox"
echo "Not yet supported by this generator: repeater, gallery, relationship, post_object, file"
echo "— add those by hand in src/edit.js if a block needs one."
echo "Each field also asks for optional help text and a column width (100/50/33/25) —"
echo "consecutive fields under 100% share a row, e.g. two 50% CTAs side by side."
echo ""

field_names=()     # camelCase JS attribute name (or base name for image/link)
field_types=()
field_labels=()    # original human label
field_snakes=()    # snake_case PHP variable name
field_options=()   # comma-separated options, select only
field_ta_styles=() # textarea only: paragraph/list/linebreak
field_link_targets=() # link only: 1 if an "open in new tab" toggle is wanted
field_helps=()   # optional help text, shown under the control
field_widths=()  # 100/50/33/25 — consecutive non-100 fields share a row

while true; do
  read -p "Field name (blank to finish): " field_label
  if [ -z "$field_label" ]; then
    break
  fi

  field_snake=$(echo "$field_label" | tr '[:upper:]' '[:lower:]' | tr ' ' '_' | tr -cd 'a-z0-9_')
  field_camel=$(echo "$field_snake" | awk -F_ '{ out=$1; for (i=2; i<=NF; i++) { out = out toupper(substr($i,1,1)) substr($i,2) }; print out }')

  read -p "Field type [text/textarea/richtext/image/url/link/number/select/checkbox]: " field_type
  case "$field_type" in
    text|textarea|richtext|image|url|link|number|select|checkbox) ;;
    *)
      echo "Unrecognised type '${field_type}', defaulting to text."
      field_type="text"
      ;;
  esac

  field_option_list=""
  if [ "$field_type" = "select" ]; then
    read -p "Comma-separated options for '${field_label}': " field_option_list
  fi

  field_ta_style="paragraph"
  if [ "$field_type" = "textarea" ]; then
    read -p "Textarea style — paragraph/list/linebreak [paragraph]: " ta_style_input
    field_ta_style="${ta_style_input:-paragraph}"
    while [ "$field_ta_style" != "paragraph" ] && [ "$field_ta_style" != "list" ] && [ "$field_ta_style" != "linebreak" ]; do
      read -p "Please enter 'paragraph', 'list', or 'linebreak' [paragraph]: " ta_style_input
      field_ta_style="${ta_style_input:-paragraph}"
    done
  fi

  field_link_target=0
  if [ "$field_type" = "link" ]; then
    read -p "Add an 'open in new tab' toggle for '${field_label}'? (y/n): " link_target_input
    if [ "$link_target_input" = "y" ] || [ "$link_target_input" = "Y" ]; then
      field_link_target=1
    fi
  fi

  field_label_display="$(echo "${field_label:0:1}" | tr '[:lower:]' '[:upper:]')${field_label:1}"

  read -p "Help text shown under the field (optional): " field_help

  read -p "Column width — 100/50/33/25 [100]: " field_width_input
  field_width="${field_width_input:-100}"
  while [ "$field_width" != "100" ] && [ "$field_width" != "50" ] && [ "$field_width" != "33" ] && [ "$field_width" != "25" ]; do
    read -p "Please enter 100, 50, 33, or 25 [100]: " field_width_input
    field_width="${field_width_input:-100}"
  done

  field_names+=("$field_camel")
  field_types+=("$field_type")
  field_labels+=("$field_label_display")
  field_snakes+=("$field_snake")
  field_options+=("$field_option_list")
  field_ta_styles+=("$field_ta_style")
  field_link_targets+=("$field_link_target")
  field_helps+=("$field_help")
  field_widths+=("$field_width")
done

if [ ${#field_names[@]} -eq 0 ]; then
  echo "No fields added — scaffolding a block with no attributes."
fi

mkdir -p "$block_dir/src"

# ---------------------------------------------------------------------------
# block.json attributes
# ---------------------------------------------------------------------------
attr_lines=()
for i in "${!field_names[@]}"; do
  name="${field_names[$i]}"
  type="${field_types[$i]}"
  case "$type" in
    text|textarea|richtext|url|select)
      attr_lines+=("\t\t\"${name}\": { \"type\": \"string\", \"default\": \"\" }")
      ;;
    number)
      attr_lines+=("\t\t\"${name}\": { \"type\": \"number\", \"default\": 0 }")
      ;;
    checkbox)
      attr_lines+=("\t\t\"${name}\": { \"type\": \"boolean\", \"default\": false }")
      ;;
    image)
      attr_lines+=("\t\t\"${name}Id\": { \"type\": \"number\", \"default\": 0 }")
      attr_lines+=("\t\t\"${name}Url\": { \"type\": \"string\", \"default\": \"\" }")
      attr_lines+=("\t\t\"${name}Alt\": { \"type\": \"string\", \"default\": \"\" }")
      ;;
    link)
      attr_lines+=("\t\t\"${name}Text\": { \"type\": \"string\", \"default\": \"\" }")
      attr_lines+=("\t\t\"${name}Url\": { \"type\": \"string\", \"default\": \"\" }")
      if [ "${field_link_targets[$i]}" = "1" ]; then
        attr_lines+=("\t\t\"${name}Target\": { \"type\": \"boolean\", \"default\": false }")
      fi
      ;;
  esac
done

attrs_json=""
for i in "${!attr_lines[@]}"; do
  if [ "$i" -eq 0 ]; then
    attrs_json="${attr_lines[$i]}"
  else
    attrs_json="${attrs_json},\n${attr_lines[$i]}"
  fi
done

# ---------------------------------------------------------------------------
# block.json
# ---------------------------------------------------------------------------
cat > "${block_dir}/block.json" <<EOF
{
	"\$schema": "https://schemas.wp.org/trunk/block.json",
	"apiVersion": 3,
	"name": "${theme_slug}/${block_kebab}",
	"title": "${block_name}",
	"category": "${theme_slug}",
	"icon": "cover-image",
	"attributes": {
$(printf "%b" "$attrs_json")
	},
	"supports": {
		"anchor": true,
		"className": true,
		"align": true
	},
	"editorScript": "file:./build/index.js",
	"render": "file:./render.php"
}
EOF
echo "Created: ${block_dir}/block.json"

# ---------------------------------------------------------------------------
# src/index.js
# ---------------------------------------------------------------------------
cat > "${block_dir}/src/index.js" <<'EOF'
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import metadata from '../block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
EOF
echo "Created: ${block_dir}/src/index.js"

# ---------------------------------------------------------------------------
# src/edit.js — work out which imports are actually needed first
# ---------------------------------------------------------------------------
need_richtext=0
need_media=0
need_textcontrol=0
need_textareacontrol=0
need_selectcontrol=0
need_togglecontrol=0
need_button=0

for i in "${!field_types[@]}"; do
  type="${field_types[$i]}"
  case "$type" in
    text|url|number) need_textcontrol=1 ;;
    textarea) need_textareacontrol=1 ;;
    richtext) need_richtext=1 ;;
    image) need_media=1; need_button=1 ;;
    link)
      need_textcontrol=1
      [ "${field_link_targets[$i]}" = "1" ] && need_togglecontrol=1
      ;;
    select) need_selectcontrol=1 ;;
    checkbox) need_togglecontrol=1 ;;
  esac
done

blockeditor_imports="useBlockProps"
[ "$need_richtext" = "1" ] && blockeditor_imports="${blockeditor_imports}, RichText"
[ "$need_media" = "1" ] && blockeditor_imports="${blockeditor_imports}, MediaUpload, MediaUploadCheck"

components_imports=()
[ "$need_textcontrol" = "1" ] && components_imports+=("TextControl")
[ "$need_textareacontrol" = "1" ] && components_imports+=("TextareaControl")
[ "$need_selectcontrol" = "1" ] && components_imports+=("SelectControl")
[ "$need_togglecontrol" = "1" ] && components_imports+=("ToggleControl")
[ "$need_button" = "1" ] && components_imports+=("Button")
components_import_line=$(IFS=,; echo "${components_imports[*]}" | sed 's/,/, /g')

# Destructured attribute names, for the Edit() function signature.
attr_destructure=()
for i in "${!field_names[@]}"; do
  name="${field_names[$i]}"
  type="${field_types[$i]}"
  case "$type" in
    image) attr_destructure+=("${name}Id" "${name}Url" "${name}Alt") ;;
    link)
      attr_destructure+=("${name}Text" "${name}Url")
      [ "${field_link_targets[$i]}" = "1" ] && attr_destructure+=("${name}Target")
      ;;
    *) attr_destructure+=("$name") ;;
  esac
done
attr_destructure_line=$(IFS=,; echo "${attr_destructure[*]}" | sed 's/,/, /g')

# field_html[i] holds each field's own control markup; a second pass below
# groups consecutive non-100%-width fields into flex rows (mirrors ACF's
# wrapper.width side-by-side layout, e.g. a 50/50 pair of CTAs or a 33/33/33
# badge trio) without affecting fields left at the 100% default.
field_html=()
for i in "${!field_names[@]}"; do
  name="${field_names[$i]}"
  type="${field_types[$i]}"
  label="${field_labels[$i]}"
  help="${field_helps[$i]}"

  help_attr=""
  [ -n "$help" ] && help_attr="\n\t\t\t\thelp={ __( '${help}', '${theme_slug}' ) }"

  help_para=""
  [ -n "$help" ] && help_para="\n\t\t\t\t<p className=\"lc-js-skeleton-editor-field__help\">{ __( '${help}', '${theme_slug}' ) }</p>"

  case "$type" in
    text|url)
      field_html+=("\t\t\t<TextControl\n\t\t\t\tlabel={ __( '${label}', '${theme_slug}' ) }\n\t\t\t\tvalue={ ${name} }\n\t\t\t\tonChange={ ( value ) => setAttributes( { ${name}: value } ) }${help_attr}\n\t\t\t/>\n")
      ;;
    number)
      field_html+=("\t\t\t<TextControl\n\t\t\t\ttype=\"number\"\n\t\t\t\tlabel={ __( '${label}', '${theme_slug}' ) }\n\t\t\t\tvalue={ ${name} }\n\t\t\t\tonChange={ ( value ) => setAttributes( { ${name}: Number( value ) } ) }${help_attr}\n\t\t\t/>\n")
      ;;
    textarea)
      field_html+=("\t\t\t<TextareaControl\n\t\t\t\tlabel={ __( '${label}', '${theme_slug}' ) }\n\t\t\t\tvalue={ ${name} }\n\t\t\t\tonChange={ ( value ) => setAttributes( { ${name}: value } ) }${help_attr}\n\t\t\t/>\n")
      ;;
    richtext)
      field_html+=("\t\t\t<div className=\"lc-js-skeleton-editor-field\">\n\t\t\t\t<label className=\"lc-js-skeleton-editor-field__label\">{ __( '${label}', '${theme_slug}' ) }</label>\n\t\t\t\t<RichText\n\t\t\t\t\ttagName=\"div\"\n\t\t\t\t\tclassName=\"lc-js-skeleton-editor-field__control\"\n\t\t\t\t\taria-label={ __( '${label}', '${theme_slug}' ) }\n\t\t\t\t\tplaceholder={ __( '${label}', '${theme_slug}' ) }\n\t\t\t\t\tvalue={ ${name} }\n\t\t\t\t\tonChange={ ( value ) => setAttributes( { ${name}: value } ) }\n\t\t\t\t/>${help_para}\n\t\t\t</div>\n")
      ;;
    checkbox)
      field_html+=("\t\t\t<ToggleControl\n\t\t\t\tlabel={ __( '${label}', '${theme_slug}' ) }\n\t\t\t\tchecked={ ${name} }\n\t\t\t\tonChange={ ( value ) => setAttributes( { ${name}: value } ) }${help_attr}\n\t\t\t/>\n")
      ;;
    select)
      IFS=',' read -ra opts <<< "${field_options[$i]}"
      options_js=""
      for opt in "${opts[@]}"; do
        opt_trimmed=$(echo "$opt" | sed 's/^ *//; s/ *$//')
        options_js+="\t\t\t\t\t{ label: '${opt_trimmed}', value: '${opt_trimmed}' },\n"
      done
      field_html+=("\t\t\t<SelectControl\n\t\t\t\tlabel={ __( '${label}', '${theme_slug}' ) }\n\t\t\t\tvalue={ ${name} }\n\t\t\t\toptions={ [\n\t\t\t\t\t{ label: '', value: '' },\n$(printf "%b" "$options_js")\t\t\t\t] }\n\t\t\t\tonChange={ ( value ) => setAttributes( { ${name}: value } ) }${help_attr}\n\t\t\t/>\n")
      ;;
    image)
      field_html+=("\t\t\t<div className=\"lc-js-skeleton-editor-field\">\n\t\t\t\t<label className=\"lc-js-skeleton-editor-field__label\">{ __( '${label}', '${theme_slug}' ) }</label>\n\t\t\t\t<MediaUploadCheck>\n\t\t\t\t\t<MediaUpload\n\t\t\t\t\t\tonSelect={ ( media ) =>\n\t\t\t\t\t\t\tsetAttributes( {\n\t\t\t\t\t\t\t\t${name}Id: media.id,\n\t\t\t\t\t\t\t\t${name}Url: media.url,\n\t\t\t\t\t\t\t\t${name}Alt: media.alt || '',\n\t\t\t\t\t\t\t} )\n\t\t\t\t\t\t}\n\t\t\t\t\t\tallowedTypes={ [ 'image' ] }\n\t\t\t\t\t\tvalue={ ${name}Id }\n\t\t\t\t\t\trender={ ( { open } ) => (\n\t\t\t\t\t\t\t<div className=\"lc-js-skeleton-editor-field__control\">\n\t\t\t\t\t\t\t\t{ ${name}Url && (\n\t\t\t\t\t\t\t\t\t<img\n\t\t\t\t\t\t\t\t\t\tsrc={ ${name}Url }\n\t\t\t\t\t\t\t\t\t\talt={ ${name}Alt }\n\t\t\t\t\t\t\t\t\t\tstyle={ { maxWidth: '200px', display: 'block', marginBottom: '8px' } }\n\t\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t\t) }\n\t\t\t\t\t\t\t\t<Button variant=\"secondary\" onClick={ open }>\n\t\t\t\t\t\t\t\t\t{ ${name}Url ? __( 'Replace ${label}', '${theme_slug}' ) : __( 'Select ${label}', '${theme_slug}' ) }\n\t\t\t\t\t\t\t\t</Button>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t) }\n\t\t\t\t\t/>\n\t\t\t\t</MediaUploadCheck>${help_para}\n\t\t\t</div>\n")
      ;;
    link)
      link_html="\t\t\t<TextControl\n\t\t\t\tlabel={ __( '${label} Text', '${theme_slug}' ) }\n\t\t\t\tvalue={ ${name}Text }\n\t\t\t\tonChange={ ( value ) => setAttributes( { ${name}Text: value } ) }\n\t\t\t/>\n\t\t\t<TextControl\n\t\t\t\ttype=\"url\"\n\t\t\t\tlabel={ __( '${label} URL', '${theme_slug}' ) }\n\t\t\t\tvalue={ ${name}Url }\n\t\t\t\tonChange={ ( value ) => setAttributes( { ${name}Url: value } ) }${help_attr}\n\t\t\t/>\n"
      if [ "${field_link_targets[$i]}" = "1" ]; then
        link_html+="\t\t\t<ToggleControl\n\t\t\t\tlabel={ __( 'Open ${label} in a new tab', '${theme_slug}' ) }\n\t\t\t\tchecked={ ${name}Target }\n\t\t\t\tonChange={ ( value ) => setAttributes( { ${name}Target: value } ) }\n\t\t\t/>\n"
      fi
      field_html+=("$link_html")
      ;;
  esac
done

# Group consecutive non-100%-width fields into flex rows.
controls=""
i=0
field_count=${#field_names[@]}
while [ $i -lt $field_count ]; do
  width="${field_widths[$i]}"
  if [ "$width" = "100" ]; then
    controls+="${field_html[$i]}"
    i=$((i + 1))
  else
    row_html=""
    row_sum=0
    while [ $i -lt $field_count ] && [ "${field_widths[$i]}" != "100" ] && [ "$row_sum" -lt 100 ]; do
      w="${field_widths[$i]}"
      # flex-grow set to the width value with a 0 basis, rather than a fixed
      # percentage — lets the browser divide the row proportionally after
      # subtracting the gap, so e.g. three 33% fields don't overflow to a
      # wrapped fourth line the way 3 × 33% + 2 × gap would.
      row_html+="\t\t\t\t<div style={ { flex: '${w} 1 0%' } }>\n${field_html[$i]}\t\t\t\t</div>\n"
      row_sum=$((row_sum + w))
      i=$((i + 1))
    done
    controls+="\t\t\t<div style={ { display: 'flex', flexWrap: 'wrap', gap: '12px' } }>\n${row_html}\t\t\t</div>\n"
  fi
done

{
  echo "import { __ } from '@wordpress/i18n';"
  echo "import { ${blockeditor_imports} } from '@wordpress/block-editor';"
  [ -n "$components_import_line" ] && echo "import { ${components_import_line} } from '@wordpress/components';"
  echo "import EditorBlockShell from '../../_shared/EditorBlockShell';"
  echo ""
  echo "export default function Edit( { attributes, setAttributes, clientId } ) {"
  if [ -n "$attr_destructure_line" ]; then
    echo "	const { ${attr_destructure_line} } = attributes;"
  fi
  echo "	const blockProps = useBlockProps( { className: 'container lc-js-skeleton-editor-block' } );"
  echo ""
  echo "	return ("
  echo "		<EditorBlockShell blockProps={ blockProps } clientId={ clientId } title=\"${block_name}\" textDomain=\"${theme_slug}\">"
  printf "%b" "$controls"
  echo "		</EditorBlockShell>"
  echo "	);"
  echo "}"
} > "${block_dir}/src/edit.js"
echo "Created: ${block_dir}/src/edit.js"

# ---------------------------------------------------------------------------
# render.php
# ---------------------------------------------------------------------------
extract_lines=""
markup_lines=""
for i in "${!field_names[@]}"; do
  name="${field_names[$i]}"
  type="${field_types[$i]}"
  snake="${field_snakes[$i]}"

  case "$type" in
    text|url|select)
      extract_lines+="\$${snake} = \$attributes['${name}'] ?? '';\n"
      markup_lines+="\t<?php if ( \$${snake} ) { ?>\n\t\t<p><?php echo esc_html( \$${snake} ); ?></p>\n\t<?php } ?>\n"
      ;;
    number)
      extract_lines+="\$${snake} = \$attributes['${name}'] ?? 0;\n"
      markup_lines+="\t<?php if ( \$${snake} ) { ?>\n\t\t<p><?php echo esc_html( \$${snake} ); ?></p>\n\t<?php } ?>\n"
      ;;
    textarea)
      extract_lines+="\$${snake} = \$attributes['${name}'] ?? '';\n"
      case "${field_ta_styles[$i]}" in
        list)
          markup_lines+="\t<?php if ( \$${snake} ) { ?>\n\t\t<ul>\n\t\t\t<?php foreach ( preg_split( '/\\\\r\\\\n|\\\\n|\\\\r/', \$${snake} ) as \$${snake}_line ) { \$${snake}_line = trim( \$${snake}_line ); if ( '' === \$${snake}_line ) { continue; } ?>\n\t\t\t\t<li><?php echo wp_kses_post( \$${snake}_line ); ?></li>\n\t\t\t<?php } ?>\n\t\t</ul>\n\t<?php } ?>\n"
          ;;
        linebreak)
          markup_lines+="\t<?php if ( \$${snake} ) { ?>\n\t\t<div><?php echo nl2br( esc_html( \$${snake} ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- nl2br() output of an already-escaped string. ?></div>\n\t<?php } ?>\n"
          ;;
        *)
          markup_lines+="\t<?php if ( \$${snake} ) { ?>\n\t\t<div><?php echo wp_kses_post( wpautop( \$${snake} ) ); ?></div>\n\t<?php } ?>\n"
          ;;
      esac
      ;;
    richtext)
      extract_lines+="\$${snake} = \$attributes['${name}'] ?? '';\n"
      markup_lines+="\t<?php if ( \$${snake} ) { ?>\n\t\t<div><?php echo wp_kses_post( \$${snake} ); ?></div>\n\t<?php } ?>\n"
      ;;
    checkbox)
      extract_lines+="\$${snake} = ! empty( \$attributes['${name}'] );\n"
      ;;
    image)
      extract_lines+="\$${snake}_url = \$attributes['${name}Url'] ?? '';\n"
      extract_lines+="\$${snake}_alt = \$attributes['${name}Alt'] ?? '';\n"
      markup_lines+="\t<?php if ( \$${snake}_url ) { ?>\n\t\t<img src=\"<?php echo esc_url( \$${snake}_url ); ?>\" alt=\"<?php echo esc_attr( \$${snake}_alt ); ?>\">\n\t<?php } ?>\n"
      ;;
    link)
      extract_lines+="\$${snake}_text = \$attributes['${name}Text'] ?? '';\n"
      extract_lines+="\$${snake}_url = \$attributes['${name}Url'] ?? '';\n"
      if [ "${field_link_targets[$i]}" = "1" ]; then
        extract_lines+="\$${snake}_target = ! empty( \$attributes['${name}Target'] );\n"
        markup_lines+="\t<?php if ( \$${snake}_url ) { ?>\n\t\t<a href=\"<?php echo esc_url( \$${snake}_url ); ?>\"<?php if ( \$${snake}_target ) { ?> target=\"_blank\" rel=\"noopener\"<?php } ?>><?php echo esc_html( \$${snake}_text ? \$${snake}_text : \$${snake}_url ); ?></a>\n\t<?php } ?>\n"
      else
        markup_lines+="\t<?php if ( \$${snake}_url ) { ?>\n\t\t<a href=\"<?php echo esc_url( \$${snake}_url ); ?>\"><?php echo esc_html( \$${snake}_text ? \$${snake}_text : \$${snake}_url ); ?></a>\n\t<?php } ?>\n"
      fi
      ;;
  esac
done

cat > "${block_dir}/render.php" <<EOF
<?php
/**
 * Block template for ${block_name}.
 *
 * @package ${theme_slug}
 */

defined( 'ABSPATH' ) || exit;

$(printf "%b" "$extract_lines")
\$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => '${block_kebab}' ) );
?>
<section <?php echo \$wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() already escapes. ?>>
$(printf "%b" "$markup_lines")</section>
EOF

# Concatenating each field's independent "<?php if (...) { ?> ... <?php } ?>"
# fragment leaves adjacent fields separated only by whitespace — a bare
# "?>" immediately followed by "<?php" with nothing meaningful between,
# which is against this project's PHP style. Collapsing those pairs keeps
# consecutive conditionals in one continuous PHP region instead.
if command -v perl >/dev/null 2>&1; then
  perl -0777 -pi -e 's/\?>\s*<\?php/\n/g' "${block_dir}/render.php"
fi

echo "Created: ${block_dir}/render.php"

echo ""
echo "If this block needs custom styles, add: ./src/blocks/${block_kebab}.css (picked up automatically, no registration needed)"
echo "Run 'npm run blocks:build' (or 'npm run blocks:start' while developing) before using this block — it won't appear correctly until build/index.js exists."
