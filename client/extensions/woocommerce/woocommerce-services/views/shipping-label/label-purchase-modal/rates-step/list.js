/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { get, mapValues } from 'lodash';

/**
 * Internal dependencies
 */
import FieldError from 'woocommerce/woocommerce-services/components/field-error';
import Notice from 'components/notice';
import ShippingRate from './shipping-rate';

const renderRateNotice = translate => {
	return (
		<Notice
			className="rates-step__notice"
			icon="info-outline"
			showDismiss={ false }
			text={ translate(
				'The service and rate chosen by the customer at checkout is not available. Please choose another.'
			) }
		/>
	);
};

// TODO: find a place to consolidate this function definition to
function getSignatureRequired( rateOptions, packageId, serviceId ) {
	if ( packageId in rateOptions && serviceId in rateOptions[ packageId ] ) {
		return rateOptions[ packageId ][ serviceId ].signatureRequired;
	}
	return false;
}

export const ShippingRates = ( {
	id,
	selectedRates, // Store owner selected rates, not customer
	availableRates,
	selectedPackages,
	updateRate,
	updateSignatureRequired,
	errors,
	shouldShowRateNotice,
	translate,
	rateOptions,
} ) => {

	const renderSinglePackage = ( pckg, pckgId ) => {
		const selectedRate = selectedRates[ pckgId ] || '';
		const packageRates = get( availableRates, [ pckgId, 'rates' ], [] );
		const packageErrors = errors[ pckgId ] || [];

		const onRateUpdate = value => updateRate( pckgId, value );
		const onSignatureRequiredUpdate = ( srvId, sigRqrd ) => updateSignatureRequired( pckgId, srvId, sigRqrd );
		return (
			<div key={ pckgId } className="rates-step__package-container">
				{ Object.values(
					mapValues( packageRates, ( ( rateObject ) => {
						const { service_id } = rateObject;
						return <ShippingRate
							id={ id + '_' + pckgId }
							rateObject={ rateObject }
							updateValue={ onRateUpdate }
							updateSignatureRequired={ ( val ) => onSignatureRequiredUpdate( service_id, val ) }
							isSelected={ service_id === selectedRate }
							signatureRequired={ getSignatureRequired( rateOptions, pckgId, service_id ) }
						/>
					} ) )
				) }
				{ packageErrors.slice( 1 ).map( ( error, index ) => {
					// Print the rest of the errors (if any) below the dropdown
					return <FieldError type="server-error" key={ index } text={ error } />;
				} ) }
			</div>
		);
	};

	return (
		<div>
			{ shouldShowRateNotice && renderRateNotice( translate ) }
			{ Object.values( mapValues( selectedPackages, renderSinglePackage ) ) }
		</div>
	);
};

ShippingRates.propTypes = {
	id: PropTypes.string.isRequired,
	selectedRates: PropTypes.object.isRequired,
	availableRates: PropTypes.object.isRequired,
	selectedPackages: PropTypes.object.isRequired,
	allPackages: PropTypes.object.isRequired,
	updateRate: PropTypes.func.isRequired,
	updateSignatureRequired: PropTypes.func.isRequired,
	errors: PropTypes.object.isRequired,
};

export default localize( ShippingRates );
