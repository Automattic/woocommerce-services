/* eslint-disable vars-on-top */
var ReactTestEnvSetup = require( './lib/react-test-env-setup' );

/**
 * External dependencies
 */
var expect = require( 'chai' ).expect,
	ReactDom = require( 'react-dom' ),
	React = require( 'react' ),
	TestUtils = require( 'react-addons-test-utils' );

/**
 * Internal dependencies
 */
var Settings = require( '../../src/views/settings' );

describe( 'Settings', function() {
	before( function() {
		ReactTestEnvSetup();
	} );

	afterEach( function() {
		ReactDom.unmountComponentAtNode( document.body );
	} );

	it( 'should contain an input', function() {
		var tree = TestUtils.renderIntoDocument( <Settings schema={{ "type": "string" }} /> ),
			input = TestUtils.findRenderedDOMComponentWithTag( tree, 'input' );

		expect( input ).to.be.ok;
	} );
} );