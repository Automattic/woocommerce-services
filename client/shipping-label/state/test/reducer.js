/**
 * External dependencies
 */
import { expect } from 'chai';
import hoek from 'hoek';

/**
 * Internal dependencies
 */
import reducer from '../reducer';
import {
	moveItem,
	addPackage,
	removePackage,
	setPackageType,
	savePackages,
	removeIgnoreValidation,
	updateAddressValue,
    clearAvailableRates,
} from '../actions';

const initialState = {
	form: {
		origin: {
			values: { address: 'Some street', postcode: '', state: 'CA', country: 'US' },
			isNormalized: false,
			normalized: null,
			ignoreValidation: { address: true, postcode: true, state: true, country: true },
		},
		packages: {
			all: {
				customPackage1: {
					inner_dimensions: '1 x 2 x 3',
					box_weight: 3.5,
				},
				customPackage2: {},
			},
			selected: {
				weight_0_custom1: {
					items: [
						{
							product_id: 123,
							weight: 1.2,
						},
					],
				},
				weight_1_custom1: {
					items: [
						{
							product_id: 456,
							weight: 2.3,
						},
					],
				},
			},
			isPacked: true,
		},
	},
	openedPackageId: 'weight_0_custom1',
};

describe( 'Label purchase form reducer', () => {
	let stateBefore;

	beforeEach( () => {
		stateBefore = hoek.clone( initialState );
	} );

	afterEach( () => {
		// make sure the state hasn't been mutated
		// after each test
		expect( initialState ).to.eql( stateBefore );
	} );

	it( 'MOVE_ITEM moves items between selected packages', () => {
		const action = moveItem( 'weight_0_custom1', 0, 'weight_1_custom1' );
		const state = reducer( initialState, action );

		expect( state.form.packages.selected.weight_0_custom1 ).to.eql( undefined );
		expect( state.form.packages.selected.weight_1_custom1.items.length ).to.eql( 2 );
		expect( state.form.packages.selected.weight_1_custom1.items )
			.to.include( initialState.form.packages.selected.weight_0_custom1.items[ 0 ] );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
		expect( state.form.needsPrintConfirmation ).to.eql( false );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'MOVE_ITEM moves items from selected packages to original packaging', () => {
		const action = moveItem( 'weight_0_custom1', 0, 'individual' );
		const state = reducer( initialState, action );

		expect( state.form.packages.selected.weight_0_custom1 ).to.eql( undefined );
		expect( state.form.packages.selected ).to.include.keys( 'client_individual_0' );
		expect( state.form.packages.selected.client_individual_0.box_id ).to.eql( 'individual' );
		expect( state.form.packages.selected.client_individual_0.items.length ).to.eql( 1 );
		expect( state.form.packages.selected.client_individual_0.items )
			.to.include( initialState.form.packages.selected.weight_0_custom1.items[ 0 ] );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
		expect( state.form.needsPrintConfirmation ).to.eql( false );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'MOVE_ITEM moves items from original packaging to selected packages and deletes original package', () => {
		const existingState = hoek.clone( initialState );
		existingState.form.packages.selected.client_individual_0 = {
			items: [ {
				product_id: 789,
			} ],
			box_id: 'individual',
		};
		existingState.openedPackageId = 'client_individual_0';

		const action = moveItem( 'client_individual_0', 0, 'weight_0_custom1' );
		const state = reducer( existingState, action );

		expect( state.form.packages.selected.weight_0_custom1.items.length ).to.eql( 2 );
		expect( state.form.packages.selected.weight_0_custom1.items )
			.to.include( existingState.form.packages.selected.client_individual_0.items[ 0 ] );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.needsPrintConfirmation ).to.eql( false );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'ADD_PACKAGE adds a new package', () => {
		const action = addPackage();
		const state = reducer( initialState, action );

		expect( state.form.packages.selected ).to.include.keys( 'client_custom_0' );
		expect( state.form.packages.selected.client_custom_0.items.length ).to.eql( 0 );
		expect( state.form.packages.selected.client_custom_0.box_id ).to.eql( 'not_selected' );
		expect( state.form.packages.selected.client_custom_0.length ).to.eql( 0 );
		expect( state.form.packages.selected.client_custom_0.width ).to.eql( 0 );
		expect( state.form.packages.selected.client_custom_0.height ).to.eql( 0 );
		expect( state.form.packages.selected.client_custom_0.weight ).to.eql( 0 );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.openedPackageId ).to.eql( 'client_custom_0' );
		expect( state.form.needsPrintConfirmation ).to.eql( false );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'REMOVE_PACKAGE removes the package and moves all the items to first package', () => {
		const action = removePackage( 'weight_0_custom1' );
		const state = reducer( initialState, action );

		expect( state.form.packages.selected ).to.not.include.keys( 'weight_0_custom1' );
		expect( state.form.packages.selected ).to.include.keys( 'weight_1_custom1' );
		expect( state.form.packages.selected.weight_1_custom1 )
			.to.include.all.keys( Object.keys( initialState.form.packages.selected.weight_0_custom1 ) );
		expect( state.form.packages.selected.weight_1_custom1 )
			.to.include.all.keys( Object.keys( initialState.form.packages.selected.weight_1_custom1 ) );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.needsPrintConfirmation ).to.eql( false );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'SET_PACKAGE_TYPE changes an existing package', () => {
		const action = setPackageType( 'weight_0_custom1', 'customPackage1' );
		const state = reducer( initialState, action );

		expect( state.form.packages.selected.weight_0_custom1.items.length ).to.eql( 1 );
		expect( state.form.packages.selected.weight_0_custom1.box_id ).to.eql( 'customPackage1' );
		expect( state.form.packages.selected.weight_0_custom1.length ).to.eql( 1 );
		expect( state.form.packages.selected.weight_0_custom1.width ).to.eql( 2 );
		expect( state.form.packages.selected.weight_0_custom1.height ).to.eql( 3 );
		expect( state.form.packages.selected.weight_0_custom1.weight ).to.eql( 4.7 );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
		expect( state.form.needsPrintConfirmation ).to.eql( false );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'SAVE_PACKAGES changes the saved state', () => {
		const existingState = hoek.clone( initialState );
		existingState.form.packages.saved = false;

		const action = savePackages();
		const state = reducer( existingState, action );

		expect( state.form.packages.saved ).to.eql( true );
	} );

	it( 'REMOVE_IGNORE_VALIDATION removes the ignore validation flags, does not change anything else', () => {
		const existingState = hoek.clone( initialState );

		const action = removeIgnoreValidation( 'origin' );
		const state = reducer( existingState, action );

		expect( state.form.origin.ignoreValidation ).to.be.null;
		state.form.origin.ignoreValidation = existingState.form.origin.ignoreValidation;
		expect( state ).to.deep.equal( existingState );
	} );

	it( 'UPDATE_ADDRESS_VALUE on any field marks the address as not validated', () => {
		const existingState = hoek.clone( initialState );
		existingState.form.origin.ignoreValidation = null;
		existingState.form.origin.isNormalized = true;
		existingState.form.origin.normalized = { address: 'MAIN ST', postcode: '12345' };

		const action = updateAddressValue( 'origin', 'address', 'Main Street' );
		const state = reducer( existingState, action );

		expect( state.form.origin.values.address ).to.equal( 'Main Street' );
		expect( state.form.origin.isNormalized ).to.be.false;
		expect( state.form.origin.normalized ).to.be.null;
	} );

	it( 'UPDATE_ADDRESS_VALUE changing the country restes the state field', () => {
		const existingState = hoek.clone( initialState );

		const action = updateAddressValue( 'origin', 'country', 'ES' );
		const state = reducer( existingState, action );

		expect( state.form.origin.values.country ).to.equal( 'ES' );
		expect( state.form.origin.values.state ).to.equal( '' );
	} );

	it( 'UPDATE_ADDRESS_VALUE removed the "ignore validation" flag on that field', () => {
		const existingState = hoek.clone( initialState );

		const action = updateAddressValue( 'origin', 'address', 'Main Street' );
		const state = reducer( existingState, action );

		expect( state.form.origin.ignoreValidation.address ).to.be.false;
		expect( state.form.origin.ignoreValidation.postcode ).to.be.true;
	} );

	it( 'CLEAR_AVAILABLE_RATES clears the available rates and resets the print confirmation', () => {
		const existingState = hoek.clone( initialState );

		const action = clearAvailableRates();
		const state = reducer( existingState, action );

		expect( state.form.rates.available ).to.eql( {} );
		expect( state.form.needsPrintConfirmation ).to.eql( false );
	} );
} );
