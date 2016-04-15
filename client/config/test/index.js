import { expect } from 'chai';
import makeConfig from '../';

describe( 'Config', () => {
	it( 'makeConfig() should return a function', () => {
		expect( makeConfig() ).to.be.a( 'function' );
	} );

	it( 'should return a value from passed in data by key', () => {
		const config = makeConfig( { test: 'foo' } );
		expect( config( 'test' ) ).to.equal( 'foo' );
	} );

	it( 'should throw an error for a missing key', () => {
		const config = makeConfig( { test: 'foo' } );
		expect( () => config( 'foo' ) ).to.throw( Error );
	} );
} );
