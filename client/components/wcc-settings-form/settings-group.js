import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import SettingsItem from './settings-item';
import ActionButtons from 'components/action-buttons';
import Notice from 'components/notice';
import noop from 'lodash/noop';
import isEmpty from 'lodash/isEmpty';
import Suggestion from 'components/suggestion';

const SettingsGroup = ( props ) => {
	const {
		group,
		form,
		saveForm,
		errors,
		schema,
		formActions,
		stepSuggestions,
		storeOptions,
	} = props;

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
					<FormSectionHeading className="settings-group-header">{ group.title }</FormSectionHeading>
					<div className="settings-group-content">
						{ renderSettingsItems() }
					</div>
				</CompactCard>
			);

		case 'step':
			if ( undefined !== form.acceptSuggestion ) {
				return (
					<div>
						<Notice
							status="is-warning"
							text={ group.suggestion_hint }
							showDismiss={ false }
						/>
						<div className="settings-group-default">
							<FormSectionHeading>{ group.title }</FormSectionHeading>
							{ group.description ? <p>{ group.description }</p> : null }
							<Suggestion
								acceptSuggestion={ Boolean( form.acceptSuggestion ) }
								formValues={ form.values }
								formActions={ formActions }
								layout={ group }
								suggestions={ stepSuggestions }
								countriesData={ storeOptions.countriesData } />
						</div>
					</div>
				);
			}

			return (
				<div className="settings-group-default">
					<FormSectionHeading>{ group.title }</FormSectionHeading>
					{ group.description ? <p>{ group.description }</p> : null }
					{ renderSettingsItems() }
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
					{ renderSettingsItems() }
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
	formActions: PropTypes.object.isRequired,
	stepSuggestions: PropTypes.object,
	errors: PropTypes.object,
};

export default SettingsGroup;
