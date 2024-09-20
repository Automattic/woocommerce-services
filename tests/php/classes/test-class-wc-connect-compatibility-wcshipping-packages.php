<?php

/**
 * Tests for WC_Connect_Compatibility_WCShipping_Packages.
 */
class WP_Test_WC_Connect_Compatibility_WCShipping_Packages extends WC_Unit_Test_Case {

	const EXAMPLE_WC_CONNECT_OPTIONS = array(
		'paper_size'          => 'letter',
		'packages'            => array(
			array(
				'box_weight'       => 101,
				'inner_dimensions' => '102 x 103 x 104',
				'is_letter'        => false,
				'is_user_defined'  => true,
				'max_weight'       => 105,
				'name'             => 'WCS&T custom box',
				'outer_dimensions' => '106 x 107 x 108',
			),
			array(
				'box_weight'       => 201,
				'inner_dimensions' => '202 x 203 x 204',
				'is_letter'        => true,
				'is_user_defined'  => true,
				'max_weight'       => 205,
				'name'             => 'WCS&T custom envelope',
				'outer_dimensions' => '206 x 207 x 208',
			),
		),
		'predefined_packages' => array( 'WCS&T predefined package' ),
	);

	const EXAMPLE_WCSHIPPING_OPTIONS = array(
		'paper_size'          => 'legal',
		'packages'            => array(
			array(
				'boxWeight'       => 301,
				'dimensions'      => '302 x 303 x 304',
				'id'              => 305,
				'is_user_defined' => true,
				'maxWeight'       => 306,
				'name'            => 'WCShipping custom box',
				'type'            => 'box',
			),
			array(
				'boxWeight'       => 401,
				'dimensions'      => '402 x 403 x 404',
				'id'              => 405,
				'is_user_defined' => true,
				'maxWeight'       => 406,
				'name'            => 'WCShipping custom envelope',
				'type'            => 'envelope',
			),
			array(
				'boxWeight'                  => 501,
				'box_weight'                 => 502,
				'dimensions'                 => '503 x 504 x 505',
				'id'                         => 506,
				'inner_dimensions'           => '507 x 508 x 509',
				'is_letter'                  => true,
				'isLetter'                   => true,
				'is_user_defined'            => true,
				'maxWeight'                  => 510,
				'max_weight'                 => 511,
				'name'                       => 'WCShipping custom envelope with some leftover fields from WCS&T migration or previous package data formatting',
				'outer_dimensions'           => '512 x 513 x 514',
				'type'                       => 'box',
				'a completely made up field' => 'every day is great at your Junes',
			),
		),
		'predefined_packages' => array( 'WCShipping predefined package' ),
	);

	/**
	 * @inherit
	 */
	public static function set_up_before_class() {
		require_once __DIR__ . '/../../../classes/class-wc-connect-api-client-live.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-service-settings-store.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-logger.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-service-schemas-store.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-package-settings.php';
		require_once __DIR__ . '/../../../classes/class-wc-rest-connect-base-controller.php';
		require_once __DIR__ . '/../../../classes/class-wc-rest-connect-packages-controller.php';
		require_once __DIR__ . '/../../../classes/class-wc-rest-connect-packages-controller.php';
		require_once __DIR__ . '/../../../classes/class-wc-connect-compatibility-wcshipping-packages.php';
	}

	public function setUp(): void {
		parent::setUp();

		// Reset options.
		update_option( 'wc_connect_options', self::EXAMPLE_WC_CONNECT_OPTIONS );
		update_option( 'wcshipping_options', self::EXAMPLE_WCSHIPPING_OPTIONS );

		// Reset hooks.
		remove_filter( 'option_wc_connect_options', array( WC_Connect_Compatibility_WCShipping_Packages::class, 'intercept_packages_read' ) );
		remove_filter( 'option_wc_connect_options', array( WC_Connect_Compatibility_WCShipping_Packages::class, 'intercept_predefined_packages_read' ) );
		remove_action( 'update_option_wc_connect_options', array( WC_Connect_Compatibility_WCShipping_Packages::class, 'intercept_packages_update' ) );
		remove_action( 'update_option_wc_connect_options', array( WC_Connect_Compatibility_WCShipping_Packages::class, 'intercept_predefined_packages_update' ) );
	}

