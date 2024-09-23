<?php

/**
 * Replaces saved package data with WooCommerce Shipping's if it is active.
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

	/**
	 * Mapping of WCShipping keys => WCS&T keys.
	 *
	 * @var array
	 */
	const WCSHIPPING_TO_WCSERVICES_KEY_MAP = array(
		'boxWeight'  => 'box_weight',
		'dimensions' => 'inner_dimensions',
		'maxWeight'  => 'max_weight',
	);

	/**
	 * Keys that are allowed in packages mapped to the WCS&T package data format.
	 *
	 * Other keys will be removed.
	 *
	 * @var array
	 */
	const KEYS_USED_BY_WCSERVICES = array(
		'box_weight',
		'inner_dimensions',
		'is_letter',
		'is_user_defined',
		'max_weight',
		'name',
	);

	/**
	 * Keys that are allowed in packages mapped to the WCShipping package data format.
	 *
	 * Other keys will be removed.
	 *
	 * @var array
	 */
	const KEYS_USED_BY_WCSHIPPING = array(
		'boxWeight',
		'dimensions',
		'id',
		'is_user_defined',
		'maxWeight',
		'name',
		'type',
	);

	/**
	 * Registers all, some, or no hooks based on store configuration.
	 *
	 * @return void
	 */
	public static function maybe_enable() {
		// Don't do anything if WooCommerce Shipping is not active.
		if ( ! WC_Connect_Loader::is_wc_shipping_activated() ) {
			return;
		}

		self::register_rest_controller_hooks();

		$is_migration_to_wcshipping_completed = self::WCSHIP_DATA_MIGRATION_COMPLETED === (int) get_option( 'wcshipping_migration_state' );
		if ( $is_migration_to_wcshipping_completed ) {
			self::register_option_overwriting_hooks();
		}
	}

	/**
	 * Enqueue REST controller registration after WCS&T has finished initializing its other controllers.
	 *
	 * @return void
	 */
	public static function register_rest_controller_hooks() {
		add_action( 'wcservices_rest_api_init', array( self::class, 'register_wcshipping_compatibility_rest_controller' ) );
	}

	/**
	 * Registers hooks intercepting reads/writes to "wc_connect_options".
	 *
	 * This is done to replace the keys "packages" and "predefined_packages" with values from WCShipping's options
	 * after doing some mapping.
	 *
	 * @return void
	 */
	public static function register_option_overwriting_hooks() {
		// Intercept reads of "wc_connect_options[packages]" and "wc_connect_options[predefined_packages]".
		add_filter( 'option_wc_connect_options', array( self::class, 'intercept_packages_read' ) );
		add_filter( 'option_wc_connect_options', array( self::class, 'intercept_predefined_packages_read' ) );

		// Intercept updates to "wc_connect_options[packages]" and "wc_connect_options[predefined_packages]".
		add_action( 'pre_update_option_wc_connect_options', array( self::class, 'intercept_packages_update' ), 10, 2 );
		add_action( 'pre_update_option_wc_connect_options', array( self::class, 'intercept_predefined_packages_update' ), 10, 2 );
	}

	/**
	 * Replaces `wc_connect_options[packages]` with mapped values from `wcshipping_options[packages]`.
	 *
	 * Leaves the rest of `wc_connect_options` intact.
	 *
	 * @param mixed $wc_connect_options "wc_connect_options" value from the WP options table.
	 *
	 * @return mixed
	 */
	public static function intercept_packages_read( $wc_connect_options ) {
		$wcshipping_options = get_option( 'wcshipping_options' );

		if ( is_array( $wcshipping_options ) && isset( $wcshipping_options['packages'] ) ) {
			$wc_connect_options['packages'] = self::map_packages_to_wcservices_format( $wcshipping_options['packages'] );
		}

		return $wc_connect_options;
	}

	/**
	 * Replaces `wc_connect_options[predefined_packages]` with values from `wcshipping_options[predefined_packages]`.
	 *
	 * Leaves the rest of `wc_connect_options` intact.
	 *
	 * @param mixed $wc_connect_options "wc_connect_options" value from the WP options table.
	 *
	 * @return mixed
	 */
	public static function intercept_predefined_packages_read( $wc_connect_options ) {
		$wcshipping_options = get_option( 'wcshipping_options' );

		if ( is_array( $wcshipping_options ) && isset( $wcshipping_options['predefined_packages'] ) ) {
			$wc_connect_options['predefined_packages'] = $wcshipping_options['predefined_packages'];
		}

		return $wc_connect_options;
	}

	/**
	 * Saves the mapped value of `wc_connect_options[packages]` to `wcshipping_options[packages]`.
	 *
	 * Reverts `wc_connect_options[packages]` to old value so that only the packages
	 * in `wcshipping_options` get updated.
	 *
	 * Leaves the rest of `wcshipping_options` intact.
	 *
	 * @param mixed $value New value for "wc_connect_options" to extract packages from.
	 * @param mixed $old_value Old value of "wc_connect_options".
	 *
	 * @return array `$value` with the `packages` field reverted to current DB value to prevent updating.
	 */
	public static function intercept_packages_update( $value, $old_value ) {
		$wcshipping_options = get_option( 'wcshipping_options' );

		if ( ! empty( $value['packages'] ) ) {
			$wcshipping_options['packages'] = self::map_packages_to_wcshipping_format( $value['packages'] );
		} else {
			$wcshipping_options['packages'] = array();
		}

		update_option( 'wcshipping_options', $wcshipping_options );

		/*
		 * Prevent update of WCS&T's packages so that only `wcshipping_options` get updated.
		 */
		$value['packages'] = $old_value['packages'];

		return $value;
	}

	/**
	 * Saves the mapped value of `wc_connect_options[predefined_packages]` to `wcshipping_options[predefined_packages]`.
	 *
	 * Reverts `wc_connect_options[predefined_packages]` to old value so that only the predefined packages
	 * in `wcshipping_options` get updated.
	 *
	 * Leaves the rest of `wcshipping_options` intact.
	 *
	 * @param mixed $value New value for "wc_connect_options" to extract predefined packages from.
	 * @param mixed $old_value Old value of "wc_connect_options".
	 *
	 * @return array `$value` with the `predefined_packages` field reverted to current DB value to prevent updating.
	 */
	public static function intercept_predefined_packages_update( $value, $old_value ) {
		$wcshipping_options = get_option( 'wcshipping_options' );

		if ( ! empty( $value['predefined_packages'] ) ) {
			$wcshipping_options['predefined_packages'] = $value['predefined_packages'];
		} else {
			$wcshipping_options['predefined_packages'] = array();
		}

		update_option( 'wcshipping_options', $wcshipping_options );

		/*
		 * Prevent update of WCS&T's predefined packages so that only `wcshipping_options` get updated.
		 */
		$value['predefined_packages'] = $old_value['predefined_packages'];

		return $value;
	}

	/**
	 * Register a REST controller that reads "wc_connect_options".
	 *
	 * We do this because if WCShipping is active, it registers its own controller under /wc/v1/connect/packages
	 * that accesses "wcshipping_options". For the purpose of the WCS&T settings page, we still want the page
	 * accessing `wc_connect_options` that we'll possibly overwrite with the option read/write-intercepting filters
	 * if migration of options from WCS&T to WCShipping has been completed.
	 *
	 * This is so that we can always modify the value of "wc_connect_options" but leave the value of
	 * "wcshipping_options" intact.
	 *
	 * If migration has been completed, the controller will overwrite the value of "wc_connect_options[packages]" with
	 * WCShipping's packages.
	 *
	 * If migration hasn't been completed, it will return the value of "wc_connect_options[packages]" with no changes.
	 *
	 * @see self::register_option_overwriting_hooks
	 *
	 * @param WC_Connect_Loader $loader WCS&T's main class.
	 */
	public static function register_wcshipping_compatibility_rest_controller( WC_Connect_Loader $loader ) {
		require_once __DIR__ . '/class-wc-rest-connect-wcshipping-compatibility-packages-controller.php';
		$rest_wcshipping_package_compatibility_controller = new WC_REST_Connect_WCShipping_Compatibility_Packages_Controller(
			$loader->get_api_client(),
			$loader->get_service_settings_store(),
			$loader->get_logger(),
			$loader->get_service_schemas_store()
		);
		$rest_wcshipping_package_compatibility_controller->register_routes();
	}

	/**
	 * Maps package data from WCShipping's to WCS&T's format.
	 *
	 * @param array $custom_packages The custom packages to map from WCShipping's to WCS&T's format.
	 *
	 * @return array
	 */
	public static function map_packages_to_wcservices_format( $custom_packages ) {
		$old_custom_packages = $custom_packages;

		foreach ( $custom_packages as &$package ) {
			$package = self::rename_keys( $package, self::WCSHIPPING_TO_WCSERVICES_KEY_MAP );
			$package = self::map_type_to_is_letter( $package );
			$package = self::unset_unused_keys( $package, self::KEYS_USED_BY_WCSERVICES );
		}

		return apply_filters(
			'wcservices_map_packages_to_wcservices_format',
			$custom_packages,
			$old_custom_packages
		);
	}

	/**
	 * Maps package data from WCS&T's to WCShipping's format.
	 *
	 * @param array $custom_packages The custom packages to map from WCS&T's to WCShipping's format.
	 *
	 * @return array
	 */
	public static function map_packages_to_wcshipping_format( $custom_packages ) {
		$old_custom_packages = $custom_packages;

		foreach ( $custom_packages as &$package ) {
			$package = self::rename_keys( $package, array_flip( self::WCSHIPPING_TO_WCSERVICES_KEY_MAP ) );
			$package = self::map_is_letter_to_type( $package );
			$package = self::unset_unused_keys( $package, self::KEYS_USED_BY_WCSHIPPING );
		}

		return apply_filters(
			'wcservices_map_packages_to_wcshipping_format',
			$custom_packages,
			$old_custom_packages
		);
	}

	/**
	 * Renames keys according to provided key map then unsets the original keys.
	 *
	 * @param array $package Package data.
	 * @param array $key_map Mapping to follow.
	 *
	 * @return array
	 */
	private static function rename_keys( $package, $key_map ) {
		foreach ( $key_map as $source => $target ) {
			if ( isset( $package[ $source ] ) ) {
				$package[ $target ] = $package[ $source ];
				unset( $package[ $source ] );
			}
		}

		return $package;
	}

	/**
	 * Unsets keys that aren't in `$allowed_keys`.
	 *
	 * @param array $package Package data.
	 * @param array $allowed_keys Keys that will be left in the array, if present. Other keys are unset.
	 *
	 * @return array
	 */
	private static function unset_unused_keys( $package, $allowed_keys ) {
		return array_intersect_key( $package, array_flip( $allowed_keys ) );
	}

	/**
	 * Maps a package's "type" prop ("box"/"envelope") to "is_letter" (true/false).
	 *
	 * "type" is the format used by WCShipping.
	 * "is_letter" is the format used by WCS&T.
	 *
	 * @param array $package Package data.
	 *
	 * @return array
	 */
	private static function map_type_to_is_letter( $package ) {
		if ( isset( $package['type'] ) ) {
			$package['is_letter'] = 'envelope' === $package['type'];
		}
		unset( $package['type'] );

		return $package;
	}

	/**
	 * Maps a package's "is_letter" prop (true/false) to "type" ("box"/"envelope").
	 *
	 * "type" is the format used by WCShipping.
	 * "is_letter" is the format used by WCS&T.
	 *
	 * @param array $package Package data.
	 *
	 * @return array
	 */
	private static function map_is_letter_to_type( $package ) {
		if ( isset( $package['is_letter'] ) ) {
			$package['type'] = $package['is_letter'] ? 'envelope' : 'box';
		}
		unset( $package['is_letter'] );

		return $package;
	}
}
