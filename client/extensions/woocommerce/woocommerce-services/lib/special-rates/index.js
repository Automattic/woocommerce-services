/** @format */

/**
 * External dependencies
 */
import { mapValues, find, pickBy } from 'lodash';
import { translate } from 'i18n-calypso';

// Mapping of signature service key to human-readable description.
const signatureLabels = {
	'signature_required': translate( 'Signature required' ),
	'adult_signature_required': translate( 'Adult signature required' ),
}

// Get the base cost (no special signature options, etc.) for a service.
const getBaseServiceCost = ( pckgId, serviceId, availableRates ) => {
	const defaultRate = find( availableRates[ pckgId ].default.rates, r => serviceId === r.service_id );
	return ( typeof defaultRate !== 'undefined' ) ? defaultRate.rate : 0;
}

// Business logic check.
const getValidSignatureRate = ( serviceId, signatureRateKey, optionRates, baseCost ) => {
	const serviceRate = find( optionRates, r => serviceId === r.service_id );
	// Skip if there was no rate for this service type.
	if ( typeof serviceRate === 'undefined' ) {
		return null;
	}

	// Only return rates associated with signature options.
	if ( ! ( signatureRateKey in signatureLabels ) ) {
		return null;
	}

	/**
	 * USPS returns signature rates that are not valid. These can be identified
	 * by the fact that the price with signature required is the same as the
	 * base cost. Priority Express service is the exception because signature
	 * can be required free-of-charge.
	 */
	if ( ( serviceRate.rate === baseCost ) && ( 'Express' !== serviceId ) ) {
		return null;
	}
	return serviceRate;
}

// Get valid signature-related rates for the given package and service.
export const getSignatureServiceRates = ( pckgId, serviceId, availableRates ) => {
	const baseCost = getBaseServiceCost( pckgId, serviceId, availableRates );

	return pickBy( mapValues( availableRates[ pckgId ], ( p, k ) => {
		const optionRate = getValidSignatureRate( serviceId, k, p.rates, baseCost )
		return ( optionRate !== null ) ? {
			rate: optionRate,
			'label': signatureLabels[ k ],
			optionNetCost: optionRate.rate - baseCost,
		} : null;
	} ), ( o ) => o !== null );
};
