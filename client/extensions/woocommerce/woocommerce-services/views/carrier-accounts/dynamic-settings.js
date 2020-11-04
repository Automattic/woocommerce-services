/** @format */
/* eslint-disable */

/**
 * External dependencies
 */
import React, { useEffect, useState } from 'react';
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
	getCarrierRegistrationFields
} from 'woocommerce/woocommerce-services/state/carrier-accounts/actions';
import { getCountryName } from 'woocommerce/state/sites/data/locations/selectors';
import { decodeEntities } from 'lib/formatting';
import * as api from 'woocommerce/woocommerce-services/api';
import { getSelectedSiteId } from 'state/ui/selectors';
import DynamicCarrierAccountSettingsForm from './dynamic-settings-form';

export const DynamicCarrierAccountSettings = ( props ) => {
	const {
		carrier,
		siteId,
	} = props;

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
};

export default connect( mapStateToProps, mapDispatchToProps )( localize( DynamicCarrierAccountSettings ) );

/* eslint-enable */
