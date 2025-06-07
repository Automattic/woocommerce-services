// WordPress dependencies.
import { registerPlugin } from '@wordpress/plugins';

// WooCommerce dependencies.
import { ExperimentalOrderShippingPackages } from '@woocommerce/blocks-checkout';

// Internal dependencies.
import { CheckoutNotices } from 'components/checkout/notices';

/*
 * `ExperimentalOrderShippingPackages` is a `Slot` component in WooCommerce.
 *
 * @see https://github.com/woocommerce/woocommerce/blob/a7231863c014a95602f5932f702171465fa7bcf2/docs/cart-and-checkout-blocks/available-slot-fills.md?plain=1#L53
 */
const render = () => {
	return (
		<ExperimentalOrderShippingPackages>
			<CheckoutNotices />
		</ExperimentalOrderShippingPackages>
	);
};

registerPlugin( 'woocommerce-services-block-checkout-notices', {
	render,
	scope: 'woocommerce-checkout',
} );
