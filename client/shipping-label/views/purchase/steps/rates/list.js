import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';
import Notice from 'components/notice';
import getPackageDescriptions from '../packages/get-package-descriptions';
import { translate as __ } from 'lib/mixins/i18n';
import { sprintf } from 'sprintf-js';
import _ from 'lodash';

const renderRateNotice = ( show ) => {
	if ( show ) {
		return (
			<Notice
				className="wcc-label-rates__notice"
				icon="info-outline"
				showDismiss={ false }
				text={ __( 'The service and rate chosen by the customer at checkout is not available. Please choose another.' ) }
			/>
		);
	}
};

const ShippingRates = ( {
		id,
		selectedRates, // Store owner selected rates, not customer
		availableRates,
		selectedPackages,
		allPackages,
		updateRate,
		currencySymbol,
		errors,
		showRateNotice,
	} ) => {
	const packageNames = getPackageDescriptions( selectedPackages, allPackages, true );

	const renderTitle = ( pckg, pckgId ) => {
		if ( 1 === Object.keys( selectedPackages ).length ) {
			return __( 'Choose rate' );
		}
		return sprintf( __( 'Choose rate: %s' ), packageNames[ pckgId ] );
	};

	const renderSinglePackage = ( pckg, pckgId ) => {
		const selectedRate = selectedRates[ pckgId ] || '';
		const packageRates = _.get( availableRates, [ pckgId, 'rates' ], [] );
		const valuesMap = { '': __( 'Select one...' ) };

		packageRates.forEach( ( rateObject ) => {
			valuesMap[ rateObject.service_id ] = rateObject.title + ' (' + currencySymbol + rateObject.rate.toFixed( 2 ) + ')';
		} );

		return (
			<div key={ pckgId }>
				<Dropdown
					id={ id + '_' + pckgId }
					valuesMap={ valuesMap }
					title={ renderTitle( pckg, pckgId ) }
					value={ selectedRate }
					updateValue={ ( value ) => updateRate( pckgId, value ) }
					error={ errors[ pckgId ] } />
			</div>
		);
	};

	return (
		<div>
			{ renderRateNotice( showRateNotice ) }
			{ Object.values( _.mapValues( selectedPackages, renderSinglePackage ) ) }
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
	currencySymbol: PropTypes.string.isRequired,
	errors: PropTypes.object.isRequired,
};

export default ShippingRates;
