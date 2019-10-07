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
import ShippingRarte from './shipping-rate';

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
	updateRate,
	errors,
	shouldShowRateNotice,
	translate,
} ) => {

	const renderSinglePackage = ( pckg, pckgId ) => {
		const selectedRate = selectedRates[ pckgId ] || '';
		const packageRates = get( availableRates, [ pckgId, 'rates' ], [] );
		const packageErrors = errors[ pckgId ] || [];

		const onRateUpdate = value => updateRate( pckgId, value );
		return (
			<div key={ pckgId } className="rates-step__package-container">
				{ Object.values( 
					mapValues( packageRates, ( ( rateObject ) => {
						return <ShippingRarte 
							id={ id + '_' + pckgId }
							rateObject={ rateObject }
							updateValue={ onRateUpdate }
							isSelected={ rateObject.service_id === selectedRate }
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
	errors: PropTypes.object.isRequired,
};

export default localize( ShippingRates );
