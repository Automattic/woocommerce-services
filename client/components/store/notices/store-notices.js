/**
 * This component is rendered by the host React inside the Cart/Checkout blocks, so it must
 * not import the plugin's bundled React or return JSX built with it: React 19 rejects
 * elements created by an older React runtime. Hooks and elements come from window.wp.*.
 *
 * @see ../../../store-notices.js
 */

const { useDispatch, useSelect } = window.wp.data;
const { useEffect }              = window.wp.element;

const noticesContext = 'wc/cart';
const noticeIdPrefix = 'wcservices-store-notices-';

/**
 * Component responsible for managing frontend store-related notices.
 *
 * Allows handling and displaying notices on the cart and checkout pages based on certain conditions,
 * such as changes in the shipping country or notices provided by extensions.
 *
 * @param {Object} props - The props for the component.
 * @param {Object} props.extensions - An object containing store API response data related for all extensions.
 * @param {Object} props.cart - An object containing details about the cart.
 *
 * @returns {null} - Nothing is rendered; the component only manages notices.
 */
export const StoreNotices = ( {
	extensions, cart,
} ) => {
	const shipToCountry                   = cart.shippingAddress.country;
	const shipToState                     = cart.shippingAddress.state;
	const shipToPostcode                  = cart.shippingAddress.postcode;
	const { createNotice, removeNotices } = useDispatch( 'core/notices' );

	// Get all existing notices with our noticeIdPrefix.
	const existingNoticeIds = useSelect( ( select ) => {
		const notices = select( 'core/notices' ).getNotices( noticesContext );

		return notices
			.map( ( notice ) => notice.id )
			.filter( ( id ) => id.startsWith( noticeIdPrefix ) );
	}, [] );

	// Remove our notices when shipping country|state|postcode are changed.
	useEffect( () => {
			if ( 'US' !== shipToCountry ) {
				return;
			}

			removeNotices( existingNoticeIds, noticesContext );
		},
		[ shipToState, shipToPostcode, shipToCountry ]
	);

	// If the notices change, update the notices.
	useEffect( () => {
			if ( 'US' !== shipToCountry ) {
				return;
			}

			// Get new notices from the API response. The extension data is
			// missing on WooCommerce versions where the Store API extension
			// is not registered, and anything else on the response is outside
			// our control, so treat everything but a list as "no notices":
			// throwing here would take down the whole checkout fill.
			const wcservicesData = extensions[ 'woocommerce-services' ];
			const rawNotices     = wcservicesData && wcservicesData.notices;
			const newNotices     = Array.isArray( rawNotices ) ? rawNotices : [];

			if ( 0 === newNotices.length ) {
				return;
			}

			// Loop through and add the new notices to the specified context.
			newNotices.forEach( ( notice, index ) => {
				const { type, message } = notice;

				createNotice( type, message, {
					id: noticeIdPrefix + index,
					context: noticesContext,
				} );
			} );
		},
		[ extensions ]
	);

	return null;
};
