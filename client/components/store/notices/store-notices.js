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

			// Get new notices from the API response.
			const newNotices = extensions[ 'woocommerce-services' ].notices;

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
