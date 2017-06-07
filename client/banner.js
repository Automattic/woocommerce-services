/*global wp */
/**
 * External dependencies
 */
import jQuery from 'jquery';

/**
 * Internal dependencies
 */
import '../assets/stylesheets/banner.scss';

jQuery( document ).ready( ( $ ) => {
	$( '.woocommerce-services__install-jetpack' ).one( 'click', function( event ) {
		event.preventDefault();
		const btn = $( this );

		btn.html( wp.updates.l10n.installing )
			.addClass( 'disabled' );

		wp.updates.installPlugin( { slug: 'jetpack' } )
			.then( ( response ) => window.location = response.activateUrl || window.location.href )
			.fail( () => {
				$( '<p/>', {
					// eslint-disable-next-line quote-props
					'class': 'woocommerce-services__jetpack-install-error-message',
					text: btn.data( 'error-message' ),
				} ).insertAfter( btn );

				btn.remove();
			} );
	} );
} );
