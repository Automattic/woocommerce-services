import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import SettingsItem from './settings-item';
import SaveForm from 'components/save-form';

const renderGroupItems = ( items, schema, storeOptions ) => {
	return (
		items.map( item => (
			<SettingsItem
				key={ item.key ? item.key : item }
				layout={ item }
				schema={ schema }
				storeOptions={ storeOptions }
			/>
		) )
	);
};

const SettingsGroup = ( {
	group,
	schema,
	storeOptions,
	form,
	saveForm,
} ) => {
	switch ( group.type ) {
		case 'fieldset':
			return (
				<CompactCard className="settings-group-card">
					<FormSectionHeading className="settings-group-header">{ group.title }</FormSectionHeading>
					<div className="settings-group-content">
						{ group.items ? renderGroupItems( group.items, schema, storeOptions ) : null }
					</div>
				</CompactCard>
			);

		case 'actions':
			return (
				<CompactCard className="save-button-bar">
					<SaveForm
						saveForm={ saveForm }
						isSaving={ form.isSaving }
					/>
				</CompactCard>
			);

		default:
			return (
				<div className="settings-group-default">
					{ group.items ? renderGroupItems( group.items, schema, storeOptions ) : null }
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
