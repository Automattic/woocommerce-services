<?php

class WP_Test_WC_Connect_NUX extends WC_Unit_Test_Case {

	public static function setupBeforeClass() {
		require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-nux.php' );
	}
	
	public function get_mock_nux( $can_accept_tos = false ) {
		$nux = $this->getMockBuilder( 'WC_Connect_Nux' )
			->disableOriginalConstructor()
			->setMethods( array( 'can_accept_tos' ) )
			->getMock();

		$nux
			->expects( $this->any() )
			->method( 'can_accept_tos' )
			->will( $this->returnValue( $can_accept_tos ) );
		
		return $nux;
	}

	public function test_get_banner_type_to_display_dev_jp() {
		$nux = $this->get_mock_nux( false );

		$this->assertEquals(
			$nux->get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_DEV,
					'tos_accepted'                    => false,
					'should_display_after_cxn_banner' => false,
				)
			), false
		);

		$this->assertEquals(
			$nux->get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_DEV,
					'tos_accepted'                    => true,
					'should_display_after_cxn_banner' => false,
				)
			), false
		);

		$this->assertEquals(
			$nux->get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_DEV,
					'tos_accepted'                    => false,
					'should_display_after_cxn_banner' => true,
				)
			), 'after_jetpack_connection'
		);

		$this->assertEquals(
			$nux->get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_DEV,
					'tos_accepted'                    => true,
					'should_display_after_cxn_banner' => true,
				)
			), 'after_jetpack_connection'
		);
	}

	public function test_get_banner_type_to_display_dev_jp_can_accept_tos() {
		$nux = $this->get_mock_nux( true );

		$this->assertEquals(
			$nux->get_banner_type_to_display(
				array(
					'jetpack_connection_status'       => WC_Connect_Nux::JETPACK_DEV,
					'tos_accepted'                    => false,
					'should_display_after_cxn_banner' => false,
				)
			), 'tos_only_banner'
		);
	}

	public function test_get_banner_type_to_display_no_jp_cxn_without_tos_acceptance() {
		$nux = $this->get_mock_nux( false );

		// before going through connection
		$this->assertEquals( $nux->get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_NOT_INSTALLED,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner'        => false,
		) ), 'before_jetpack_connection' );

		$this->assertEquals( $nux->get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_INSTALLED_NOT_ACTIVATED,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner'        => false,
		) ), 'before_jetpack_connection' );

		$this->assertEquals( $nux->get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_ACTIVATED_NOT_CONNECTED,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner'        => false,
		) ), 'before_jetpack_connection' );

		// after going through connection, TOS was never accepted
		$this->assertEquals( $nux->get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_CONNECTED,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner'        => true,
		) ), 'after_jetpack_connection' );
	}

	public function test_get_banner_type_to_display_no_jp_cxn_with_tos_acceptance() {
		$nux = $this->get_mock_nux( true );

		// before going through connection
		$this->assertEquals( $nux->get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_NOT_INSTALLED,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner'        => false,
		) ), 'before_jetpack_connection' );

		$this->assertEquals( $nux->get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_INSTALLED_NOT_ACTIVATED,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner'        => false,
		) ), 'before_jetpack_connection' );

		$this->assertEquals( $nux->get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_ACTIVATED_NOT_CONNECTED,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner'        => false,
		) ), 'before_jetpack_connection' );

		// after going through connection, TOS was already previously accepted
		$this->assertEquals( $nux->get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_CONNECTED,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner'        => true,
		) ), 'after_jetpack_connection' );
	}

	public function test_get_banner_type_to_display_with_jp_cxn_without_tos_acceptance() {
		$nux = $this->get_mock_nux( true );

		// Jetpack is already connected, TOS was not yet accepted
		$this->assertEquals( $nux->get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_CONNECTED,
			'tos_accepted'                           => false,
			'should_display_after_cxn_banner'        => false,
		) ), 'tos_only_banner' );
	}

	public function test_get_banner_type_to_display_with_jp_cxn_with_tos_acceptance() {
		$nux = $this->get_mock_nux( true );

		// Jetpack is already connected, TOS is already accepted
		// did not show before connection banner
		$this->assertEquals( $nux->get_banner_type_to_display( array(
			'jetpack_connection_status'              => WC_Connect_Nux::JETPACK_CONNECTED,
			'tos_accepted'                           => true,
			'should_display_after_cxn_banner'        => false,
		) ), false );
	}
}