	public function test_it_enables_all_compatibility_features_if_wcshipping_is_active_and_settings_were_migrated_from_wcservices() {
		$this->set_is_wcshipping_active( true );
		$this->set_has_completed_migration( true );

		WC_Connect_Compatibility_WCShipping_Packages::maybe_enable();

		$this->assert_is_rest_controller_registration_hook_registered( true );
		$this->assert_are_option_interception_hooks_registered( true );
	}

	public function test_it_enables_rest_controller_but_not_option_interception_if_wcshipping_is_active_but_settings_were_not_migrated_from_wcservices() {
		$this->set_is_wcshipping_active( true );
		$this->set_has_completed_migration( false );

		WC_Connect_Compatibility_WCShipping_Packages::maybe_enable();

		$this->assert_is_rest_controller_registration_hook_registered( true );
		$this->assert_are_option_interception_hooks_registered( false );
	}

	public function test_it_does_not_enable_anything_if_wcshipping_is_not_active() {
		$this->set_is_wcshipping_active( false );
		$this->set_has_completed_migration( true );

		WC_Connect_Compatibility_WCShipping_Packages::maybe_enable();

		$this->assert_is_rest_controller_registration_hook_registered( false );
		$this->assert_are_option_interception_hooks_registered( false );
	}

	public function test_wc_connect_options_contains_wcshipping_packages_mapped_to_wcservices_format() {
		$this->set_is_wcshipping_active( true );
		$this->set_has_completed_migration( true );

		WC_Connect_Compatibility_WCShipping_Packages::maybe_enable();

		/*
		 * self::EXAMPLE_WCSHIPPING_OPTIONS["packages"] mapped to WCS&T's format and unused keys removed.
		 *
		 * Note the expected result for the item with leftover fields uses the following approach:
		 * - if both a WCShipping key and a WCS&T key is present (perhaps as a result of options migration),
		 *   prefer the WCShipping key and disregard the WCS&T one (e.g. will use "boxWeight");
		 * - for determining package type (box/envelope), if the "type" (current WCShipping approach), "isLetter"
		 *   (deprecated WCShipping approach), and "is_letter" (WCS&T approach) keys are present, it will use the "type"
		 *   key and disregard the rest, even if they are conflicting (hypothetical situation);
		 * - keys that aren't contained in the "KEYS_USED_BY_*" const will be removed.
		 */
		$expected_custom_wcshipping_packages_mapped_to_wcservices_format = array(
			array(
				'box_weight'       => 301,
				'inner_dimensions' => '302 x 303 x 304',
				'is_letter'        => false,
				'is_user_defined'  => true,
				'max_weight'       => 306,
				'name'             => 'WCShipping custom box',
			),
			array(
				'box_weight'       => 401,
				'inner_dimensions' => '402 x 403 x 404',
				'is_letter'        => true,
				'is_user_defined'  => true,
				'max_weight'       => 406,
				'name'             => 'WCShipping custom envelope',
			),
			array(
				'box_weight'       => 501,
				'inner_dimensions' => '503 x 504 x 505',
				'is_letter'        => false,
				'is_user_defined'  => true,
				'max_weight'       => 510,
				'name'             => 'WCShipping custom envelope with some leftover fields from WCS&T migration or previous package data formatting',
			),
		);

		$this->assertEquals(
			array_merge(
				self::EXAMPLE_WC_CONNECT_OPTIONS,
				array(
					'packages'            => $expected_custom_wcshipping_packages_mapped_to_wcservices_format,
					'predefined_packages' => self::EXAMPLE_WCSHIPPING_OPTIONS['predefined_packages'],
				)
			),
			get_option( 'wc_connect_options' )
		);
	}

