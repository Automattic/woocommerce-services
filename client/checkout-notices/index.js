/**
 * Block Notices
 *
 * This file is responsible for rendering Abort Messages from the Table Rate Shipping plugin.
 *
 * @package WooCommerce_Services
 */

const { useSelect }                                         = window.wp.data;
const { registerPlugin }                                    = window.wp.plugins;
const { ExperimentalOrderShippingPackages, StoreNotice }    = window.wc.blocksCheckout;
const { RawHTML }                                           = window.wp.element;

const createStoreNotice = ( notice, index, type = 'info' ) => {
	if ( 'debug' === type ) {
		type = 'info';
	}

	// eslint-disable-next-line react/react-in-jsx-scope
	const message = <RawHTML>{notice}</RawHTML>;

	return (
		// eslint-disable-next-line react/react-in-jsx-scope
		<StoreNotice key={index} status={type} isDismissible={false}>
			{message}
		</StoreNotice>
	);
};

const Notices = ({ messages }) => {
	if ( ! messages.notice ) {
		return null;
	}

	const currentMessage = messages.notice;

	return (
		// eslint-disable-next-line react/react-in-jsx-scope
		<div className="checkout-notices__message-info">
			{createStoreNotice( currentMessage, 0, 'info' )}
		</div>
	);
};

const render = () => {
	const { errorNotices } = useSelect((select) => {
		const storeCartData     = select( 'wc/store/cart' ).getCartData();

		if ( ! storeCartData.extensions || ! storeCartData.extensions.woocommerce_services || ! storeCartData.extensions.woocommerce_services.error_notices ) {
			return {};
		}
		// eslint-disable-next-line no-shadow
		const errorNotices = storeCartData.extensions.woocommerce_services.error_notices;
		
		return {
			errorNotices,
		};
	}, []);

	// Ensure we only show abort messages if no shipping rates are available.
	if ( ! errorNotices ) {
		return null;
	}

	return (
		// eslint-disable-next-line react/react-in-jsx-scope
		<ExperimentalOrderShippingPackages><Notices messages={errorNotices} /></ExperimentalOrderShippingPackages>
	);
};

registerPlugin('woocommerce-services-notices', {
	render,
	scope: 'woocommerce-checkout',
});
