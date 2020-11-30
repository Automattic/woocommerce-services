/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import CompactCard from 'components/card/compact';
import Dialog from 'components/dialog';
import Dropdown from 'woocommerce/woocommerce-services/components/dropdown';
import Checkbox from 'woocommerce/woocommerce-services/components/checkbox';
import TextField from 'woocommerce/woocommerce-services/components/text-field';
import {
	getDestinationCountryNames,
	getStateNames,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import {
	getCarrierAccountsState,
	getFormErrors,
	getFormValidState,
} from 'woocommerce/woocommerce-services/state/carrier-accounts/selectors';
import {
	setVisibilityCancelConnectionDialog,
	submitCarrierSettings,
	updateCarrierSettings,
	toggleShowUPSInvoiceFields,
} from 'woocommerce/woocommerce-services/state/carrier-accounts/actions';
import { getCountryName } from 'woocommerce/state/sites/data/locations/selectors';
import { decodeEntities } from 'lib/formatting';

export const CarrierAccountSettings = ( props ) => {
	const {
		carrier,
		countryNames,
		fieldErrors,
		isConnectionSuccess,
		isFormValid,
		isSaving,
		showCancelConnectionDialog,
		showUPSInvoiceFields,
		siteId,
		stateNames,
		translate,
		values,
	} = props;

	if ( isConnectionSuccess ) {
		const url = new URL( window.location.href );
		url.searchParams.delete( 'carrier' );
		window.onbeforeunload = null;
		window.location.href = url.href;
	}

	const getValue = ( fieldName ) => {
		return values[ fieldName ] ? decodeEntities( values[ fieldName ] ) : '';
	};
	const updateValue = ( fieldName ) => ( newValue ) =>
		props.updateCarrierSettings( siteId, carrier, fieldName, newValue );
	const submitCarrierSettingsHandler = () => {
		props.submitCarrierSettings( siteId, carrier, values );
	};
	const showCancelDialogHandler = () => {
		props.setVisibilityCancelConnectionDialog( siteId, carrier, true );
	};
	const hideCancelDialogHandler = () => {
		props.setVisibilityCancelConnectionDialog( siteId, carrier, false );
	};
	const updateShowUPSInvoiceFields = () => {
		props.toggleShowUPSInvoiceFields( siteId, carrier );
	};

	const displayErrors = () => {};
	const upsInvoiceFields = () => {
		return (
			<div className="carrier-accounts__settings-ups-invoice">
				<div className="carrier-accounts__settings-two-columns">
					<TextField
						id={ 'invoice_number' }
						title={ translate( 'UPS invoice number' ) }
						value={ getValue( 'invoice_number' ) }
						updateValue={ updateValue( 'invoice_number' ) }
						error={ fieldErrors.invoice_number }
					/>
					<TextField
						id={ 'invoice_date' }
						title={ translate( 'UPS invoice date' ) }
						value={ getValue( 'invoice_date' ) }
						updateValue={ updateValue( 'invoice_date' ) }
						error={ fieldErrors.invoice_date }
						placeholder={ 'YYYY-MM-DD' }
					/>
				</div>
				<div className="carrier-accounts__settings-two-columns">
					<TextField
						id={ 'invoice_amount' }
						title={ translate( 'UPS invoice amount' ) }
						value={ getValue( 'invoice_amount' ) }
						updateValue={ updateValue( 'invoice_amount' ) }
						error={ fieldErrors.invoice_amount }
					/>
					<TextField
						id={ 'invoice_currency' }
						title={ translate( 'UPS invoice currency' ) }
						value={ getValue( 'invoice_currency' ) }
						updateValue={ updateValue( 'invoice_currency' ) }
						error={ fieldErrors.invoice_currency }
					/>
				</div>
				<TextField
					id={ 'invoice_control_id' }
					title={ translate( 'UPS invoice control id' ) }
					value={ getValue( 'invoice_control_id' ) }
					updateValue={ updateValue( 'invoice_control_id' ) }
					error={ fieldErrors.invoice_control_id }
				/>
			</div>
		);
	};

	const cancelDialogButton = () => {
		return [
			<Button compact onClick={ hideCancelDialogHandler }>
				{ translate( 'Cancel' ) }
			</Button>,
			<Button compact primary scary onClick={ () => history.back() }>
				{ translate( 'Ok' ) }
			</Button>,
		];
	};

	return (
		<div className="carrier-accounts__settings-container">
			{ displayErrors() }
			<div className="carrier-accounts__settings">
				<div className="carrier-accounts__settings-info">
					<h4 className="carrier-accounts__settings-subheader-above-description">
						{ translate( 'Connect your UPS account' ) }
					</h4>
					<p className="carrier-accounts__settings-subheader-description">
						{ translate(
							'Set up your own UPS carrier account to compare rates and print labels from multiple carriers in WooCommerce Shipping. Learn more about adding {{a}}carrier accounts{{/a}}.',
							{ components: { a: <a href="https://docs.woocommerce.com/document/productid-type-permalinks/using-your-own-ups-account-in-woocommerce-shipping/" /> } }
						) }
					</p>
					<p className="carrier-accounts__settings-subheader-description">
						{ translate(
							'If you need a UPS account number, go to {{a}}UPS.com{{/a}} to create a new account.',
							{ components: { a: <a href="https://ups.com/" /> } }
						) }
					</p>
				</div>
				<div className="carrier-accounts__settings-form">
					<CompactCard>
						<h4 className="carrier-accounts__settings-subheader">{ translate( 'General information' ) }</h4>
						<p className="carrier-accounts__settings-subheader-description">
							{ translate( 'This is the account number and address from your UPS profile' ) }
						</p>
					</CompactCard>
					<CompactCard className="carrier-accounts__settings-account-number">
						<TextField
							id={ 'account_number' }
							title={ translate( 'Account number' ) }
							value={ getValue( 'account_number' ) }
							updateValue={ updateValue( 'account_number' ) }
							error={ fieldErrors.account_number }
						/>
					</CompactCard>
					<CompactCard className="carrier-accounts__settings-address">
						<TextField
							id={ 'name' }
							title={ translate( 'Name' ) }
							value={ getValue( 'name' ) }
							updateValue={ updateValue( 'name' ) }
							error={ fieldErrors.name }
						/>
						<TextField
							id={ 'street1' }
							title={ translate( 'Address' ) }
							value={ getValue( 'street1' ) }
							updateValue={ updateValue( 'street1' ) }
							error={ fieldErrors.street1 }
						/>
						<div className="carrier-accounts__settings-two-columns">
							<TextField
								id={ 'street2' }
								title={ translate( 'Address 2 (optional)' ) }
								value={ getValue( 'street2' ) }
								updateValue={ updateValue( 'street2' ) }
								error={ fieldErrors.street2 }
							/>
							<TextField
								id={ 'city' }
								title={ translate( 'City' ) }
								value={ getValue( 'city' ) }
								updateValue={ updateValue( 'city' ) }
								error={ fieldErrors.city }
							/>
						</div>
						<div className="carrier-accounts__settings-two-columns">
							{ stateNames ? (
								<Dropdown
									id={ 'state' }
									title={ translate( 'State' ) }
									value={ getValue( 'state' ) }
									valuesMap={ { '': props.translate( 'Select one…' ), ...stateNames } }
									updateValue={ updateValue( 'state' ) }
									error={ fieldErrors.state }
								/>
							) : (
								<TextField
									id={ 'state' }
									title={ translate( 'State' ) }
									value={ getValue( 'state' ) }
									updateValue={ updateValue( 'state' ) }
									error={ fieldErrors.state }
								/>
							) }

							<Dropdown
								id={ 'country' }
								title={ translate( 'Country' ) }
								value={ getValue( 'country' ) }
								valuesMap={ countryNames }
								updateValue={ updateValue( 'country' ) }
								error={ fieldErrors.country }
							/>
						</div>
						<div className="carrier-accounts__settings-two-columns">
							<TextField
								id={ 'postal_code' }
								title={ translate( 'ZIP/Postal code' ) }
								value={ getValue( 'postal_code' ) }
								updateValue={ updateValue( 'postal_code' ) }
								error={ fieldErrors.postal_code }
							/>
							<TextField
								id={ 'phone' }
								title={ translate( 'Phone' ) }
								value={ getValue( 'phone' ) }
								updateValue={ updateValue( 'phone' ) }
								error={ fieldErrors.phone }
							/>
						</div>
						<TextField
							id={ 'email' }
							title={ translate( 'Email' ) }
							value={ getValue( 'email' ) }
							updateValue={ updateValue( 'email' ) }
							error={ fieldErrors.email }
						/>
					</CompactCard>
					<CompactCard className="carrier-accounts__settings-company-info">
						<div className="carrier-accounts__settings-header">
							<h4 className="carrier-accounts__settings-subheader">
								{ translate( 'Company information' ) }
							</h4>
							<p className="carrier-accounts__settings-subheader-description">
								{ translate( 'This is the company info you used to create your UPS account' ) }
							</p>
						</div>
						<TextField
							id={ 'company' }
							title={ translate( 'Company name' ) }
							value={ getValue( 'company' ) }
							updateValue={ updateValue( 'company' ) }
							error={ fieldErrors.company }
						/>
						<div className="carrier-accounts__settings-two-columns">
							<TextField
								id={ 'title' }
								title={ translate( 'Job title' ) }
								value={ getValue( 'title' ) }
								updateValue={ updateValue( 'title' ) }
								error={ fieldErrors.title }
							/>
							<TextField
								id={ 'website' }
								title={ translate( 'Company website' ) }
								value={ getValue( 'website' ) }
								updateValue={ updateValue( 'website' ) }
								error={ fieldErrors.website }
							/>
						</div>
					</CompactCard>
					<CompactCard className="carrier-accounts__settings-ups-info">
						<div className="carrier-accounts__settings-header">
							<h4 className="carrier-accounts__settings-subheader">
								{ translate( 'UPS account information' ) }
							</h4>
							<Checkbox
								id={ 'enable_ups_invoice_fields' }
								checked={ showUPSInvoiceFields }
								onChange={ updateShowUPSInvoiceFields }
							/>
							<span>
								{ translate( 'I have been issued an invoice from UPS within the past 90 days' ) }
							</span>
						</div>
						{ showUPSInvoiceFields && upsInvoiceFields() }
					</CompactCard>
					<CompactCard className="carrier-accounts__settings-actions">
						<Button
							compact
							primary
							onClick={ submitCarrierSettingsHandler }
							disabled={ ! isFormValid || isSaving }
							busy={ isSaving }
						>
							{ translate( 'Connect' ) }
						</Button>
						<Button compact onClick={ showCancelDialogHandler }>
							{ translate( 'Cancel' ) }
						</Button>
					</CompactCard>
				</div>
				<Dialog
					isVisible={ showCancelConnectionDialog }
					additionalClassNames="carrier-accounts__settings-cancel-dialog"
					onClose={ hideCancelDialogHandler }
					buttons={ cancelDialogButton() }
				>
					<div className="carrier-accounts__settings-cancel-dialog-header">
						<h2 className="carrier-accounts__settings-cancel-dialog-title">
							{ translate( 'Cancel connection' ) }
						</h2>
						<button
							className="carrier-accounts__settings-cancel-dialog-close-button"
							onClick={ hideCancelDialogHandler }
						>
							<Gridicon icon="cross" />
						</button>
					</div>
					<p className="carrier-accounts__settings-cancel-dialog-description">
						{ translate( 'This action will delete any information entered on the form.' ) }
					</p>
				</Dialog>
			</div>
		</div>
	);
};

const mapStateToProps = ( state, { siteId, carrier } ) => {
	const carrierAccountState = getCarrierAccountsState( state, siteId, carrier );
	const { isSaving } = carrierAccountState;
	const {
		values,
		showCancelConnectionDialog,
		showUPSInvoiceFields,
		isConnectionSuccess,
	} = carrierAccountState.settings;
	const fieldErrors = getFormErrors( state, siteId, carrier );
	const isFormValid = getFormValidState( state, siteId, carrier );

	let countryNames = getDestinationCountryNames( state, siteId );

	if ( ! countryNames[ values.country ] ) {
		// If the selected country is not supported but the user managed to select it, add it to the list
		countryNames = {
			[ values.country ]: getCountryName( state, values.country, siteId ),
			...countryNames,
		};
	}

	const ret = {
		countryNames,
		fieldErrors,
		isConnectionSuccess,
		isFormValid,
		isSaving,
		showCancelConnectionDialog,
		showUPSInvoiceFields,
		stateNames: getStateNames( state, values.country, siteId ),
		values,
	};
	return ret;
};

const mapDispatchToProps = ( dispatch ) => {
	return bindActionCreators(
		{
			setVisibilityCancelConnectionDialog,
			submitCarrierSettings,
			toggleShowUPSInvoiceFields,
			updateCarrierSettings,
		},
		dispatch
	);
};

CarrierAccountSettings.propTypes = {
	carrier: PropTypes.string.isRequired,
};

export default connect( mapStateToProps, mapDispatchToProps )( localize( CarrierAccountSettings ) );
