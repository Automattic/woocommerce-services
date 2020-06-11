/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';
import { size } from 'lodash';

/**
 * Internal dependencies
 */
import Button from 'components/button';
import CompactCard from 'components/card/compact';
import Dropdown from 'woocommerce/woocommerce-services/components/dropdown';
import Checkbox from 'woocommerce/woocommerce-services/components/checkbox';
import TextField from 'woocommerce/woocommerce-services/components/text-field';
import {
	getDestinationCountryNames,
	getStateNames,
} from 'woocommerce/woocommerce-services/state/shipping-label/selectors';
import { getCarrierAccountsState } from 'woocommerce/woocommerce-services/state/carrier-accounts/selectors';
import { submitCarrierSettings, updateCarrierSettings } from 'woocommerce/woocommerce-services/state/carrier-accounts/actions';
import { getCountryName } from 'woocommerce/state/sites/data/locations/selectors';
import { decodeEntities } from 'lib/formatting';

const CarrierAccountSettings = props => {
	const {
		siteId,
		carrier,
		countryNames,
		values,
		fieldErrors,
		stateNames,
		translate,
	} = props;

	const generalErrorOnly = fieldErrors.general && size( fieldErrors ) === 1;
	const getValue = fieldName =>
		values[ fieldName ] ? decodeEntities( values[ fieldName ] ) : '';
	const updateValue = fieldName => newValue =>
		props.updateCarrierSettings( siteId, carrier, fieldName, newValue );
	const submitCarrierSettingsHandler = () =>
		props.submitCarrierSettings( siteId, carrier );
	const cancelHandler = () => {
		history.back();
	}

	return (
		<div className="carrier-accounts__settings">
			<div className="carrier-accounts__settings-info">
				<h4 className="carrier-accounts__settings-subheader">{ translate( 'Connect your UPS account' ) }</h4>
				<p className="carrier-accounts__settings-subheader-description">{ translate( 'Set up your own UPS carrier account to compare rates and print labels from multiple carriers in WooCommerce Services. Learn more about adding {{a}}carrier accounts{{/a}}.', { components: { a: <a href="https://link.to.carrier.accounts.com/" />  } } ) }</p>
				<p className="carrier-accounts__settings-subheader-description">{ translate( 'If you need a UPS account number, go to {{a}}UPS.com{{/a}} to create a new account.', { components: { a: <a href="https://ups.com/" />  } } ) }</p>
			</div>
			<div className="carrier-accounts__settings-form">
				<CompactCard>
				<h4 className="carrier-accounts__settings-subheader">{ translate( 'General Information' ) }</h4>
				<p className="carrier-accounts__settings-subheader-description">{ translate( 'This is the account number an address from your UPS profile' ) }</p>
				</CompactCard>
				<CompactCard className="carrier-accounts__settings-account-number">
					<TextField
						id={ 'account_number' }
						title={ translate( 'Account number' ) }
						value={ getValue( 'account_number' ) }
						updateValue={ updateValue( 'account_number' ) }
						error={ fieldErrors.account_number || generalErrorOnly }
					/>
				</CompactCard>
				<CompactCard className="carrier-accounts__settings-address">
					<TextField
						id={ 'name' }
						title={ translate( 'Name' ) }
						value={ getValue( 'name' ) }
						updateValue={ updateValue( 'name' ) }
						error={ fieldErrors.name || generalErrorOnly }
					/>
					<TextField
						id={ 'address' }
						title={ translate( 'Address' ) }
						value={ getValue( 'address' ) }
						updateValue={ updateValue( 'address' ) }
						error={ fieldErrors.address || generalErrorOnly }
					/>
					<div className="carrier-accounts__settings-two-columns">
						<TextField
							id={ 'address_2' }
							title={ translate( 'Address 2 (optional)' ) }
							value={ getValue( 'address_2' ) }
							updateValue={ updateValue( 'address_2' ) }
							error={ fieldErrors.address_2 || generalErrorOnly }
						/>
						<TextField
							id={ 'city' }
							title={ translate( 'city' ) }
							value={ getValue( 'city' ) }
							updateValue={ updateValue( 'city' ) }
							error={ fieldErrors.city || generalErrorOnly }
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
								error={ fieldErrors.state || generalErrorOnly }
							/>
						) : (
							<TextField
								id={ 'state' }
								title={ translate( 'State' ) }
								value={ getValue( 'state' ) }
								updateValue={ updateValue( 'state' ) }
								error={ fieldErrors.state || generalErrorOnly }
							/>
						) }

						<Dropdown
							id={ 'country' }
							title={ translate( 'Country' ) }
							value={ getValue( 'country' ) }
							valuesMap={ countryNames }
							updateValue={ updateValue( 'country' ) }
							error={ fieldErrors.country || generalErrorOnly }
						/>
					</div>
					<div className="carrier-accounts__settings-two-columns">
						<TextField
							id={ 'postal_code' }
							title={ translate( 'Postal code / Zip' ) }
							value={ getValue( 'postal_code' ) }
							updateValue={ updateValue( 'postal_code' ) }
							error={ fieldErrors.postal_code || generalErrorOnly }
							/>
						<TextField
							id={ 'phone' }
							title={ translate( 'Phone' ) }
							value={ getValue( 'phone' ) }
							updateValue={ updateValue( 'phone' ) }
							error={ fieldErrors.phone || generalErrorOnly }
						/>
					</div>
					<TextField
						id={ 'email' }
						title={ translate( 'Email' ) }
						value={ getValue( 'email' ) }
						updateValue={ updateValue( 'email' ) }
						error={ fieldErrors.email || generalErrorOnly }
					/>
				</CompactCard>
				<CompactCard className="carrier-accounts__settings-company-info">
					<div className="carrier-accounts__settings-header">
						<h4 className="carrier-accounts__settings-subheader">{ translate( 'Company information' ) }</h4>
						<p className="carrier-accounts__settings-subheader-description">{ translate( 'This is the company info you used to create your UPS account' ) }</p>
					</div>
					<TextField
						id={ 'company_name' }
						title={ translate( 'Company name' ) }
						value={ getValue( 'company_name' ) }
						updateValue={ updateValue( 'company_name' ) }
						error={ fieldErrors.company_name || generalErrorOnly }
					/>
					<div className="carrier-accounts__settings-two-columns">
						<TextField
							id={ 'job_title' }
							title={ translate( 'Job title' ) }
							value={ getValue( 'job_title' ) }
							updateValue={ updateValue( 'job_title' ) }
							error={ fieldErrors.job_title || generalErrorOnly }
						/>
						<TextField
							id={ 'company_website' }
							title={ translate( 'Company website' ) }
							value={ getValue( 'company_website' ) }
							updateValue={ updateValue( 'company_website' ) }
							error={ fieldErrors.company_website || generalErrorOnly }
						/>
					</div>
				</CompactCard>
				<CompactCard className="carrier-accounts__settings-ups-info">
					<div className="carrier-accounts__settings-header">
						<h4 className="carrier-accounts__settings-subheader">{ translate( 'UPS account information' ) }</h4>
						<p className="carrier-accounts__settings-subheader-description">{ translate( 'Only required if you have a UPS invoice within the last 90 days' ) }</p>
					</div>
					<div className="carrier-accounts__settings-two-columns">
					<TextField
						id={ 'ups_invoice_number' }
						title={ translate( 'UPS invoice number' ) }
						value={ getValue( 'ups_invoice_number' ) }
						updateValue={ updateValue( 'ups_invoice_number' ) }
						error={ fieldErrors.ups_invoice_number || generalErrorOnly }
					/>
					<TextField
						id={ 'ups_invoice_date' }
						title={ translate( 'UPS invoice date' ) }
						value={ getValue( 'ups_invoice_date' ) }
						updateValue={ updateValue( 'ups_invoice_date' ) }
						error={ fieldErrors.ups_invoice_date || generalErrorOnly }
					/>
					</div>
					<div className="carrier-accounts__settings-two-columns">
					<TextField
						id={ 'ups_invoice_amount' }
						title={ translate( 'UPS invoice amount' ) }
						value={ getValue( 'ups_invoice_amount' ) }
						updateValue={ updateValue( 'ups_invoice_amount' ) }
						error={ fieldErrors.ups_invoice_amount || generalErrorOnly }
					/>
					<TextField
						id={ 'ups_invoice_currency' }
						title={ translate( 'UPS invoice currency' ) }
						value={ getValue( 'ups_invoice_currency' ) }
						updateValue={ updateValue( 'ups_invoice_currency' ) }
						error={ fieldErrors.ups_invoice_currency || generalErrorOnly }
					/>
					</div>
					<TextField
						id={ 'ups_invoice_control_id' }
						title={ translate( 'UPS invoice control id' ) }
						value={ getValue( 'ups_invoice_control_id' ) }
						updateValue={ updateValue( 'ups_invoice_control_id' ) }
						error={ fieldErrors.ups_invoice_control_id || generalErrorOnly }
					/>
					<Checkbox id={ 'license_agreement' } checked={ false } onChange={ () => {}  } />
					<span>{ translate( 'I have read the {{a}}License Agreement{{/a}}', { components: { a: <a href="https://link.to.terms.com/" />  } } ) }</span>
				</CompactCard>
				<CompactCard className="carrier-accounts__settings-actions">
					<Button compact primary onClick={ submitCarrierSettingsHandler } disabled={ true  }>
						{ translate( 'Connect' ) }
					</Button>
					<Button compact onClick={ cancelHandler }>
						{ translate( 'Cancel' ) }
					</Button>
				</CompactCard>
			</div>
		</div>
	);
};

const mapStateToProps = ( state, { siteId, carrier } ) => {

	const carrierAccountState = getCarrierAccountsState( state, siteId, carrier );
	const formData = carrierAccountState.settings || {};
	const values = formData.values || {};
	const fieldErrors = formData.fieldErrors || {};

	let countryNames = getDestinationCountryNames( state, siteId );

	if ( ! countryNames[ values.country ] ) {
		// If the selected country is not supported but the user managed to select it, add it to the list
		countryNames = {
			[ values.country ]: getCountryName( state, values.country, siteId ),
			...countryNames,
		};
	}

	return {
		countryNames,
		stateNames: getStateNames( state, values.country, siteId ),
		values,
		fieldErrors,
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators(
		{
			submitCarrierSettings,
			updateCarrierSettings,
		},
		dispatch
	);
};

CarrierAccountSettings.propTypes = {
	carrier: PropTypes.string.isRequired,
};

export default connect(
	mapStateToProps,
	mapDispatchToProps
)( localize( CarrierAccountSettings ) );
