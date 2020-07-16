/**
 * @format
 */
/* eslint no-console: [ "error", { "allow": [ "log" ] } ] */

/**
 * External dependencies
 */
import WooCommerceRestApi from "@woocommerce/woocommerce-rest-api";
/* eslint import/no-nodejs-modules: [ "error", { "allow": [ "fs" ] } ] */
import fs from 'fs';
import moment from 'moment';


/**
 * Internal dependencies
 */
import { withMockedShippingLabel } from '../mocks/index';

const WooCommerce = new WooCommerceRestApi( {
	url: process.env.WP_BASE_URL,
	consumerKey: process.env.WC_E2E_REST_API_CONSUMER_KEY,
	consumerSecret: process.env.WC_E2E_REST_API_CONSUMER_SECRET,
	wpAPI: true,
	version: 'wc/v3'
} );

const WooCommerceServices = new WooCommerceRestApi( {
	url: process.env.WP_BASE_URL,
	consumerKey: process.env.WC_E2E_REST_API_CONSUMER_KEY,
	consumerSecret: process.env.WC_E2E_REST_API_CONSUMER_SECRET,
	wpAPI: true,
	version: 'wc/v1'
} );

const loadJSON = ( jsonFileName ) => {
	return JSON.parse(
		fs.readFileSync( `./tests/e2e-tests/fixtures/${ jsonFileName }` ) );
}

const createProduct = async () => {
	const ProductsJson = loadJSON( 'products.json' );

	return await new Promise( ( resolve, reject ) => {
		WooCommerce.post( "products", ProductsJson ).then( ( response ) => {
			resolve( response.data );
		} ).catch( ( error ) => {
			console.log( error.response.data );
			reject( error );
		} );
	} );
}

const createOrder = async ( product ) => {
	const OrdersJson = loadJSON( 'orders.json' );

	OrdersJson.line_items.push( {
		"product_id": product.id,
		"quantity": 1
	} );

	return await new Promise( ( resolve, reject ) => {
		WooCommerce.post( "orders", OrdersJson ).then( ( response ) => {
			resolve( response.data );
		} ).catch( ( error ) => {
			console.log( error.response.data );
			reject( error );
		} );
	} );
}

const createShippingLabel = async( order	) => {
	const createShippingLabelJson = loadJSON( 'create-shipping-label.json' );

	createShippingLabelJson.shipment_id = `shp_${ order.id }`;
	createShippingLabelJson.rate_id = `rate_${ order.id }`;
	createShippingLabelJson.packages[0].products = [ order.line_items[0].product_id ];

	return await new Promise( ( resolve, reject ) => {
		WooCommerceServices.post( `connect/label/${ order.id }`, createShippingLabelJson ).then( ( response ) => {
			resolve( response.data );
		} ).catch( ( error ) => {
			console.log( error.response.data );
			reject( error );
		} );
	} );
}

const destroyProduct = async ( product ) => {
	WooCommerce.delete( `products/${ product.id }`, {
		force: true
	} );
}

const destroyOrder = async ( order ) => {
	WooCommerce.delete( `orders/${ order.id }`, {
		force: true
	} );
}

const withOrder = async ( callback ) => {

	const product	= await createProduct();

	const order = await createOrder( product );

	await callback( order );

	await destroyOrder( order );

	await destroyProduct( product );
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
