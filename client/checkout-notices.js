/**
 * External dependencies
 */
import React from 'react';

/**
 * Internal dependencies
 */
import { CheckoutNotices } from 'components/checkout/notices';

const { registerPlugin } = window.wp.plugins;
const { ExperimentalOrderShippingPackages } = window.wc.blocksCheckout;

/**
 * Render function for the checkout notices.
 *
 * @see https://github.com/woocommerce/woocommerce/blob/a7231863c014a95602f5932f702171465fa7bcf2/docs/cart-and-checkout-blocks/available-slot-fills.md?plain=1#L53
 *
 * @return {JSX.Element} The plugin content.
 */
const render = () => {
	return (
		<ExperimentalOrderShippingPackages>
			<CheckoutNotices />
		</ExperimentalOrderShippingPackages>
	);
};

registerPlugin( 'woocommerce-services-checkout-notices', {
	render,
	scope: 'woocommerce-checkout',
} );