	public function test_updating_wc_connect_options_updates_packages_in_wcshipping_options_mapped_to_wcshipping_format() {
		$this->set_is_wcshipping_active( true );
		$this->set_has_completed_migration( true );

		WC_Connect_Compatibility_WCShipping_Packages::maybe_enable();

		update_option(
			'wc_connect_options',
			array(
				'foo'                 => 'bar',
				'packages'            => self::EXAMPLE_WC_CONNECT_OPTIONS['packages'],
				'predefined_packages' => self::EXAMPLE_WC_CONNECT_OPTIONS['predefined_packages'],
			)
		);

		/*
		 * The approach to mapping is the same as described in the comment to
		 * test_wc_connect_options_contains_wcshipping_packages_mapped_to_wcservices_format
		 * except here we map a WCS&T custom package to WCShipping custom package format when an update
		 * to "wc_connect_options" is requested.
		 *
		 * @see self::test_wc_connect_options_contains_wcshipping_packages_mapped_to_wcservices_format
		 */
		$expected_custom_wcservices_packages_mapped_to_wcshipping_format = array(
			array(
				'boxWeight'       => 101,
				'dimensions'      => '102 x 103 x 104',
				'is_user_defined' => true,
				'maxWeight'       => 105,
				'name'            => 'WCS&T custom box',
				'type'            => 'box',
			),
			array(
				'boxWeight'       => 201,
				'dimensions'      => '202 x 203 x 204',
				'is_user_defined' => true,
				'maxWeight'       => 205,
				'name'            => 'WCS&T custom envelope',
				'type'            => 'envelope',
			),
		);

		$this->assertEquals(
			array_merge(
				self::EXAMPLE_WCSHIPPING_OPTIONS,
				array(
					'packages'            => $expected_custom_wcservices_packages_mapped_to_wcshipping_format,
					'predefined_packages' => self::EXAMPLE_WC_CONNECT_OPTIONS['predefined_packages'],
				)
			),
			get_option( 'wcshipping_options' )
		);
	}

	public function test_it_does_not_intercept_reads_or_writes_if_it_did_not_enable() {
		$this->set_is_wcshipping_active( false );
		$this->set_has_completed_migration( false );

		// This won't actually enable anything because the conditions above prevent it.
		WC_Connect_Compatibility_WCShipping_Packages::maybe_enable();

		// Read.
		$this->assertEquals( self::EXAMPLE_WC_CONNECT_OPTIONS, get_option( 'wc_connect_options' ) );

		// Write.
		update_option(
			'wc_connect_options',
			array(
				'foo'                 => 'bar',
				'packages'            => self::EXAMPLE_WC_CONNECT_OPTIONS['packages'],
				'predefined_packages' => self::EXAMPLE_WC_CONNECT_OPTIONS['predefined_packages'],
			)
		);
		$this->assertEquals( self::EXAMPLE_WCSHIPPING_OPTIONS, get_option( 'wcshipping_options' ) );
	}

	public function test_adding_a_package_changes_the_return_value_of_wc_connect_options_and_wcshipping_options() {
		$this->set_is_wcshipping_active( true );
		$this->set_has_completed_migration( true );

		WC_Connect_Compatibility_WCShipping_Packages::maybe_enable();

		$wc_connect_options               = get_option( 'wc_connect_options' );
		$wc_connect_options['packages'][] = array(
			'box_weight'       => 601,
			'inner_dimensions' => '602 x 603 x 604',
			'is_letter'        => false,
			'is_user_defined'  => true,
			'max_weight'       => 605,
			'name'             => 'WCS&T new package',
			'outer_dimensions' => '606 x 607 x 608',
		);

		update_option( 'wc_connect_options', $wc_connect_options );

		$this->assertEquals( array( 301, 401, 501, 601 ), array_column( get_option( 'wc_connect_options' )['packages'], 'box_weight' ) );
		$this->assertEquals( array( 301, 401, 501, 601 ), array_column( get_option( 'wcshipping_options' )['packages'], 'boxWeight' ) );
	}

