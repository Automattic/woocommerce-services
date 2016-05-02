import { expect } from 'chai';
import reducer from '../reducer';
import {
	addPackage,
	editPackage,
	dismissModal,
	updatePackagesField,
	toggleOuterDimensions,
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

	it( 'ADD_PACKAGE preserves form data', () => {
		const existingAddState = {
			showModal: false,
			mode: 'add',
			packageData: {
				name: 'Package name here',
			},
		};
		const action = addPackage();
		const state = reducer( existingAddState, action );

		expect( state ).to.eql( {
			showModal: true,
			mode: 'add',
			packageData: existingAddState.packageData,
		} );
	} );

	it( "ADD_PACKAGE clears previous 'edit' data", () => {
		const existingEditState = {
			showModal: false,
			mode: 'edit',
			packageData: {
				index: 1,
				name: 'Package name here',
			},
		};
		const action = addPackage();
		const state = reducer( existingEditState, action );

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
		const initialStateVisibleOuterDimensions = Object.assign( {}, initialState, {
			showOuterDimensions: true,
		} );
		const action = editPackage( packageData );
		const state = reducer( initialStateVisibleOuterDimensions, action );

		expect( state ).to.eql( {
			showModal: true,
			mode: 'edit',
			packageData,
			showOuterDimensions: false,
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
			index: 1,
		};
		const action = updatePackagesField( {
			name: 'Box Test',
			max_weight: '300',
			index: null,
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

	it( 'TOGGLE_OUTER_DIMENSIONS', () => {
		const visibleModalState = {
			showModal: true,
		}
		const action = toggleOuterDimensions();
		const state = reducer( visibleModalState, action );

		expect( state ).to.eql( {
			showModal: true,
			showOuterDimensions: true,
		} );
	} );
} );
