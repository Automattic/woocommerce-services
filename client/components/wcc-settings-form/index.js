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
import { getFormErrors } from 'state/selectors';
import { translate as __ } from 'lib/mixins/i18n';
import ActionButtons from 'components/action-buttons';

const getStepFormErrors = ( allErrors, stepLayout ) => {
	const stepFields = {};
	stepLayout.items.forEach( group => group.items.forEach( item => stepFields[ item.key ] = true ) );
	return allErrors.filter( elem => stepFields[ elem[0] ] );
};

const WCCSettingsForm = ( props ) => {
	const currentStep = props.form.currentStep;
	const currentStepLayout = props.layout[ currentStep ];

	const renderCurrentStep = () => {
		if ( ! currentStepLayout ) {
			return <span>... spinner or something ...</span>;
		}

		return (
			<WCCSettingsGroup
				{ ...props }
				group={ currentStepLayout }
				saveForm={ props.settingsActions.submit }
			/>
		);
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
		const buttons = [];
		buttons.push( {
			label: ( currentStepLayout || {} ).action_label || __( 'Next' ),
			onClick: props.formActions.nextStep,
			isDisabled: ! currentStepLayout || 0 < props.stepErrors.length || props.form.isSaving,
			isPrimary: true,
		} );
		if ( props.onCancel ) {
			buttons.push( {
				label: __( 'Cancel' ),
				onClick: props.onCancel,
			} );
		}

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
				<div>
					{ renderCurrentStep() }
				</div>
				<div>
					<ActionButtons buttons={ buttons } />
				</div>
			</div>
		);
	};

	const renderSingleStepForm = () => {
		return (
			<div>
				{ props.layout.map( ( group, idx ) => (
					<WCCSettingsGroup
						{ ...props }
						group={ group }
						saveForm={ props.settingsActions.submit }
						key={ idx }
					/>
				) ) }
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
		errors: getFormErrors( state, props ),
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
