import React, { PropTypes } from 'react';
import WCCSettingsStep from './settings-step';
import notices from 'notices';
import GlobalNotices from 'components/global-notices';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import * as FormActions from 'state/form/actions';
import { successNotice, errorNotice } from 'state/notices/actions';
import * as SettingsActions from 'state/settings/actions';
import * as PackagesActions from 'state/form/packages/actions';
import { getFormErrors, getStepFormErrors } from 'state/selectors';
import { translate as __ } from 'lib/mixins/i18n';
import FormButton from 'components/forms/form-button';
import FormButtonsBar from 'components/forms/form-buttons-bar';

const WCCSettingsForm = ( props ) => {
	const currentStep = props.form.currentStep;
	const currentStepLayout = props.layout[ currentStep ];

	const renderCurrentStep = () => {
		if ( ! currentStepLayout ) {
			return <span>... spinner or something ...</span>;
		}

		return (
			<WCCSettingsStep
				{ ...props }
				layout={ currentStepLayout }
			/>
		);
	};

	const renderActionButton = () => {
		// TODO: extend <SaveForm> and use it in place of this
		const label = ( currentStepLayout || {} ).action_label || __( 'Next' );
		const isDisabled = ! currentStepLayout || 0 < props.stepErrors.length || props.form.isSaving;
		return (
			<FormButtonsBar>
				<FormButton
					type="button"
					disabled={ isDisabled }
					onClick={ props.formActions.nextStep }>
					{ label }
				</FormButton>
			</FormButtonsBar>
		)
	};

	const renderTab = ( label, index ) => {
		if ( index === currentStep ) {
			return <b>{ label }</b>;
		} else if ( index < currentStep ) {
			return <a onClick={ () => props.formActions.goToStep( index ) }>{ label }</a>;
		}
		return <span>{ label }</span>;
	};

	const renderMultiStepForm = () => {
		return (
			<div>
				<div>
					<ul>
						{ props.layout.map( ( step, index ) => (
							<li key={ index }>
								{ renderTab( step.tab_title, index ) }
							</li>
						) ) }
					</ul>
				</div>
				{ renderCurrentStep() }
				{ renderActionButton() }
			</div>
		);
	};

	return (
		<div>
			<GlobalNotices id="notices" notices={ notices.list } />
			{ 'step' === props.layout[ 0 ].type
				? renderMultiStepForm()
				: <WCCSettingsStep
					{ ...props }
					layout={ { items: props.layout } }
					/>
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
		settings: state.settings,
		form: state.form,
		errors: getFormErrors( state, props.schema ),
		stepErrors: getStepFormErrors( state, props.schema, props.layout ),
	};
}

function mapDispatchToProps( dispatch ) {
	return {
		formActions: bindActionCreators( FormActions, dispatch ),
		noticeActions: bindActionCreators( { successNotice, errorNotice }, dispatch ),
		packagesActions: bindActionCreators( PackagesActions, dispatch ),
		settingsActions: bindActionCreators( SettingsActions, dispatch ),
	};
}

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( WCCSettingsForm );
