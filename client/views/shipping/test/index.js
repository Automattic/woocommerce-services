import ReactTestEnvSetup from 'lib/tests/react-test-env-setup';
import { expect } from 'chai';
import ReactDOM from 'react-dom';

describe( 'Shipping Settings', () => {
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
