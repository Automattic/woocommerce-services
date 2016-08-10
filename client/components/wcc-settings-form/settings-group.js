import React, { PropTypes } from 'react';
import CompactCard from 'components/card/compact';
import FormSectionHeading from 'components/forms/form-section-heading';
import SettingsItem from './settings-item';
import ActionButtons from 'components/action-buttons';
import Notice from 'components/notice';
import noop from 'lodash/noop';
import isEmpty from 'lodash/isEmpty';
import Suggestion from 'components/suggestion';
import sanitizeHTML from 'lib/utils/sanitize-html';
import Button from 'components/button';
import Summary from 'components/summary';
import { translate as __ } from 'lib/mixins/i18n';

const SettingsGroup = ( props ) => {
	const {
		group,
		form,
		saveForm,
		errors,
		layout,
		schema,
		formActions,
		stepSuggestions,
		storeOptions,
		index,
	} = props;

	const renderSettingsItem = ( item ) => {
		const itemKey = item.key ? item.key : item;
		if ( 'packing_method' === itemKey && ( ! form.values.boxes || 0 === form.values.boxes.length ) ) {
			return null;
		}

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

		case 'step':
			if ( undefined !== form.acceptSuggestion ) {
				return (
					<div>
						<Notice
							status="is-warning"
							showDismiss={ false } >
							<span dangerouslySetInnerHTML={ sanitizeHTML( group.suggestion_hint ) } />
						</Notice>
						<div className="settings-group-default">
							<FormSectionHeading dangerouslySetInnerHTML={ sanitizeHTML( group.title ) } />
							{ group.description ? <p dangerouslySetInnerHTML={ sanitizeHTML( group.description ) } /> : null }
							<Suggestion
								acceptSuggestion={ Boolean( form.acceptSuggestion ) }
								formValues={ form.values }
								formActions={ formActions }
								layout={ group }
								suggestions={ stepSuggestions }
								storeOptions={ storeOptions }
								fieldsOptions={ form.fieldsOptions } />
						</div>
					</div>
				);
			}

			return (
				<div className="settings-group-default">
					<FormSectionHeading dangerouslySetInnerHTML={ sanitizeHTML( group.title ) } />
					{ group.description ? <p dangerouslySetInnerHTML={ sanitizeHTML( group.description ) } /> : null }
					{ renderSettingsItems() }
				</div>
			);

		case 'summary':
			const renderStepSummary = ( step, stepIndex ) => (
				<div key={ stepIndex } className="settings-step-summary">
					<h4>{ step.tab_title }</h4>
					<Summary
						formValues={ form.values }
						layoutItems={ step.items }
						summaryTemplate={ step.summary }
						storeOptions={ storeOptions }
						fieldsOptions={ form.fieldsOptions } />
					<Button compact
							onClick={ () => formActions.goToStep( stepIndex ) } >
						{ __( 'Edit' ) }
					</Button>
				</div>
			);

			return (
				<div className="settings-group-default">
					<FormSectionHeading dangerouslySetInnerHTML={ sanitizeHTML( group.title ) } />
					{ group.description ? <p>{ group.description }</p> : null }
					<div className="settings-steps-summary">
						{ layout.slice( 0, index ).map( renderStepSummary ) }
					</div>
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
