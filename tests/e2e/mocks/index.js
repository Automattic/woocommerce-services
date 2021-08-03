/**
 * @format
 */

const withMockedShippingLabel = async( shippingLabel, callback ) => {
	await page.setRequestInterception(true);
	const getShippingLabelRequestListener = (request) => {
		if ( request.url().match( 'wp-json/wc/v1/connect/label/.*/refund' ) ) {
			request.respond( {
				status: 200,
				contentType: 'application/json; charset=UTF-8',
				body: JSON.stringify( {
					success: true,
					refund: {
						status: 'pending',
						request_date: Date.now()
					}
				} )
			} );
		} else if ( request.url().match( 'wp-json/wc/v1/connect/label/.*' ) ) {
			request.respond( {
				status: 200,
				contentType: 'application/json; charset=UTF-8',
				body: JSON.stringify( {
					success: true,
					labels: [
						{
							carrier_id: 'usps',
							commercial_invoice_url: null,
							created: shippingLabel.created,
							created_date: shippingLabel.created_date,
							currency: 'USD',
							is_letter: false,
							label_id: shippingLabel.label_id,
							main_receipt_id: 19629638,
							package_name: 'Medium Flat Rate Box 2, Side Loading',
							product_ids: [shippingLabel.product_ids],
							product_names: [shippingLabel.product_names],
							rate: 13.2,
							receipt_item_id: 24151858,
							service_name: 'USPS - Priority Mail',
							refundable_amount: 13.2,
							status: 'PURCHASED',
							expiry_date: shippingLabel.expiry_date,
							tracking: '9405536897846488998110',
						}
					],
				} )
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

