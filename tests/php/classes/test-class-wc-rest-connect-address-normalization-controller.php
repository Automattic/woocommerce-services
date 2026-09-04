<?php

/**
 * Unit test for WC_REST_Connect_Address_Normalization_Controller
 */
class WP_Test_WC_REST_Connect_Address_Normalization_Controller extends WC_REST_Unit_Test_Case {

	const ROUTE = '/wc/v1/connect/normalize-address';

	/** @var WC_Connect_API_Client_Live $api_client_mock */
	protected $api_client_mock;

	/** @var WC_Connect_Logger $connect_logger_mock */
	protected $connect_logger_mock;

	/** @var WC_Connect_Service_Schemas_Store $service_schemas_store_mock */
	protected $service_schemas_store_mock;

	/** @var WC_Connect_Service_Settings_Store $settings_store */
	protected $settings_store;

	/** @var WC_REST_Connect_Address_Normalization_Controller $controller */
	protected $controller;

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
		require_once __DIR__ . '/../../../classes/class-wc-rest-connect-address-normalization-controller.php';
	}

	/**
	 * Setup the test case. Overrides setUp() rather than set_up() because
	 * WC_REST_Unit_Test_Case only creates the REST server after the polyfilled
	 * set_up() has already returned.
	 *
	 * @see WC_REST_Unit_Test_Case::setUp()
	 */
	public function setUp(): void {
		parent::setUp();

		// The base controller sends a no-cache header on every dispatch, which PHPUnit
		// would report as "headers already sent". Core's spy server records headers instead.
		$GLOBALS['wp_rest_server'] = new Spy_REST_Server();
		$this->server              = $GLOBALS['wp_rest_server'];

		// The plugin does not register its own routes under test, so the controller
		// registered below is the only handler this REST server dispatches to.
		$this->assertArrayNotHasKey( self::ROUTE, $this->server->get_routes(), 'The route must not be registered before the test controller registers it' );

		// Creating a mock class and override protected request method so that we can mock the API response.
		$this->api_client_mock = $this->getMockBuilder( WC_Connect_API_Client_Live::class )
			->disableOriginalConstructor()
			->setMethods( array( 'request' ) )
			->getMock();

		$this->connect_logger_mock        = $this->createMock( WC_Connect_Logger::class );
		$this->service_schemas_store_mock = $this->createMock( WC_Connect_Service_Schemas_Store::class );
		$this->settings_store             = new WC_Connect_Service_Settings_Store( $this->service_schemas_store_mock, $this->api_client_mock, $this->connect_logger_mock );

		$this->controller = new WC_REST_Connect_Address_Normalization_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock );
		$this->controller->register_routes();
	}

	/**
	 * Address types an anonymous client could send.
	 */
	public function anonymous_type_provider() {
		return array(
			'destination'  => array( array( 'type' => 'destination' ) ),
			'origin'       => array( array( 'type' => 'origin' ) ),
			'arbitrary'    => array( array( 'type' => 'anything' ) ),
			'null'         => array( array( 'type' => null ) ),
			'missing type' => array( array() ),
		);
	}

	/**
	 * @testdox An anonymous request is rejected with 401 whatever address type it claims.
	 *
	 * @dataProvider anonymous_type_provider
	 *
	 * @param array $type_fields The type field to merge into the body, if any.
	 */
	public function test_anonymous_request_is_rejected_for_every_address_type( $type_fields ) {
		// Given.
		wp_set_current_user( 0 );
		$this->api_client_mock->expects( $this->never() )->method( 'request' );

		// When.
		$response = $this->dispatch_json( array_merge( array( 'address' => $this->sample_address() ), $type_fields ) );

		// Then.
		$this->assertEquals( 401, $response->get_status() );
		$this->assertEquals( 'rest_forbidden', $response->get_data()['code'] );
	}

	/**
	 * WordPress rejects invalid JSON before the permission callback runs, so the status is 400 here.
	 *
	 * @testdox An anonymous request with a body that is not JSON is rejected and nothing is proxied.
	 */
	public function test_anonymous_request_with_non_json_body_is_rejected() {
		// Given.
		wp_set_current_user( 0 );
		$this->api_client_mock->expects( $this->never() )->method( 'request' );

		// When.
		$response = $this->dispatch_raw( 'not json' );

		// Then.
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'rest_invalid_json', $response->get_data()['code'] );
	}

	/**
	 * @testdox A logged-in user without the labels capability gets 403 for both address types.
	 */
	public function test_user_without_labels_capability_is_rejected() {
		// Given.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		$this->api_client_mock->expects( $this->never() )->method( 'request' );

		// When.
		$destination_response = $this->dispatch_json(
			array(
				'address' => $this->sample_address(),
				'type'    => 'destination',
			)
		);
		$origin_response      = $this->dispatch_json(
			array(
				'address' => $this->sample_address(),
				'type'    => 'origin',
			)
		);

		// Then.
		$this->assertEquals( 403, $destination_response->get_status() );
		$this->assertEquals( 403, $origin_response->get_status() );
	}

	/**
	 * Address types a logged-in label manager sends from the shipping label form.
	 */
	public function authenticated_type_provider() {
		return array(
			'destination' => array( 'destination' ),
			'origin'      => array( 'origin' ),
		);
	}

	/**
	 * @testdox A shop manager normalizes both address types, with the phone kept out of the proxied payload and echoed back on the result.
	 *
	 * @dataProvider authenticated_type_provider
	 *
	 * @param string $type The address type.
	 */
	public function test_shop_manager_normalizes_address( $type ) {
		// Given.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );
		$address         = $this->sample_address();
		$proxied_address = $address;
		unset( $proxied_address['phone'] );
		$normalized_address = (object) $proxied_address;

		$this->api_client_mock->expects( $this->once() )
			->method( 'request' )
			->with( 'POST', '/shipping/address/normalize', array( 'destination' => $proxied_address ) )
			->willReturn(
				(object) array(
					'normalized'               => $normalized_address,
					'is_trivial_normalization' => true,
				)
			);

		// When.
		$response = $this->dispatch_json(
			array(
				'address' => $address,
				'type'    => $type,
			)
		);

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertTrue( $data['is_trivial_normalization'] );
		$this->assertEquals( $address['phone'], $data['normalized']->phone );
		$this->assertEquals( $proxied_address['address'], $data['normalized']->address );
	}

	/**
	 * @testdox A user holding only the wcship_manage_labels capability can normalize an address.
	 */
	public function test_user_with_only_labels_capability_normalizes_address() {
		// Given.
		$user = get_user_by( 'id', $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		$user->add_cap( 'wcship_manage_labels' );
		wp_set_current_user( $user->ID );
		$this->mock_successful_normalization();

		// When.
		$response = $this->dispatch_json(
			array(
				'address' => $this->sample_address(),
				'type'    => 'destination',
			)
		);

		// Then.
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Existing integrations grant the permission to other roles through this filter,
	 * so the tightened capability check must not close that door.
	 *
	 * @testdox The wcship_user_can_manage_labels filter still decides access.
	 */
	public function test_manage_labels_filter_grants_access() {
		// Given.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		add_filter( 'wcship_user_can_manage_labels', '__return_true' );
		$this->mock_successful_normalization();

		try {
			// When.
			$response = $this->dispatch_json(
				array(
					'address' => $this->sample_address(),
					'type'    => 'destination',
				)
			);
		} finally {
			remove_filter( 'wcship_user_can_manage_labels', '__return_true' );
		}

		// Then.
		$this->assertEquals( 200, $response->get_status() );
	}

	/**
	 * Bodies that do not carry a usable address.
	 */
	public function malformed_body_provider() {
		return array(
			'scalar address'  => array(
				array(
					'address' => '1 Main St',
					'type'    => 'destination',
				),
			),
			'null address'    => array(
				array(
					'address' => null,
					'type'    => 'destination',
				),
			),
			'empty address'   => array(
				array(
					'address' => array(),
					'type'    => 'destination',
				),
			),
			'missing address' => array( array( 'type' => 'destination' ) ),
			'empty body'      => array( array() ),
			'scalar body'     => array( '1 Main St' ),
			'json array body' => array( array( 'destination' ) ),
		);
	}

	/**
	 * @testdox A body without a usable address returns 400 and is not proxied.
	 *
	 * @dataProvider malformed_body_provider
	 *
	 * @param mixed $body The request body, which is not always an object.
	 */
	public function test_malformed_body_returns_400_without_proxying( $body ) {
		// Given.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->api_client_mock->expects( $this->never() )->method( 'request' );

		// When.
		$response = $this->dispatch_json( $body );

		// Then.
		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'bad_form_data', $response->get_data()['code'] );
	}

	/**
	 * @testdox An authenticated request with a body that is not JSON returns 400 and is not proxied.
	 */
	public function test_non_json_body_returns_400_without_proxying() {
		// Given.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->api_client_mock->expects( $this->never() )->method( 'request' );

		// When.
		$response = $this->dispatch_raw( 'not json' );

		// Then.
		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * @testdox An address without a phone is normalized and comes back with an empty phone.
	 */
	public function test_address_without_phone_is_normalized() {
		// Given.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );
		$address = $this->sample_address();
		unset( $address['phone'] );

		$this->api_client_mock->expects( $this->once() )
			->method( 'request' )
			->with( 'POST', '/shipping/address/normalize', array( 'destination' => $address ) )
			->willReturn( (object) array( 'normalized' => (object) $address ) );

		// When.
		$response = $this->dispatch_json(
			array(
				'address' => $address,
				'type'    => 'destination',
			)
		);

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( '', $data['normalized']->phone );
		$this->assertFalse( $data['is_trivial_normalization'] );
	}

	/**
	 * Phones that cannot be echoed back as a string.
	 */
	public function non_scalar_phone_provider() {
		return array(
			'object phone' => array( array( 'x' => 1 ) ),
			'array phone'  => array( array( '2125550123' ) ),
		);
	}

	/**
	 * @testdox A non-scalar phone is dropped rather than echoed back on the response.
	 *
	 * @dataProvider non_scalar_phone_provider
	 *
	 * @param mixed $phone The phone value sent in the address.
	 */
	public function test_non_scalar_phone_is_dropped( $phone ) {
		// Given.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );
		$address          = $this->sample_address();
		$address['phone'] = $phone;
		$proxied_address  = $this->sample_address();
		unset( $proxied_address['phone'] );

		$this->api_client_mock->expects( $this->once() )
			->method( 'request' )
			->with( 'POST', '/shipping/address/normalize', array( 'destination' => $proxied_address ) )
			->willReturn( (object) array( 'normalized' => (object) $proxied_address ) );

		// When.
		$response = $this->dispatch_json(
			array(
				'address' => $address,
				'type'    => 'destination',
			)
		);

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame( '', $response->get_data()['normalized']->phone );
	}

	/**
	 * @testdox Field errors reported by the Connect server are passed back to the caller.
	 */
	public function test_field_errors_are_returned() {
		// Given.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );
		$field_errors = (object) array( 'postcode' => 'Invalid postcode' );
		$this->api_client_mock->method( 'request' )->willReturn( (object) array( 'field_errors' => $field_errors ) );

		// When.
		$response = $this->dispatch_json(
			array(
				'address' => $this->sample_address(),
				'type'    => 'destination',
			)
		);

		// Then.
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( $field_errors, $data['field_errors'] );
	}

	/**
	 * @testdox A Connect server error is returned as an error response.
	 */
	public function test_api_error_is_returned() {
		// Given.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->api_client_mock->method( 'request' )->willReturn( new WP_Error( 'missing_token', 'Unable to send request', array( 'status' => 500 ) ) );

		// When.
		$response = $this->dispatch_json(
			array(
				'address' => $this->sample_address(),
				'type'    => 'destination',
			)
		);

		// Then.
		$this->assertEquals( 500, $response->get_status() );
		$this->assertEquals( 'missing_token', $response->get_data()['code'] );
	}

	/**
	 * A complete address as the shipping label form sends it.
	 */
	private function sample_address() {
		return array(
			'name'      => 'Jane Doe',
			'company'   => '',
			'address'   => '1 Main St',
			'address_2' => '',
			'city'      => 'New York',
			'state'     => 'NY',
			'postcode'  => '10001',
			'country'   => 'US',
			'phone'     => '2125550123',
		);
	}

	/**
	 * Make the API client mock answer every normalization with a normalized copy of the sample address.
	 */
	private function mock_successful_normalization() {
		$this->api_client_mock->method( 'request' )
			->willReturn( (object) array( 'normalized' => (object) $this->sample_address() ) );
	}

	/**
	 * Dispatch a JSON body to the route.
	 *
	 * @param mixed $body The body to encode.
	 * @return WP_REST_Response
	 */
	private function dispatch_json( $body ) {
		return $this->dispatch_raw( wp_json_encode( $body ) );
	}

	/**
	 * Dispatch a raw body to the route with a JSON content type.
	 *
	 * @param string $body The raw body.
	 * @return WP_REST_Response
	 */
	private function dispatch_raw( $body ) {
		$request = new WP_REST_Request( 'POST', self::ROUTE );
		$request->set_header( 'content-type', 'application/json' );
		$request->set_body( $body );

		return $this->server->dispatch( $request );
	}
}
