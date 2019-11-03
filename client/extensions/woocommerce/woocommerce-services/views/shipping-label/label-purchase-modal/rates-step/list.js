/** @format */

/**
 * External dependencies
 */
import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { localize } from 'i18n-calypso';
import { mapValues, find, take } from 'lodash';

/**
 * Internal dependencies
 */
import FieldError from 'woocommerce/woocommerce-services/components/field-error';
import Notice from 'components/notice';
import ShippingRate from './shipping-rate';
import getPackageDescriptions from '../packages-step/get-package-descriptions';

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
	selectedRates, // Store owner selected rates, not customer
	availableRates,
	selectedPackages,
	allPackages,
	updateRate,
	errors,
	shouldShowRateNotice,
	translate,
} ) => {
	const showMoreItemsIncrement = 1;
	const [ numOfItemsToShow , setNumOfItemsToShow ] = useState( showMoreItemsIncrement );
	const packageNames = getPackageDescriptions( selectedPackages, allPackages, true );
	const hasSinglePackage = 1 === Object.keys( selectedPackages ).length;

	const renderSinglePackage = ( pckg, pckgId ) => {
		if ( ! ( pckgId in availableRates ) ) {
			return null;
		}
		const selectedRate = selectedRates[ pckgId ] || '';
		const packageRates = take( availableRates[ pckgId ].default.rates, numOfItemsToShow );
		const totalRatesCount = availableRates[ pckgId ].default.rates.length;
		const showShowMore = totalRatesCount > numOfItemsToShow;
		let signatureRates = null;
		if ( 'signature_required' in availableRates[ pckgId ] ) {
			signatureRates = availableRates[ pckgId ].signature_required.rates || null;
		}
		const packageErrors = errors[ pckgId ] || [];

		const onRateUpdate = ( serviceId, signatureRequired ) => updateRate( pckgId, serviceId, signatureRequired );
		const showMoreClick = () => setNumOfItemsToShow( numOfItemsToShow + showMoreItemsIncrement );
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
						const rateObjectSignatureRequired = find( signatureRates, r => service_id === r.service_id );
						return <ShippingRate
							id={ id + '_' + pckgId }
							key={ id + '_' + pckgId + '_' + service_id }
							rateObject={ serviceRateObject }
							rateObjectSignatureRequired={ rateObjectSignatureRequired }
							updateValue={ onRateUpdate }
							isSelected={ service_id === selectedRate.serviceId }
						/>
					} ) )
				) }
				{ packageErrors.slice( 1 ).map( ( error, index ) => {
					// Print the rest of the errors (if any) below the dropdown
					return <FieldError type="server-error" key={ index } text={ error } />;
				} ) }
				{ showShowMore ? ( 
					<div 
						className="rates-step__package-container-rates-show-more"
						onClick={ showMoreClick }
						onKeyDown={ showMoreClick }
						role="button"
						tabIndex="0">
						Show more rates.
					</div> 
				) : null }
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
