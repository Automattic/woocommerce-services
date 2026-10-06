<?php
/**
 * Tests that the label routes act only on a real order, and only on that order's labels.
 *
 * @package WooCommerce_Services
 */

/**
 * The label routes take the order ID from the URL. Anything that is not an order must be
 * refused before any write or upstream call.
 */
class WP_Test_WC_REST_Connect_Label_Order_Binding extends WC_REST_Unit_Test_Case {

	/**
	 * Origin address stored before each test, which a refused request may not change.
	 */
	const STORED_ORIGIN = array(
		'address' => '1 Main St',
		'city'    => 'Marquette',
		'country' => 'US',
	);

	/**
	 * API client stand-in.
	 *
	 * @var WC_Connect_API_Client|PHPUnit\Framework\MockObject\MockObject
	 */
	private $api_client;

	/**
	 * Logger stand-in.
	 *
	 * @var WC_Connect_Logger|PHPUnit\Framework\MockObject\MockObject
	 */
	private $logger;

	/**
	 * Real settings store, so writes to options and order meta can be checked.
	 *
	 * @var WC_Connect_Service_Settings_Store
	 */
	private $settings_store;

	/**
	 * Messages passed to the logger's log().
	 *
	 * @var string[]
	 */
	private $logged = array();

	/**
	 * Load the classes under test.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		$classes = __DIR__ . '/../../../classes/';
		require_once $classes . 'class-wc-connect-options.php';
		require_once $classes . 'class-wc-connect-utils.php';
		require_once $classes . 'class-wc-connect-logger.php';
		require_once $classes . 'class-wc-connect-api-client.php';
		require_once $classes . 'class-wc-connect-service-schemas-store.php';
		require_once $classes . 'class-wc-connect-service-settings-store.php';
		require_once $classes . 'class-wc-rest-connect-base-controller.php';
		require_once $classes . 'class-wc-rest-connect-shipping-rates-controller.php';
		require_once $classes . 'class-wc-rest-connect-shipping-label-status-controller.php';
		require_once $classes . 'class-wc-rest-connect-shipping-label-refund-controller.php';
		require_once $classes . 'class-wc-connect-shipping-label.php';
		require_once $classes . 'class-wc-connect-payment-methods-store.php';
		require_once $classes . 'class-wc-rest-connect-shipping-label-controller.php';
	}

	/**
	 * Set up the REST server and the controllers. Overrides setUp() rather than set_up()
	 * because WC_REST_Unit_Test_Case creates the server in setUp().
	 */
	public function setUp(): void {
		parent::setUp();

		// The base controller sends a no-cache header, which core's spy server records instead.
		$GLOBALS['wp_rest_server'] = new Spy_REST_Server();
		$this->server              = $GLOBALS['wp_rest_server'];

		$this->api_client = $this->createMock( WC_Connect_API_Client::class );
		$this->logger     = $this->createMock( WC_Connect_Logger::class );
		$this->logged     = array();
		$this->logger->method( 'log' )->willReturnCallback(
			function ( $message ) {
				$this->logged[] = is_wp_error( $message ) ? $message->get_error_code() : (string) $message;
			}
		);

		$this->settings_store = new WC_Connect_Service_Settings_Store(
			$this->createMock( WC_Connect_Service_Schemas_Store::class ),
			$this->api_client,
			$this->logger
		);

		( new WC_REST_Connect_Shipping_Rates_Controller( $this->api_client, $this->settings_store, $this->logger ) )->register_routes();
		( new WC_REST_Connect_Shipping_Label_Status_Controller( $this->api_client, $this->settings_store, $this->logger ) )->register_routes();
		( new WC_REST_Connect_Shipping_Label_Refund_Controller( $this->api_client, $this->settings_store, $this->logger ) )->register_routes();
		( new WC_REST_Connect_Shipping_Label_Controller(
			$this->api_client,
			$this->settings_store,
			$this->logger,
			$this->createMock( WC_Connect_Shipping_Label::class ),
			$this->createMock( WC_Connect_Payment_Methods_Store::class )
		) )->register_routes();

		WC_Connect_Options::update_option( 'origin_address', self::STORED_ORIGIN );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Clean up the options touched.
	 */
	public function tearDown(): void {
		WC_Connect_Options::delete_option( 'origin_address' );
		parent::tearDown();
	}

	/**
	 * IDs that are not orders: missing, a page, a product, a refund.
	 *
	 * @return array
	 */
	public function not_an_order_provider() {
		return array(
			'nonexistent' => array( 'nonexistent' ),
			'page'        => array( 'page' ),
			'product'     => array( 'product' ),
			'refund'      => array( 'refund' ),
		);
	}

	/**
	 * Build an ID of the given kind.
	 *
	 * @param string $kind One of the not_an_order_provider() keys.
	 * @return int
	 */
	private function make_id( $kind ) {
		switch ( $kind ) {
			case 'page':
				return self::factory()->post->create( array( 'post_type' => 'page' ) );
			case 'product':
				return WC_Helper_Product::create_simple_product()->get_id();
			case 'refund':
				$order = WC_Helper_Order::create_order();
				return wc_create_refund(
					array(
						'order_id' => $order->get_id(),
						'amount'   => 1,
					)
				)->get_id();
			default:
				return 999999;
		}
	}

	/**
	 * Send a JSON request.
	 *
	 * @param string $method HTTP method.
	 * @param string $route  Route.
	 * @param mixed  $body   Value to JSON-encode, or null for no body.
	 * @return WP_REST_Response
	 */
	private function send( $method, $route, $body = null ) {
		$request = new WP_REST_Request( $method, $route );
		if ( null !== $body ) {
			$request->set_header( 'content-type', 'application/json' );
			$request->set_body( wp_json_encode( $body ) );
		}

		return $this->server->dispatch( $request );
	}

	/**
	 * Assert a refused request: the given error and status.
	 *
	 * @param WP_REST_Response $response Response.
	 * @param string           $code     Expected error code.
	 * @param int              $status   Expected HTTP status.
	 */
	private function assert_refused( $response, $code, $status ) {
		$this->assertSame( $status, $response->get_status() );
		$this->assertSame( $code, $response->get_data()['code'] );
	}

	/**
	 * A valid rates body for the given product IDs.
	 *
	 * @param array $product_ids IDs to put in the package, with customs data.
	 * @return array
	 */
	private function rates_body( array $product_ids ) {
		$items = array();
		foreach ( $product_ids as $product_id ) {
			$items[] = array(
				'product_id'       => $product_id,
				'description'      => 'Customs ' . $product_id,
				'hs_tariff_number' => '123456',
				'origin_country'   => 'US',
			);
		}

		return array(
			'origin'      => array(
				'address' => '2 New St',
				'city'    => 'Gwinn',
				'country' => 'US',
				'name'    => 'Store',
			),
			'destination' => array(
				'address' => '9 Dest Rd',
				'city'    => 'Detroit',
				'country' => 'US',
				'name'    => 'Customer',
			),
			'packages'    => array(
				array(
					'id'            => 'box1',
					'contents_type' => 'merchandise',
					'items'         => $items,
				),
			),
		);
	}

	/**
	 * Make the rates call succeed.
	 */
	private function expect_rates_once() {
		$this->api_client->expects( $this->once() )
			->method( 'get_label_rates' )
			->willReturn( (object) array( 'rates' => (object) array( 'box1' => (object) array( 'rates' => array() ) ) ) );
	}

	/**
	 * Rates for something that is not an order: 404, nothing written, nothing sent.
	 *
	 * @dataProvider not_an_order_provider
	 * @param string $kind Kind of ID.
	 */
	public function test_rates_for_an_id_that_is_not_an_order_is_refused( $kind ) {
		$product = WC_Helper_Product::create_simple_product();
		$this->api_client->expects( $this->never() )->method( 'get_label_rates' );

		$response = $this->send( 'POST', '/wc/v1/connect/label/' . $this->make_id( $kind ) . '/rates', $this->rates_body( array( $product->get_id() ) ) );

		$this->assert_refused( $response, 'not_found', 404 );
		$this->assertSame( self::STORED_ORIGIN, WC_Connect_Options::get_option( 'origin_address' ) );
		$this->assertSame( '', get_post_meta( $product->get_id(), 'wc_connect_customs_info', true ) );
	}

	/**
	 * Bodies missing what the route needs.
	 *
	 * @return array
	 */
	public function bad_rates_body_provider() {
		return array(
			'empty object'                => array( new stdClass() ),
			'no packages'                 => array(
				array(
					'origin'      => array( 'address' => '2 New St' ),
					'destination' => array( 'address' => '9 Dest Rd' ),
				),
			),
			'origin not array'            => array(
				array(
					'origin'      => 'x',
					'destination' => array( 'address' => '9 Dest Rd' ),
					'packages'    => array(),
				),
			),
			'destination not array'       => array(
				array(
					'origin'      => array( 'address' => '2 New St' ),
					'destination' => 'x',
					'packages'    => array(),
				),
			),
			'packages not array'          => array(
				array(
					'origin'      => array( 'address' => '2 New St' ),
					'destination' => array( 'address' => '9 Dest Rd' ),
					'packages'    => 'x',
				),
			),
			'empty addresses'             => array(
				array(
					'origin'      => array(),
					'destination' => array(),
					'packages'    => array(),
				),
			),
			'origin without address'      => array(
				array(
					'origin'      => array( 'city' => 'Gwinn' ),
					'destination' => array( 'address' => '9 Dest Rd' ),
					'packages'    => array(),
				),
			),
			'destination without address' => array(
				array(
					'origin'      => array( 'address' => '2 New St' ),
					'destination' => array( 'city' => 'Detroit' ),
					'packages'    => array(),
				),
			),
			'destination address empty'   => array(
				array(
					'origin'      => array( 'address' => '2 New St' ),
					'destination' => array( 'address' => ' ' ),
					'packages'    => array(),
				),
			),
			'package not array'           => array(
				array(
					'origin'      => array( 'address' => '2 New St' ),
					'destination' => array( 'address' => '9 Dest Rd' ),
					'packages'    => array( 'x' ),
				),
			),
		);
	}

	/**
	 * A body without an origin or destination street, or whose packages are not all arrays: 400,
	 * and neither the stored origin nor the order's shipping address changes. Before, {} saved a null origin, blanked the
	 * order's street and then failed with a TypeError.
	 *
	 * @dataProvider bad_rates_body_provider
	 * @param mixed $body Request body.
	 */
	public function test_rates_with_a_bad_body_changes_nothing( $body ) {
		$order = WC_Helper_Order::create_order();
		$order->set_shipping_address_1( '5 Kept St' );
		$order->save();
		$this->api_client->expects( $this->never() )->method( 'get_label_rates' );

		$response = $this->send( 'POST', '/wc/v1/connect/label/' . $order->get_id() . '/rates', $body );

		$this->assert_refused( $response, 'bad_request', 400 );
		$this->assertSame( self::STORED_ORIGIN, WC_Connect_Options::get_option( 'origin_address' ) );
		$this->assertSame( '5 Kept St', wc_get_order( $order->get_id() )->get_shipping_address_1() );
		$this->assertFalse( wc_get_order( $order->get_id() )->meta_exists( '_wc_connect_destination_normalized' ) );
	}

	/**
	 * Customs info is saved only for products on the order, including a variation. Other
	 * IDs are skipped and logged, and the rates are still returned.
	 */
	public function test_rates_save_customs_info_only_for_products_on_the_order() {
		$on_order  = WC_Helper_Product::create_simple_product();
		$variable  = WC_Helper_Product::create_variation_product();
		$variation = wc_get_product( $variable->get_children()[0] );
		$off_order = WC_Helper_Product::create_simple_product();
		$page_id   = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$order     = WC_Helper_Order::create_order( 1, $on_order );
		$order->add_product( $variation, 1 );
		$order->save();
		$this->expect_rates_once();

		$response = $this->send(
			'POST',
			'/wc/v1/connect/label/' . $order->get_id() . '/rates',
			$this->rates_body( array( $on_order->get_id(), $variation->get_id(), $off_order->get_id(), $page_id ) )
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['success'] );
		$this->assertSame( 'Customs ' . $on_order->get_id(), get_post_meta( $on_order->get_id(), 'wc_connect_customs_info', true )['description'] );
		$this->assertSame( 'Customs ' . $variation->get_id(), get_post_meta( $variation->get_id(), 'wc_connect_customs_info', true )['description'] );
		$this->assertSame( '', get_post_meta( $off_order->get_id(), 'wc_connect_customs_info', true ) );
		$this->assertSame( '', get_post_meta( $page_id, 'wc_connect_customs_info', true ) );
		$this->assertContains( 'Skipped customs info for ID "' . $off_order->get_id() . '": not a product on order ' . $order->get_id() . '.', $this->logged );
		$this->assertContains( 'Skipped customs info for ID "' . $page_id . '": not a product on order ' . $order->get_id() . '.', $this->logged );
	}

	/**
	 * The normal flow still saves the origin and the order's address, then asks for rates.
	 */
	public function test_rates_for_an_order_saves_the_addresses_and_returns_rates() {
		$order = WC_Helper_Order::create_order();
		$this->expect_rates_once();

		$response = $this->send( 'POST', '/wc/v1/connect/label/' . $order->get_id() . '/rates', $this->rates_body( array() ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '2 New St', WC_Connect_Options::get_option( 'origin_address' )['address'] );
		$this->assertSame( '9 Dest Rd', wc_get_order( $order->get_id() )->get_shipping_address_1() );
	}

	/**
	 * An order carrying one purchased label.
	 *
	 * @param int $label_id Label ID.
	 * @return WC_Order
	 */
	private function order_with_label( $label_id ) {
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data(
			'wc_connect_labels',
			array(
				array(
					'label_id' => $label_id,
					'status'   => 'PURCHASED',
				),
			)
		);
		$order->save();

		return $order;
	}

	/**
	 * The labels stored on an order.
	 *
	 * @param WC_Order $order Order.
	 * @return array
	 */
	private function labels_of( WC_Order $order ) {
		return wc_get_order( $order->get_id() )->get_meta( 'wc_connect_labels', true );
	}

	/**
	 * Status for something that is not an order: 404, no upstream call.
	 *
	 * @dataProvider not_an_order_provider
	 * @param string $kind Kind of ID.
	 */
	public function test_status_for_an_id_that_is_not_an_order_is_refused( $kind ) {
		$this->api_client->expects( $this->never() )->method( 'get_label_status' );

		$response = $this->send( 'GET', '/wc/v1/connect/label/' . $this->make_id( $kind ) . '/111' );

		$this->assert_refused( $response, 'not_found', 404 );
	}

	/**
	 * Status for another order's label, alone or next to this order's own: 404 before any
	 * upstream call, and neither order's labels change.
	 */
	public function test_status_for_a_label_of_another_order_is_refused() {
		$order_a = $this->order_with_label( 111 );
		$order_b = $this->order_with_label( 222 );
		$this->api_client->expects( $this->never() )->method( 'get_label_status' );

		foreach ( array( '222', '111,222' ) as $label_ids ) {
			$response = $this->send( 'GET', '/wc/v1/connect/label/' . $order_a->get_id() . '/' . $label_ids );

			$this->assert_refused( $response, 'not_found', 404 );
			$this->assertSame( 'Shipping label not found', $response->get_data()['message'] );
		}
		$this->assertSame( 'PURCHASED', $this->labels_of( $order_a )[0]['status'] );
		$this->assertSame( 'PURCHASED', $this->labels_of( $order_b )[0]['status'] );
	}

	/**
	 * Status for the order's own label still asks upstream and saves the answer.
	 */
	public function test_status_for_the_orders_own_label_is_updated() {
		$order = $this->order_with_label( 111 );
		$this->api_client->expects( $this->once() )
			->method( 'get_label_status' )
			->with( '111' )
			->willReturn(
				(object) array(
					'label' => (object) array(
						'label_id' => 111,
						'status'   => 'DELIVERED',
					),
				)
			);

		$response = $this->send( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/111' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'DELIVERED', $this->labels_of( $order )[0]['status'] );
	}

	/**
	 * Refund for something that is not an order: 404, nothing refunded.
	 *
	 * @dataProvider not_an_order_provider
	 * @param string $kind Kind of ID.
	 */
	public function test_refund_for_an_id_that_is_not_an_order_is_refused( $kind ) {
		$this->api_client->expects( $this->never() )->method( 'send_shipping_label_refund_request' );

		$response = $this->send( 'POST', '/wc/v1/connect/label/' . $this->make_id( $kind ) . '/111/refund' );

		$this->assert_refused( $response, 'not_found', 404 );
	}

	/**
	 * Refund of another order's label through this order's URL: 404, nothing refunded
	 * upstream, and neither order's labels change.
	 */
	public function test_refund_of_a_label_of_another_order_is_refused() {
		$order_a = $this->order_with_label( 111 );
		$order_b = $this->order_with_label( 222 );
		$this->api_client->expects( $this->never() )->method( 'send_shipping_label_refund_request' );

		$response = $this->send( 'POST', '/wc/v1/connect/label/' . $order_a->get_id() . '/222/refund' );

		$this->assert_refused( $response, 'not_found', 404 );
		$this->assertSame( 'Shipping label not found', $response->get_data()['message'] );
		$this->assertArrayNotHasKey( 'refund', $this->labels_of( $order_a )[0] );
		$this->assertArrayNotHasKey( 'refund', $this->labels_of( $order_b )[0] );
	}

	/**
	 * Refund of the order's own label is sent upstream and saved on that order.
	 */
	public function test_refund_of_the_orders_own_label_is_saved() {
		$order  = $this->order_with_label( 111 );
		$refund = (object) array( 'status' => 'pending' );
		$this->api_client->expects( $this->once() )
			->method( 'send_shipping_label_refund_request' )
			->with( '111' )
			->willReturn(
				(object) array(
					'label'  => (object) array( 'id' => 111 ),
					'refund' => $refund,
				)
			);

		$response = $this->send( 'POST', '/wc/v1/connect/label/' . $order->get_id() . '/111/refund' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEquals( $refund, $this->labels_of( $order )[0]['refund'] );
	}

	/**
	 * A label stored with a string ID is refunded and the refund is saved on the order.
	 * Before, the check passed but the save compared strictly, so the refund was lost.
	 */
	public function test_refund_of_a_label_stored_with_a_string_id_is_saved() {
		$order  = $this->order_with_label( '444' );
		$refund = (object) array( 'status' => 'pending' );
		$this->api_client->expects( $this->once() )
			->method( 'send_shipping_label_refund_request' )
			->with( '444' )
			->willReturn(
				(object) array(
					'label'  => (object) array( 'id' => 444 ),
					'refund' => $refund,
				)
			);

		$response = $this->send( 'POST', '/wc/v1/connect/label/' . $order->get_id() . '/444/refund' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertEquals( $refund, $this->labels_of( $order )[0]['refund'] );
	}

	/**
	 * An order whose labels are still stored in the old JSON format.
	 *
	 * @param int $label_id Label ID.
	 * @return WC_Order
	 */
	private function order_with_json_label( $label_id ) {
		$order = WC_Helper_Order::create_order();
		$order->update_meta_data(
			'wc_connect_labels',
			wp_json_encode(
				array(
					array(
						'label_id' => $label_id,
						'status'   => 'PURCHASED',
					),
				)
			)
		);
		$order->save();

		return $order;
	}

	/**
	 * Labels stored in the old JSON format are still the order's own: status and refund
	 * work for them, as before.
	 */
	public function test_status_and_refund_of_a_label_stored_as_json_still_work() {
		$status_order = $this->order_with_json_label( 555 );
		$refund_order = $this->order_with_json_label( 666 );
		$refund       = (object) array( 'status' => 'pending' );
		$this->api_client->expects( $this->once() )
			->method( 'get_label_status' )
			->with( '555' )
			->willReturn(
				(object) array(
					'label' => (object) array(
						'label_id' => 555,
						'status'   => 'DELIVERED',
					),
				)
			);
		$this->api_client->expects( $this->once() )
			->method( 'send_shipping_label_refund_request' )
			->with( '666' )
			->willReturn(
				(object) array(
					'label'  => (object) array( 'id' => 666 ),
					'refund' => $refund,
				)
			);

		$status_response = $this->send( 'GET', '/wc/v1/connect/label/' . $status_order->get_id() . '/555' );
		$refund_response = $this->send( 'POST', '/wc/v1/connect/label/' . $refund_order->get_id() . '/666/refund' );

		$this->assertSame( 200, $status_response->get_status() );
		$this->assertSame( 'DELIVERED', $this->labels_of( $status_order )[0]['status'] );
		$this->assertSame( 200, $refund_response->get_status() );
		$this->assertEquals( $refund, $this->labels_of( $refund_order )[0]['refund'] );
	}

	/**
	 * A purchase body for one package holding the given product.
	 *
	 * @param int $product_id Product in the package.
	 * @return array
	 */
	private function purchase_body( $product_id ) {
		return array(
			'packages' => array(
				array(
					'box_id'       => 'individual',
					'service_id'   => 'pri',
					'carrier_id'   => 'usps',
					'service_name' => 'USPS - Priority Mail',
					'products'     => array( $product_id ),
				),
			),
		);
	}

	/**
	 * Buying a label for something that is not an order: 404, and no label is bought.
	 * Before, the label was bought upstream and then the request failed, so the paid
	 * label was recorded nowhere.
	 *
	 * @dataProvider not_an_order_provider
	 * @param string $kind Kind of ID.
	 */
	public function test_purchase_for_an_id_that_is_not_an_order_is_refused( $kind ) {
		$product = WC_Helper_Product::create_simple_product();
		$this->api_client->expects( $this->never() )->method( 'send_shipping_label_request' );

		$response = $this->send( 'POST', '/wc/v1/connect/label/' . $this->make_id( $kind ), $this->purchase_body( $product->get_id() ) );

		$this->assert_refused( $response, 'not_found', 404 );
	}

	/**
	 * Buying a label for an order still buys it and saves it on that order.
	 */
	public function test_purchase_for_an_order_saves_the_label_on_it() {
		$product = WC_Helper_Product::create_simple_product();
		$order   = WC_Helper_Order::create_order( 1, $product );
		$this->api_client->expects( $this->once() )
			->method( 'send_shipping_label_request' )
			->willReturn(
				(object) array(
					'labels' => array(
						(object) array(
							'label' => (object) array(
								'label_id'          => 333,
								'tracking_id'       => '',
								'refundable_amount' => 7.5,
								'created'           => 1,
								'carrier_id'        => 'usps',
								'status'            => 'PURCHASE_IN_PROGRESS',
							),
						),
					),
				)
			);

		$response = $this->send( 'POST', '/wc/v1/connect/label/' . $order->get_id(), $this->purchase_body( $product->get_id() ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 333, $this->labels_of( $order )[0]['label_id'] );
	}

	/**
	 * The settings store's order writers return instead of failing when the ID is not an
	 * order, for any caller that has not checked it first.
	 *
	 * @dataProvider not_an_order_provider
	 * @param string $kind Kind of ID.
	 */
	public function test_settings_store_order_writers_ignore_an_id_that_is_not_an_order( $kind ) {
		$id    = $this->make_id( $kind );
		$label = (object) array(
			'label_id' => 111,
			'status'   => 'DELIVERED',
		);

		$this->assertSame( $label, $this->settings_store->update_label_order_meta_data( $id, $label ) );
		$this->assertNull( $this->settings_store->add_labels_to_order( $id, array( array( 'label_id' => 111 ) ) ) );
		$this->assertNull( $this->settings_store->update_destination_address( $id, array( 'address' => '9 Dest Rd' ) ) );
		// A refund is an order object: with HPOS its meta is not in postmeta, so read it through CRUD.
		$object = wc_get_order( $id );
		if ( $object ) {
			$this->assertFalse( $object->meta_exists( 'wc_connect_labels' ) );
		} else {
			$this->assertSame( '', get_post_meta( $id, 'wc_connect_labels', true ) );
		}
	}

	/**
	 * Another order's label, through an order that has no labels at all: refused by both
	 * routes, and no wc_connect_labels row is created on the target order. Before, an empty
	 * list was saved there, which _has_any_labels_db_check() counts as a label.
	 */
	public function test_a_label_of_another_order_creates_no_label_meta_on_the_target_order() {
		$target = WC_Helper_Order::create_order();
		$this->order_with_label( 222 );
		$this->api_client->expects( $this->never() )->method( 'get_label_status' );
		$this->api_client->expects( $this->never() )->method( 'send_shipping_label_refund_request' );

		$this->assert_refused( $this->send( 'GET', '/wc/v1/connect/label/' . $target->get_id() . '/222' ), 'not_found', 404 );
		$this->assert_refused( $this->send( 'POST', '/wc/v1/connect/label/' . $target->get_id() . '/222/refund' ), 'not_found', 404 );

		$this->assertFalse( wc_get_order( $target->get_id() )->meta_exists( 'wc_connect_labels' ) );
	}

	/**
	 * The settings store does not save a label list for a label the order does not have.
	 */
	public function test_settings_store_saves_nothing_for_a_label_the_order_does_not_have() {
		$order = WC_Helper_Order::create_order();
		$label = (object) array(
			'label_id' => 222,
			'status'   => 'DELIVERED',
		);

		$this->assertSame( $label, $this->settings_store->update_label_order_meta_data( $order->get_id(), $label ) );
		$this->assertFalse( wc_get_order( $order->get_id() )->meta_exists( 'wc_connect_labels' ) );
	}

	/**
	 * A trashed order is still an order: its labels can still be checked, as before.
	 */
	public function test_status_for_a_trashed_orders_own_label_is_still_updated() {
		$order    = $this->order_with_label( 111 );
		$order_id = $order->get_id();
		$order->delete( false );
		$order = wc_get_order( $order_id );
		$this->assertSame( 'trash', $order->get_status() );
		$this->api_client->expects( $this->once() )
			->method( 'get_label_status' )
			->willReturn(
				(object) array(
					'label' => (object) array(
						'label_id' => 111,
						'status'   => 'DELIVERED',
					),
				)
			);

		$response = $this->send( 'GET', '/wc/v1/connect/label/' . $order->get_id() . '/111' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'DELIVERED', $this->labels_of( $order )[0]['status'] );
	}
}
