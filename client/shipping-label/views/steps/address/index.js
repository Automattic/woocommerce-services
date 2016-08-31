import React, { PropTypes } from 'react';
import { translate as __ } from 'lib/mixins/i18n';
import AddressFields from './fields';
import { hasNonEmptyLeaves } from 'lib/utils/tree';
import Card from 'shipping-label/views/card';
import isEqual from 'lodash/isEqual';

const renderSummary = ( { values, isNormalized, normalizationInProgress, normalized, selectNormalized, storeOptions, errors }, showCountry ) => {
	if ( normalizationInProgress ) {
		return __( 'Validating address...' );
	}
	if ( hasNonEmptyLeaves( errors ) || ( isNormalized && ! normalized ) ) {
		return __( 'Invalid address' );
	}
	const { countriesData } = storeOptions;
	const { city, postcode, state, country } = ( normalized && selectNormalized ) ? normalized : values;
	// Summary format: "city, state postcode [, country]"
	let str = city + ', ';
	if ( state ) {
		const statesMap = ( countriesData[ country ] || {} ).states || {};
		str += ( statesMap[ state ] || state ) + ' ';
	}
	str += ( 'US' === country ? postcode.split( '-' )[ 0 ] : postcode );
	if ( showCountry ) {
		str += ', ' + countriesData[ country ].name;
	}
	return str;
};

const getNormalizationStatus = ( { normalizationInProgress, errors, isNormalized, values, normalized } ) => {
	if ( normalizationInProgress ) {
		return { isProgress: true };
	}
	if ( hasNonEmptyLeaves( errors ) || ( isNormalized && ! normalized ) ) {
		return { isError: true };
	}
	if ( isNormalized ) {
		return isEqual( values, normalized ) ? { isSuccess: true } : { isWarning: true };
	}
	return {};
};

const Origin = ( props ) => {
	const showCountryInSummary = false;
	return (
		<Card
			title={ __( 'Origin address' ) }
			summary={ renderSummary( props, showCountryInSummary ) }
			{ ...getNormalizationStatus( props ) } >
			<AddressFields
				{ ...props }
				group="origin" />
		</Card>
	);
};

const Destination = ( props ) => {
	const showCountryInSummary = props.form.origin.values.country !== props.values.country;
	return (
		<Card
			title={ __( 'Destination address' ) }
			summary={ renderSummary( props, showCountryInSummary ) }
			{ ...getNormalizationStatus( props ) } >
			<AddressFields
				{ ...props }
				group="destination" />
		</Card>
	);
};

Origin.propTypes = Destination.propTypes = {
	values: PropTypes.object.isRequired,
	isNormalized: PropTypes.bool.isRequired,
	normalized: PropTypes.object,
	selectNormalized: PropTypes.bool.isRequired,
	normalizationInProgress: PropTypes.bool.isRequired,
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
