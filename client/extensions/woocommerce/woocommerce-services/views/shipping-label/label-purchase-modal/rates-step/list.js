/** @format */

/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { mapValues } from 'lodash';

/**
 * Internal dependencies
 */
import FieldError from 'woocommerce/woocommerce-services/components/field-error';
import Notice from 'components/notice';
import ShippingRate from './shipping-rate';
import getPackageDescriptions from '../packages-step/get-package-descriptions';
import { getSignatureServiceRates } from '../../../../lib/special-rates';

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


export const ShippingRates = ( {
	id,
	currency,
	selectedRates, // Store owner selected rates, not customer
	availableRates,
	selectedPackages,
	allPackages,
	updateRate,
	errors,
	shouldShowRateNotice,
	translate,
} ) => {
	const packageNames = getPackageDescriptions( selectedPackages, allPackages, true );
	const hasSinglePackage = 1 === Object.keys( selectedPackages ).length;

	const renderSinglePackage = ( pckg, pckgId ) => {
		if ( ! ( pckgId in availableRates ) ) {
			return null;
		}
		const selectedRate = selectedRates[ pckgId ] || '';
		const packageRates = availableRates[ pckgId ].default.rates;
		// filter out duplicate error messages
		const packageErrors = ( errors[ pckgId ] || [] ).filter( ( item, index ) => errors[ pckgId ].indexOf( item ) === index );

		const onRateUpdate = ( serviceId, carrierId, signatureRequired ) => updateRate( pckgId, serviceId, carrierId, signatureRequired );
		return (
			<div key={ pckgId } className="rates-step__package-container">

				{ ! hasSinglePackage ? (
					<div className="rates-step__package-container-rates-header">
						{ translate( 'Choose rate: %(pckg)s', { args: { pckg: packageNames[ pckgId ] } } ) }
					</div>
				) : null }
				{ Object.values(
					mapValues( packageRates, ( ( serviceRateObject ) => {
						const { service_id } = serviceRateObject;
						return <ShippingRate
							id={ id + '_' + pckgId }
							key={ id + '_' + pckgId + '_' + service_id }
							currency={ currency }
							rateObject={ serviceRateObject }
							signatureRates={ getSignatureServiceRates( pckgId, service_id, availableRates ) }
							updateValue={ onRateUpdate }
							isSelected={ service_id === selectedRate.serviceId }
						/>
					} ) )
				) }
				{ packageErrors.map( ( error, index ) => {
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
	errors: PropTypes.object.isRequired,
};

export default localize( ShippingRates );
