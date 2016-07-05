import React, { PropTypes } from 'react';
import WCCSettingsGroup from './settings-group';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as FormActions from 'state/form/actions';
import { successNotice, errorNotice } from 'state/notices/actions';
import * as FormValueActions from 'state/form/values/actions';
import * as PackagesActions from 'state/form/packages/actions';
import { getFormErrors, getStepFormErrors, getStepFormSuggestions } from 'state/selectors/errors';
import { translate as __ } from 'lib/mixins/i18n';
import ActionButtons from 'components/action-buttons';
import Tabs from 'components/tabs';
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
			<div>
				<div>
					<Tabs
						layout={ props.layout }
						currentStep={ currentStep }
						onTabClick={ props.formActions.goToStep } />
				</div>
				<div>
					{ renderGroup( currentStep ) }
				</div>
				<div>
					<ActionButtons buttons={ getActionButtons() } />
				</div>
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

	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			{ 'step' === props.layout[ 0 ].type
				? renderMultiStepForm()
				: renderSingleStepForm()
			}
		</div>
	);
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
