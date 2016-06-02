const leftPoint = /^\.\d+$/;
const rightPoint = /^\d+\.$/;
const number = ( value ) => {
	if ( leftPoint.test( value ) ) {
		value = '0' + value;
	} else if ( rightPoint.test( value ) ) {
		value = value.slice( 0, value.length - 1 );
	}

	return value;
};

const dimensionRegex = /^(\S+)\s*x\s*(\S+)\s*x\s*(\S+)$/;
const dimensions = ( value ) => {
	const result = dimensionRegex.exec( value );
	if ( result ) {
		return result.splice( 1, 4 ).map( number ).join( ' x ' );
	}

	return value;
};

export default {
	number,
	dimensions,
};
