/**
 * External dependencies
 */
/*global wp */
/*global ajaxurl */
/*global wcs_install_banner */
import jQuery from 'jquery';

/**
 * Internal dependencies
 */
import '../assets/stylesheets/banner.scss';

jQuery( document ).ready( ( $ ) => {
	$( '.woocommerce-services__install-jetpack' ).one( 'click', function( event ) {
		event.preventDefault();
		const btn = $( this );
		btn.addClass( 'disabled' );

		// Either install or activate, and then connect.
		let installStep = $.Deferred().resolve();
		if ( 'uninstalled' === wcs_install_banner.initial_install_status ) {
			btn.html( wp.updates.l10n.installing );
			installStep = wp.updates.installPlugin( { slug: 'jetpack' } );
		}

		installStep
			.then( function() {
				btn.html( wcs_install_banner.translations.activating );
				return $.post( ajaxurl, {
					action: 'activate_jetpack',
					_ajax_nonce: wcs_install_banner.nonce,
				} );
			} )
			.then( function( response ) {
				if ( 'success' === response ) {
					return;
				}
				return $.Deferred().reject( response );
			} )
			.then( function() {
				btn.html( wcs_install_banner.translations.connecting );
				return;
			} )
			.fail( function( error ) {
				let errorMessage = wcs_install_banner.translations.defaultError;
				if ( error ) {
					errorMessage = wcs_install_banner.translations.defaultError;
				}
				if ( error && error.install && 'plugin' === error.install ) {
					// plugin install error
					errorMessage = wcs_install_banner.translations.installError;
				}
				$( '<p/>', {
					// eslint-disable-next-line quote-props
					'class': 'woocommerce-services__jetpack-install-error-message',
					text: errorMessage,
				} ).insertAfter( btn );
				btn.remove();
			} );
	} );
} );
