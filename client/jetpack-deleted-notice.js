/* global wcServicesJetpackDeletedNotice */
/**
 * External dependencies
 */
import jQuery from 'jquery';
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import ReactDOM from "react-dom";
import React from "react";
import { useEffect, useState } from "@wordpress/element";

const App = () => {
	const [ isVisible, setIsVisible ] = useState( false );

	useEffect( () => {
		const handleDeleteSuccess = ( event, response ) => {
			if ( 'jetpack' === response.slug ) {
				setIsVisible( true );
			}
		};

		jQuery( document ).on( 'wp-plugin-delete-success', handleDeleteSuccess );

		return () => {
			jQuery( document ).off( 'wp-plugin-delete-success', handleDeleteSuccess );
		};
	}, [ setIsVisible ] );

	const handleClick = () => {
		const form = document.createElement( 'form' );
		form.action = wcServicesJetpackDeletedNotice.url;
		form.method = 'POST';

		const nonceField = document.createElement( 'input' );
		nonceField.type = 'hidden';
		nonceField.name = '_wpnonce';
		nonceField.value = wcServicesJetpackDeletedNotice.nonce;
		form.appendChild( nonceField );

		const actionField = document.createElement( 'input' );
		actionField.type = 'hidden';
		actionField.name = 'action';
		actionField.value = wcServicesJetpackDeletedNotice.action;
		form.appendChild( actionField );

		const redirectField = document.createElement( 'input' );
		redirectField.type = 'hidden';
		redirectField.name = 'redirect_url';
		redirectField.value = wcServicesJetpackDeletedNotice.redirect_url;
		form.appendChild( redirectField );

		document.body.appendChild( form );

		form.submit();
	};

	return (
		isVisible && (
			<Modal onRequestClose={ () => setIsVisible( false ) }
				   title={ __( 'WooCommerce Shipping & Tax has been disconnected', 'woocommerce-services' ) }
				   style={ { maxWidth: '600px' } }
			>
				<p>
					{ __( 'We have noticed you have just uninstalled Jetpack.', 'woocommerce-services' ) }
				</p>

				<p>
					{ __( 'Although WooCommerce Shipping & Tax no longer requires Jetpack, uninstalling the Jetpack plugin removes the connection to WordPress.com that WooCommerce Shipping & Tax depends on to function.', 'woocommerce-services' ) }
				</p>

				<p>
					{ __( 'To continue using WooCommerce Shipping & Tax, please reconnect your site.', 'woocommerce-services' ) }
				</p>

				<Button isPrimary onClick={ handleClick }>{ __( 'Reconnect', 'woocommerce-services' ) }</Button>
			</Modal>
		)
	);
};

document.addEventListener( 'DOMContentLoaded', () => {
	const container = document.createElement( 'div' );
	container.id = 'wc-connect-jetpack-deleted-notice';
	document.body.appendChild( container );

	ReactDOM.render(
		<App />,
		container
	);
} );
