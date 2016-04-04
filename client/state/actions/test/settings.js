import { expect } from 'chai';
import {
	onFieldChange,
	updateSettingsArrayField
} from '../settings';

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

	it( '#updateSettingsArrayField()', () => {
		const array_key = 'testArrayKey';
		const id = 'testID';
		const key = 'testKey';
		const value = 'testValue';

		const action = updateSettingsArrayField( array_key, id, key, value );

		expect( action ).to.eql( {
			type: 'UPDATE_SETTINGS_ARRAY_FIELD',
			array_key: 'testArrayKey',
			id: 'testID',
			key: 'testKey',
			value: 'testValue',
		} );
	} );
} );
