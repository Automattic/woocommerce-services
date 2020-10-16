/**
 * @format
 */
/* eslint no-console: [ "error", { "allow": [ "log" ] } ] */

/**
 * External dependencies
 */
var WPAPI = require( 'wpapi' );
/* eslint import/no-nodejs-modules: [ "error", { "allow": [ "fs" ] } ] */
import fs from 'fs';
import moment from 'moment';


/**
 * Internal dependencies
 */
import { withMockedShippingLabel } from '../mocks/index';

var WooCommerce = WPAPI.discover( process.env.WP_BASE_URL ).then(function( site ) {
    return site.auth({
		username: process.env.WP_ADMIN_USER_NAME,
		password: process.env.WP_ADMIN_USER_PW,
    });
});

const loadJSON = ( jsonFileName ) => {
	return JSON.parse(
		fs.readFileSync( `${__dirname}/${ jsonFileName }` )
	);
}

const createProduct = async () => {
	const ProductsJson = loadJSON( 'products.json' );

	return WooCommerce.then(function( site ) {
		return site.namespace( 'wc/v3' ).products().create( ProductsJson );
	});
}

const createOrder = async ( product ) => {
	const OrdersJson = loadJSON( 'orders.json' );

	OrdersJson.line_items.push( {
		"product_id": product.id,
		"quantity": 1
	} );

	return WooCommerce.then(function( site ) {
		return site.namespace( 'wc/v3' ).orders().create( OrdersJson );
	});
}

const createShippingLabel = async( order ) => {
	const createShippingLabelJson = loadJSON( 'create-shipping-label.json' );

	createShippingLabelJson.shipment_id = `shp_${ order.id }`;
	createShippingLabelJson.rate_id = `rate_${ order.id }`;
	createShippingLabelJson.packages[0].products = [ order.line_items[0].product_id ];

	// return await new Promise( ( resolve, reject ) => {
	// 	WooCommerceServices.post( `connect/label/${ order.id }`, createShippingLabelJson ).then( ( response ) => {
	// 		resolve( response.data );
	// 	} ).catch( ( error ) => {
	// 		console.log( error.response.data );
	// 		reject( error );
	// 	} );
	// } );
	return WooCommerce.then(function( site ) {
		console.log({site: site});
		console.log({wc: site.namespace( 'wc/v1' )});
		return site.namespace( 'wc/v1' ).orders().create( createShippingLabelJson );
	});
}

const destroyProduct = async ( product ) => {
	return site.namespace( 'wc/v3' ).products().delete( product.id );
}

const destroyOrder = async ( order ) => {
	return site.namespace( 'wc/v3' ).products().delete( order.id );
}

const withOrder = async ( callback ) => {

	const product	= await createProduct();

	const order = await createOrder( product );

	try {
		await callback( order );
	} catch (error) {
		console.error( error );
	} finally {
		await destroyOrder( order );

		await destroyProduct( product );
	}
};

const withShippingLabelAndOrder = async( callback ) => {
	await withOrder( async ( order ) => {
		const { labels: [ shippingLabel ] } = await createShippingLabel( order );
		shippingLabel.created = shippingLabel.created_date = Date.now();
		shippingLabel.expiry_date = moment().add( 30, 'days' ).milliseconds();

		await withMockedShippingLabel( shippingLabel, async() => {
			await callback( { shippingLabel, order } );
		} );

	} );
};

const withExpiredShippingLabelAndOrder = async( callback ) => {
	await withOrder( async ( order ) => {
		const { labels: [ shippingLabel ] } = await createShippingLabel( order );
		shippingLabel.created = shippingLabel.created_date = moment().subtract( 30, 'days' ).milliseconds();
		shippingLabel.expiry_date = Date.now();

		await withMockedShippingLabel( shippingLabel, async() => {
			await callback( { shippingLabel, order } );
		} );

	} );
}

module.exports = {
	withOrder,
	withShippingLabelAndOrder,
	withExpiredShippingLabelAndOrder,
};
