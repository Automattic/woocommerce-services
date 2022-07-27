/**
 * External dependencies
 */
/*global wp */
/*global ajaxurl */
/*global wcs_nux_notice */
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
				// plugin install error.
				if ( error && error.install && 'plugin' === error.install ) {
					errorMessage = wcs_nux_notice.translations.installError;
				
				// catch error object for the non 'plugin' error.install.
				} else if ( ! error || ( error && typeof error === 'object' ) ) {
					errorMessage = wcs_nux_notice.translations.defaultError;
				}
				$( '<p/>', {
					// eslint-disable-next-line quote-props
					'class': 'woocommerce-services__jetpack-install-error-message',
					text: errorMessage,
				} ).insertAfter( btn );
				btn.remove();
			} );

		function installStep() {
			if ( 'uninstalled' === wcs_nux_notice.initial_install_status ) {
				return $.when()
					.then( function() {
						btn.html( wp.updates.l10n.installing );
						return wp.updates.installPlugin( { slug: 'jetpack' } );
					} );
			}
			return $.Deferred().resolve();
		}

		function activateStep() {
			if ( 'installed' === wcs_nux_notice.initial_install_status ||
				'uninstalled' === wcs_nux_notice.initial_install_status
			) {
				return $.when()
				.then( function() {
					btn.html( wcs_nux_notice.translations.activating );
					return $.post( ajaxurl, {
						action: 'woocommerce_services_activate_jetpack',
						_ajax_nonce: wcs_nux_notice.nonce,
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
				btn.html( wcs_nux_notice.translations.connecting );
				return $.post( ajaxurl, {
					action: 'woocommerce_services_get_jetpack_connect_url',
					_ajax_nonce: wcs_nux_notice.nonce,
					redirect_url: wcs_nux_notice.redirect_url,
				} );
			} )
			.then( function( jetpackConnectUrl ) {
				window.location.href = jetpackConnectUrl;
			} );
		}
	} );
} );
