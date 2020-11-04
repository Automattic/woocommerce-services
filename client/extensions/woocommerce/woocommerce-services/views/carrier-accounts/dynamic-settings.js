/** @format */

/**
 * External dependencies
 */
import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { localize } from 'i18n-calypso';


/**
 * Internal dependencies
 */
import {
	getCarrierRegistrationFields
} from 'woocommerce/woocommerce-services/state/carrier-accounts/actions';
import * as api from 'woocommerce/woocommerce-services/api';
import { getSelectedSiteId } from 'state/ui/selectors';
import DynamicCarrierAccountSettingsForm from './dynamic-settings-form';

export const DynamicCarrierAccountSettings = ( props ) => {
	/**
	 * This maps the URL querystring to the API response name for the carrier.
	 * Keys are in lowercase.
	 */
	const carrierNameMapper = {
		dhl: 'DhlExpressAccount',
		ups: 'UpsAccount'
	};

	const [carrierRegistrationFields, setCarrierRegistrationFields] = useState([]);

	useEffect(() => {
		const fetchRegistrationFields = async () => {
			const registrationFields = await api.get(props.siteId, api.url.shippingCarrierTypes());
			setCarrierRegistrationFields(registrationFields.carriers);
		}
		fetchRegistrationFields();
	}, [props.siteId]);

	const apiResponseCarrierName = carrierNameMapper[props.carrier.toLowerCase()];

	if (!apiResponseCarrierName && Object.keys(apiResponseCarrierName).length > 0) {
		return (
			<div>{props.carrier} not supported.</div>
		);
	}

	if (!carrierRegistrationFields || carrierRegistrationFields.length < 1) {
		return (
			<div>Loading...</div>
		);
	}

	const currentCarrierRegistrationField = carrierRegistrationFields.filter(carrier => carrier.type === apiResponseCarrierName)[0];

    return (
		<div>
			<DynamicCarrierAccountSettingsForm
				carrier={props.carrier}
				carrierType={currentCarrierRegistrationField.type}
				carrierName={currentCarrierRegistrationField.name}
				registrationFields={currentCarrierRegistrationField.fields}
			/>
		</div>
	);
};

const mapStateToProps = ( state ) => {
	return {
		siteId: getSelectedSiteId( state ),
    };
};

const mapDispatchToProps = ( dispatch, {siteId} ) => ( {
	getCarrierRegistrationFields: () => dispatch( getCarrierRegistrationFields( siteId ) ),
} );

DynamicCarrierAccountSettings.propTypes = {
	carrier: PropTypes.string.isRequired,
	siteId: PropTypes.number
};

export default connect( mapStateToProps, mapDispatchToProps )( localize( DynamicCarrierAccountSettings ) );
