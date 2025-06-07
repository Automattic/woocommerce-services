import React from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';

const noticesContext = 'wc/checkout/shipping-address';
const noticeIdPrefix = 'wcservices-av-';

export const CheckoutNotices = ( {
	extensions,
	cart,
} ) => {
	const shipToCountry = cart?.shippingAddress?.country;
	const { createNotice, removeNotices } = useDispatch( 'core/notices' );

	// Get all existing notices that are related to the address validation.
	const existingNoticeIds = useSelect( ( select ) => {
		const notices = select( 'core/notices' ).getNotices( noticesContext );

		return notices
			.map( ( notice ) => notice.id )
			.filter( ( id ) => id.startsWith( noticeIdPrefix ) );
	}, [] );

	// If the shipToCountry changes, remove the notices.
	useEffect(
		() => {
			if ( ! shipToCountry ) {
				return;
			}

			removeNotices( existingNoticeIds, noticesContext );
		}, // The effect should only rely on shipToCountry
		[ shipToCountry ]
	);

	// If the notices change, update the notices.
	useEffect(
		() => {
			removeNotices( existingNoticeIds, noticesContext );

			const newNotices =
				      extensions[ 'woocommerce-shipping' ]?.notices ?? [];

			if ( newNotices.length === 0 ) {
				return;
			}

			newNotices.forEach( ( notice, index ) => {
				const { type, message } = notice;

				createNotice( type, message, {
					id: noticeIdPrefix + index,
					context: noticesContext,
				} );
			} );
		}, // The effect should only rely on extensions
		[ extensions ]
	);

	return <></>;
};
