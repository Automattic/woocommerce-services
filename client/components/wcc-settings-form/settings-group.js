import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import SettingsItem from './settings-item';
import ActionButtons from 'components/action-buttons';
import Notice from 'components/notice';
import noop from 'lodash/noop';
import isEmpty from 'lodash/isEmpty';
import omit from 'lodash/omit';
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

	const renderSuggestion = ( suggestions ) => {
		return (
			<div>
				<RadioButton
					checked={ ! form.acceptSuggestion }
					onChange={ () => props.formActions.setFormProperty( 'acceptSuggestion', false ) } >
					{ group.suggestion_original_title }
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
