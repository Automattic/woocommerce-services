/** @format */

/**
 * External dependencies
 */

import { trim } from 'lodash';

const string = value => {
	return trim( value );
};

const leftPoint = /^\.\d+$/;
const rightPoint = /^\d+\.$/;

const number = value => {
	value = trim( value );
	if ( leftPoint.test( value ) ) {
		value = '0' + value;
	} else if ( rightPoint.test( value ) ) {
		value = value.slice( 0, value.length - 1 );
	}

	return value;
};

const dimensionRegex = /^\s*(\S+)\s*x\s*(\S+)\s*x\s*(\S+)\s*$/;
const optionalDimensionRegex = /^\s*(\S+)?\s*x\s*(\S+)?\s*x\s*(\S+)?\s*$/;
const dimensions = value => {
	const result = dimensionRegex.exec( value );
	if ( result ) {
		const dims = [ result[ 1 ], result[ 2 ], result[ 3 ] ];
		return dims.map( number ).join( ' x ' );
	}

	return value;
};

const checkDimension = dimension => {
	if ( isNaN( dimension ) ) {
		return '';
	}

	// Allow users to type `0`, but not negative numbers
	if ( 0 > dimension ) {
		return '';
	}

	return dimension;
};

const parseDimensions = value => {
	let length = '';
	let width = '';
	let height = '';

	const match = optionalDimensionRegex.exec( value );
	if ( match && 4 === match.length ) {
		length = checkDimension( match[ 1 ] );
		width = checkDimension( match[ 2 ] );
		height = checkDimension( match[ 3 ] );
	}

	return { length, width, height };
};

// Final dimensions must all be positive numbers
const validateDimensions = value => {
	const { length, width, height } = parseDimensions( value );
	return ( 0 < length ) && ( 0 < width ) && ( 0 < height );
};

export default {
	string,
	number,
	dimensions,
	parseDimensions,
	validateDimensions,
};
