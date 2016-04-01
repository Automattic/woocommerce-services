import { expect } from 'chai';
import { onFieldChange } from '../settings';

describe( 'Settings actions', () => {
	it( '#onFieldChange()', () => {
		const event = {
			target: {
				name: 'testField',
				value: 'testValue',
				type: 'text'
			},
		};

		const action = onFieldChange( event );

		expect( action ).to.eql( {
			type: 'UPDATE_SETTINGS_FIELD',
			key: 'testField',
			value: 'testValue',
		} );
	} );

	it( '#onFieldChange() checkbox', () => {
		const event = {
			target: {
				name: 'testCheckboxField',
				checked: true,
				type: 'checkbox'
			},
		};

		const action = onFieldChange( event );

		expect( action ).to.eql( {
			type: 'UPDATE_SETTINGS_FIELD',
			key: 'testCheckboxField',
			value: true,
		} );
	} );
} );
