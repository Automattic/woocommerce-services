import { expect } from 'chai';
import reducer from '../reducer';
import {
	addPackage,
	editPackage,
	dismissModal,
} from '../actions';

const initialState = {
	showModal: false,
	packageData: null,
};

describe( 'Packages form reducer', () => {
	afterEach( () => {
		// make sure the state hasn't been mutated
		// after each test
		expect( initialState ).to.eql( {
			showModal: false,
			packageData: null,
		} );
	} );

	it( 'ADD_PACKAGE', () => {
		const action = addPackage();
		const state = reducer( initialState, action );

		expect( state ).to.eql( {
			showModal: true,
			mode: 'add',
			packageData: null,
		} );
	} );

	it( 'EDIT_PACKAGE', () => {
		const packageData = {
			index: 1,
			name: 'Test Box',
		};
		const action = editPackage( packageData );
		const state = reducer( initialState, action );

		expect( state ).to.eql( {
			showModal: true,
			mode: 'edit',
			packageData,
		} );
	} );

	it( 'DISMISS_MODAL', () => {
		const visibleModalState = {
			showModal: true,
		}
		const action = dismissModal();
		const state = reducer( visibleModalState, action );

		expect( state ).to.eql( {
			showModal: false,
		} );
	} );
} );
