/**
 * @format
 */

const withMockedShippingLabel = async( shippingLabel, callback ) => {

	const mockedShippingLabel = {
		"success": true,
		"label": {
			"label_id": shippingLabel.label_id,
			"tracking": "9405536897846488998110",
			"refundable_amount": 13.2,
			"created": shippingLabel.created,
			"carrier_id": "usps",
			"service_name": "USPS - Priority Mail",
			"status": "PURCHASED",
			"package_name": "Medium Flat Rate Box 2, Side Loading",
			"product_names": shippingLabel.product_names,
			"receipt_item_id": 24151858,
			"created_date": shippingLabel.created_date,
			"main_receipt_id": 19629638,
			"rate": 13.2,
			"currency": "USD",
			"expiry_date": shippingLabel.expiry_date
		},

	}

	const mockedRefund = {
		"success": true,
		"refund": {
			"status": "pending",
			"request_date": Date.now()
		}
	}

	await page.setRequestInterception(true);
	const getShippingLabelRequestListener = (request) => {
		if ( request.url().match( 'wp-json/wc/v1/connect/label/.*/refund' ) ) {
			request.respond( {
				status: 200,
				contentType: 'application/json; charset=UTF-8',
				body: JSON.stringify( mockedRefund )
			} );
		} else if ( request.url().match( 'wp-json/wc/v1/connect/label/.*' ) ) {
			request.respond( {
				status: 200,
				contentType: 'application/json; charset=UTF-8',
				body: JSON.stringify( mockedShippingLabel )
			} );
		} else {
			request.continue();
		}
	};

	page.on( 'request', getShippingLabelRequestListener );

	await callback();

	page.removeListener( 'request', getShippingLabelRequestListener );
	await page.setRequestInterception(false);
};

module.exports = {
	withMockedShippingLabel
};

