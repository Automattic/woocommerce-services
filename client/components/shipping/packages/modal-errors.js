import some from 'lodash/some';

const addError = ( key, message ) => {
	return { [key]: message, any: true };
};

const isNullOrEmpty = ( value ) => ! value || '' === value;

const checkNameField = ( value, boxNames ) => {
	if ( isNullOrEmpty( value ) ) {
		return addError( 'name', 'field is required' );
	}

	if ( some( boxNames, ( boxName ) => boxName === value ) ) {
		return addError( 'name', 'a box with this name already exists' );
	}

	return {};
};

const checkOuterDimensions = ( value, regex ) => {
	if ( isNullOrEmpty( value ) ) {
		return {};
	}

	if ( ! regex.test( value ) ) {
		return addError( 'outer_dimensions', 'invalid dimension format' );
	}

	return {}
};

const checkInnerDimensions = ( value, regex ) => {
	if ( isNullOrEmpty( value ) ) {
		return addError( 'inner_dimensions', 'field is required' );
	}

	if ( ! regex.test( value ) ) {
		return addError( 'inner_dimensions', 'invalid dimension format' );
	}

	return {}
};

const numberRegex = /^\d+(\.\d+)?$/;
const checkWeightField = ( key, value ) => {
	if ( ! numberRegex.test( value ) ) {
		return addError( key, 'must be a number' );
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
