/**
 * @format
 */

/**
 * External dependencies
 */
import WooCommerceRestApi from "@woocommerce/woocommerce-rest-api";

import fs from 'fs';

const WooCommerce = new WooCommerceRestApi( {
    url: process.env.WP_BASE_URL,
    consumerKey: process.env.WC_E2E_REST_API_CONSUMER_KEY,
    consumerSecret: process.env.WC_E2E_REST_API_CONSUMER_SECRET,
    wpAPI: true,
    version: 'wc/v3'
} );

const createProduct = async () => {
    const ProductsJson = JSON.parse( fs.readFileSync( './tests/e2e-tests/fixtures/products.json' ) );

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
    const OrdersJson = JSON.parse( fs.readFileSync( './tests/e2e-tests/fixtures/orders.json' ) );

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

	const product  = await createProduct();

	const order = await createOrder( product );

	console.log( {
		product,
		order
	} );

	await callback( order );

	//await destroyOrder( order );

	//await destroyProduct( product );
};

//await db.models.OrderMeta.destroy({
//where: {
//meta_key: 'wc_connect_labels'
//}
//});

module.exports = {
    withOrder
};
