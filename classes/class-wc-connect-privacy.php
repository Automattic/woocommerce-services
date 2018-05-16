<?php

if ( ! class_exists( 'WC_Abstract_Privacy' ) ) {
	return;
}

class WC_Connect_Privacy extends WC_Abstract_Privacy {
	public function __construct() {
		parent::__construct( 'WooCommerce Services' );
	}

	public function get_privacy_message() {
		return wpautop(
			sprintf(
				wp_kses(
					__( 'By using this extension, you may be storing personal data or sharing data with external services. <a href="%s" target="_blank">Learn more about how this works, including what you may want to include in your privacy policy.</a>', 'woocommerce-services' ),
					array( 'a' => array( 'href' => array() ) )
				),
				'https://jetpack.com/support/for-your-privacy-policy/#woocommerce-services'
			)
		);
	}
}

new WC_Connect_Privacy();
