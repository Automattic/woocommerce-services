import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';
import Notice from 'components/notice';
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
		packages,
		updateRate,
		currencySymbol,
		errors,
		showRateNotice,
	} ) => {
	const renderTitle = ( pckg, index ) => {
		if ( 1 === Object.keys( packages ).length ) {
			return __( 'Choose rate' );
		}
		return sprintf( __( 'Choose rate: Package %(index)d' ), { index } );
	};

	let packageNum = 1;

	const renderSinglePackage = ( pckg, pckgId ) => {
		const selectedRate = selectedRates[ pckgId ] || '';
		const packageRates = _.get( availableRates, [ pckgId, 'rates' ], [] );
		const valuesMap = { '': __( 'Select one...' ) };

		packageRates.forEach( ( rateObject ) => {
			valuesMap[ rateObject.service_id ] = rateObject.title + ' (' + currencySymbol + rateObject.rate.toFixed( 2 ) + ')';
		} );

		return (
			<div key={ packageNum }>
				<Dropdown
					id={ id + '_' + packageNum }
					valuesMap={ valuesMap }
					title={ renderTitle( pckg, packageNum++ ) }
					value={ selectedRate }
					updateValue={ ( value ) => updateRate( pckgId, value ) }
					error={ errors[ pckgId ] } />
			</div>
		);
	};

	return (
		<div>
			{ renderRateNotice( showRateNotice ) }
			{ Object.values( _.mapValues( packages, renderSinglePackage ) ) }
		</div>
	);
};

ShippingRates.propTypes = {
	id: PropTypes.string.isRequired,
	selectedRates: PropTypes.object.isRequired,
	availableRates: PropTypes.object.isRequired,
	packages: PropTypes.object.isRequired,
	updateRate: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	currencySymbol: PropTypes.string.isRequired,
	errors: PropTypes.object.isRequired,
};

export default ShippingRates;
