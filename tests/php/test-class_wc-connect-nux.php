<?php

class WP_Test_WC_Connect_NUX extends WC_Unit_Test_Case {

	public static function set_up_before_class() {
		require_once __DIR__ . '/../../classes/class-wc-connect-nux.php';
	}

	public function test_get_banner_type_to_display_dev_jp() {
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status' => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
				)
			),
			false
		);

		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => null, // irrelevant here, TOS is accepted (DEV)
					'should_display_after_cxn_banner' => false,
				)
			),
			false
		);

		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => null, // irrelevant here, TOS will be accepted in "after" banner
					'should_display_after_cxn_banner' => true,
				)
			),
			'after_jetpack_connection'
		);

		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => false,
					'should_display_after_cxn_banner' => true,
				)
			),
			'after_jetpack_connection'
		);

		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_OFFLINE_MODE,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
				)
			),
			'tos_only_banner'
		);
	}

	public function test_get_banner_type_to_display_no_jp_cxn_without_tos_acceptance() {
		// before going through connection
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_NOT_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => false, // no master user, not DEV
					'should_display_after_cxn_banner' => false,
				)
			),
			'before_jetpack_connection'
		);

		// after going through connection, TOS was never accepted
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => null, // irrelevant here, TOS will be accepted in "after" banner
					'should_display_after_cxn_banner' => true,
				)
			),
			'after_jetpack_connection'
		);
	}

	public function test_get_banner_type_to_display_no_jp_cxn_with_tos_acceptance() {
		// before going through connection
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_NOT_CONNECTED,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
				)
			),
			'before_jetpack_connection'
		);

		// after going through connection, TOS was already previously accepted
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => true,
				)
			),
			'after_jetpack_connection'
		);
	}

	public function test_get_banner_type_to_display_with_jp_cxn_without_tos_acceptance() {
		// Jetpack is already connected, TOS was not yet accepted
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
				)
			),
			'tos_only_banner'
		);

		// Regression test for changing order of "tos accepted" and "should display after cxn banner"
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => true,
				)
			),
			'after_jetpack_connection'
		);
	}

	public function test_get_banner_type_to_display_with_jp_cxn_without_tos_acceptance_non_owner() {
		// Jetpack is already connected, TOS was not yet accepted, user is not the connection owner
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => false,
					'can_accept_tos'                  => false,
					'should_display_after_cxn_banner' => false,
				)
			),
			'tos_informational_banner'
		);
	}

	public function test_get_banner_type_to_display_with_jp_cxn_with_tos_acceptance() {
		// Jetpack is already connected, TOS is already accepted
		// did not show before connection banner
		$this->assertEquals(
			WC_Connect_Nux::get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_CONNECTED,
					'tos_accepted'                    => true,
					'can_accept_tos'                  => true,
					'should_display_after_cxn_banner' => false,
				)
			),
			false
		);
	}

	/**
	 * The banner gate does not touch instance state, so build the object without
	 * running the constructor to avoid pulling in the tracks and shipping label deps.
	 */
	private function get_nux() {
		$reflection = new ReflectionClass( 'WC_Connect_Nux' );

		return $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Build a minimal stand-in for WP_Screen carrying just the properties the gate reads.
	 *
	 * @param string $base Screen base.
	 * @return stdClass
	 */
	private function make_screen( $base ) {
		$screen       = new stdClass();
		$screen->base = $base;
		$screen->id   = $base;

		return $screen;
	}

	/**
	 * Clear the request globals the screen gate reads between tests.
	 */
	public function tear_down() {
		unset( $_GET['tab'], $_GET['section'] );

		parent::tear_down();
	}

	/**
	 * The banner renders on WooCommerce » Settings » Tax.
	 */
	public function test_should_display_nux_notice_on_the_tax_settings_tab() {
		$_GET['tab'] = 'tax';

		$this->assertTrue(
			$this->get_nux()->should_display_nux_notice_on_screen(
				$this->make_screen( 'woocommerce_page_wc-settings' )
			)
		);
	}

	/**
	 * Every section of the Tax tab, including the rate tables, still qualifies.
	 */
	public function test_should_display_nux_notice_on_every_section_of_the_tax_settings_tab() {
		$_GET['tab'] = 'tax';

		foreach ( array( 'standard', 'reduced-rate', 'zero-rate' ) as $section ) {
			$_GET['section'] = $section;

			$this->assertTrue(
				$this->get_nux()->should_display_nux_notice_on_screen(
					$this->make_screen( 'woocommerce_page_wc-settings' )
				),
				"Expected the banner to be allowed on the {$section} tax section."
			);
		}
	}

	/**
	 * Other WooCommerce settings tabs no longer show the banner.
	 */
	public function test_should_not_display_nux_notice_on_other_settings_tabs() {
		foreach ( array( 'general', 'products', 'shipping', 'checkout', 'advanced' ) as $tab ) {
			$_GET['tab'] = $tab;

			$this->assertFalse(
				$this->get_nux()->should_display_nux_notice_on_screen(
					$this->make_screen( 'woocommerce_page_wc-settings' )
				),
				"Expected the banner to be suppressed on the {$tab} settings tab."
			);
		}
	}

	/**
	 * The settings landing page defaults to General, so it must not show the banner.
	 */
	public function test_should_not_display_nux_notice_on_settings_without_a_tab() {
		$this->assertFalse(
			$this->get_nux()->should_display_nux_notice_on_screen(
				$this->make_screen( 'woocommerce_page_wc-settings' )
			)
		);
	}

	/**
	 * The Plugins page stays a connection surface regardless of any tab in the URL.
	 */
	public function test_should_display_nux_notice_on_the_plugins_page() {
		$this->assertTrue(
			$this->get_nux()->should_display_nux_notice_on_screen( $this->make_screen( 'plugins' ) )
		);

		// The Plugins page carries no tab, and an unrelated one must not suppress it.
		$_GET['tab'] = 'general';

		$this->assertTrue(
			$this->get_nux()->should_display_nux_notice_on_screen( $this->make_screen( 'plugins' ) )
		);
	}

	/**
	 * Product, order, extension and dashboard screens no longer show the banner.
	 */
	public function test_should_not_display_nux_notice_on_non_tax_screens() {
		$_GET['tab'] = 'tax';

		$screens = array(
			'edit',
			'post',
			'woocommerce_page_wc-addons',
			'woocommerce_page_wc-orders',
			'dashboard',
		);

		foreach ( $screens as $base ) {
			$this->assertFalse(
				$this->get_nux()->should_display_nux_notice_on_screen( $this->make_screen( $base ) ),
				"Expected the banner to be suppressed on the {$base} screen."
			);
		}
	}

	/**
	 * A missing screen is handled without a fatal.
	 */
	public function test_should_not_display_nux_notice_without_a_screen() {
		$_GET['tab'] = 'tax';

		$this->assertFalse( $this->get_nux()->should_display_nux_notice_on_screen( null ) );
	}
}
