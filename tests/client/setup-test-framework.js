/** @format */
/**
 * External dependencies
 */
const nock = require( 'nock' );

beforeAll( () => {
	// Disables all network requests for all tests.
	nock.disableNetConnect();
} );

beforeEach( () => {
	if (!nock.isActive()) nock.activate();
} );

afterAll( () => {
	nock.enableNetConnect();
} );

function cleanupAfterEach() {
	jest.clearAllTimers();
	nock.cleanAll();
	nock.restore();
}

afterEach(() => {
	if ( nock.isDone() ) {
		cleanupAfterEach();
		return;
	}

	const pendingNockRequests = [...nock.pendingMocks()]
	cleanupAfterEach()

	throw new Error(`WOAH! Your test case has some pending nock request:\n\n ${pendingNockRequests.join(' | ')}`);
});

jest.setTimeout(10000);

// It "mocks" enzyme, so that we can delay loading of
// the utility functions until enzyme is imported in tests.
// Props to @gdborton for sharing this technique in his article:
// https://medium.com/airbnb-engineering/unlocking-test-performance-migrating-from-mocha-to-jest-2796c508ec50.
let mockEnzymeSetup = false;

jest.mock( 'enzyme', () => {
	const actualEnzyme = require.requireActual( 'enzyme' );
	if ( ! mockEnzymeSetup ) {
		mockEnzymeSetup = true;

		// configure custom enzyme matchers for chai
		const chai = require.requireActual( 'chai' );
		const chaiEnzyme = require.requireActual( 'chai-enzyme' );
		chai.use( chaiEnzyme() );

		// configure enzyme 3 for React, from docs: http://airbnb.io/enzyme/docs/installation/index.html
		const Adapter = require.requireActual( 'enzyme-adapter-react-16' );
		actualEnzyme.configure( { adapter: new Adapter(), disableLifecycleMethods: false } );

		// configure snapshot serializer for enzyme
		const { createSerializer } = require.requireActual( 'enzyme-to-json' );
		expect.addSnapshotSerializer( createSerializer( { mode: 'deep' } ) );
	}
	return actualEnzyme;
} );

// It "mocks" sinon, so that we can delay loading of
// the utility functions until sinon is imported in tests.
let mockSinonSetup = false;

jest.mock( 'sinon', () => {
	const actualSinon = require.requireActual( 'sinon' );
	if ( ! mockSinonSetup ) {
		mockSinonSetup = true;

		// configure custom sinon matchers for chai
		const chai = require.requireActual( 'chai' );
		const sinonChai = require.requireActual( 'sinon-chai' );
		chai.use( sinonChai );
		actualSinon.assert.expose( chai.assert, { prefix: '' } );
	}
	return actualSinon;
} );
