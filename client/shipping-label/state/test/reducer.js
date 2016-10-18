import reducer from '../reducer';
import {
	moveItem,
	addPackage,
	removePackage,
	setPackageType,
	savePackages,
} from '../actions';
import hoek from 'hoek';

const initialState = {
	form: {
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
			unpacked: [],
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

		expect( state.form.packages.selected.weight_0_custom1.items.length ).to.eql( 0 );
		expect( state.form.packages.selected.weight_1_custom1.items.length ).to.eql( 2 );
		expect( state.form.packages.selected.weight_1_custom1.items ).to.include( initialState.form.packages.selected.weight_0_custom1.items[ 0 ] );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'MOVE_ITEM moves items from selected packages to original packaging', () => {
		const action = moveItem( 'weight_0_custom1', 0, 'individual' );
		const state = reducer( initialState, action );

		expect( state.form.packages.selected.weight_0_custom1.items.length ).to.eql( 0 );
		expect( state.form.packages.selected ).to.include.keys( 'client_individual_0' );
		expect( state.form.packages.selected.client_individual_0.box_id ).to.eql( 'individual' );
		expect( state.form.packages.selected.client_individual_0.items.length ).to.eql( 1 );
		expect( state.form.packages.selected.client_individual_0.items ).to.include( initialState.form.packages.selected.weight_0_custom1.items[ 0 ] );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'MOVE_ITEM moves items from selected packages to saved for later', () => {
		const action = moveItem( 'weight_0_custom1', 0, '' );
		const state = reducer( initialState, action );

		expect( state.form.packages.selected.weight_0_custom1.items.length ).to.eql( 0 );
		expect( state.form.packages.unpacked.length ).to.eql( 1 );
		expect( state.form.packages.unpacked ).to.include( initialState.form.packages.selected.weight_0_custom1.items[ 0 ] );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'MOVE_ITEM moves items from saved for later to selected packages', () => {
		const existingState = hoek.clone( initialState );
		existingState.form.packages.unpacked.push( {
			product_id: 789,
		} );

		const action = moveItem( '', 0, 'weight_0_custom1' );
		const state = reducer( existingState, action );

		expect( state.form.packages.unpacked.length ).to.eql( 0 );
		expect( state.form.packages.selected.weight_0_custom1.items.length ).to.eql( 2 );
		expect( state.form.packages.selected.weight_0_custom1.items ).to.include( existingState.form.packages.unpacked[ 0 ] );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'MOVE_ITEM moves items from saved for later to original packaging', () => {
		const existingState = hoek.clone( initialState );
		existingState.form.packages.unpacked.push( {
			product_id: 789,
		} );

		const action = moveItem( '', 0, 'individual' );
		const state = reducer( existingState, action );

		expect( state.form.packages.unpacked.length ).to.eql( 0 );
		expect( state.form.packages.selected ).to.include.keys( 'client_individual_0' );
		expect( state.form.packages.selected.client_individual_0.box_id ).to.eql( 'individual' );
		expect( state.form.packages.selected.client_individual_0.items.length ).to.eql( 1 );
		expect( state.form.packages.selected.client_individual_0.items ).to.include( existingState.form.packages.unpacked[ 0 ] );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
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
		expect( state.form.packages.selected.weight_0_custom1.items ).to.include( existingState.form.packages.selected.client_individual_0.items[ 0 ] );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'MOVE_ITEM moves items from original packaging to saved for later and deletes original package', () => {
		const existingState = hoek.clone( initialState );
		existingState.form.packages.selected.client_individual_0 = {
			items: [ {
				product_id: 789,
			} ],
			box_id: 'individual',
		};
		existingState.openedPackageId = 'client_individual_0';

		const action = moveItem( 'client_individual_0', 0, '' );
		const state = reducer( existingState, action );

		expect( state.form.packages.unpacked.length ).to.eql( 1 );
		expect( state.form.packages.unpacked ).to.include( existingState.form.packages.selected.client_individual_0.items[ 0 ] );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'ADD_PACKAGE adds a new package', () => {
		const action = addPackage();
		const state = reducer( initialState, action );

		expect( state.form.packages.selected ).to.include.keys( 'client_custom_0' );
		expect( state.form.packages.selected.client_custom_0.items.length ).to.eql( 0 );
		expect( state.form.packages.selected.client_custom_0.box_id ).to.eql( 'customPackage1' );
		expect( state.form.packages.selected.client_custom_0.length ).to.eql( 1 );
		expect( state.form.packages.selected.client_custom_0.width ).to.eql( 2 );
		expect( state.form.packages.selected.client_custom_0.height ).to.eql( 3 );
		expect( state.form.packages.selected.client_custom_0.weight ).to.eql( 3.5 );
		expect( state.form.packages.saved ).to.eql( false );
		expect( state.openedPackageId ).to.eql( 'client_custom_0' );
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'REMOVE_PACKAGE removes the package and moves all the items to saved for later', () => {
		const action = removePackage( 'weight_0_custom1' );
		const state = reducer( initialState, action );

		expect( state.form.packages.selected ).to.not.include.keys( 'weight_0_custom1' );
		expect( state.form.packages.unpacked.length ).to.eql( 1 );
		expect( state.form.packages.unpacked ).to.include( initialState.form.packages.selected.weight_0_custom1.items[ 0 ] );
		expect( state.form.rates.values ).to.include.all.keys( Object.keys( state.form.packages.selected ) );
		expect( state.form.packages.saved ).to.eql( false );
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
		expect( state.form.rates.available ).to.eql( {} );
	} );

	it( 'SAVE_PACKAGES changes the saved state', () => {
		const existingState = hoek.clone( initialState );
		existingState.form.packages.saved = false;

		const action = savePackages();
		const state = reducer( existingState, action );

		expect( state.form.packages.saved ).to.eql( true );
	} );
} );
