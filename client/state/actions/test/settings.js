import { expect } from 'chai';
import {
	onFieldChange,
	updateSettingsArrayField,
	addSettingsObjectField,
	removeSettingsObjectField,
	updateSettingsObjectSubField,
	removeSettingsObjectSubField,
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

	it( '#addSettingsObjectField()', () => {
		const settingsKey = 'testPckgs';
		const key = 'testKey';
		const obj = {
			1: 'testValue',
			second: 'testValue2',
		};

		const action = addSettingsObjectField( settingsKey, key, obj );

		expect( action ).to.eql( {
			type: 'ADD_SETTINGS_OBJECT_FIELD',
			settings_key: 'testPckgs',
			key: 'testKey',
			object: {
				1: 'testValue',
				second: 'testValue2',
			},
		} );
	} );

	it( '#removeSettingsObjectField()', () => {
		const settingsKey = 'testPckgs';
		const key = 'testKey';

		const action = removeSettingsObjectField( settingsKey, key );

		expect( action ).to.eql( {
			type: 'REMOVE_SETTINGS_OBJECT_FIELD',
			settings_key: 'testPckgs',
			key: 'testKey',
		} );
	} );

	it( '#removeSettingsObjectSubField()', () => {
		const settingsKey = 'testPckgs';
		const key = 'testKey';
		const subfieldKey = 'param1';

		const action = removeSettingsObjectSubField( settingsKey, key, subfieldKey );

		expect( action ).to.eql( {
			type: 'REMOVE_SETTINGS_OBJECT_SUB_FIELD',
			settings_key: 'testPckgs',
			key: 'testKey',
			subfield_key: 'param1',
		} );
	} );

	it( '#updateSettingsObjectSubField()', () => {
		const settingsKey = 'testPckgs';
		const key = 'testKey';
		const subfieldKey = 'param1';
		const val = 'val1';

		const action = updateSettingsObjectSubField( settingsKey, key, subfieldKey, val );

		expect( action ).to.eql( {
			type: 'UPDATE_SETTINGS_OBJECT_SUB_FIELD',
			settings_key: 'testPckgs',
			key: 'testKey',
			subfield_key: 'param1',
			value: 'val1',
		} );
	} );
} );
