import { __ } from '@wordpress/i18n';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { TextControl, TextareaControl, Button } from '@wordpress/components';

/**
 * Generic repeater UI for a block attribute holding an array of row objects.
 * The block-editor equivalent of the `repeater` field type in
 * inc/options.php — same sub-field vocabulary (text/textarea/image), separate
 * implementation since one runs in wp-admin and the other inside the block
 * editor's React tree.
 *
 * Rows lay out inline: each sub-field takes an equal-width slot, with
 * compact move-up/move-down/remove icon buttons at the row's end. Sub-field
 * labels render once, as column headers above the rows, rather than
 * repeating per row — `hideLabelFromVision` keeps them screen-reader
 * accessible on each control without rendering visually twice.
 *
 * @param {Object}   props
 * @param {string}   props.label    Field group label.
 * @param {Object[]} props.value    Current rows.
 * @param {Function} props.onChange ( rows ) => void
 * @param {Object[]} props.fields   [ { name, label, type: 'text'|'textarea'|'image'|'file', help, mimeTypes } ]
 * @param {Object}   props.emptyRow Shape of a freshly-added row, e.g. { stat: '', title: '' }.
 */
export default function RepeaterField( { label, value, onChange, fields, emptyRow } ) {
	const rows = value || [];

	function updateRow( index, patch ) {
		const next = rows.slice();
		next[ index ] = { ...next[ index ], ...patch };
		onChange( next );
	}

	function addRow() {
		onChange( [ ...rows, { ...emptyRow } ] );
	}

	function removeRow( index ) {
		onChange( rows.filter( ( _row, i ) => i !== index ) );
	}

	function moveRow( index, direction ) {
		const target = index + direction;
		if ( target < 0 || target >= rows.length ) {
			return;
		}
		const next = rows.slice();
		const tmp = next[ index ];
		next[ index ] = next[ target ];
		next[ target ] = tmp;
		onChange( next );
	}

	return (
		<div className="lc-js-skeleton-repeater-field">
			<label className="lc-js-skeleton-editor-field__label">{ label }</label>
			{ rows.length > 0 && (
				<div className="lc-js-skeleton-repeater-field__header">
					<span className="lc-js-skeleton-repeater-field__number-spacer" />
					{ fields.map( ( field ) => (
						<span
							key={ field.name }
							className={
								'image' === field.type || 'file' === field.type
									? 'lc-js-skeleton-repeater-field__header-cell lc-js-skeleton-repeater-field__header-cell--image'
									: 'lc-js-skeleton-repeater-field__header-cell'
							}
						>
							{ field.label }
						</span>
					) ) }
					<span className="lc-js-skeleton-repeater-field__row-actions-spacer" />
				</div>
			) }
			{ rows.map( ( row, index ) => (
				<div className="lc-js-skeleton-repeater-field__row" key={ index }>
					<span className="lc-js-skeleton-repeater-field__number">{ index + 1 }</span>
					{ fields.map( ( field ) => {
						if ( 'image' === field.type ) {
							return (
								<MediaUploadCheck key={ field.name }>
									<MediaUpload
										onSelect={ ( media ) =>
											updateRow( index, {
												[ field.name ]: media.id,
												[ `${ field.name }Url` ]: media.url,
											} )
										}
										allowedTypes={ [ 'image' ] }
										value={ row[ field.name ] }
										render={ ( { open } ) => (
											<div className="lc-js-skeleton-repeater-field__image">
												{ row[ `${ field.name }Url` ] && (
													<img src={ row[ `${ field.name }Url` ] } alt="" />
												) }
												<Button variant="secondary" size="small" onClick={ open }>
													{ row[ field.name ]
														? __( 'Replace', 'lc-js-skeleton2026' )
														: __( 'Select', 'lc-js-skeleton2026' ) }
												</Button>
											</div>
										) }
									/>
								</MediaUploadCheck>
							);
						}

						if ( 'file' === field.type ) {
							return (
								<MediaUploadCheck key={ field.name }>
									<MediaUpload
										onSelect={ ( media ) =>
											updateRow( index, {
												[ field.name ]: media.id,
												[ `${ field.name }Name` ]: media.filename || media.title || '',
											} )
										}
										allowedTypes={ field.mimeTypes || [] }
										value={ row[ field.name ] }
										render={ ( { open } ) => (
											<div className="lc-js-skeleton-repeater-field__image">
												{ row[ `${ field.name }Name` ] && (
													<span className="lc-js-skeleton-repeater-field__file-name">
														{ row[ `${ field.name }Name` ] }
													</span>
												) }
												<Button variant="secondary" size="small" onClick={ open }>
													{ row[ field.name ]
														? __( 'Replace', 'lc-js-skeleton2026' )
														: __( 'Select', 'lc-js-skeleton2026' ) }
												</Button>
											</div>
										) }
									/>
								</MediaUploadCheck>
							);
						}

						if ( 'textarea' === field.type ) {
							return (
								<TextareaControl
									key={ field.name }
									label={ field.label }
									hideLabelFromVision
									value={ row[ field.name ] || '' }
									onChange={ ( v ) => updateRow( index, { [ field.name ]: v } ) }
									help={ field.help }
								/>
							);
						}

						return (
							<TextControl
								key={ field.name }
								label={ field.label }
								hideLabelFromVision
								value={ row[ field.name ] || '' }
								onChange={ ( v ) => updateRow( index, { [ field.name ]: v } ) }
								help={ field.help }
							/>
						);
					} ) }
					<div className="lc-js-skeleton-repeater-field__row-actions">
						<Button
							size="small"
							label={ __( 'Move up', 'lc-js-skeleton2026' ) }
							onClick={ () => moveRow( index, -1 ) }
							disabled={ 0 === index }
						>
							&#9650;
						</Button>
						<Button
							size="small"
							label={ __( 'Move down', 'lc-js-skeleton2026' ) }
							onClick={ () => moveRow( index, 1 ) }
							disabled={ index === rows.length - 1 }
						>
							&#9660;
						</Button>
						<Button
							size="small"
							isDestructive
							label={ __( 'Remove', 'lc-js-skeleton2026' ) }
							onClick={ () => removeRow( index ) }
						>
							&times;
						</Button>
					</div>
				</div>
			) ) }
			<Button variant="primary" onClick={ addRow }>
				{ __( 'Add row', 'lc-js-skeleton2026' ) }
			</Button>
		</div>
	);
}
