import React, { PropTypes } from 'react';
import find from 'lodash/find';

/*
 * Renders the field values of the given step in a non-editable manner.
 *
 * The "summaryTemplate" parameter will be a string with this format:
 * "{fieldName1}, {fieldName2}\\n{fieldName4}"
 * The characters outside of the "{}" brackets will be printed literally. Line-breaks will be
 * replaced with paragraphs. "{fieldNameX}" will be replaced with the textual representation
 * of fieldNameX value.
 *
 * When parsing the template string and replacing each field placeholder for its value, the
 * value of the field will be obtained from "formValues". However, if the field is also present
 * in the "overrideFields" map, then that value will be used instead, and the field value
 * will be highlighted.
 *
 * Example:
 * summaryTemplate = 'Mr. {name} {surname}\\n{profession}'
 * formValues = {
 *     name: 'Indiana',
 *     surname: 'Jones',
 *     profession: 'Spaceship pilot',
 * }
 * overrideFields = {
 *     profession: 'Archeologist',
 * }
 *
 * Result:
 * Mr. Indiana Jones
 * Archeologist <--- highlighted
 */
const Summary = ( { overrideFields, formValues, layoutItems, summaryTemplate, countriesData } ) => {
	if ( ! overrideFields ) {
		overrideFields = {};
	}
	const fieldRawValues = {};
	layoutItems.map( ( item ) => item.key ).forEach( ( fieldName ) => {
		if ( undefined !== overrideFields[ fieldName ] && overrideFields[ fieldName ] !== formValues[ fieldName ] ) {
			fieldRawValues[ fieldName ] = {
				value: overrideFields[ fieldName ],
				override: true,
			};
		} else {
			fieldRawValues[ fieldName ] = {
				value: formValues[ fieldName ],
			};
		}
	} );

	// Some kind of fields (for example, radio buttons) have a value that's
	// not adequate to print as-is. This function will transform the raw value
	// of a field to its textual representation.
	const getPrintableValue = ( fieldName ) => {
		const fieldRawValue = fieldRawValues[ fieldName ].value;
		const layout = find( layoutItems, [ 'key', fieldName ] );
		switch ( layout.type ) {
			case 'radios':
			case 'dropdown':
				return layout.titleMap[ fieldRawValue ] || fieldRawValue;

			case 'country':
				return ( countriesData[ fieldRawValue ] || {} ).name || fieldRawValue;

			case 'state':
				const countryCode = fieldRawValues[ layout.country_field ].value;
				const statesMap = ( countriesData[ countryCode ] || {} ).states;
				if ( ! statesMap ) {
					return fieldRawValue;
				}
				if ( ! Object.keys( statesMap ).length || ! fieldRawValue ) {
					return null;
				}
				return statesMap[ fieldRawValue ] || fieldRawValue;

			default:
				return fieldRawValue;
		}
	};
	const fieldValues = {};
	Object.keys( fieldRawValues ).forEach( ( fieldName ) => (
		fieldValues[ fieldName ] = {
			value: getPrintableValue( fieldName ),
			override: fieldRawValues[ fieldName ].override,
		}
	) );

	const renderSummaryLine = ( line, idx ) => {
		const children = [];
		while ( true ) {
			const match = /{(.+?)}/.exec( line );
			if ( ! match ) {
				break;
			}
			if ( match.index ) {
				children.push( { value: line.substr( 0, match.index ) } );
			}
			children.push( fieldValues[ match[ 1 ] ] );
			line = line.substr( match.index + match[ 0 ].length );
		}
		if ( line.length ) {
			children.push( { value: line } );
		}
		return (
			<p key={ idx }>
				{ children.map( ( field, index ) => (
					<span
						key={ index }
						className={ field.override ? 'highlight' : '' } >
						{ field.value }
					</span>
				) ) }
			</p>
		);
	};

	return (
		<div className="summary">
			{ summaryTemplate.split( '\\n' ).map( renderSummaryLine ) }
		</div>
	)
};

Summary.propTypes = {
	overrideFields: PropTypes.object,
	formValues: PropTypes.object.isRequired,
	layoutItems: PropTypes.array.isRequired,
	summaryTemplate: PropTypes.string.isRequired,
	countriesData: PropTypes.object.isRequired,
};

export default Summary;
