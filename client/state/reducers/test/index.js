import { expect } from 'chai';
import settings from '../settings';
import { updateSettingsArrayField } from '../../actions/settings';

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
	]
};

describe( 'Settings reducer', () => {
	it( 'settings array field reducer should update correct array item', () => {
		const array_key = 'testArrayKey';
		const id = 'ALPHA';
		const key = 'testItemField';
		const value = 'ACK';
		const action = updateSettingsArrayField( array_key, id, key, value );
		const state = settings( initialState, action );

		expect( state ).to.eql( {
			testField: 'testValue',
			testArrayKey: [
				{
					id: 'ALPHA',
					testItemField: 'ACK',
				},
				{
					id: 'BETA',
					testItemField: 'BEE',
				},
			]
		} );
	} );
} );
