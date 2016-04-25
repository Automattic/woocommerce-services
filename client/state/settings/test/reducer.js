import { expect } from 'chai';
import settings from '../reducer';
import {
	updateSettingsField,
	addSettingsObjectField,
	removeSettingsObjectField,
	updateSettingsObjectSubField,
	removeSettingsObjectSubField,
	addSettingsArrayFieldItem,
	removeSettingsArrayFieldItem,
} from '../actions';

const initialState = {
	testField: 'testValue',
	testArrayKey: [
		{
			id: 'ALPHA',
			testItemField: 'AYE',
		},
		{
			id: 'BETA',
			testItemField: 'BEE',
		},
	],
	testPckgs: {
		PCKG_A: {
			id: 'PCKG_A',
			dimensions: {
				width: 10,
				length: 11,
				height: 23,
			},
			value: 1122,
		},
	},
};

describe( 'Settings reducer', () => {
	afterEach( () => {
		// make sure the state hasn't been mutated
		// after each test
		expect( initialState ).to.eql( {
			testField: 'testValue',
			testArrayKey: [
				{
					id: 'ALPHA',
					testItemField: 'AYE',
				},
				{
					id: 'BETA',
					testItemField: 'BEE',
				},
			],
			testPckgs: {
				PCKG_A: {
					id: 'PCKG_A',
					dimensions: {
						width: 10,
						length: 11,
						height: 23,
					},
					value: 1122,
				},
			},
		} );
	} );

	it( 'UPDATE_SETTINGS_FIELD', () => {
		const key = 'testField';
		const val = 'testValue2';
		const action = updateSettingsField( key, val );
		const state = settings( initialState, action );

		expect( state ).to.eql( {
			testField: 'testValue2',
			testArrayKey: [
				{
					id: 'ALPHA',
					testItemField: 'AYE',
				},
				{
					id: 'BETA',
					testItemField: 'BEE',
				},
			],
			testPckgs: {
				PCKG_A: {
					id: 'PCKG_A',
					dimensions: {
						width: 10,
						length: 11,
						height: 23,
					},
					value: 1122,
				},
			},
		} );
	} );

	it( 'UPDATE_SETTINGS_FIELD (add field)', () => {
		const key = 'testField2';
		const val = 'FOO_BAR';
		const action = updateSettingsField( key, val );
		const state = settings( initialState, action );

		expect( state ).to.eql( {
			testField: 'testValue',
			testField2: 'FOO_BAR',
			testArrayKey: [
				{
					id: 'ALPHA',
					testItemField: 'AYE',
				},
				{
					id: 'BETA',
					testItemField: 'BEE',
				},
			],
			testPckgs: {
				PCKG_A: {
					id: 'PCKG_A',
					dimensions: {
						width: 10,
						length: 11,
						height: 23,
					},
					value: 1122,
				},
			},
		} );
	} );

	it( 'ADD_SETTINGS_OBJECT_FIELD', () => {
		const settingsKey = 'testPckgs';
		const key = 'PCKG_B';
		const obj = {
			id: key,
			dimensions: {
				width: 12,
				length: 13,
				height: 11,
			},
			value: 1234,
		};
		const action = addSettingsObjectField( settingsKey, key, obj );
		const state = settings( initialState, action );

		expect( state ).to.eql( {
			testField: 'testValue',
			testArrayKey: [
				{
					id: 'ALPHA',
					testItemField: 'AYE',
				},
				{
					id: 'BETA',
					testItemField: 'BEE',
				},
			],
			testPckgs: {
				PCKG_A: {
					id: 'PCKG_A',
					dimensions: {
						width: 10,
						length: 11,
						height: 23,
					},
					value: 1122,
				},
				PCKG_B: {
					id: 'PCKG_B',
					dimensions: {
						width: 12,
						length: 13,
						height: 11,
					},
					value: 1234,
				},
			},
		} );
	} );

	it( 'REMOVE_SETTINGS_OBJECT_FIELD', () => {
		const settingsKey = 'testPckgs';
		const key = 'PCKG_A';

		const action = removeSettingsObjectField( settingsKey, key );
		const state = settings( initialState, action );

		expect( state ).to.eql( {
			testField: 'testValue',
			testArrayKey: [
				{
					id: 'ALPHA',
					testItemField: 'AYE',
				},
				{
					id: 'BETA',
					testItemField: 'BEE',
				},
			],
			testPckgs: {},
		} );
	} );

	it( 'UPDATE_SETTINGS_OBJECT_SUB_FIELD', () => {
		const settingsKey = 'testPckgs';
		const key = 'PCKG_A';
		const subFieldKey = 'value';
		const val = 12345;

		const action = updateSettingsObjectSubField( settingsKey, key, subFieldKey, val );
		const state = settings( initialState, action );

		expect( state ).to.eql( {
			testField: 'testValue',
			testArrayKey: [
				{
					id: 'ALPHA',
					testItemField: 'AYE',
				},
				{
					id: 'BETA',
					testItemField: 'BEE',
				},
			],
			testPckgs: {
				PCKG_A: {
					id: 'PCKG_A',
					dimensions: {
						width: 10,
						length: 11,
						height: 23,
					},
					value: 12345,
				},
			},
		} );
	} );

	it( 'REMOVE_SETTINGS_OBJECT_SUB_FIELD', () => {
		const settingsKey = 'testPckgs';
		const key = 'PCKG_A';
		const subFieldKey = 'dimensions';

		const action = removeSettingsObjectSubField( settingsKey, key, subFieldKey );
		const state = settings( initialState, action );

		expect( state ).to.eql( {
			testField: 'testValue',
			testArrayKey: [
				{
					id: 'ALPHA',
					testItemField: 'AYE',
				},
				{
					id: 'BETA',
					testItemField: 'BEE',
				},
			],
			testPckgs: {
				PCKG_A: {
					id: 'PCKG_A',
					value: 1122,
				},
			},
		} );
	} );

	it( 'ADD_SETTINGS_ARRAY_FIELD_ITEM', () => {
		const settingsKey = 'testArrayKey';
		const item = {
			id: 'OMEGA',
			testItemField: 'OHH',
		};

		const action = addSettingsArrayFieldItem( settingsKey, item );
		const state = settings( initialState, action );

		expect( state ).to.eql( {
			testField: 'testValue',
			testArrayKey: [
				{
					id: 'ALPHA',
					testItemField: 'AYE',
				},
				{
					id: 'BETA',
					testItemField: 'BEE',
				},
				{
					id: 'OMEGA',
					testItemField: 'OHH',
				},
			],
			testPckgs: {
				PCKG_A: {
					id: 'PCKG_A',
					dimensions: {
						width: 10,
						length: 11,
						height: 23,
					},
					value: 1122,
				},
			},
		} );
	} );

	it( 'REMOVE_SETTINGS_ARRAY_FIELD_ITEM', () => {
		const settingsKey = 'testArrayKey';
		const index = 1;

		const action = removeSettingsArrayFieldItem( settingsKey, index );
		const state = settings( initialState, action );

		expect( state ).to.eql( {
			testField: 'testValue',
			testArrayKey: [
				{
					id: 'ALPHA',
					testItemField: 'AYE',
				},
			],
			testPckgs: {
				PCKG_A: {
					id: 'PCKG_A',
					dimensions: {
						width: 10,
						length: 11,
						height: 23,
					},
					value: 1122,
				},
			},
		} );
	} );
} );
