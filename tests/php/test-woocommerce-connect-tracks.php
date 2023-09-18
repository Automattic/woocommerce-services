<?php

abstract class WP_Test_WC_Connect_Tracks extends WC_Unit_Test_Case {
	/** @var  WC_Connect_Tracks */
	protected $tracks;
	protected $logger;

	public static function set_up_before_class() {
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-tracks.php';
	}

	public function set_up() {
		$this->logger = $this->getMockBuilder( 'WC_Connect_Logger' )
			->disableOriginalConstructor()
			->setMethods( array( 'log' ) )
			->getMock();

		$this->tracks = new WC_Connect_Tracks( $this->logger, __FILE__ );
		$this->tracks->init();
	}
}

class WP_Test_WC_Connect_Tracks_No_Jetpack extends WP_Test_WC_Connect_Tracks {

	public function test_no_jetpack() {
		// Only test WC < 7.9.0.
		// Because WC >= 7.9.0 has JetPack built-in on the plugin.
		if ( version_compare( WC()->version, '7.9.0', '<' ) ) {
			$this->logger->expects( $this->once() )
				->method( 'log' )
				->with(
					$this->stringContains( 'Error. jetpack_tracks_record_event is not defined.' )
				);

			$record = $this->tracks->opted_out();
			$this->assertNull( $record );
		}
	}

}

class WP_Test_WC_Connect_Tracks_With_Jetpack extends WP_Test_WC_Connect_Tracks {
	protected $jetpack_options;

	public static function set_up_before_class() {
		parent::set_up_before_class();

		require_once dirname( __FILE__ ) . '/mocks/jetpack.php';
	}

	public function set_up() {
		$this->jetpack_options = $this->getMockBuilder( 'Jetpack_Options' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_option' ) )
			->getMock();

		$this->logger = $this->getMockBuilder( 'WC_Connect_Logger' )
			->disableOriginalConstructor()
			->setMethods( array( 'log' ) )
			->getMock();

		$this->tracks = new WC_Connect_Tracks( $this->logger, __FILE__ );
		$this->tracks->init();
	}

	public function test_record_user_event() {
		global $mock_recorded_tracks_events;
		$mock_recorded_tracks_events = array();

		$this->jetpack_options->expects( $this->any() )
			->method( 'get_option' )
			->with( 'id' )
			->willReturn( '12345' );

		$this->logger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'woocommerceconnect_test' )
			);

