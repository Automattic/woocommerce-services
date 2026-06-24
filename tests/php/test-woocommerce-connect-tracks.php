<?php

class WP_Test_WC_Connect_Tracks extends WC_Unit_Test_Case {
	/** @var  WC_Connect_Tracks */
	protected $tracks;
	protected $logger;
	protected $jetpack_tracker;

	public static function set_up_before_class() {
		require_once __DIR__ . '/../../classes/class-wc-connect-options.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-tracks.php';
	}

	public function set_up() {
		$this->jetpack_tracker = $this->createMock( Automattic\Jetpack\Tracking::class );
		$this->logger          = $this->getMockBuilder( 'WC_Connect_Logger' )
										->disableOriginalConstructor()
										->setMethods( array( 'log' ) )
										->getMock();

		$this->tracks = new WC_Connect_Tracks( $this->logger, __FILE__ );
		$this->tracks->init();
	}

	public function test_record_user_event() {
		$this->logger->expects( $this->once() )
					->method( 'log' )
					->with(
						$this->stringContains( 'woocommerceconnect_test' )
					);

		$this->jetpack_tracker->expects( $this->any() )->method( 'tracks_record_event' )->with(
			$this->callback(
				function ( $argument ) {
					return $argument instanceof WP_User;
				}
			),
			$this->callback(
				function ( $argument ) {
					return 'woocommerceconnect_test' === $argument;
				}
			),
			$this->callback(
				function ( $argument ) {
					return is_array( $argument ) &&
						isset( $argument['_via_ua'] ) &&
						isset( $argument['_via_ip'] ) &&
						isset( $argument['_lg'] ) &&
						isset( $argument['blog_url'] ) &&
						isset( $argument['blog_id'] ) &&
						isset( $argument['jetpack_version'] ) &&
						isset( $argument['wc_version'] ) &&
						isset( $argument['wp_version'] );
				}
			)
		);

		$this->tracks->record_user_event( 'test' );
	}

	public function test_opted_in() {
		$this->logger->expects( $this->once() )
					->method( 'log' )
					->with(
						$this->stringContains( 'woocommerceconnect_opted_in' )
					);

		$this->jetpack_tracker->expects( $this->any() )->method( 'tracks_record_event' )->with(
			$this->callback(
				function ( $argument ) {
					return $argument instanceof WP_User;
				}
			),
			$this->callback(
				function ( $argument ) {
					return 'woocommerceconnect_opted_in' === $argument;
				}
			),
			$this->callback(
				function ( $argument ) {
					return is_array( $argument ) &&
						isset( $argument['_via_ua'] ) &&
						isset( $argument['_via_ip'] ) &&
						isset( $argument['_lg'] ) &&
						isset( $argument['blog_url'] ) &&
						isset( $argument['blog_id'] ) &&
						isset( $argument['jetpack_version'] ) &&
						isset( $argument['wc_version'] ) &&
						isset( $argument['wp_version'] );
				}
			)
		);

		$this->tracks->opted_in();
	}

	public function test_opted_out() {
		// Set tos_accepted to true to simulate user has opted in
		WC_Connect_Options::update_option( 'tos_accepted', true );

		$this->logger->expects( $this->once() )
					->method( 'log' )
					->with(
						$this->stringContains( 'woocommerceconnect_opted_out' )
					);

		$this->jetpack_tracker->expects( $this->any() )->method( 'tracks_record_event' )->with(
			$this->callback(
				function ( $argument ) {
					return $argument instanceof WP_User;
				}
			),
			$this->callback(
				function ( $argument ) {
					return 'woocommerceconnect_opted_out' === $argument;
				}
			),
			$this->callback(
				function ( $argument ) {
					return is_array( $argument ) &&
						isset( $argument['_via_ua'] ) &&
						isset( $argument['_via_ip'] ) &&
						isset( $argument['_lg'] ) &&
						isset( $argument['blog_url'] ) &&
						isset( $argument['blog_id'] ) &&
						isset( $argument['jetpack_version'] ) &&
						isset( $argument['wc_version'] ) &&
						isset( $argument['wp_version'] );
				}
			)
		);

		// $record will contain the args received by jetpack_tracks_record_event
		$this->tracks->opted_out();

		// Clean up
		WC_Connect_Options::delete_option( 'tos_accepted' );
	}

	public function test_opted_out_without_opt_in() {
		// Ensure tos_accepted is not set (user never opted in)
		WC_Connect_Options::delete_option( 'tos_accepted' );

		// Logger should NOT be called since user never opted in
		$this->logger->expects( $this->never() )
					->method( 'log' );

		// Jetpack tracker should NOT be called
		$this->jetpack_tracker->expects( $this->never() )
								->method( 'tracks_record_event' );

		// Call opted_out - should not send any event
		$this->tracks->opted_out();
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
