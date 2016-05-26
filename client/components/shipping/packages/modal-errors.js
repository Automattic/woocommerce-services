import { some, values} from 'lodash';

const checkName = ( name, boxNames ) => {
	if ( ! name || '' === name ) {
		return 'Name cannot be empty';
	}

	if ( some( boxNames, ( boxName ) => boxName === name ) ) {
		return 'This name is already in use in your package list';
	}

	return null;
};

const dimensionRegex = /^\d+ x \d+ x \d+$/
const checkOuterDimensions = ( dimensions ) => {
	if ( ! dimensions || '' === dimensions ) {
		return null;
	}

	if ( ! dimensionRegex.test( dimensions ) ) {
		return 'Invalid format for dimensions';
	};

	return null;
};

const checkInnerDimensions = ( dimensions ) => {
	if ( ! dimensions || '' === dimensions ) {
		return 'Cannot be blank';
	}

	return checkOuterDimensions( dimensions );
};

const isNumber = ( value ) => /^\d+$/.test( value );
const checkWeight = ( value ) => {
	if ( ! value || '' === value ) {
		return 'Cannot be blank';
	}

	if ( ! isNumber( value ) ) {
		return 'Must be a number';
	}

	return null;
};

const anyErrors = ( errors ) => {
	return some( values( errors ), ( value ) => null !== value );
};

const getErrors = ( packageData, boxNames ) => {
	const errors = {
		name: checkName( packageData.name, boxNames ),
		inner_dimensions: checkInnerDimensions( packageData.inner_dimensions ),
		outer_dimensions: checkOuterDimensions( packageData.outer_dimensions ),
		box_weight: checkWeight( packageData.box_weight ),
		max_weight: checkWeight( packageData.max_weight ),
	};

	return Object.assign( errors, { any: anyErrors( errors ) } );
};

module.exports = getErrors;
