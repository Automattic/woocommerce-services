import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import SettingsItem from './settings-item';
import ActionButtons from 'components/action-buttons';
import Notice from 'components/notice';
import noop from 'lodash/noop';
import isEmpty from 'lodash/isEmpty';
import omit from 'lodash/omit';
import find from 'lodash/find';
import FormLabel from 'components/forms/form-label';
import FormRadio from 'components/forms/form-radio';
import Button from 'components/button';
import { translate as __ } from 'lib/mixins/i18n';

const RadioButton = ( props ) => {
	return (
		<FormLabel>
			<FormRadio { ...omit( props, 'children' ) } />
			{ props.children }
		</FormLabel>
	);
};

const SettingsGroup = ( props ) => {
	const {
		group,
		form,
		saveForm,
		errors,
		schema,
	} = props;

	const renderSummary = ( overrideFields = {} ) => {
		const fieldRawValues = {};
		group.items.map( ( item ) => item.key ).forEach( ( fieldName ) => {
			if ( undefined !== overrideFields[ fieldName ] && overrideFields[ fieldName ] !== form.values[ fieldName ] ) {
				fieldRawValues[ fieldName ] = {
					value: overrideFields[ fieldName ],
					override: true,
				};
			} else {
				fieldRawValues[ fieldName ] = {
					value: form.values[ fieldName ],
				};
			}
		} );

		const getPrintableValue = ( fieldName ) => {
			const fieldRawValue = fieldRawValues[ fieldName ].value;
			const layout = find( group.items, [ 'key', fieldName ] );
			switch ( layout.type ) {
				case 'radios':
				case 'dropdown':
					return layout.titleMap[ fieldRawValue ] || fieldRawValue;
				case 'state':
					const countryCode = fieldRawValues[ layout.country_field ].value;
					const statesMap = layout.dataset[ countryCode ];
					if ( ! statesMap ) {
						return fieldRawValue;
					}
					if ( ! Object.keys( statesMap ).length || ! fieldRawValue ) {
						return null;
					}
					return statesMap[ fieldRawValue ] || fieldRawValue;
				default:
					return fieldRawValue;
			}
		};
		const fieldValues = {};
		Object.keys( fieldRawValues ).forEach( ( fieldName ) => (
			fieldValues[ fieldName ] = {
				value: getPrintableValue( fieldName ),
				override: fieldRawValues[ fieldName ].override,
			}
		) );

		const renderSummaryLine = ( line, idx ) => {
			const children = [];
			while ( true ) {
				const match = /{(.+?)}/.exec( line );
				if ( ! match ) {
					break;
				}
				if ( match.index ) {
					children.push( { value: line.substr( 0, match.index ) } );
				}
				children.push( fieldValues[ match[ 1 ] ] );
				line = line.substr( match.index + match[ 0 ].length );
			}
			if ( line.length ) {
				children.push( { value: line } );
			}
			return (
				<p key={ idx }>
					{ children.map( ( field, index ) => (
						<span
							key={ index }
							style={ field.override ? { backgroundColor: 'cyan' } : {} }>
							{ field.value }
						</span>
					) ) }
				</p>
			)
		};
		const template = group.summary;
		const lines = template.split( '\\n' );

		return (
			<div>
				{ lines.map( renderSummaryLine ) }
			</div>
		)
	};

	const renderSuggestion = ( suggestions ) => {
		return (
			<div>
				<RadioButton
					checked={ ! form.acceptSuggestion }
					onChange={ () => props.formActions.setFormProperty( 'acceptSuggestion', false ) } >
					{ group.suggestion_original_title }
					{ renderSummary() }
					<Button onClick={ () => {
						props.formActions.setFormProperty( 'errors', {} );
						props.formActions.setFormProperty( 'acceptSuggestion', undefined );
						props.formActions.setFormProperty( 'pristine', false );
					} } >
						{ __( 'Edit' ) }
					</Button>
				</RadioButton>
				<RadioButton
					checked={ !! form.acceptSuggestion }
					onChange={ () => props.formActions.setFormProperty( 'acceptSuggestion', true ) } >
					{ group.suggestion_corrected_title }
					{ renderSummary( suggestions ) }
				</RadioButton>
			</div>
		);
	};

	const renderSettingsItem = ( item ) => {
		const key = item.key ? item.key : item;
		if ( 'packing_method' === key && ( ! form.values.boxes || 0 === form.values.boxes.length ) ) {
			return '';
		}

		return (
			<SettingsItem
				{ ...props }
				{ ...{ key } }
				layout={ item }
				errors={ errors[ key ] || {} }
			/>
		);
	};

	switch ( group.type ) {
		case 'fieldset':
			return (
				<CompactCard className="settings-group-card">
					<FormSectionHeading className="settings-group-header">{ group.title }</FormSectionHeading>
					<div className="settings-group-content">
						{ group.items.map( renderSettingsItem ) }
					</div>
				</CompactCard>
			);

		case 'step':
			if ( undefined !== form.acceptSuggestion ) {
				return (
					<div className="settings-group-default">
						<Notice
							status="is-warning"
							text={ group.suggestion_hint }
							showDismiss={ false }
						/>
						<FormSectionHeading>{ group.title }</FormSectionHeading>
						{ group.description ? <p>{ group.description }</p> : null }
						{ renderSuggestion( props.stepSuggestions ) }
					</div>
				);
			}

			return (
				<div className="settings-group-default">
					<FormSectionHeading>{ group.title }</FormSectionHeading>
					{ group.description ? <p>{ group.description }</p> : null }
					{ group.items.map( renderSettingsItem ) }
				</div>
			);

		case 'actions':
			const buttons = group.items.map( ( button ) => {
				let onClick = noop;
				let isDisabled = false;
				let label = button.title;
				let isPrimary = false;
				if ( 'submit' === button.type ) {
					isPrimary = true;
					if ( form.isSaving ) {
						isDisabled = true;
						label = button.progressTitle || label;
					} else {
						onClick = () => saveForm( schema );
						isDisabled = ! isEmpty( errors );
					}
				}
				return { label, onClick, isDisabled, isPrimary };
			} );
			return (
				<CompactCard className="save-button-bar">
					<ActionButtons buttons={ buttons } />
				</CompactCard>
			);

		default:
			return (
				<div className="settings-group-default">
					{ group.items.map( renderSettingsItem ) }
				</div>
			)
	}
};

SettingsGroup.propTypes = {
	group: PropTypes.shape( {
		title: PropTypes.string,
		items: PropTypes.array,
	} ),
	schema: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	saveForm: PropTypes.func.isRequired,
	form: PropTypes.object.isRequired,
	errors: PropTypes.object,
};

export default SettingsGroup;
