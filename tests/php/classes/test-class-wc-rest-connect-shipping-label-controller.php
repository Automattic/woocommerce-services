<?php

/**
 * Unit test for WC_REST_Connect_Shipping_Label_Controller
 */
class WP_Test_WC_REST_Connect_Shipping_Label_Controller extends WC_Unit_Test_Case {

	/** @var WC_Connect_API_Client_Live $api_client_mock */
	protected $api_client_mock;

	/** @var WC_Connect_Logger $connect_logger_mock */
	protected $connect_logger_mock;

	/** @var WC_Connect_Service_Schemas_Store $service_schemas_store_mock */
	protected $service_schemas_store_mock;

	/** @var WC_Connect_Service_Settings_Store $settings_store */
	protected $settings_store;

	/** @var WC_Connect_Shipping_Label $shipping_label */
	protected $shipping_label;

	/**
	 * @inherit
	 */
	public static function setupBeforeClass() {
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-api-client-live.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-service-settings-store.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-logger.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-service-schemas-store.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-package-settings.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-account-settings.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-continents.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-compatibility.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-connect-shipping-label.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-rest-connect-base-controller.php';
		require_once dirname( __FILE__ ) . '/../../../classes/class-wc-rest-connect-shipping-label-controller.php';
	}

	/**
	 * Setup the test case.
	 *
	 * @see WC_Unit_Test_Case::setUp()
	 */
	public function setUp() {
		parent::setUp();

		// Creating a mock class and overide protected request method so that we can mock the API response.
		$this->api_client_mock = $this->getMockBuilder( WC_Connect_API_Client_Live::class )
			->disableOriginalConstructor()
			->setMethods( array( 'request' ) )
			->getMock();

		$this->connect_logger_mock        = $this->createMock( WC_Connect_Logger::class );
		$this->service_schemas_store_mock = $this->createMock( WC_Connect_Service_Schemas_Store::class );
		$this->settings_store             = new WC_Connect_Service_Settings_Store( $this->service_schemas_store_mock, $this->api_client_mock, $this->connect_logger_mock );

		$payment_methods_store = $this->getMockBuilder( 'WC_Connect_Payment_Methods_Store' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();
		$this->shipping_label  = new WC_Connect_Shipping_Label( $this->api_client_mock, $this->settings_store, $this->service_schemas_store_mock, $payment_methods_store );
	}

	/**
	 * Test that a WC store with account settings disabled is not eligible for shipping label creation.
	 */
	public function test_store_with_account_settings_disabled_is_not_eligible_for_shipping_label_creation() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		$this->update_shipping_labels_account_settings( false, $this->settings_store );
		$product = $this->create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason'      => 'account_settings_disabled',
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * Test that a WC store in a supported country is eligible for shipping label creation while fulfilling all the other requirements.
	 */
	public function test_store_in_supported_countries_is_eligible_for_shipping_label_creation() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => true,
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * When the client cannot create a customs form, test that a WC store outside the US is not eligible for shipping label creation.
	 */
	public function test_non_US_store_is_not_eligible_for_shipping_label_creation_when_client_cannot_create_customs_form() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		update_option( 'woocommerce_default_country', 'PR' );
		$product = $this->create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'can_create_customs_form', 'false' );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason'      => 'store_country_not_supported_when_customs_form_is_not_supported_by_client',
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * When the client cannot create a customs form, test that an order with destination address outside the US is not eligible for shipping label creation.
	 */
	public function test_US_store_with_non_US_destination_address_is_not_eligible_for_shipping_label_creation_when_client_cannot_create_customs_form() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );
		$this->set_origin_address( array( 'country' => 'US' ) );
		$this->set_destination_address( $order, array( 'country' => 'PR' ) );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'can_create_customs_form', 'false' );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason'      => 'origin_or_destination_country_not_supported_when_customs_form_is_not_supported_by_client',
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * When the client cannot create a customs form, test that it is eligible for shipping label creation when:
	 * - A store is in the US
	 * - Both the origin and destination addresses are in the US
	 */
	public function test_US_store_with_US_addresses_is_eligible_for_shipping_label_creation_when_client_cannot_create_customs_form() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );
		$this->set_origin_address( array( 'country' => 'US' ) );
		$this->set_destination_address( $order, array( 'country' => 'US' ) );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'can_create_customs_form', 'false' );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => true,
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * When the client can create a package, test that a store without any packages is eligible for shipping label creation.
	 */
	public function test_store_without_existing_packages_is_eligible_for_shipping_label_creation_when_client_can_create_package() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );
		$this->update_packages( array(), $this->settings_store );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => true,
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * When the client cannot create a package, test that a store without any packages is not eligible for shipping label creation.
	 */
	public function test_store_without_existing_packages_is_not_eligible_for_shipping_label_creation_when_client_cannot_create_package() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );
		$this->update_packages( array(), $this->settings_store );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'can_create_package', 'false' );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason'      => 'no_packages_when_client_cannot_create_package',
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * When the client cannot create a package, test that a store with one custom package is eligible for shipping label creation.
	 */
	public function test_store_with_one_existing_custom_package_is_eligible_for_shipping_label_creation_when_client_cannot_create_package() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );
		$this->update_packages( array( array( 'name' => 'Fun box' ) ), $this->settings_store );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'can_create_package', 'false' );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => true,
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * Test that an order with one virtual product is not eligible for shipping label creation (no product to ship).
	 */
	public function test_order_with_virtual_product_is_not_eligible_for_shipping_label_creation() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product( false );
		$order   = WC_Helper_Order::create_order( 1, $product );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason'      => 'order_not_eligible',
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * Test that for a user who cannot manage payment, a store without selected payment method is not eligible for shipping label creation.
	 */
	public function test_store_without_selected_payment_method_is_not_eligible_for_shipping_label_creation_when_user_cannot_manage_payment() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		$this->unset_selected_payment_method( $this->settings_store );
		$product = $this->create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason'      => 'no_selected_payment_method_and_user_cannot_manage_payment_methods',
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * When the client cannot create payment methods, test that a store without selected payment method is not eligible for shipping label creation.
	 */
	public function test_store_without_selected_payment_method_is_not_eligible_for_shipping_label_creation_when_client_cannot_create_payment_method() {
		// Given.
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_store_and_settings_to_be_eligible_for_shipping_label_creation();
		$this->unset_selected_payment_method( $this->settings_store );
		$product = $this->create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );

		// When.
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param( 'order_id', $order->get_id() );
		$request->set_param( 'can_create_payment_method', 'false' );
		$response = $controller->get_creation_eligibility( $request );

		// Then.
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason'      => 'no_selected_payment_method_when_client_cannot_manage_payment',
		);
		$this->assertEquals( $expected_data, $response->get_data() );
	}

	/**
	 * A helper to create a simple product that is default to be shippable.
	 *
	 * @param bool $is_shippable Whether the simple product is shippable or not.
	 * @return mixed
	 */
	private function create_simple_product( $is_shippable = true ) {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual( ! $is_shippable );
		$product->save();
		return $product;
	}

	/**
	 * Set up all requirements for a store and order to be eligible for shipping label creation.
	 */
	private function set_store_and_settings_to_be_eligible_for_shipping_label_creation() {
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$this->set_selected_payment_method( $this->settings_store );
		$this->update_shipping_labels_account_settings( true, $this->settings_store );
	}

	/**
	 * Set up a store to be eligible for shipping label creation.
	 */
	private function set_store_to_be_eligible_for_shipping_label_creation() {
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_default_country', 'US' );
	}

	/**
	 * Disable shipping labels account settings in a settings store.
	 *
	 * @param bool                              $is_account_settings_enabled Whether shipping labels account settings is enabled.
	 * @param WC_Connect_Service_Settings_Store $settings_store Settings store where shipping labels settings are stored.
	 */
	private function update_shipping_labels_account_settings( bool $is_account_settings_enabled, WC_Connect_Service_Settings_Store $settings_store ) {
		$account_settings            = $settings_store->get_account_settings();
		$account_settings['enabled'] = $is_account_settings_enabled;
		$settings_store->update_account_settings( $account_settings );
	}

	/**
	 * Update packages in a settings store.
	 *
	 * @param array                             $packages Packages to be updated for the store.
	 * @param WC_Connect_Service_Settings_Store $settings_store Settings store where packages are stored.
	 */
	private function update_packages( array $packages, WC_Connect_Service_Settings_Store $settings_store ) {
		$settings_store->update_packages( $packages );
	}

	/**
	 * Set a default selected payment method in a settings store.
	 *
	 * @param WC_Connect_Service_Settings_Store $settings_store Settings store where shipping labels settings are stored.
	 */
	private function set_selected_payment_method( WC_Connect_Service_Settings_Store $settings_store ) {
		$settings_store->set_selected_payment_method_id( 123 );
	}

	/**
	 * Unset the selected payment method in a settings store.
	 *
	 * @param WC_Connect_Service_Settings_Store $settings_store Settings store where shipping labels settings are stored.
	 */
	private function unset_selected_payment_method( WC_Connect_Service_Settings_Store $settings_store ) {
		$account_settings = $settings_store->get_account_settings();
		unset( $account_settings['selected_payment_method_id'] );
		$settings_store->update_account_settings( $account_settings );
	}

	/**
	 * Set the store's origin address.
	 *
	 * @param array $address A dictionary that represents the origin address.
	 */
	private function set_origin_address( $address = array() ) {
		WC_Connect_Options::update_option( 'origin_address', $address );
	}

	/**
	 * Set an order's destination address.
	 *
	 * @param WC_Order $order The order whose destination address is set.
	 * @param array    $address A dictionary that represents the destination address.
	 */
	private function set_destination_address( WC_Order $order, $address = array() ) {
		$order->set_address( $address, 'shipping' );
	}
}
