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
	const [carrierRegistrationFields, setCarrierRegistrationFields] = useState([]);

	useEffect(() => {
		const fetchRegistrationFields = async () => {
			const registrationFields = await api.get(props.siteId, api.url.shippingCarrierTypes());
			setCarrierRegistrationFields(registrationFields.carriers);
		}
		fetchRegistrationFields();
	}, [props.siteId]);

	if (!carrierRegistrationFields || carrierRegistrationFields.length < 1) {
		return (
			<div>Loading...</div>
		);
	}

	const [ currentCarrierRegistrationField ] = carrierRegistrationFields.filter(carrier => carrier.type === props.carrier);

	if (!currentCarrierRegistrationField) {
		return (
			<div>{props.carrier} not supported.</div>
		);
	}

    return (
		<div>
			<DynamicCarrierAccountSettingsForm
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