		$this->tracks->record_user_event( 'test' );
		// $record will contain the args received by jetpack_tracks_record_event
		$record = $mock_recorded_tracks_events[0];
		$this->assertInstanceOf( 'WP_User', $record[0] );
		$this->assertEquals( 'woocommerceconnect_test', $record[1] );
		$this->assertIsArray( $record[2] );
		$this->assertArrayHasKey( '_via_ua', $record[2] );
		$this->assertArrayHasKey( '_via_ip', $record[2] );
		$this->assertArrayHasKey( '_lg', $record[2] );
		$this->assertArrayHasKey( 'blog_url', $record[2] );
		$this->assertArrayHasKey( 'blog_id', $record[2] );
		$this->assertArrayHasKey( 'jetpack_version', $record[2] );
		$this->assertArrayHasKey( 'wc_version', $record[2] );
		$this->assertArrayHasKey( 'wp_version', $record[2] );
	}

	public function test_opted_in() {
		global $mock_recorded_tracks_events;
		$mock_recorded_tracks_events = array();

		$this->jetpack_options->expects( $this->any() )
			->method( 'get_option' )
			->with( 'id' )
			->willReturn( '12345' );

		$this->logger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'woocommerceconnect_opted_in' )
			);

		$this->tracks->opted_in();
		// $record will contain the args received by jetpack_tracks_record_event
		$record = $mock_recorded_tracks_events[0];
		$this->assertInstanceOf( 'WP_User', $record[0] );
		$this->assertEquals( 'woocommerceconnect_opted_in', $record[1] );
		$this->assertIsArray( $record[2] );
		$this->assertArrayHasKey( '_via_ua', $record[2] );
		$this->assertArrayHasKey( '_via_ip', $record[2] );
		$this->assertArrayHasKey( '_lg', $record[2] );
		$this->assertArrayHasKey( 'blog_url', $record[2] );
		$this->assertArrayHasKey( 'blog_id', $record[2] );
		$this->assertArrayHasKey( 'jetpack_version', $record[2] );
		$this->assertArrayHasKey( 'wc_version', $record[2] );
		$this->assertArrayHasKey( 'wp_version', $record[2] );
	}

	public function test_opted_out() {
		global $mock_recorded_tracks_events;
		$mock_recorded_tracks_events = array();

		$this->jetpack_options->expects( $this->any() )
			->method( 'get_option' )
			->with( 'id' )
			->willReturn( '12345' );

		$this->logger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'woocommerceconnect_opted_out' )
			);

		// $record will contain the args received by jetpack_tracks_record_event
		$this->tracks->opted_out();
		$record = $mock_recorded_tracks_events[0];
		$this->assertInstanceOf( 'WP_User', $record[0] );
		$this->assertEquals( 'woocommerceconnect_opted_out', $record[1] );
		$this->assertIsArray( $record[2] );
		$this->assertArrayHasKey( '_via_ua', $record[2] );
		$this->assertArrayHasKey( '_via_ip', $record[2] );
		$this->assertArrayHasKey( '_lg', $record[2] );
		$this->assertArrayHasKey( 'blog_url', $record[2] );
		$this->assertArrayHasKey( 'blog_id', $record[2] );
		$this->assertArrayHasKey( 'jetpack_version', $record[2] );
		$this->assertArrayHasKey( 'wc_version', $record[2] );
		$this->assertArrayHasKey( 'wp_version', $record[2] );
	}

	public function test_saved_service_settings() {

		$this->logger->expects( $this->exactly( 2 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->stringContains( 'woocommerceconnect_saved_service_settings' ),
				),
				array(
					$this->stringContains( 'woocommerceconnect_saved_usps_settings' ),
				)
			);

		$this->tracks->saved_service_settings( 'usps' );
	}

	public function test_shipping_zone_method_added() {

		$this->logger->expects( $this->exactly( 2 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->stringContains( 'woocommerceconnect_shipping_zone_method_added' ),
				),
				array(
					$this->stringContains( 'woocommerceconnect_shipping_zone_usps_added' ),
				)
			);

		do_action( 'wc_connect_shipping_zone_method_added', 2, 'usps', 3 );
	}

	public function test_shipping_zone_method_deleted() {
		$this->logger->expects( $this->exactly( 2 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->stringContains( 'woocommerceconnect_shipping_zone_method_deleted' ),
				),
				array(
					$this->stringContains( 'woocommerceconnect_shipping_zone_canada_post_deleted' ),
				)
			);

		do_action( 'wc_connect_shipping_zone_method_deleted', 2, 'canada_post', 3 );
	}

	public function test_shipping_zone_method_enabled() {
		$this->logger->expects( $this->exactly( 2 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->stringContains( 'woocommerceconnect_shipping_zone_method_enabled' ),
				),
				array(
					$this->stringContains( 'woocommerceconnect_shipping_zone_usps_enabled' ),
				)
			);

		do_action( 'wc_connect_shipping_zone_method_status_toggled', 2, 'usps', 3, true );
	}

	public function test_shipping_zone_method_disabled() {

		$this->logger->expects( $this->exactly( 2 ) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->stringContains( 'woocommerceconnect_shipping_zone_method_disabled' ),
				),
				array(
					$this->stringContains( 'woocommerceconnect_shipping_zone_usps_disabled' ),
				)
			);

		do_action( 'wc_connect_shipping_zone_method_status_toggled', 2, 'usps', 3, false );
	}

}
