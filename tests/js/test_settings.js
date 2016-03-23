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
import Settings from '../../client/views/usps';

describe( 'Settings', () => {
	before( () => {
		ReactTestEnvSetup();
	} );

	afterEach( () => {
		ReactDOM.unmountComponentAtNode( document.body );
	} );

	it( 'should contain a button', () => {
		const tree = TestUtils.renderIntoDocument( <Settings /> );
		const divs = TestUtils.scryRenderedDOMComponentsWithTag( tree, 'div' );

		expect( divs ).to.be.ok;
	} );
} );