	public function test_adding_a_package_by_updating_wc_connect_options_does_not_change_the_actual_db_field() {
		$this->set_is_wcshipping_active( true );
		$this->set_has_completed_migration( true );

		WC_Connect_Compatibility_WCShipping_Packages::maybe_enable();

		$wc_connect_options                          = get_option( 'wc_connect_options' );
		$wc_connect_options['packages'][]            = array(
			'box_weight'       => 601,
			'inner_dimensions' => '602 x 603 x 604',
			'is_letter'        => false,
			'is_user_defined'  => true,
			'max_weight'       => 605,
			'name'             => 'WCS&T new package',
			'outer_dimensions' => '606 x 607 x 608',
		);
		$wc_connect_options['predefined_packages'][] = 'foo';

		update_option( 'wc_connect_options', $wc_connect_options );

		$wc_connect_options = get_option( 'wc_connect_options' );
		$this->assertEquals( array( 301, 401, 501, 601 ), array_column( $wc_connect_options['packages'], 'box_weight' ) );
		$this->assertEquals( array( 'WCShipping predefined package', 'foo' ), $wc_connect_options['predefined_packages'] );

		// Deregister the "read" hook so we can access the actual values in the DB without interception.
		remove_filter( 'option_wc_connect_options', array( WC_Connect_Compatibility_WCShipping_Packages::class, 'intercept_packages_read' ) );
		remove_filter( 'option_wc_connect_options', array( WC_Connect_Compatibility_WCShipping_Packages::class, 'intercept_predefined_packages_read' ) );

		// Assert the original `wc_connect_options` remain unchanged throughout the entire test.
		$wc_connect_options = get_option( 'wc_connect_options' );
		$this->assertEquals( array( 101, 201 ), array_column( $wc_connect_options['packages'], 'box_weight' ) );
		$this->assertEquals( array( 'WCS&T predefined package' ), $wc_connect_options['predefined_packages'] );
	}

	public function set_is_wcshipping_active( $is_active ) {
		update_option( 'active_plugins', $is_active ? array( 'woocommerce-shipping/woocommerce-shipping.php' ) : array() );
	}

	public function set_has_completed_migration( $is_migration_completed ) {
		update_option( 'wcshipping_migration_state', $is_migration_completed ? WC_Connect_Compatibility_WCShipping_Packages::WCSHIP_DATA_MIGRATION_COMPLETED : '0' );
	}

	public function assert_are_option_interception_hooks_registered( $expected_registration_status ) {
		$this->assertEquals( $expected_registration_status, has_filter( 'option_wc_connect_options', array( WC_Connect_Compatibility_WCShipping_Packages::class, 'intercept_packages_read' ) ) );
		$this->assertEquals( $expected_registration_status, has_filter( 'option_wc_connect_options', array( WC_Connect_Compatibility_WCShipping_Packages::class, 'intercept_predefined_packages_read' ) ) );
		$this->assertEquals( $expected_registration_status, has_filter( 'pre_update_option_wc_connect_options', array( WC_Connect_Compatibility_WCShipping_Packages::class, 'intercept_packages_update' ) ) );
		$this->assertEquals( $expected_registration_status, has_filter( 'pre_update_option_wc_connect_options', array( WC_Connect_Compatibility_WCShipping_Packages::class, 'intercept_predefined_packages_update' ) ) );
	}

	public function assert_is_rest_controller_registration_hook_registered( $expected_registration_status ) {
		$this->assertEquals(
			$expected_registration_status,
			has_filter(
				'wcservices_rest_api_init',
				array(
					WC_Connect_Compatibility_WCShipping_Packages::class,
					'register_wcshipping_compatibility_rest_controller',
				)
			)
		);
	}
}
