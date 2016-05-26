import some from 'lodash/some';
import { translate as __ } from 'lib/mixins/i18n';

const addError = ( key, message ) => {
	return { [key]: message, any: true };
};

const isNullOrEmpty = ( value ) => ! value || '' === value;

const checkNameField = ( value, boxNames ) => {
	if ( isNullOrEmpty( value ) ) {
		return addError( 'name', __( 'field is required' ) );
	}

	if ( some( boxNames, ( boxName ) => boxName === value ) ) {
		return addError( 'name', __( 'a box with this name already exists' ) );
	}

	return {};
};

const checkOuterDimensions = ( value, regex ) => {
	if ( isNullOrEmpty( value ) ) {
		return {};
	}

	if ( ! regex.test( value ) ) {
		return addError( 'outer_dimensions', __( 'invalid dimension format' ) );
	}

	return {}
};

const checkInnerDimensions = ( value, regex ) => {
	if ( isNullOrEmpty( value ) ) {
		return addError( 'inner_dimensions', __( 'field is required' ) );
	}

	if ( ! regex.test( value ) ) {
		return addError( 'inner_dimensions', __( 'invalid dimension format' ) );
	}

	return {}
};

const numberRegex = /^\d+(\.\d+)?$/;
const checkWeightField = ( key, value ) => {
	if ( ! numberRegex.test( value ) ) {
		return addError( key, __( 'must be a number' ) );
	}

	return {}
};

const getErrors = ( packageData, boxNames, schema ) => {
	const regex = new RegExp( schema.properties.inner_dimensions.pattern );
	return Object.assign( {}
		, checkNameField( packageData.name, boxNames )
		, checkInnerDimensions( packageData.inner_dimensions, regex )
		, checkOuterDimensions( packageData.outer_dimensions, regex )
		, checkWeightField( 'box_weight', packageData.box_weight )
		, checkWeightField( 'max_weight', packageData.max_weight )
	);
};

module.exports = getErrors;
