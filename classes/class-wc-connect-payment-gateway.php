<?php

if ( ! class_exists( 'WC_Connect_Payment_Gateway' ) ) {

	/**
	 * Payment gateway that hydrates itself from a settings array.
	 */
	class WC_Connect_Payment_Gateway extends WC_Payment_Gateway {

		/**
		 * Constructor.
		 *
		 * @param array|object $settings Settings used to populate the gateway properties.
		 */
		public function __construct( $settings ) {

			foreach ( (array) $settings as $key => $value ) {
				$this->{$key} = $value;
			}

			$this->init_settings();
		}
	}

}//end if
