import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import SettingsItem from './settings-item';
import ActionButtons from 'components/action-buttons';
import noop from 'lodash/noop';

const filterErrorsForItem = ( groupErrors, itemKey ) => {
	let itemErrors = [];

	groupErrors.forEach( ( errorPath ) => {
		// Collect errant fields that have the current item as a parent
		if ( itemKey === errorPath[ 0 ] ) {
			itemErrors.push( errorPath.slice( 1 ) );
		}
	} );

	return itemErrors;
};

const SettingsGroup = ( props ) => {
	const {
		group,
		form,
		saveForm,
		errors,
		schema,
	} = props;

	const renderSettingsItem = ( item ) => {
		const key = item.key ? item.key : item;
		if ( 'packing_method' === key && ( ! form.values.boxes || 0 === form.values.boxes.length ) ) {
			return '';
		}

		const itemErrors = filterErrorsForItem( errors, key );

		return (
			<SettingsItem
				{ ...props }
				{ ...{ key } }
				layout={ item }
				errors={ itemErrors }
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
						isDisabled = ( 'undefined' !== typeof errors ) && ( 0 < errors.length );
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
	errors: PropTypes.array,
};

export default SettingsGroup;
