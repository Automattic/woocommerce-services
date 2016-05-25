import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import SettingsItem from './settings-item';
import SaveForm from 'components/save-form';

const filterErrorsForItem = ( groupErrors, itemKey ) => {
	let itemErrors = [];

	groupErrors.forEach( ( error ) => {
		// Errors are represented as a dot-notation path to a field
		const path = error.split( '.' );
		// Collect errant fields that have the current item as a parent
		if ( itemKey === path[0] ) {
			itemErrors.push( path.slice( 1 ).join( '.' ) );
		}
	} );

	return itemErrors;
};

const SettingsGroup = ( {
	group,
	schema,
	storeOptions,
	form,
	saveForm,
	errors,
} ) => {
	const renderSettingsItem = ( item ) => {
		const key = item.key ? item.key : item;
		const itemErrors = filterErrorsForItem( errors, key );

		return (
			<SettingsItem
				layout={ item }
				errors={ itemErrors }
				{ ...{
					key,
					schema,
					storeOptions,
				} }
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

		case 'actions':
			return (
				<CompactCard className="save-button-bar">
					<SaveForm
						saveForm={ saveForm }
						isSaving={ form.isSaving }
						formHasError={ ( 'undefined' !== typeof errors ) && ( 0 < errors.length ) }
					/>
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
};

export default SettingsGroup;
