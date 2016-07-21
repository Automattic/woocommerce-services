import React, { PropTypes } from 'react';
import find from 'lodash/find';

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
						style={ field.override ? { backgroundColor: 'cyan' } : {} } >
						{ field.value }
					</span>
				) ) }
			</p>
		);
	};

	return (
		<div>
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
