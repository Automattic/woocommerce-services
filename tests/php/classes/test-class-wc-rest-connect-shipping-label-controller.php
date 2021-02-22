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
		// Creating a mock class and overide protected request method so that we can mock the API response.
		$this->api_client_mock = $this->getMockBuilder( WC_Connect_API_Client_Live::class )
			->disableOriginalConstructor()
			->setMethods( [ 'request' ] )
			->getMock();

		$this->connect_logger_mock = $this->createMock( WC_Connect_Logger::class );
		$this->service_schemas_store_mock = $this->createMock( WC_Connect_Service_Schemas_Store::class );
		$this->settings_store = new WC_Connect_Service_Settings_Store( $this->service_schemas_store_mock, $this->api_client_mock, $this->connect_logger_mock );

		$payment_methods_store = $this->getMockBuilder( 'WC_Connect_Payment_Methods_Store' )
			->disableOriginalConstructor()
			->setMethods( null )
			->getMock();
		$this->shipping_label = new WC_Connect_Shipping_Label( $this->api_client_mock, $this->settings_store, $this->service_schemas_store_mock, $payment_methods_store );

		$this->unset_selected_payment_method($this->settings_store);
		$this->disable_shipping_labels_account_settings($this->settings_store);
	}

	public function test_store_with_account_settings_disabled_is_not_eligible_for_shipping_label_creation() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->set_selected_payment_method($this->settings_store);
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order = WC_Helper_Order::create_order(1, $product);

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason' => 'account_settings_disabled'
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	public function test_store_in_supported_countries_is_eligible_for_shipping_label_creation() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->enable_shipping_labels_account_settings($this->settings_store);
		$this->set_selected_payment_method($this->settings_store);
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order = WC_Helper_Order::create_order(1, $product);

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => true
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	public function test_non_US_store_is_not_eligible_for_shipping_label_creation_when_client_cannot_create_customs_form() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$product = $this->create_simple_product();
		$this->enable_shipping_labels_account_settings($this->settings_store);
		$this->set_selected_payment_method($this->settings_store);
		update_option( 'woocommerce_default_country', 'PR' );
		$order = WC_Helper_Order::create_order(1, $product);

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$request->set_param('can_create_customs_form', false);
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason' => 'store_country_not_supported_when_customs_form_is_not_supported_by_client'
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	public function test_US_store_with_non_US_destination_address_is_not_eligible_for_shipping_label_creation_when_client_cannot_create_customs_form() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$product = $this->create_simple_product();
		$this->enable_shipping_labels_account_settings($this->settings_store);
		$this->set_selected_payment_method($this->settings_store);
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$order = WC_Helper_Order::create_order(1, $product);
		$this->set_origin_address(array('country' => 'US'));
		$this->set_destination_address($order, array('country' => 'PR'));

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$request->set_param('can_create_customs_form', false);
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason' => 'origin_or_destination_country_not_supported_when_customs_form_is_not_supported_by_client'
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	public function test_US_store_with_US_addresses_is_eligible_for_shipping_label_creation_when_client_cannot_create_customs_form() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->enable_shipping_labels_account_settings($this->settings_store);
		$this->set_selected_payment_method($this->settings_store);
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order = WC_Helper_Order::create_order(1, $product);
		$this->set_origin_address(array('country' => 'US'));
		$this->set_destination_address($order, array('country' => 'US'));

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$request->set_param('can_create_customs_form', false);
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => true
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	public function test_store_without_existing_packages_is_eligible_for_shipping_label_creation_when_client_can_create_package() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->enable_shipping_labels_account_settings($this->settings_store);
		$this->set_selected_payment_method($this->settings_store);
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order = WC_Helper_Order::create_order(1, $product);

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => true
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	public function test_store_without_existing_packages_is_not_eligible_for_shipping_label_creation_when_client_cannot_create_package() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->enable_shipping_labels_account_settings($this->settings_store);
		$this->set_selected_payment_method($this->settings_store);
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order = WC_Helper_Order::create_order(1, $product);

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$request->set_param('can_create_package', false);
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason' => 'no_packages_when_client_cannot_create_package'
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	public function test_store_with_one_existing_custom_package_is_eligible_for_shipping_label_creation_when_client_cannot_create_package() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->enable_shipping_labels_account_settings($this->settings_store);
		$this->set_selected_payment_method($this->settings_store);
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order = WC_Helper_Order::create_order(1, $product);
		$this->settings_store->update_packages( array(array('name' => 'Fun box')) );

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$request->set_param('can_create_package', false);
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => true
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	public function test_order_with_virtual_product_is_not_eligible_for_shipping_label_creation() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->enable_shipping_labels_account_settings($this->settings_store);
		$this->set_selected_payment_method($this->settings_store);
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product(false);
		$order = WC_Helper_Order::create_order(1, $product);

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason' => 'order_not_eligible'
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	public function test_store_without_selected_payment_method_is_not_eligible_for_shipping_label_creation_when_user_cannot_manage_payment() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->enable_shipping_labels_account_settings($this->settings_store);
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order = WC_Helper_Order::create_order(1, $product);

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason' => 'no_selected_payment_method_and_user_cannot_manage_payment_methods'
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	public function test_store_without_selected_payment_method_is_not_eligible_for_shipping_label_creation_when_client_cannot_create_payment_method() {
		// Given
		$controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client_mock, $this->settings_store, $this->connect_logger_mock, $this->shipping_label );
		$this->enable_shipping_labels_account_settings($this->settings_store);
		$this->set_store_to_be_eligible_for_shipping_label_creation();
		$product = $this->create_simple_product();
		$order = WC_Helper_Order::create_order(1, $product);

		// When
		$request = new WP_REST_Request( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/creation_eligibility' );
		$request->set_param('order_id', $order->get_id());
		$request->set_param('can_create_payment_method', false);
		$response = $controller->get_creation_eligibility($request);

		// Then
		$this->assertEquals( 200, $response->status );
		$expected_data = array(
			'is_eligible' => false,
			'reason' => 'no_selected_payment_method_when_client_cannot_manage_payment'
		);
		$this->assertEquals($expected_data, $response->get_data());
	}

	private function create_simple_product($is_shippable = true) {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_virtual(!$is_shippable);
		$product->save();
		return $product;
	}

	private function set_store_to_be_eligible_for_shipping_label_creation() {
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_default_country', 'US' );
	}

	private function enable_shipping_labels_account_settings($settings_store) {
		$account_settings = $this->settings_store->get_account_settings();
		$account_settings['enabled'] = true;
		$this->settings_store->update_account_settings($account_settings);
	}

	private function disable_shipping_labels_account_settings($settings_store) {
		$account_settings = $this->settings_store->get_account_settings();
		$account_settings['enabled'] = false;
		$this->settings_store->update_account_settings($account_settings);
	}

	private function set_selected_payment_method($settings_store) {
		$settings_store->set_selected_payment_method_id(123);
	}

	private function unset_selected_payment_method($settings_store) {
		$account_settings = $settings_store->get_account_settings();
		unset($account_settings['selected_payment_method_id']);
		$settings_store->update_account_settings($account_settings);
	}

	private function set_origin_address($address = array()) {
		WC_Connect_Options::update_option( 'origin_address', $address );
	}

	private function set_destination_address($order, $address = array()) {
		$order->set_address($address, 'shipping');
	}
}
