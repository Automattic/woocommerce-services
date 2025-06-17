/**
 * External dependencies
 */
import React from 'react';
const { useDispatch, useSelect } = window.wp.data;
const { useEffect } = window.wp.element;

const noticesContext = 'wc/checkout/shipping-address';
const noticeIdPrefix = 'wcservices-notices-';

/**
 * A simplified version of the CheckoutNotices component that doesn't rely on @wordpress/data.
 * This is a temporary solution for the build process.
 *
 * @return {JSX.Element} The checkout notices component.
 */
export const CheckoutNotices = ( {
	extensions, cart,
} ) => {
	const shipToCountry                   = cart.shippingAddress.country;
	const { createNotice, removeNotices } = useDispatch( 'core/notices' );

	// Get all existing notices that are related to the address validation.
	const existingNoticeIds = useSelect( ( select ) => {
		const notices = select( 'core/notices' ).getNotices( noticesContext );

		return notices
			.map( ( notice ) => notice.id )
			.filter( ( id ) => id.startsWith( noticeIdPrefix ) );
	}, [] );

	// If the shipToCountry changes, remove the notices.
	useEffect( () => {
			if ( ! shipToCountry ) {
				return;
			}

			removeNotices( existingNoticeIds, noticesContext );
		},
		[ shipToCountry ]
	);

	// If the notices change, update the notices.
	useEffect( () => {
			removeNotices( existingNoticeIds, noticesContext );

			const newNotices = extensions[ 'woocommerce-services' ].notices;

			if ( newNotices.length === 0 ) {
				return;
			}

			newNotices.forEach( ( notice, index ) => {
				const { type, message } = notice;

				createNotice( type, message, {
					id: noticeIdPrefix + index, context: noticesContext,
				} );
			} );
		},
		[ extensions ]
	);

	return <></>;
};
