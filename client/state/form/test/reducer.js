import { expect } from 'chai';
import reducer from '../reducer';
import {
	updateFormElementField,
	setField,
} from '../actions';
import { updateSettingsField } from '../../settings/actions';

const initialState = {
	textObj: {
		field: {
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
			textObj: {
				field: {
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

	it( 'UPDATE_FORM_ELEMENT_FIELD', () => {
		const key = 'textObj';
		const field = 'field';
		const val = { id: 'newID', newfield: 'some new value' };
		const action = updateFormElementField( key, field, val );
		const state = reducer( initialState, action );

		expect( state ).to.eql( {
			textObj: {
				field: {
					id: 'newID',
					newfield: 'some new value',
				},
			},
		} );
	} );

	it( 'SET_FIELD', () => {
		const key = 'textObj';
		const val = { id: 'newID', newfield: 'some new value' };
		const action = setField( key, val );
		const state = reducer( initialState, action );

		expect( state ).to.eql( {
			textObj: {
				id: 'newID',
				newfield: 'some new value',
			},
		} );
	} );

	it( 'Clears errors on settings state change', () => {
		const initialErrorState = Object.assign( { errors: ['data.title'] }, initialState );
		const action = updateSettingsField( 'some_key', 'some value' );
		const state = reducer( initialErrorState, action );

		expect( state ).to.eql( initialState );
	} );
} );
