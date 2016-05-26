import { some, values} from 'lodash';

const getNameError = ( name, boxNames ) => {
	if ( ! name || '' === name ) {
		return 'Name cannot be empty';
	}

	if ( some( boxNames, ( boxName ) => boxName === name ) ) {
		return 'This name is already in use in your package list';
	}

	return null;
};

const anyErrors = ( errors ) => {
	return some( values( errors ), ( value ) => null !== value );
};

const getErrors = ( packageData, boxNames ) => {
	const errors = {
		name: getNameError( packageData.name, boxNames ),
	};

	return Object.assign( errors, { any: anyErrors( errors ) } );
};

module.exports = getErrors;
