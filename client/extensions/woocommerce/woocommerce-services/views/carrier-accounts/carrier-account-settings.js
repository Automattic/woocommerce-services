/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { localize } from 'i18n-calypso';

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
import { getCountryName } from 'woocommerce/state/sites/data/locations/selectors';

const CarrierAccountSettings = props => {
	const { translate, stateNames, countryNames, formData } = props;

	const onClose = () => {};

	return (
		<div className="carrier-account-settings">
			<div className="carrier-account-settings__info">
				<h4 className="carrier-account-settings__subheader">{ translate( 'Connect your UPS account' ) }</h4>
				<p className="carrier-account-settings__subheader-description">{ translate( 'Set up your own UPS carrier account to compare rates and print labels from multiple carriers in WooCommerce Services. Learn more about adding {{a}}carrier accounts{{/a}}.', { components: { a: <a href="https://link.to.carrier.accounts.com/" />  } } ) }</p>
				<p className="carrier-account-settings__subheader-description">{ translate( 'If you need a UPS account number, go to {{a}}UPS.com{{/a}} to create a new account.', { components: { a: <a href="https://ups.com/" />  } } ) }</p>
			</div>
			<div className="carrier-account-settings__form">
				<CompactCard>
				<h4 className="carrier-account-settings__subheader">{ translate( 'General Information' ) }</h4>
				<p className="carrier-account-settings__subheader-description">{ translate( 'This is the account number an address from your UPS profile' ) }</p>
				</CompactCard>
				<CompactCard className="carrier-account-settings__account-number">
					<TextField
						id={ 'account_number' }
						title={ translate( 'Account number' ) }
						value={ '' }
					/>
				</CompactCard>
				<CompactCard className="carrier-account-settings__address">
					<TextField
						id={ 'name' }
						title={ translate( 'Name' ) }
						value={ '' }
					/>
					<TextField
						id={ 'address' }
						title={ translate( 'Address' ) }
						value={ '' }
					/>
					<div className="carrier-account-settings__two-columns">
						<TextField
							id={ 'address_2' }
							title={ translate( 'Address 2 (optional)' ) }
							value={ '' }
						/>
						<TextField
							id={ 'city' }
							title={ translate( 'city' ) }
							value={ '' }
						/>
					</div>
					<div className="carrier-account-settings__two-columns">

						{ stateNames ? (
							<Dropdown
								id={ 'state' }
								title={ translate( 'State' ) }
								value={ '' }
								valuesMap={ { '': props.translate( 'Select one…' ), ...stateNames } }
								updateValue={ () => {} }
							/>
						) : (
							<TextField
								id={ 'state' }
								title={ translate( 'State' ) }
								value={ '' }
								updateValue={ () => {} }
							/>
						) }

						<Dropdown
							id={ 'country' }
							title={ translate( 'Country' ) }
							value={ formData.country }
							valuesMap={ countryNames }
							updateValue={ () => {} }
						/>
					</div>
					<div className="carrier-account-settings__two-columns">
						<TextField
							id={ 'postal_code' }
							title={ translate( 'Postal code / Zip' ) }
							value={ '' }
						/>
						<TextField
							id={ 'phone' }
							title={ translate( 'Phone' ) }
							value={ '' }
						/>
					</div>
					<TextField
						id={ 'email' }
						title={ translate( 'email' ) }
						value={ '' }
					/>
				</CompactCard>
				<CompactCard className="carrier-account-settings__company-info">
					<div className="carrier-account-settings__header">
						<h4 className="carrier-account-settings__subheader">{ translate( 'Company information' ) }</h4>
						<p className="carrier-account-settings__subheader-description">{ translate( 'This is the company info you used to create your UPS account' ) }</p>
					</div>
					<TextField
						id={ 'company_name' }
						title={ translate( 'Company name' ) }
						value={ '' }
					/>
					<div className="carrier-account-settings__two-columns">
						<TextField
							id={ 'job_title' }
							title={ translate( 'Job title' ) }
							value={ '' }
						/>
						<TextField
							id={ 'company_website' }
							title={ translate( 'Company website' ) }
							value={ '' }
						/>
					</div>
				</CompactCard>
				<CompactCard className="carrier-account-settings__ups-info">
					<div className="carrier-account-settings__header">
						<h4 className="carrier-account-settings__subheader">{ translate( 'UPS account information' ) }</h4>
						<p className="carrier-account-settings__subheader-description">{ translate( 'Only required if you have a UPS invoice within the last 90 days' ) }</p>
					</div>
					<div className="carrier-account-settings__two-columns">
					<TextField
						id={ 'ups_invoice_number' }
						title={ translate( 'UPS invoice number' ) }
						value={ '' }
					/>
					<TextField
						id={ 'ups_invoice_date' }
						title={ translate( 'UPS invoice date' ) }
						value={ '' }
					/>
					</div>
					<div className="carrier-account-settings__two-columns">
					<TextField
						id={ 'ups_invoice_amount' }
						title={ translate( 'UPS invoice amount' ) }
						value={ '' }
					/>
					<TextField
						id={ 'ups_invoice_currency' }
						title={ translate( 'UPS invoice currency' ) }
						value={ '' }
					/>
					</div>
					<TextField
						id={ 'ups_invoice_control_id' }
						title={ translate( 'UPS invoice control id' ) }
						value={ '' }
					/>
					<Checkbox id={ 'license_agreement' } checked={ false } onChange={ () => {}  } />
					<span>{ translate( 'I have read the {{a}}License Agreement{{/a}}', { components: { a: <a href="https://link.to.terms.com/" />  } } ) }</span>
				</CompactCard>
				<CompactCard className="carrier-account-settings__actions">
					<Button compact primary onClick={ () => { onConnect( data ) } }>
						{ translate( 'Connect' ) }
					</Button>
					<Button compact onClick={ () => { onConnect( data ) } }>
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

	let countryNames = getDestinationCountryNames( state, siteId );

	if ( ! countryNames[ formData.country ] ) {
		// If the selected country is not supported but the user managed to select it, add it to the list
		countryNames = {
			[ formData.country ]: getCountryName( state, formData.country, siteId ),
			...countryNames,
		};
	}

	return {
		countryNames,
		stateNames: getStateNames( state, formData.country, siteId ),
		formData
	};
};

const mapDispatchToProps = dispatch => {
	return bindActionCreators(
		{
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
