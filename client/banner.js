/*global wp */
import jQuery from 'jquery';
import '../assets/stylesheets/banner.scss';

jQuery( document ).ready( ( $ ) => {
	$( '.wcc-admin-notice .wcc-install-jetpack' ).one( 'click', function() {
		const btn = $( this );

		btn.html( wp.updates.l10n.installing )
			.addClass( 'disabled' );

		wp.updates.installPlugin( { slug: 'jetpack' } )
			.then( ( response ) => window.location = response.activateUrl || window.location.href )
			.fail( () =>
				btn.parent().addClass( 'error' )
					.html( btn.data( 'error-message' ) )
			);
	} );

	$( '.wcc-admin-notice .wcc-install-woocommerce' ).one( 'click', function() {
		const btn = $( this );

		btn.html( wp.updates.l10n.installing )
			.addClass( 'disabled' );

		wp.updates.installPlugin( { slug: 'woocommerce' } )
			.then( ( response ) => window.location = response.activateUrl || window.location.href )
			.fail( () =>
				btn.parent().addClass( 'error' )
					.html( btn.data( 'error-message' ) )
			);
	} );
} );
