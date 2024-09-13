<?php

if ( ! class_exists( 'WC_Connect_Compatibility_WCShipping' ) ) {

	class WC_Connect_Compatibility_WCShipping {

		public static function maybe_enable_options_overwriting() {
			if ( WC_Connect_Loader::is_wc_shipping_activated() ) {
				add_filter(
					'pre_option_wc_connect_options',
					array( self::class, 'intercept_plugin_options_read' ),
					10,
					3
				);
			}
		}

		public static function disable_options_overwriting() {
			remove_filter( 'get_option', array( self::class, 'intercept_plugin_options_read' ), 10 );
		}

		public static function intercept_plugin_options_read( $value, $option, $default_value ) {
			$wcshipping_options = get_option( 'wcshipping_options', $default_value );

			if ( is_array( $wcshipping_options['packages'] ) ) {
				$wcshipping_options['packages'] = self::map_custom_package_data_to_wcservices_format( $wcshipping_options['packages'] );
			}

			return $wcshipping_options;
		}

		public static function map_custom_package_data_to_wcservices_format( $custom_packages ) {
			$keys_to_map = array(
				// from => to
				'boxWeight'  => 'box_weight',
				'dimensions' => 'inner_dimensions',
				'isLetter'   => 'is_letter',
			);

			$keys_to_unset = array(
				'id',
				'type',
			);

			foreach ( $custom_packages as &$package ) {
				// Map keys from WCShipping's format to WCS&T's, then unset WCShipping's.
				foreach ( $keys_to_map as $wcshipping_key => $wcservices_key ) {
					if ( isset( $package[ $wcshipping_key ] ) ) {
						$package[ $wcservices_key ] = $package[ $wcshipping_key ];
						unset( $package[ $wcshipping_key ] );
					}
				}

				// Unset keys from WCShipping which the Connect Server does not accept.
				foreach ( $keys_to_unset as $wcshipping_key ) {
					unset( $package[ $wcshipping_key ] );
				}
			}

			return $custom_packages;
		}
	}
}
