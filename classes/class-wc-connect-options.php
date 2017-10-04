<?php

if ( ! class_exists( 'WC_Connect_Options' ) ) {
	class WC_Connect_Options {
		/**
		 * An array that maps a grouped option type to an option name.
		 * @var array
		 */
		private static $grouped_options = array(
			'compact' => 'wc_connect_options',
		);

		/**
		 * Returns an array of option names for a given type.
		 *
		 * @param string $type The type of option to return. Defaults to 'compact'.
		 *
		 * @return array
		 */
		public static function get_option_names( $type = 'compact' ) {
			switch ( $type ) {
				case 'non_compact':
					return array(
						'error_notice',
						'services',
						'services_last_update',
						'last_heartbeat',
						'origin_address',
						'last_rate_request',
					);
				case 'shipping_method':
					return array(
						'form_settings',
						'failure_timestamp',
					);
			}

			return array(
				'tos_accepted',
				'store_guid',
				'debug_logging_enabled',
				'payment_methods',
				'account_settings',
				'paper_size',
				'packages',
				'predefined_packages',
				'shipping_methods_migrated',
				'should_display_nux_after_jp_cxn_banner',
				'needs_tax_environment_setup',
				'stripe_state',
			);
		}

		/**
		 * Deletes all options created by WooCommerce Services, including shipping method options
		 */
		public static function delete_all_options() {
			if ( defined( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL' ) ) {
				return;
			}

			foreach( self::$grouped_options as $group_key => $group ) {
				//delete legacy options
				foreach ( self::get_option_names( $group_key ) as $group_option ) {
					delete_option( "wc_connect_$group_option" );
				}

				delete_option( $group );
			}

			$non_compacts = self::get_option_names( 'non_compact' );
			foreach ( $non_compacts as $non_compact ) {
				delete_option( "wc_connect_$non_compact" );
			}

			self::delete_all_shipping_methods_options();
		}

		/**
		 * Returns the requested option.  Looks in wc_connect_options or wc_connect_$name as appropriate.
		 *
		 * @param string $name Option name
		 * @param mixed $default (optional)
		 *
		 * @return mixed
		 */
		public static function get_option( $name, $default = false ) {
			if ( self::is_valid( $name, 'non_compact' ) ) {
				return get_option( "wc_connect_$name", $default );
			}

			foreach ( array_keys( self::$grouped_options ) as $group ) {
				if ( self::is_valid( $name, $group ) ) {
					return self::get_grouped_option( $group, $name, $default );
				}
			}

			trigger_error( sprintf( 'Invalid WooCommerce Services option name: %s', $name ), E_USER_WARNING );
			return $default;
		}

		/**
		 * Updates the single given option.  Updates wc_connect_options or wc_connect_$name as appropriate.
		 *
		 * @param string $name Option name
		 * @param mixed $value Option value
		 *
		 * @return bool Was the option successfully updated?
		 */
		public static function update_option( $name, $value) {
			if ( self::is_valid( $name, 'non_compact' ) ) {
				return update_option( "wc_connect_$name", $value );
			}
			foreach ( array_keys( self::$grouped_options ) as $group ) {
				if ( self::is_valid( $name, $group ) ) {
					return self::update_grouped_option( $group, $name, $value );
				}
			}
			trigger_error( sprintf( 'Invalid WooCommerce Services option name: %s', $name ), E_USER_WARNING );
			return false;
		}

		/**
		 * Deletes the given option.  May be passed multiple option names as an array.
		 * Updates wc_connect_options and/or deletes wc_connect_$name as appropriate.
		 *
		 * @param string|array $names
		 *
		 * @return bool Was the option successfully deleted?
		 */
		public static function delete_option( $names ) {
			$result = true;
			$names  = (array) $names;
			if ( ! self::is_valid( $names ) ) {
				trigger_error( sprintf( 'Invalid WooCommerce Services option names: %s', print_r( $names, 1 ) ), E_USER_WARNING );
				return false;
			}
			foreach ( array_intersect( $names, self::get_option_names( 'non_compact' ) ) as $name ) {
				if ( ! delete_option( "wc_connect_$name" ) ) {
					$result = false;
				}
			}
			foreach ( array_keys( self::$grouped_options ) as $group ) {
				if ( ! self::delete_grouped_option( $group, $names ) ) {
					$result = false;
				}
			}
			return $result;
		}

		/**
		 * Gets a shipping method option
		 *
		 * @param $name
		 * @param $default
		 * @param $service_id
		 * @param $service_instance
		 *
		 * @return mixed
		 */
		public static function get_shipping_method_option( $name, $default, $service_id, $service_instance = false ) {
			$option_name = self::get_shipping_method_option_name( $name, $service_id, $service_instance );

			if ( ! $option_name ) {
				trigger_error( sprintf( 'Invalid WooCommerce Services shipping method option name: %s', $name ), E_USER_WARNING );
				return $default;
			}

			return get_option( $option_name, $default );
		}

		/**
		 * Updates a shipping method option
		 *
		 * @param $name
		 * @param $value
		 * @param $service_id
		 * @param $service_instance
		 *
		 * @return bool
		 */
		public static function update_shipping_method_option( $name, $value, $service_id, $service_instance = false ) {
			$option_name = self::get_shipping_method_option_name( $name, $service_id, $service_instance );

			if ( ! $option_name ) {
				trigger_error( sprintf( 'Invalid WooCommerce Services shipping method option name: %s', $name ), E_USER_WARNING );
				return false;
			}

			return update_option( $option_name, $value );
		}

		/**
		 * Deletes a shipping method option
		 *
		 * @param $name
		 * @param $service_id
		 * @param $service_instance
		 *
		 * @return bool
		 */
		public static function delete_shipping_method_option( $name, $service_id, $service_instance = false ) {
			$option_name = self::get_shipping_method_option_name( $name, $service_id, $service_instance );

			if ( ! $option_name ) {
				trigger_error( sprintf( 'Invalid WooCommerce Services shipping method option name: %s', $name ), E_USER_WARNING );
				return false;
			}

			return delete_option( $option_name );
		}

		/**
		 * Deletes all options related to a shipping method
		 *
		 * @param $service_id
		 * @param $service_instance
		 */
		public static function delete_shipping_method_options( $service_id, $service_instance = false ) {
			$option_names = self::get_option_names( 'shipping_method' );

			foreach ( $option_names as $name ) {
				delete_option( self::get_shipping_method_option_name( $name, $service_id, $service_instance ) );
			}
		}

		private static function get_grouped_option( $group, $name, $default ) {
			$options = get_option( self::$grouped_options[ $group ] );
			if ( is_array( $options ) && isset( $options[ $name ] ) ) {
				return $options[ $name ];
			}

			//make the grouped options backwards-compatible and migrate the old options
			$legacy_name = "wc_connect_$name";
			$legacy_option = get_option( $legacy_name, false );
			if ( ! $legacy_option ) {
				return $default;
			}
			if ( self::update_grouped_option( $group, $name, $legacy_option ) ) {
				delete_option( $legacy_name );
			}

			return $legacy_option;
		}

		private static function update_grouped_option( $group, $name, $value ) {
			$options = get_option( self::$grouped_options[ $group ] );
			if ( ! is_array( $options ) ) {
				$options = array();
			}
			$options[ $name ] = $value;
			return update_option( self::$grouped_options[ $group ], $options );
		}

		private static function delete_grouped_option( $group, $names ) {
			$options = get_option( self::$grouped_options[ $group ], array() );
			$to_delete = array_intersect( $names, self::get_option_names( $group ), array_keys( $options ) );
			if ( $to_delete ) {
				foreach ( $to_delete as $name ) {
					unset( $options[ $name ] );
				}
				return update_option( self::$grouped_options[ $group ], $options );
			}
			return true;
		}

		/**
		 * Based on the service id and optional instance, generates the option name
		 *
		 * @param $name
		 * @param $service_id
		 * @param $service_instance
		 *
		 * @return string|bool
		 */
		private static function get_shipping_method_option_name( $name, $service_id, $service_instance = false ) {
			if ( ! in_array( $name, self::get_option_names( 'shipping_method' ) ) ) {
				return false;
			}

			if ( ! $service_instance ) {
				return 'woocommerce_' . $service_id . '_' . $name;
			}

			return 'woocommerce_' . $service_id . '_' . $service_instance . '_' . $name;
		}

		/**
		 * Is the option name valid?
		 *
		 * @param string      $name  The name of the option
		 * @param string      $group The name of the group that the option is in. Defaults to compact.
		 *
		 * @return bool Is the option name valid?
		 */
		private static function is_valid( $name, $group = 'non_compact' ) {
			$group_keys = array_keys( self::$grouped_options );

			if ( is_array( $name ) ) {
				$compact_names = array();
				foreach ( $group_keys as $_group ) {
					$compact_names = array_merge( $compact_names, self::get_option_names( $_group ) );
				}
				$result = array_diff( $name, self::get_option_names( 'non_compact' ), $compact_names );
				return empty( $result );
			}

			if ( is_null( $group ) || 'non_compact' === $group ) {
				if ( in_array( $name, self::get_option_names( $group ) ) ) {
					return true;
				}
			}

			foreach ( array_keys( self::$grouped_options ) as $_group ) {
				if ( is_null( $group ) || $group === $_group ) {
					if ( in_array( $name, self::get_option_names( $_group ) ) ) {
						return true;
					}
				}
			}
			return false;
		}

		/**
		 * Deletes all options of all shipping methods
		 */
		private static function delete_all_shipping_methods_options() {
			global $wpdb;
			$methods = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}woocommerce_shipping_zone_methods " );

			foreach ( (array) $methods as $method ) {
				self::delete_shipping_method_options( $method->method_id, $method->instance_id );
			}
		}
	}
}
