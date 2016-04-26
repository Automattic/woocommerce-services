import { expect } from 'chai';
import {
	updateSettingsField,
	addSettingsObjectField,
	removeSettingsObjectField,
	updateSettingsObjectSubField,
	removeSettingsObjectSubField,
	addSettingsArrayFieldItem,
	removeSettingsArrayFieldItem,
	updateSettingsArrayFieldItem,
	ADD_SETTINGS_ARRAY_FIELD_ITEM,
	REMOVE_SETTINGS_ARRAY_FIELD_ITEM,
	UPDATE_SETTINGS_ARRAY_FIELD_ITEM,
} from '../actions';

describe( 'Settings actions', () => {
	it( '#updateSettingsField()', () => {
		const key = 'testField';
		const value = 'testValue';

		const action = updateSettingsField( key, value );

		expect( action ).to.eql( {
			type: 'UPDATE_SETTINGS_FIELD',
			key: 'testField',
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

	it( '#addSettingsArrayFieldItem()', () => {
		const settingsKey = 'boxes';
		const item = {
			name: 'Test Envelope',
			type: 'letter',
			dimensions: '14 x 7 x .25',
		};

		const action = addSettingsArrayFieldItem( settingsKey, item );

		expect( action ).to.eql( {
			type: ADD_SETTINGS_ARRAY_FIELD_ITEM,
			settings_key: settingsKey,
			item,
		} );
	} );

	it( '#removeSettingsArrayFieldItem()', () => {
		const settingsKey = 'boxes';
		const index = 1;

		const action = removeSettingsArrayFieldItem( settingsKey, index );

		expect( action ).to.eql( {
			type: REMOVE_SETTINGS_ARRAY_FIELD_ITEM,
			settings_key: settingsKey,
			index,
		} );
	} );

	it( '#updateSettingsArrayFieldItem()', () => {
		const settingsKey = 'boxes';
		const index = 1;
		const item = {
			name: 'Test Envelope',
			type: 'letter',
			dimensions: '14 x 7 x .25',
		};

		const action = updateSettingsArrayFieldItem( settingsKey, index, item );

		expect( action ).to.eql( {
			type: UPDATE_SETTINGS_ARRAY_FIELD_ITEM,
			settings_key: settingsKey,
			index,
			item,
		} );
	} );
} );
