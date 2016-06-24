import {
	updateSettingsField,
	removeSettingsField,
	addSettingsArrayFieldItem,
	UPDATE_SETTINGS_FIELD,
	REMOVE_SETTINGS_FIELD,
	ADD_SETTINGS_ARRAY_FIELD_ITEM,
} from '../actions';

describe( 'Settings actions', () => {
	it( '#updateSettingsField()', () => {
		const path = 'testField';
		const value = 'testValue';

		const action = updateSettingsField( path, value );

		expect( action ).to.eql( {
			type: UPDATE_SETTINGS_FIELD,
			path: 'testField',
			value: 'testValue',
		} );
	} );

	it( '#removeeSettingsField()', () => {
		const path = 'testField';
		const action = removeSettingsField( path );

		expect( action ).to.eql( {
			type: REMOVE_SETTINGS_FIELD,
			path: 'testField',
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
} );
