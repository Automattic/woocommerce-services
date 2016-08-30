import { createSelector } from 'reselect';
import { translate as __ } from 'lib/mixins/i18n';
import isEmpty from 'lodash/isEmpty';

const getAddressErrors = ( { values, isNormalized, normalized, selectNormalized }, countriesData ) => {
	if ( isNormalized && ! normalized ) {
		// If the address is normalized but the server didn't return a normalized address, then it's
		// invalid and must register as an error, but no concrete field is erroneous, so return a truthy value
		return true;
	}
	const { postcode, state, country } = ( isNormalized && selectNormalized ) ? normalized : values;
	const requiredFields = [ 'name', 'address', 'city', 'postcode', 'country' ];
	const errors = {};
	requiredFields.forEach( ( field ) => {
		if ( ! values[ field ] ) {
			errors[ field ] = __( 'This field is required' );
		}
	} );

	switch ( country ) {
		case 'US':
			if ( ! /^\d{5}(?:-\d{4})?$/.test( postcode ) ) {
				errors.postcode = __( 'Invalid ZIP code format' );
			}
			break;
	}

	if ( ! isEmpty( countriesData[ country ] ) && ! state ) {
		errors.state = __( 'This field is required' );
	}

	return errors;
};

const getPackagesErrors = ( values ) => values.map( ( pckg ) => {
	const errors = {};
	if ( ! pckg.weight || 'number' !== typeof pckg.weight || 0 > pckg.weight ) {
		errors.weight = __( 'Invalid weight' );
	}
	return errors;
} );

const getRatesErrors = ( values ) => values.map( ( rate ) => rate ? null : __( 'Please choose a rate' ) );

export default createSelector(
	( state ) => state.shippingLabel.form,
	( state, { countriesData } ) => countriesData,
	( form, countriesData ) => {
		return {
			origin: getAddressErrors( form.origin, countriesData ),
			destination: getAddressErrors( form.destination, countriesData ),
			packages: getPackagesErrors( form.packages.values ),
			rates: getRatesErrors( form.rates.values ),
			preview: {},
		};
	}
);
