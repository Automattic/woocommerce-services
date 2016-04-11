import { expect } from 'chai';
import reducer from '../form';
import {
	updateFormElementField,
	setField,
} from '../../actions/form';

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
} );
