<?php

class WP_Test_WC_Connect_NUX extends WC_Unit_Test_Case {

	public static function setupBeforeClass() {
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-nux.php' );
	}

	public function test_get_banner_type_to_display_dev_jp() {
		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_DEV,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner' => false,
		) ), false );

		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_DEV,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner' => false,
		) ), false );

		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_DEV,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner' => true,
		) ), false );

		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_DEV,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner' => true,
		) ), false );
	}

	public function test_get_banner_type_to_display_no_jp_cxn_without_tos_acceptance() {
		// before going through connection
		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_UNINSTALLED,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner' => false,
		) ), 'before_jetpack_connection' );

		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_INSTALLED,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner' => false,
		) ), 'before_jetpack_connection' );

		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_ACTIVATED,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner' => false,
		) ), 'before_jetpack_connection' );

		// after going through connection, TOS was never accepted
		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_CONNECTED,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner' => true,
		) ), 'after_jetpack_connection' );
	}

	public function test_get_banner_type_to_display_no_jp_cxn_with_tos_acceptance() {
		// before going through connection
		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_UNINSTALLED,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner' => false,
		) ), 'before_jetpack_connection' );

		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_INSTALLED,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner' => false,
		) ), 'before_jetpack_connection' );

		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_ACTIVATED,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner' => false,
		) ), 'before_jetpack_connection' );

		// after going through connection, TOS was already previously accepted
		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_CONNECTED,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner' => true,
		) ), 'after_jetpack_connection' );
	}

	public function test_get_banner_type_to_display_with_jp_cxn_without_tos_acceptance() {
		// Jetpack is already connected, TOS was not yet accepted
		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_CONNECTED,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner' => false,
		) ), 'tos_only_banner' );
	}

	public function test_get_banner_type_to_display_with_jp_cxn_with_tos_acceptance() {
		// Jetpack is already connected, TOS is already accepted
		// did not show before connection banner
		$this->assertEquals( WC_Connect_Nux::get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_CONNECTED,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner' => false,
		) ), false );
	}
}
