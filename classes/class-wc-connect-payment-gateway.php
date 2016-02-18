<?php

if ( ! class_exists( 'WC_Connect_Payment_Gateway' ) ) {

	class WC_Connect_Payment_Gateway extends WC_Payment_Gateway {

		public function __construct( $settings ) {

			foreach ( (array) $settings as $key => $value ) {
				$this->{$key} = $value;
			}

			$this->init_settings();

		}

	}

}

