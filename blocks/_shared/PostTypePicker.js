import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { ComboboxControl } from '@wordpress/components';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Generic single-post picker for a block attribute holding one post ID —
 * the block-editor equivalent of ACF's `post_object` field (single,
 * `return_format: object`). Search-as-you-type via ComboboxControl, backed
 * by @wordpress/core-data rather than a custom REST call.
 *
 * @param {Object}   props
 * @param {string}   props.label    Field label.
 * @param {string}   props.postType Post type slug to search (e.g. 'product').
 * @param {number}   props.value    Currently selected post ID, or 0/undefined for none.
 * @param {Function} props.onChange ( id: number ) => void — 0 clears the selection.
 * @param {string}   [props.help]   Optional help text.
 */
export default function PostTypePicker( { label, postType, value, onChange, help } ) {
	const [ search, setSearch ] = useState( '' );

	const searchResults = useSelect(
		( select ) =>
			select( coreStore ).getEntityRecords( 'postType', postType, {
				search,
				status: 'publish',
				orderby: 'title',
				order: 'asc',
				per_page: 20,
			} ),
		[ postType, search ]
	);

	// The currently selected post may not be among the current search
	// results (e.g. right after the block loads) — resolve it separately so
	// it always has a matching option and doesn't show as blank.
	const selectedPost = useSelect(
		( select ) => ( value ? select( coreStore ).getEntityRecord( 'postType', postType, value ) : null ),
		[ postType, value ]
	);

	const options = [];

	if ( selectedPost && ! ( searchResults || [] ).some( ( post ) => post.id === selectedPost.id ) ) {
		options.push( { value: selectedPost.id, label: decodeEntities( selectedPost.title?.rendered || '' ) || `#${ selectedPost.id }` } );
	}

	( searchResults || [] ).forEach( ( post ) => {
		options.push( { value: post.id, label: decodeEntities( post.title?.rendered || '' ) || `#${ post.id }` } );
	} );

	return (
		<ComboboxControl
			label={ label }
			value={ value || null }
			options={ options }
			onFilterValueChange={ ( input ) => setSearch( input ) }
			onChange={ ( id ) => onChange( id ? Number( id ) : 0 ) }
			help={ help }
		/>
	);
}
