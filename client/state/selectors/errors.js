import { createSelector } from 'reselect';
import isObject from 'lodash/isObject';
import validator from 'is-my-json-valid';
import ObjectPath from 'objectpath';
import coerceFormValues from 'lib/utils/coerce-values';

export const EMPTY_ERROR = {};
Object.freeze( EMPTY_ERROR );

/*
 * Errors from `is-my-json-valid` are paths to fields, all using `data` as the root.
 *
 * e.g.: `data.services.first_class_parcel.adjustment`
 *
 * This removes the `data.` prepending all errors, and transforms the array of errors into a tree-like structure.
 * Example:
 * Before parsing:
 * {
 * 	'data.services.first_class_parcel.adjustment': {},
 * 	'data.title': {},
 * 	'data.postalcode': {},
 * }
 *
 * After parsing:
 * {
 * 	'services': {
 * 		'first_class_parcel': {
 * 			'adjustment': {
 * 				'': {} // The actual error, it could have meta-data inside like the level of severity
 *	 		}
 * 		}
 * 	},
 * 	'title': {
 * 		'': {} // The actual error
 * 	},
 * 	'postalcode': {
 * 		'': {} // The actual error
 * 	}
 * }
 */
const parseErrorsList = ( errantFields ) => {
	if ( ! isObject( errantFields ) ) {
		return {};
	}

	const parsedErrors = {};
	Object.keys( errantFields ).forEach( ( fieldName ) => {
		const errorPath = ObjectPath.parse( fieldName );
		let newName = errorPath;
		if ( 'data' === errorPath[ 0 ] ) {
			newName = errorPath.slice( 1 );
		}
		let currentNode = parsedErrors;
		newName.forEach( ( pathChunk ) => {
			if ( ! currentNode[ pathChunk ] ) {
				currentNode[ pathChunk ] = {};
			}
			currentNode = currentNode[ pathChunk ];
		} );
		currentNode[ '' ] = errantFields[ fieldName ];
	} );
	return parsedErrors;
};

const getRawFormErrors = ( schema, data ) => {
	const validate = validator( schema, { greedy: true } );
	const coerced = coerceFormValues( schema, data );
	const success = validate( coerced );

	if ( ! success && validate.errors && validate.errors.length ) {
		const rawErrors = {};
		validate.errors.forEach( ( error ) => rawErrors[ error.field ] = EMPTY_ERROR );
		return rawErrors;
	}

	return {};
};

export const getFormErrors = createSelector(
	( state ) => state.form.errors,
	( state, schema ) => schema,
	( state ) => state.form.values,
	( errors, schema, data ) => parseErrorsList( errors || getRawFormErrors( schema, data ) )
);

export const getStepFormErrors = createSelector(
	( state ) => state,
	( state, schema ) => schema,
	( state, schema, layout ) => layout,
	( state, schema, layout ) => {
		const allErrors = getFormErrors( state, schema );
		const stepLayout = layout[ state.form.currentStep ];
		const stepFields = {};
		if ( stepLayout ) {
			stepLayout.items.forEach( item => stepFields[ item.key ] = true );
		}
		const stepErrors = {};
		Object.keys( allErrors ).forEach( fieldName => {
			if ( stepFields[ fieldName ] ) {
				stepErrors[ fieldName ] = allErrors[ fieldName ];
			}
		} );
		return stepErrors;
	}
);

export const getStepFormSuggestions = createSelector(
	( state ) => state,
	( state, schema ) => schema,
	( state, schema, layout ) => layout,
	( state, schema, layout ) => {
		const stepErrors = getStepFormErrors( state, schema, layout );
		const suggestions = {};
		Object.keys( stepErrors ).forEach( ( fieldName ) => {
			const fieldError = stepErrors[ fieldName ][ '' ];
			if ( fieldError && 'suggestion' === fieldError.level ) {
				suggestions[ fieldName ] = fieldError.value;
			}
		} );
		return suggestions;
	}
);
