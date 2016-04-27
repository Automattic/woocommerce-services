import { expect } from 'chai';
import reducer from '../reducer';
import {
	addPackage,
	editPackage,
	dismissModal,
	updatePackagesField,
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
			packageData: {},
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

	it( 'UPDATE_PACKAGES_FIELD', () => {
		const packageData = {
			name: 'Test Box',
			is_letter: false,
		};
		const action = updatePackagesField( {
			name: 'Box Test',
			max_weight: '300',
		} );
		const state = reducer( { packageData }, action );

		expect( state ).to.eql( {
			packageData: {
				name: 'Box Test',
				max_weight: '300',
				is_letter: false,
			},
		} );
	} );
} );
