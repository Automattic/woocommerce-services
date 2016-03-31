/* eslint-disable vars-on-top */
import ReactTestEnvSetup from './lib/react-test-env-setup';

/**
 * External dependencies
 */
import { expect } from 'chai';
import ReactDOM from 'react-dom';
import React from 'react';
import TestUtils from 'react-addons-test-utils';

/**
 * Internal dependencies
 */
import { onFieldChange } from '../../client/actions/settings';

describe( 'Settings', () => {
	before( () => {
		ReactTestEnvSetup();
	} );

	afterEach( () => {
		ReactDOM.unmountComponentAtNode( document.body );
	} );

	it( 'test suite should work', () => {
		expect( true ).to.be.ok;
	} );
} );

describe( 'Settings actions', () => {
	it( '#onFieldChange()', () => {
		const event =  {
			target: {
				name: 'testField',
				value: 'testValue',
				type: 'text'
			},
		};

		const action = onFieldChange( event );

		expect( action ).to.eql( {
			type: 'UPDATE_SETTINGS_FIELD',
			key: 'testField',
			value: 'testValue',
		} );
	} );

	it( '#onFieldChange() checkbox', () => {
		const event =  {
			target: {
				name: 'testCheckboxField',
				checked: true,
				type: 'checkbox'
			},
		};

		const action = onFieldChange( event );

		expect( action ).to.eql( {
			type: 'UPDATE_SETTINGS_FIELD',
			key: 'testCheckboxField',
			value: true,
		} );
	} );
} );