/** @format */

/**
 * External dependencies
 */
import { expect } from 'chai';
import { cloneDeep } from 'lodash';

/**
 * Internal dependencies
 */
import {
	carrierAccountConnectionSuccess,
	disconnectCarrier,
	setVisibilityCancelConnectionDialog,
	setVisibilityDisconnectCarrierDialog,
	submitCarrierSettings,
	toggleSettingsIsSaving,
	toggleShowUPSInvoiceFields,
	updateCarrierSettings,
} from '../actions';
import reducer from '../reducer';

const initialState = {
	modalErrors: {},
	pristine: true,
};

const siteId = 123;
const carrier = 'carrier';

describe( 'Carrier Accounts reducer', () => {
	const expectedEndState = cloneDeep( initialState );

	afterEach( () => {
		// make sure the state hasn't been mutated
		// after each test
		expect( initialState ).to.eql( expectedEndState );
	} );

	test( 'SUBMIT_SETTINGS connects the carrier', () => {
		const existingAddState = {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					fieldErrors: {},
				},
			},
		};
		const action = submitCarrierSettings( siteId, carrier );
		const state = reducer( existingAddState, action );

		expect( state ).to.eql( {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					fieldErrors: {},
				},
			},
		} );
	} );

	test( 'UPDATE_SETTINGS updates the carrier settings', () => {
		const existingAddState = {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					values: {},
					ignoreValidation: {},
				},
			},
		};
		const action = updateCarrierSettings( siteId, carrier, 'myField', 'myValue' );
		const state = reducer( existingAddState, action );

		expect( state ).to.eql( {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					values: {
						myField: 'myValue',
					},
					ignoreValidation: {
						myField: false,
					},
				},
			},
		} );
	} );

	test( 'TOGGLE_SHOW_UPS_INVOICE_FIELDS toggles a flag', () => {
		const existingAddState = {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					showUPSInvoiceFields: false,
				},
			},
		};
		const action = toggleShowUPSInvoiceFields( siteId, carrier, true );
		const state = reducer( existingAddState, action );

		expect( state ).to.eql( {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					showUPSInvoiceFields: true,
				},
			},
		} );
	} );

	test( 'ENABLE_CANCEL_CONNECTION_DIALOG toggles a flag', () => {
		const existingAddState = {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					showCancelConnectionDialog: false,
				},
			},
		};
		const action = setVisibilityCancelConnectionDialog( siteId, carrier, true );
		const state = reducer( existingAddState, action );

		expect( state ).to.eql( {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					showCancelConnectionDialog: true,
				},
			},
		} );
	} );

	test( 'ENABLE_DISCONNECT_DIALOG toggles a flag', () => {
		const existingAddState = {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				showDisconnectDialog: false,
			},
		};
		const action = setVisibilityDisconnectCarrierDialog( siteId, carrier, true );
		const state = reducer( existingAddState, action );

		expect( state ).to.eql( {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				showDisconnectDialog: true,
			},
		} );
	} );

	test( 'DISCONNECT_CARRIER disconnects the carrier', () => {
		const existingAddState = {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {},
			},
		};
		const action = disconnectCarrier( siteId, carrier );
		const state = reducer( existingAddState, action );

		expect( state ).to.eql( {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {},
			},
		} );
	} );

	test( 'WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_TOGGLE_IS_SAVING toggles a flag', () => {
		const existingAddState = {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					isSaving: false,
				},
			},
		};
		const action = toggleSettingsIsSaving( siteId, carrier, true );
		const state = reducer( existingAddState, action );

		expect( state ).to.eql( {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					isSaving: true,
				},
			},
		} );
	} );
	test( 'WOOCOMMERCE_SERVICES_CARRIER_ACCOUNTS_CONNECTION_SUCCESS toggles a flag', () => {
		const existingAddState = {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					isConnectionSuccess: false,
				},
			},
		};
		const action = carrierAccountConnectionSuccess( siteId, carrier, true );
		const state = reducer( existingAddState, action );

		expect( state ).to.eql( {
			modalErrors: {},
			pristine: true,
			[ carrier ]: {
				settings: {
					isConnectionSuccess: true,
				},
			},
		} );
	} );
} );
