/**
 * Internal dependencies
 */
import { StoreNotices } from 'components/store/notices';

const { registerPlugin } = window.wp.plugins;
const { createElement } = window.wp.element;
const { ExperimentalOrderMeta } = window.wc.blocksCheckout;

/**
 * Render function for the store notices.
 *
 * Elements here must be created with the WordPress-provided React
 * (window.wp.element), not the plugin's bundled React: this tree is rendered
 * by the host React inside the Cart/Checkout blocks, and React 19 rejects
 * elements created by an older React runtime.
 *
 * @see https://github.com/woocommerce/woocommerce/blob/a7231863c014a95602f5932f702171465fa7bcf2/docs/cart-and-checkout-blocks/available-slot-fills.md?plain=1#L53
 *
 * @return {Object} The plugin content.
 */
const render = () => {
	return createElement( ExperimentalOrderMeta, null, createElement( StoreNotices ) );
};

registerPlugin( 'woocommerce-services-store-notices', {
	render,
	scope: 'woocommerce-checkout',
} );
