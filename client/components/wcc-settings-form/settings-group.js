import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import SettingsItem from './settings-item';
import SaveForm from 'components/save-form';

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
		const itemErrors = errors.length ? errors.filter( ( error ) => {
			const path = error.split( '.' );
			return path[0] === key;
		} ).map( ( path ) => {
			if ( 0 === path.indexOf( key + '.' ) ) {
				return path.substr( key.length + 1 );
			}
			return path;
		} ) : false;

		return (
			<SettingsItem
				layout={ item }
				{ ...{
					key,
					schema,
					storeOptions,
				} }
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
};

export default SettingsGroup;
