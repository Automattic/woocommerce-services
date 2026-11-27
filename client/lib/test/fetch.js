/**
 * Internal dependencies
 */
import fetch from '../fetch';

const originalFetch = window.fetch;

afterEach( () => {
	window.fetch = originalFetch;
} );

it( 'uses the browser fetch with its receiver and request options', async () => {
	const response = { ok: true };
	window.fetch = jest.fn( function() {
		expect( this ).toBe( window );
		return Promise.resolve( response );
	} );
	const options = { credentials: 'same-origin', method: 'POST', body: '{}' };
	expect( await fetch( '/wp-json/example', options ) ).toBe( response );
	expect( window.fetch ).toHaveBeenCalledWith( '/wp-json/example', options );
} );

it( 'installs the XHR fallback when native fetch is unavailable', () => {
	delete window.fetch;
	jest.isolateModules( () => {
		require( '../fetch' );
		expect( typeof window.fetch ).toBe( 'function' );
		expect( window.fetch.polyfill ).toBe( true );
	} );
} );
