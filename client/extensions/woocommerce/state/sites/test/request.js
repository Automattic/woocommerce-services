/** @format */

/**
 * External dependencies
 */
import { expect } from 'chai';
import nock from 'nock';

/**
 * Internal dependencies
 */
import request from 'woocommerce/state/sites/request';

describe( 'request', () => {
	describe( '#get', () => {
		const siteId = '123';
		const getResponse = { name: 'placeholder get response', placeholder: true };

		beforeEach( () => {
			nock( 'https://public-api.wordpress.com' )
				.get( '/rest/v1.1/jetpack-blogs/123/rest-api/' )
				.query( { path: '/wc/v3/placeholder_endpoint&_via_calypso&_method=get', json: true } )
				.reply( 200, { data: getResponse } );
		} );

		test( 'should fetch data via promise', async () => {
			const data = await request( siteId ).get( 'placeholder_endpoint' );

			expect( data ).to.eql( getResponse );
		} );

		test( 'should catch error via promise', () => {
			return request( siteId )
				.get( 'bad_placeholder_endpoint' )
				.catch( error => {
					expect( error.statusCode ).to.equal( 404 );
					nock.cleanAll();
				} );
		} );
	} );

	describe( '#post', () => {
		const siteId = '123';
		const body = { name: 'placeholder post request', placeholder: true };
		const postResponse = { name: 'placeholder post response', placeholder: true };

		beforeEach( () => {
			nock( 'https://public-api.wordpress.com' )
				.post( '/rest/v1.1/jetpack-blogs/123/rest-api/', {
					path: '/wc/v3/placeholder_endpoint&_via_calypso&_method=post',
					body: JSON.stringify( body ),
					json: true,
				} )
				.reply( 201, { data: postResponse } );
		} );


		test( 'should post data', async () => {
			const data = await request( siteId ).post( 'placeholder_endpoint', body );

			expect( data ).to.eql( postResponse );
		} );

		test( 'should catch error via promise', () => {
			return request( siteId )
				.post( 'bad_placeholder_endpoint' )
				.catch( error => {
					expect( error.statusCode ).to.equal( 404 );
					nock.cleanAll();
				} );
		} );
	} );

	describe( '#put', () => {
		const siteId = '123';
		const body = { name: 'placeholder put request', placeholder: true };
		const putResponse = { name: 'placeholder put response', placeholder: true };

		beforeEach( () => {
			nock( 'https://public-api.wordpress.com' )
				.post( '/rest/v1.1/jetpack-blogs/123/rest-api/', {
					path: '/wc/v3/placeholder_endpoint&_via_calypso&_method=put',
					body: JSON.stringify( body ),
					json: true,
				} )
				.reply( 200, { data: putResponse } );
		} );

		test( 'should put data', async () => {
			const data = await request( siteId ).put( 'placeholder_endpoint', body )

			expect( data ).to.eql( putResponse );
		} );

		test( 'should catch error via promise', () => {
			return request( siteId )
				.put( 'bad_placeholder_endpoint' )
				.catch( error => {
					expect( error.statusCode ).to.equal( 404 );
					nock.cleanAll();
				} );
		} );
	} );

	describe( '#del', () => {
		const siteId = '123';
		const deleteResponse = { name: 'placeholder delete response', placeholder: true };

		beforeEach( () => {
			nock( 'https://public-api.wordpress.com' )
				.post( '/rest/v1.1/jetpack-blogs/123/rest-api/', {
					path: '/wc/v3/placeholder_endpoint&_via_calypso&_method=delete',
					json: true,
				} )
				.reply( 200, { data: deleteResponse } );
		} );

		test( 'should delete', async () => {
			const data = await request( siteId ).del( 'placeholder_endpoint' );

			expect( data ).to.eql( deleteResponse );
		} );

		test( 'should catch error via promise', () => {
			return request( siteId )
				.del( 'bad_placeholder_endpoint' )
				.catch( error => {
					expect( error.statusCode ).to.equal( 404 );
					nock.cleanAll();
				} );
		} );
	} );
} );
