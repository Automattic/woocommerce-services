import {
	updateFormElementField,
} from '../actions';

describe( 'Form state actions', () => {
	it( '#updateFormElementField()', () => {
		const element = 'testElement'
		const field = 'testField';
		const value = 'testValue';

		const action = updateFormElementField( element, field, value );

		expect( action ).to.eql( {
			type: 'UPDATE_FORM_ELEMENT_FIELD',
			element: 'testElement',
			field: 'testField',
			value: 'testValue',
		} );
	} );
} );
