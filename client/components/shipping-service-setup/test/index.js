import { expect } from 'chai';
import ReactDOM from 'react-dom';
import 'react-addons-test-utils';
import ReactTestEnvSetup from 'lib/tests/react-test-env-setup';

describe( 'ShippingServiceSetup', () => {
	before( () => {
		ReactTestEnvSetup();
	} );

	afterEach( () => {
		ReactDOM.unmountComponentAtNode( document.body );
	} );

	// [TODO] write real tests here
	it( 'ShippingServiceSetup should work', () => {
		expect( true ).to.be.ok;
	} );
} );
