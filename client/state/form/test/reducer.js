import reducer from '../reducer';
import { setFormProperty, SET_FORM_PROPERTY } from '../actions';
import { updateField } from '../values/actions';

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

	it( 'SET_FORM_PROPERTY', () => {
		const key = 'textObj';
		const val = { id: 'newID', newfield: 'some new value' };
		const action = setFormProperty( key, val );
		const plainAction = {
			type: SET_FORM_PROPERTY,
			field: key,
			value: val,
		};
		let calledAction = null;

		expect( action ).to.be.a( 'function' );
		const dispatch = ( _calledAction ) => calledAction = _calledAction;
		action( dispatch );
		expect( calledAction ).to.eql( plainAction );

		const state = reducer( initialState, plainAction );

		expect( state ).to.eql( {
			textObj: {
				id: 'newID',
				newfield: 'some new value',
			},
		} );
	} );

	it( 'Clears fields status on settings state change', () => {
		const initialErrorState = Object.assign( { fieldsStatus: [ 'data.title' ] }, initialState );
		const action = updateField( 'some_key', 'some value' );
		const state = reducer( initialErrorState, action );

		expect( state ).to.not.have.property( 'fieldsStatus' );
	} );
} );
