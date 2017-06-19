/**
 * External dependencies
 */
import React, { PropTypes } from 'react';
import _ from 'lodash';

/**
 * Internal dependencies
 */
import SettingsItem from './settings-item';
import ActionButtonsCard from 'components/action-buttons-card';
import SettingsGroupCard from 'components/settings-group-card';

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
			<div className="settings-form__row" key={ idx }>{ items }</div>
		) );
	};

	switch ( group.type ) {
		case 'fieldset':
			return (
				<SettingsGroupCard heading={ group.title } >
					{ renderSettingsItems() }
				</SettingsGroupCard>
			);

		case 'actions':
			const buttons = group.items.map( ( button ) => {
				let onClick = _.noop;
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
						isDisabled = ! _.isEmpty( errors );
					}
				}
				return { label, onClick, isDisabled, isPrimary };
			} );
			return <ActionButtonsCard buttons={ buttons } />;

		default:
			return (
				<div>
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
