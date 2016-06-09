const numberRegex = /^\d+(\.\d+)?$/;
const parseNumber = ( value ) => {
	return numberRegex.test( value ) ? Number.parseFloat( value ) : value;
};

export default parseNumber;
