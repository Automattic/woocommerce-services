<?php

abstract class WP_Test_WC_Connect_Tracks extends WC_Unit_Test_Case {
	protected $tracks;
	protected $logger;

	public static function setupBeforeClass() {
		$loader = new WC_Connect_Loader();
		$loader->load_dependencies();
	}

	public function setUp() {
		$this->logger = $this->getMockBuilder( 'WC_Connect_Logger' )
			->disableOriginalConstructor()
			->setMethods( array( 'log' ) )
			->getMock();

		$this->tracks = new WC_Connect_Tracks( $this->logger );
	}
}

class WP_Test_WC_Connect_Tracks_No_Jetpack extends WP_Test_WC_Connect_Tracks {

	public function test_no_jetpack() {
		$this->logger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'Error' ),
				$this->anything()
		);
		$record = $this->tracks->opted_out();
		$this->assertNull( $record );
	}

}

class WP_Test_WC_Connect_Tracks_With_Jetpack extends WP_Test_WC_Connect_Tracks {

	public static function setupBeforeClass() {
		parent::setupBeforeClass();
		require_once( dirname( __FILE__ ) . '/mocks/jetpack.php' );
	}

	public function test_record_user_event() {
		// $record will contain the args received by jetpack_tracks_record_event

		$this->logger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'woocommerceconnect_test' ),
				$this->anything()
			);

		$record = $this->tracks->record_user_event( 'test' );
		$this->assertInstanceOf( 'WP_User', $record[0] );
		$this->assertEquals( 'woocommerceconnect_test', $record[1] );
		$this->assertInternalType( 'array', $record[2] );
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

		$this->logger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'woocommerceconnect_opted_in' ),
				$this->anything()
			);

		// $record will contain the args received by jetpack_tracks_record_event
		$record = $this->tracks->opted_in();
		$this->assertInstanceOf( 'WP_User', $record[0] );
		$this->assertEquals( 'woocommerceconnect_opted_in', $record[1] );
		$this->assertInternalType( 'array', $record[2] );
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

		$this->logger->expects( $this->once() )
			->method( 'log' )
			->with(
				$this->stringContains( 'woocommerceconnect_opted_out' ),
				$this->anything()
			);

		// $record will contain the args received by jetpack_tracks_record_event
		$record = $this->tracks->opted_out();
		$this->assertInstanceOf( 'WP_User', $record[0] );
		$this->assertEquals( 'woocommerceconnect_opted_out', $record[1] );
		$this->assertInternalType( 'array', $record[2] );
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

		$this->logger->expects( $this->exactly(2) )
			->method( 'log' )
			->withConsecutive(
				array(
					$this->stringContains( 'woocommerceconnect_saved_service_settings' ),
					$this->anything(),
				),
				array(
					$this->stringContains( 'woocommerceconnect_saved_usps_settings' ),
					$this->anything(),
				)
			);

		$this->tracks->saved_service_settings( 'usps' );
	}

}
