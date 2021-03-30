/**
 * @format
 */
/* eslint no-console: [ "error", { "allow": [ "log" ] } ] */

/**
 * External dependencies
 */
import WooCommerceRestApi from "@woocommerce/woocommerce-rest-api";
import fs from 'fs';
import moment from 'moment';

/* eslint import/no-nodejs-modules: [ "error", { "allow": [ "fs" ] } ] */

/**
 * Internal dependencies
 */
import { withMockedShippingLabel } from '../mocks/index';
const defaultConfig = require( '../config/default.json' );

const baseURL = process.env.WP_BASE_URL || defaultConfig.url;
const consumerKey = process.env.WC_E2E_REST_API_CONSUMER_KEY || defaultConfig.consumerKey;
const consumerSecret = process.env.WC_E2E_REST_API_CONSUMER_SECRET || defaultConfig.consumerSecret;

const apiV1 = new WooCommerceRestApi({
	url: baseURL,
	consumerKey: consumerKey,
	consumerSecret: consumerSecret,
	version: 'wc/v1',
} );
const apiV3 = new WooCommerceRestApi({
	url: baseURL,
	consumerKey: consumerKey,
	consumerSecret: consumerSecret,
	version: 'wc/v3',
} );

const loadJSON = ( jsonFileName ) => {
	return JSON.parse(
		fs.readFileSync( `${__dirname}/${ jsonFileName }` )
	);
}

const createProduct = async () => {
	const ProductsJson = loadJSON( 'products.json' );

	const response = await apiV3.post( 'products', ProductsJson );
	return response.data;
}

const createOrder = async ( product ) => {
	const OrdersJson = loadJSON( 'orders.json' );

	OrdersJson.line_items.push( {
		"product_id": product.id,
		"quantity": 1
	} );

	const response = await apiV3.post( 'orders', OrdersJson );
	return response.data;
}

const createShippingLabel = async ( order ) => {
	const createShippingLabelJson = loadJSON( 'create-shipping-label.json' );

	createShippingLabelJson.shipment_id = `shp_${ order.id }`;
	createShippingLabelJson.rate_id = `rate_${ order.id }`;
	createShippingLabelJson.packages[0].products = [ order.line_items[0].product_id ];

	const response = await apiV1.post( `connect/label/${ order.id }`, createShippingLabelJson );
	return response.data;
}

const destroyProduct = async ( product ) => {
	await apiV3.delete( `products/${ product.id }` );
}

const destroyOrder = async ( order ) => {
	await apiV3.delete( `orders/${ order.id }` );
}

const withOrder = async ( callback ) => {

	const product = await createProduct();

	const order = await createOrder( product );

	await callback( order );

	await destroyOrder( order );
	await destroyProduct( product );
};

const withShippingLabelAndOrder = async ( callback ) => {
	await withOrder( async ( order ) => {
		const shippingLabel = await createShippingLabel( order );
		shippingLabel.created = shippingLabel.created_date = Date.now();
		shippingLabel.expiry_date = moment().add( 30, 'days' ).milliseconds();

		await withMockedShippingLabel( shippingLabel, async() => {
			await callback( { shippingLabel, order } );
		} );

	} );
};

const withExpiredShippingLabelAndOrder = async( callback ) => {
	await withOrder( async ( order ) => {
		const shippingLabel = await createShippingLabel( order );
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
