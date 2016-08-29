import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import AddressFields from './fields';
import { hasNonEmptyLeaves } from 'lib/utils/tree';
import Card from 'shipping-label/views/card';
import isEqual from 'lodash/isEqual';

const renderSummary = ( { values, isValidated, normalized, pickNormalized, storeOptions, errors } ) => {
	if ( hasNonEmptyLeaves( errors ) || ( isValidated && ! normalized ) ) {
		return __( 'Invalid address' );
	}
	const { countriesData } = storeOptions;
	const { city, postcode, state, country } = ( normalized && pickNormalized ) ? normalized : values;
	// Summary format: "city, postcode state, country"
	let str = city + ', ';
	str += ( 'US' === country ? postcode.split( '-' )[ 0 ] : postcode ) + ' ';
	if ( state ) {
		const statesMap = ( countriesData[ country ] || {} ).states || {};
		str += ( statesMap[ state ] || state ) + ', ';
	}
	str += countriesData[ country ].name;
	return str;
};

const getValidationStatus = ( { validationInProgress, errors, isValidated, values, normalized } ) => {
	if ( validationInProgress ) {
		return { isProgress: true };
	}
	if ( hasNonEmptyLeaves( errors ) || ( isValidated && ! normalized ) ) {
		return { isError: true };
	}
	if ( isValidated ) {
		return isEqual( values, normalized ) ? { isSuccess: true } : { isWarning: true };
	}
	return {};
};

const Origin = ( props ) => {
	return (
		<Card
			title={ __( 'Origin address' ) }
			summary={ renderSummary( props ) }
			{ ...getValidationStatus( props ) } >
			<AddressFields
				{ ...props }
				group="origin" />
		</Card>
	);
};

const Destination = ( props ) => {
	return (
		<Card
			title={ __( 'Destination address' ) }
			summary={ renderSummary( props ) }
			{ ...getValidationStatus( props ) } >
			<AddressFields
				{ ...props }
				group="destination" />
		</Card>
	);
};

Origin.propTypes = Destination.propTypes = {
	values: PropTypes.object.isRequired,
	isValidated: PropTypes.bool.isRequired,
	normalized: PropTypes.object,
	pickNormalized: PropTypes.bool.isRequired,
	validationInProgress: PropTypes.bool.isRequired,
	allowChangeCountry: PropTypes.bool.isRequired,
	labelActions: PropTypes.object.isRequired,
	storeOptions: PropTypes.object.isRequired,
	errors: PropTypes.oneOfType( [
		PropTypes.object,
		PropTypes.bool,
	] ).isRequired,
};

export default {
	Origin,
	Destination,
};
