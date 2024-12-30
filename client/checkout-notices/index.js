/**
 * Block Notices
 *
 * This file is responsible for rendering Abort Messages from the Table Rate Shipping plugin.
 *
 * @package WooCommerce_Services
 */

window.addEventListener("load", () => {
	const { useSelect }                                         = window.wp.data;
	const { registerPlugin }                                    = window.wp.plugins;
	const { ExperimentalOrderShippingPackages, StoreNotice }    = window.wc.blocksCheckout;
	const { RawHTML }                                           = window.wp.element;

	const createStoreNotice = ( notice, index, type = 'info' ) => {
		if ( 'debug' === type ) {
			type = 'info';
		}

		const message = <RawHTML>{notice}</RawHTML>;

		return (
			<StoreNotice key={index} status={type} isDismissible={false}>
				{message}
			</StoreNotice>
		);
	};

	const Notices = ({ messages }) => {
		if ( ! messages['notice'] ) {
			return null;
		}

		const currentMessage = messages['notice'];

		return (
			<div className="woocommerce-services-block-notices">
				{createStoreNotice( currentMessage, 0, 'info' )}
			</div>
		);
	};

	const render = () => {
		const { errorNotices } = useSelect((select) => {
			const storeCartData     = select( 'wc/store/cart' ).getCartData();

			if ( ! storeCartData.extensions && ! storeCartData.extensions['woocommerce_services'] && ! storeCartData.extensions['woocommerce_services'].error_notices ) {
				return {};
			}
			const errorNotices = storeCartData.extensions['woocommerce_services'].error_notices;

			return {
				errorNotices,
			};
		}, []);
	
		// Ensure we only show abort messages if no shipping rates are available.
		if ( ! errorNotices ) {
			return null;
		}

		return (
			<ExperimentalOrderShippingPackages>
				<Notices messages={errorNotices} />
			</ExperimentalOrderShippingPackages>
		);
	};

	registerPlugin('woocommerce-services-notices', {
		render,
		scope: 'woocommerce-checkout',
	});
});
