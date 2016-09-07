import React, { PropTypes } from 'react';
import Dropdown from 'components/dropdown';
import Notice from 'components/notice';
import { translate as __ } from 'lib/mixins/i18n';
import { sprintf } from 'sprintf-js';
import get from 'lodash/get';

const renderRateNotice = ( show ) => {
	if ( show ) {
		return (
			<Notice
				status="is-info"
				icon="info-outline"
				showDismiss={ false }
				text={ __( 'The service and rate chosen by the customer at checkout is not available. A similar one has been applied instead.' ) }
			/>
		);
	}
};

const ShippingRates = ( {
		id,
		selectedRates,
		availableRates,
		packages,
		updateRate,
		currencySymbol,
		errors,
		showRateNotice,
	} ) => {
	const renderTitle = ( pckg, idx ) => {
		if ( 1 === packages.length ) {
			return __( 'Choose rate' );
		}
		return sprintf( __( 'Choose rate: Package %(index)d' ), { index: idx + 1 } );
	};

	const renderSinglePackage = ( pckg, index ) => {
		const selectedRate = selectedRates[ pckg.id ] || '';
		const packageRates = get( availableRates, [ pckg.id, 'rates' ], [] );
		const valuesMap = { '': __( 'Select one...' ) };

		packageRates.forEach( ( rateObject ) => {
			valuesMap[ rateObject.service_id ] = rateObject.title + ' (' + currencySymbol + rateObject.rate.toFixed( 2 ) + ')';
		} );

		return (
			<div key={ index }>
				<Dropdown
					id={ id + '_' + index }
					valuesMap={ valuesMap }
					title={ renderTitle( pckg, index ) }
					value={ selectedRate }
					updateValue={ ( value ) => updateRate( pckg.id, value ) }
					error={ errors[ pckg.id ] } />
			</div>
		);
	};

	return (
		<div>
			{ renderRateNotice( showRateNotice ) }
			{ packages.map( renderSinglePackage ) }
		</div>
	);
};

ShippingRates.propTypes = {
	id: PropTypes.string.isRequired,
	selectedRates: PropTypes.object.isRequired,
	availableRates: PropTypes.object.isRequired,
	packages: PropTypes.array.isRequired,
	updateRate: PropTypes.func.isRequired,
	dimensionUnit: PropTypes.string.isRequired,
	weightUnit: PropTypes.string.isRequired,
	currencySymbol: PropTypes.string.isRequired,
	errors: PropTypes.object.isRequired,
};

export default ShippingRates;
