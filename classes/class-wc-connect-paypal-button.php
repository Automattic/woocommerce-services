<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_PayPal_Button' ) ) {
	class WC_Connect_PayPal_Button {
		public function __construct() {
			add_action( 'woocommerce_proceed_to_checkout', array( $this, 'display_paypal_button' ), 20 );
			// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	
			// if ( 'yes' === wc_gateway_ppec()->settings->checkout_on_single_product_enabled ) {
			//     add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'display_paypal_button_product' ), 1 );
			//     add_action( 'wc_ajax_wc_ppec_generate_cart', array( $this, 'wc_ajax_generate_cart' ) );
			// }
	
			// add_action( 'wc_ajax_wc_ppec_update_shipping_costs', array( $this, 'wc_ajax_update_shipping_costs' ) );
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
						label: 'pay',
						// fundingicons: true,
						shape: 'rect',
						color: 'silver'
						size: 'responsive',
					},

					payment: function() {
						var payload = {
							payee: 'paul.dechov+payee@automattic.com',
							amount: '0.01',
							currency: 'USD',
						};
						return new paypal.Promise( function( resolve, reject ) {
							jQuery.post( '/wp-json/wc/v1/connect/paypal/payment', payload )
								.done( function( response ) {
									console.log( response );
									if ( ! response ) {
										console.log( 'error' );
										return reject( new Error( 'server_error' ) );
									}
									resolve( response.id );
								} )
								.fail( function( paymentError ) {
									console.error( 'fail ');
									reject( new Error( code ) );
									
								} );
						} );
					},

					onAuthorize: function(data) {
						var payload = {
							payerID: data.payerID,
						};
						return new paypal.Promise( function( resolve, reject ) {
							jQuery.post( '/wp-json/wc/v1/connect/paypal/payment/' + data.paymentID + '/execute', payload )
								.done( function( response ) {
									console.log( response );
									if ( ! response ) {
										console.log( 'error' );
										return reject( new Error( 'server_error' ) );
									}
									resolve( response.id );
								} )
								.fail( function( paymentError ) {
									console.error( 'fail ');
									reject( new Error( code ) );
									
								} );
						} );
					},
				}, '#wcs-ppec-button' );
			" ) );
		}	
	}
}
