import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import SettingsItem from './settings-item';
import ActionButtons from 'components/action-buttons';
import noop from 'lodash/noop';
import isEmpty from 'lodash/isEmpty';
import sanitizeHTML from 'lib/utils/sanitize-html';

const SettingsGroup = ( props ) => {
	const {
		group,
		form,
		saveForm,
		errors,
		schema,
	} = props;

	const renderSettingsItem = ( item ) => {
		const itemKey = item.key ? item.key : item;

		return (
			<SettingsItem
				{ ...props }
				key={ itemKey }
				layout={ item }
				errors={ errors[ itemKey ] || {} }
			/>
		);
	};

	const renderSettingsItems = () => {
		const rows = [];
		let currentRowItems = [];
		group.items.forEach( ( item ) => {
			if ( item.inline ) {
				currentRowItems.push( renderSettingsItem( item ) );
			} else {
				rows.push( currentRowItems );
				currentRowItems = [];
				rows.push( [ renderSettingsItem( item ) ] );
			}
		} );
		rows.push( currentRowItems );
		return rows.filter( ( arr ) => arr.length ).map( ( items, idx ) => (
			<div className="settings-form-row" key={ idx }>{ items }</div>
		) );
	};

	switch ( group.type ) {
		case 'fieldset':
			return (
				<CompactCard className="settings-group-card">
					<FormSectionHeading className="settings-group-header" dangerouslySetInnerHTML={ sanitizeHTML( group.title ) } />
					<div className="settings-group-content">
						{ renderSettingsItems() }
					</div>
				</CompactCard>
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
					{ renderSettingsItems() }
				</div>
			);
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
	formActions: PropTypes.object.isRequired,
	errors: PropTypes.object,
};

export default SettingsGroup;
