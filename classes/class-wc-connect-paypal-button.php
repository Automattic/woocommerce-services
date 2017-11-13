<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_PayPal_Button' ) ) {
	class WC_Connect_PayPal_Button {
		public function __construct() {
			add_action( 'woocommerce_proceed_to_checkout', array( $this, 'display_paypal_button' ), 20 );
		}

		public function display_paypal_button() {
			?>
			<div id="wcs-ppec-button"></div>
			<?php
			wp_enqueue_script( 'paypal-checkout-js', 'https://www.paypalobjects.com/api/checkout.js', array( 'jquery' ), null, true );
			wp_add_inline_script( 'paypal-checkout-js', sprintf( "
				paypal.Button.render( {
					env: 'sandbox',
					commit: true,
				
					style: {
						label: 'checkout',
						// fundingicons: true,
						shape: 'rect',
						color: 'silver',
						size: 'responsive',
					},

					payment: function() {
						var payload = {
							email: 'paul.dechov+payee@automattic.com',
							amount: '0.01',
							currency: 'USD',
						};
						return new paypal.Promise( function( resolve, reject ) {
							jQuery.post( '/wp-json/wc/v1/connect/paypal/payment', payload )
								.done( function( response ) {
									if ( ! response ) {
										return reject( new Error( 'server_error' ) );
									}
									resolve( response.id );
								} )
								.fail( function( paymentError ) {
									reject( new Error( paymentError ) );
								} );
						} );
					},

					onAuthorize: function( data ) {
						var payload = {
							payerID: data.payerID,
						};
						return new paypal.Promise( function( resolve, reject ) {
							jQuery.post( '/wp-json/wc/v1/connect/paypal/payment/' + data.paymentID + '/execute', payload )
								.done( function( response ) {
									if ( ! response ) {
										return reject( new Error( 'server_error' ) );
									}
									resolve( response.id );
								} )
								.fail( function( paymentError ) {
									reject( new Error( paymentError ) );
								} );
						} );
					},
				}, '#wcs-ppec-button' );
			" ) );
		}	
	}
}
