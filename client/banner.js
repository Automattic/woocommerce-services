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
	$( '.woocommerce-services__connect-jetpack' ).one( 'click', function( event ) {
		event.preventDefault();
		const btn = $( this );
		btn.addClass( 'disabled' );

		installStep()
			.then( activateStep )
			.then( connectStep )
			.fail( function( error ) {
				let errorMessage = error;
				if ( ! error ) {
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

		function installStep() {
			if ( 'uninstalled' === wcs_install_banner.initial_install_status ) {
				return $.when()
					.then( function() {
						btn.html( wp.updates.l10n.installing );
						return wp.updates.installPlugin( { slug: 'jetpack' } );
					} );
			}
			return $.Deferred().resolve();
		}

		function activateStep() {
			if ( 'installed' === wcs_install_banner.initial_install_status ||
				'uninstalled' === wcs_install_banner.initial_install_status
			) {
				return $.when()
				.then( function() {
					btn.html( wcs_install_banner.translations.activating + '...' );
					return $.post( ajaxurl, {
						action: 'woocommerce_services_activate_jetpack',
						_ajax_nonce: wcs_install_banner.nonce,
					} );
				} )
				.then( function( response ) {
					if ( 'success' === response ) {
						return;
					}
					return $.Deferred().reject( response );
				} );
			}
			return $.Deferred().resolve();
		}

		function connectStep() {
			return $.when()
			.then( function() {
				btn.html( wcs_install_banner.translations.connecting + '...' );
				return $.post( ajaxurl, {
					action: 'woocommerce_services_get_jetpack_connect_url',
					_ajax_nonce: wcs_install_banner.nonce,
					redirect_url: wcs_install_banner.redirect_url,
				} );
			} )
			.then( function( jetpackConnectUrl ) {
				window.location.href = jetpackConnectUrl;
			} );
		}
	} );
} );
