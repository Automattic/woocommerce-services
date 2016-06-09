import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import SettingsItem from './settings-item';
import SaveForm from 'components/save-form';

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
		settings,
	} = props;

	const renderSettingsItem = ( item ) => {
		const key = item.key ? item.key : item;
		if ( 'packing_method' === key && ( ! settings.boxes || 0 === settings.boxes.length ) ) {
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
	errors: PropTypes.array,
	settings: PropTypes.object.isRequired,
};

export default SettingsGroup;
