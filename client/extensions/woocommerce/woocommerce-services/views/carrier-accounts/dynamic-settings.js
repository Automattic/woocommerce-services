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
import * as api from 'woocommerce/woocommerce-services/api';
import { getSelectedSiteId } from 'state/ui/selectors';
import DynamicCarrierAccountSettingsForm from './dynamic-settings-form';

export const DynamicCarrierAccountSettings = ( props ) => {
	const { translate } = props;
	const [carrierRegistrationFields, setCarrierRegistrationFields] = useState([]);

	useEffect(() => {
		const fetchRegistrationFields = async () => {
			const registrationFields = await api.get(props.siteId, api.url.shippingCarrierTypes());
			setCarrierRegistrationFields(registrationFields.carriers);
		}
		fetchRegistrationFields();
	}, [ props.siteId, setCarrierRegistrationFields ]);

	if ( ! carrierRegistrationFields || carrierRegistrationFields.length === 0) {
		return (
			<div>{ translate( 'Loading' ) }...</div>
		);
	}

	const [ currentCarrierRegistrationField ] = carrierRegistrationFields.filter(carrier => carrier.type === props.carrier);

	if (!currentCarrierRegistrationField) {
		return (
			<div>
			{ translate( '%(carrierName)s not supported.', {
				args: {
					carrierName: props.carrier,
				}
			} ) }
			</div>
		);
	}

    return (
		<div>
			<DynamicCarrierAccountSettingsForm
				carrierType={currentCarrierRegistrationField.type}
				carrierName={currentCarrierRegistrationField.name}
				carrierDescription={currentCarrierRegistrationField.description}
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

DynamicCarrierAccountSettings.propTypes = {
	carrier: PropTypes.string.isRequired,
	siteId: PropTypes.number
};

export default connect( mapStateToProps )( localize( DynamicCarrierAccountSettings ) );
