<?php

/**
 * Uses package data from WooCommerce Shipping if it is active.
 *
 * Redirects reads of and writes to wc_connect_options[packages] to wcshipping_options[packages].
 *
 * @since x.x.x
 */
class WC_Connect_Compatibility_WCShipping_Packages {

	/**
	 * Number WCShipping uses to indicate data migration from WCS&T has completed.
	 *
	 * @var int
	 */
	const WCSHIP_DATA_MIGRATION_COMPLETED = 14;

	// Mapping of WCShipping keys => WCS&T keys.
	const WCSHIPPING_TO_WCSERVICES_KEY_MAP = array(
		'boxWeight'  => 'box_weight',
		'dimensions' => 'inner_dimensions',
		'maxWeight'  => 'max_weight',
	);

	const KEYS_UNUSED_BY_WCSERVICES = array( 'id', 'isLetter' );
	const KEYS_UNUSED_BY_WCSHIPPING = array( 'is_letter' );

	public static function maybe_enable() {
		$is_migration_to_wcshipping_completed = self::WCSHIP_DATA_MIGRATION_COMPLETED === (int) get_option( 'wcshipping_migration_state' );

		if ( WC_Connect_Loader::is_wc_shipping_activated() && $is_migration_to_wcshipping_completed ) {
			self::register_hooks();
		}
	}

	public static function register_hooks() {
		// Remapping the "packages" key of "wc_connect_options".
		add_filter( 'option_wc_connect_options', array( self::class, 'intercept_packages_read' ) );
		add_filter( 'option_wc_connect_options', array( self::class, 'intercept_predefined_packages_read' ) );
		add_action( 'update_option_wc_connect_options', array( self::class, 'intercept_packages_update' ), 10, 2 );
		add_action( 'update_option_wc_connect_options', array( self::class, 'intercept_predefined_packages_update' ), 10, 2 );
	}

	public static function intercept_packages_read( $wc_connect_options ) {
		$wcshipping_options = get_option( 'wcshipping_options' );

		if ( is_array( $wcshipping_options ) && isset( $wcshipping_options['packages'] ) ) {
			$wc_connect_options['packages'] = self::map_packages_to_wcservices_format( $wcshipping_options['packages'] );
		}

		return $wc_connect_options;
	}

	public static function intercept_predefined_packages_read( $wc_connect_options ) {
		$wcshipping_options = get_option( 'wcshipping_options' );

		if ( is_array( $wcshipping_options ) && isset( $wcshipping_options['predefined_packages'] ) ) {
			$wc_connect_options['predefined_packages'] = $wcshipping_options['predefined_packages'];
		}

		return $wc_connect_options;
	}

	public static function intercept_packages_update( $old_wc_connect_options, $wc_connect_options ) {
		$wcshipping_options = get_option( 'wcshipping_options' );

		if ( ! empty( $wc_connect_options['packages'] ) ) {
			$wcshipping_options['packages'] = self::map_packages_to_wcshipping_format( $wc_connect_options['packages'] );
		} else {
			$wcshipping_options['packages'] = array();
		}

		update_option( 'wcshipping_options', $wcshipping_options );
	}

	public static function intercept_predefined_packages_update( $old_wc_connect_options, $wc_connect_options ) {
		$wcshipping_options = get_option( 'wcshipping_options' );

		if ( ! empty( $wc_connect_options['predefined_packages'] ) ) {
			$wcshipping_options['predefined_packages'] = $wc_connect_options['predefined_packages'];
		} else {
			$wcshipping_options['predefined_packages'] = array();
		}

		update_option( 'wcshipping_options', $wcshipping_options );
	}

	public static function map_packages_to_wcservices_format( $custom_packages ) {
		$old_custom_packages = $custom_packages;

		foreach ( $custom_packages as &$package ) {
			$package = self::rename_keys( $package, self::WCSHIPPING_TO_WCSERVICES_KEY_MAP );
			$package = self::map_type_to_is_letter( $package );
			$package = self::unset_keys( $package, self::KEYS_UNUSED_BY_WCSERVICES );
		}
		unset( $package ); // Unset the reference so the variable can't be accidentally replaced.

		return apply_filters(
			'wcservices_map_packages_to_wcservices_format',
			$custom_packages,
			$old_custom_packages
		);
	}

	public static function map_packages_to_wcshipping_format( $custom_packages ) {
		$old_custom_packages = $custom_packages;

		foreach ( $custom_packages as &$package ) {
			$package = self::rename_keys( $package, array_flip( self::WCSHIPPING_TO_WCSERVICES_KEY_MAP ) );
			$package = self::map_is_letter_to_type( $package );
			$package = self::unset_keys( $package, self::KEYS_UNUSED_BY_WCSHIPPING );

		}
		unset( $package ); // Unset the reference so the variable can't be accidentally replaced.

		return apply_filters(
			'wcservices_map_packages_to_wcshipping_format',
			$custom_packages,
			$old_custom_packages
		);
	}

	// Rename keys from WCShipping's format to WCS&T's, then unset WCShipping's.
	private static function rename_keys( $package, $key_map ) {
		foreach ( $key_map as $source => $target ) {
			if ( isset( $package[ $source ] ) ) {
				$package[ $target ] = $package[ $source ];
				unset( $package[ $source ] );
			}
		}

		return $package;
	}

	private static function unset_keys( $package, $keys_to_unset ) {
		foreach ( $keys_to_unset as $wcshipping_key ) {
			unset( $package[ $wcshipping_key ] );
		}

		return $package;
	}

	private static function map_type_to_is_letter( $package ) {
		if ( isset( $package['type'] ) ) {
			$package['is_letter'] = 'envelope' === $package['type'];
		}
		unset( $package['type'] );

		return $package;
	}

	private static function map_is_letter_to_type( $package ) {
		if ( isset( $package['is_letter'] ) ) {
			$package['type'] = $package['is_letter'] ? 'envelope' : 'box';
		}
		unset( $package['is_letter'] );

		return $package;
	}
}
