import React, { PropTypes } from 'react';
import WCCSettingsGroup from './settings-group';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as FormActions from 'state/form/actions';
import { successNotice, errorNotice } from 'state/notices/actions';
import * as FormValueActions from 'state/form/values/actions';
import * as PackagesActions from 'state/form/packages/actions';
import { getFormErrors, getStepFormErrors, getStepFormSuggestions } from 'state/selectors/errors';
import { translate as __ } from 'lib/mixins/i18n';
import ActionButtons from 'components/action-buttons';
import TabBar from 'components/tab-bar';
import isEmpty from 'lodash/isEmpty';

const WCCSettingsForm = ( props ) => {
	const currentStep = props.form.currentStep;

	const renderGroup = ( stepIndex ) => {
		if ( ! props.layout[ stepIndex ] ) {
			return null; // TODO: display a spinner here
		}

		return (
			<WCCSettingsGroup
				{ ...props }
				group={ props.layout[ stepIndex ] }
				saveForm={ props.formValueActions.submit }
				key={ stepIndex }
			/>
		);
	};

	const getActionButtons = () => {
		const buttons = [];
		const currentStepLayout = props.layout[ currentStep ];
		let submitEnabled = currentStepLayout && isEmpty( props.stepErrors ) && ! props.form.isSaving;
		// If the user has been presented with a suggestion, then the submit button is always enabled,
		// because any options the user chooses ("original value" or "suggested value") are valid
		if ( ! isEmpty( props.stepSuggestions ) ) {
			submitEnabled = true;
		}
		buttons.push( {
			label: ( currentStepLayout || {} ).action_label || __( 'Next' ),
			onClick: props.formActions.nextStep,
			isDisabled: ! submitEnabled,
			isPrimary: true,
		} );
		if ( props.onCancel ) {
			buttons.push( {
				label: __( 'Cancel' ),
				onClick: props.onCancel,
			} );
		}
		return buttons;
	};

	const renderMultiStepForm = () => {
		return (
			<div className="multistep-form-container">
				<TabBar
					layout={ props.layout }
					currentStep={ currentStep }
					onTabClick={ props.formActions.goToStep } />
				<div className="step-content">
					{ renderGroup( currentStep ) }
				</div>
				<ActionButtons buttons={ getActionButtons() } />
			</div>
		);
	};

	const renderSingleStepForm = () => {
		return (
			<div>
				{ props.layout.map( ( group, idx ) => renderGroup( idx ) ) }
			</div>
		);
	};

	return 'step' === props.layout[ 0 ].type
				? renderMultiStepForm()
				: renderSingleStepForm();
};

WCCSettingsForm.propTypes = {
	storeOptions: PropTypes.object.isRequired,
	schema: PropTypes.object.isRequired,
	layout: PropTypes.array.isRequired,
};

function mapStateToProps( state, props ) {
	return {
		form: state.form,
		errors: getFormErrors( state, props.schema ),
		stepErrors: getStepFormErrors( state, props.schema, props.layout ),
		stepSuggestions: getStepFormSuggestions( state, props.schema, props.layout ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		formActions: bindActionCreators( FormActions, dispatch ),
		noticeActions: bindActionCreators( { successNotice, errorNotice }, dispatch ),
		packagesActions: bindActionCreators( PackagesActions, dispatch ),
		formValueActions: bindActionCreators( FormValueActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( WCCSettingsForm );
