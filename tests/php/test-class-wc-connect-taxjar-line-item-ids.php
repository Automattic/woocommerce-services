<?php
/**
 * TaxJar line item ids: the product they name, and how each path finds a line back.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Line_Item_Ids
 *
 * A line item is sent to TaxJar as `<product_id>-<fingerprint>-<occurrence>`. The
 * product id in front is how get_itemized_tax_rates() picks the tax class whose rows
 * the answer is written to, and how the cart and checkout find a product's line in
 * the answer. These tests drive the admin Recalculate through the hooks init()
 * registers, with TaxJar mocked, and call the matching directly.
 */
class WP_Test_WC_Connect_TaxJar_Line_Item_Ids extends WC_Unit_Test_Case {

	/**
	 * A tax class whose name ends in a TaxJar product tax code.
	 */
	const CLOTHING = 'Clothing 20010';

	/**
	 * TaxJar's components for a line with no product tax code: 6% state tax.
	 *
	 * @var array
	 */
	private static $general = array(
		'city_tax_rate'        => 0.0,
		'county_tax_rate'      => 0.0,
		'special_tax_rate'     => 0.0,
		'state_sales_tax_rate' => 0.06,
	);

	/**
	 * TaxJar's components for a line sent with the clothing code: 5% state tax.
	 *
	 * @var array
	 */
	private static $clothing = array(
		'city_tax_rate'        => 0.0,
		'county_tax_rate'      => 0.0,
		'special_tax_rate'     => 0.0,
		'state_sales_tax_rate' => 0.05,
	);

	/**
	 * Slug of the clothing tax class.
	 *
	 * @var string
	 */
	private $clothing_slug;

	/**
	 * Products created by a test, deleted in tear_down().
	 *
	 * @var WC_Product[]
	 */
	private $products = array();

	/**
	 * Load the classes under test.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../classes/class-wc-connect-taxjar-integration.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-api-client.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-logger.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-tracks.php';
	}

	/**
	 * Taxes on, an empty rate table and the clothing tax class.
	 */
	public function set_up() {
		parent::set_up();

		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		WC_Cache_Helper::invalidate_cache_group( 'taxes' );

		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );
		update_option( 'woocommerce_tax_based_on', 'shipping' );
		update_option( 'woocommerce_shipping_tax_class', 'inherit' );

		$created             = WC_Tax::create_tax_class( self::CLOTHING );
		$this->clothing_slug = is_wp_error( $created ) ? 'clothing-20010' : $created['slug'];
	}

	/**
	 * Undo set_up().
	 */
	public function tear_down() {
		WC()->cart->empty_cart();
		foreach ( $this->products as $product ) {
			$product->delete( true );
		}
		$this->products = array();

		WC_Tax::delete_tax_class_by( 'slug', $this->clothing_slug );
		delete_option( 'woocommerce_calc_taxes' );

		parent::tear_down();
	}

	/**
	 * An integration for a store in Marquette, MI. TaxJar answers each line with the
	 * clothing components when it carries a product tax code, the general ones when not.
	 *
	 * @param bool $freight_taxable Whether TaxJar taxes shipping, at the general components.
	 * @return WC_Connect_TaxJar_Integration
	 */
	private function taxjar( $freight_taxable = false ) {
		$integration = $this->getMockBuilder( 'WC_Connect_TaxJar_Integration' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'smartcalcs_cache_request', 'get_store_settings', '_log' ) )
			->getMock();

		$integration->method( 'get_store_settings' )->willReturn(
			array(
				'country'  => 'US',
				'state'    => 'MI',
				'postcode' => '49855',
				'city'     => 'Marquette',
				'street'   => '1 Main St',
			)
		);

		$integration->method( 'smartcalcs_cache_request' )->willReturnCallback(
			function ( $json ) use ( $freight_taxable ) {
				$body  = json_decode( $json, true );
				$lines = array();
				foreach ( $body['line_items'] ?? array() as $line_item ) {
					$components = '' !== (string) $line_item['product_tax_code'] ? self::$clothing : self::$general;
					$lines[]    = array_merge(
						array(
							'id'                => $line_item['id'],
							'combined_tax_rate' => array_sum( $components ),
						),
						$components
					);
				}

				$breakdown = array( 'line_items' => $lines );
				if ( $freight_taxable ) {
					$breakdown['shipping'] = array_merge( array( 'combined_tax_rate' => array_sum( self::$general ) ), self::$general );
				}

				return array(
					'body' => wp_json_encode(
						array(
							'tax' => array(
								'rate'            => array_sum( self::$general ),
								'freight_taxable' => $freight_taxable,
								'has_nexus'       => true,
								'jurisdictions'   => array(
									'county' => 'MARQUETTE',
									'city'   => strtoupper( (string) ( $body['to_city'] ?? '' ) ),
								),
								'breakdown'       => $breakdown,
							),
						)
					),
				);
			}
		);

		return $integration;
	}

	/**
	 * A plain integration, for calling the line matching directly.
	 *
	 * @return WC_Connect_TaxJar_Integration
	 */
	private function integration() {
		return new WC_Connect_TaxJar_Integration(
			$this->getMockBuilder( 'WC_Connect_API_Client' )->disableOriginalConstructor()->getMock(),
			$this->getMockBuilder( 'WC_Connect_Logger' )->disableOriginalConstructor()->getMock(),
			'https://example.com',
			$this->getMockBuilder( 'WC_Connect_Tracks' )->disableOriginalConstructor()->getMock()
		);
	}

	/**
	 * A $100 simple product in the Standard class.
	 *
	 * @return WC_Product_Simple
	 */
	private function simple_product() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 100 );
		$product->save();
		$this->products[] = $product;

		return $product;
	}

	/**
	 * A variable product in the Standard class, with one $100 variation per tax class
	 * given. 'parent' is a variation left at "Same as parent".
	 *
	 * @param string[] $tax_classes Each variation's tax class.
	 * @return WC_Product_Variation[]
	 */
	private function variations( array $tax_classes ) {
		$parent = new WC_Product_Variable();
		$parent->set_name( 'Tee' );
		$parent->set_tax_class( '' );
		$parent->save();
		$this->products[] = $parent;

		$variations = array();
		foreach ( $tax_classes as $tax_class ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $parent->get_id() );
			$variation->set_regular_price( 100 );
			$variation->set_tax_class( $tax_class );
			$variation->save();
			$variations[] = $variation;
		}

		// A variation set to its own class must not read as "Same as parent".
		foreach ( $variations as $index => $variation ) {
			$expected = 'parent' === $tax_classes[ $index ] ? '' : $tax_classes[ $index ];
			$this->assertSame( $expected, wc_get_product( $variation->get_id() )->get_tax_class(), 'Variation tax class was not saved.' );
		}

		return $variations;
	}

	/**
	 * An order shipped to Gwinn, MI, holding one of each product.
	 *
	 * @param WC_Product[] $products Products.
	 * @param float        $shipping Shipping total, 0 for none.
	 * @return WC_Order
	 */
	private function order( array $products, $shipping = 0.0 ) {
		$address = array(
			'country'   => 'US',
			'state'     => 'MI',
			'postcode'  => '49841',
			'city'      => 'Gwinn',
			'address_1' => '1 Test St',
		);

		$order = wc_create_order();
		$order->set_billing_address( $address );
		$order->set_shipping_address( $address );
		foreach ( $products as $product ) {
			$order->add_product( $product, 1 );
		}
		if ( $shipping ) {
			$item = new WC_Order_Item_Shipping();
			$item->set_method_title( 'Flat rate' );
			$item->set_method_id( 'flat_rate' );
			$item->set_total( $shipping );
			$order->add_item( $item );
		}
		$order->calculate_totals( false );
		$order->save();

		return $order;
	}

	/**
	 * Press Recalculate on the order, with the hooks init() registers for that path.
	 *
	 * @param WC_Order                      $order       Order.
	 * @param WC_Connect_TaxJar_Integration $integration Integration under test.
	 * @return WC_Order The order read back.
	 */
	private function admin_recalculate( $order, $integration ) {
		if ( ! class_exists( \Automattic\WooCommerce\Internal\Orders\TaxesController::class ) ) {
			$this->markTestSkipped( 'This WooCommerce version has no TaxesController.' );
		}

		$post = array(
			'order_id' => $order->get_id(),
			'country'  => 'US',
			'state'    => 'MI',
			'postcode' => '49841',
			'city'     => 'Gwinn',
			'street'   => '1 Test St',
			'items'    => '',
		);

		$saved_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Saved to restore after the simulated request.
		$_POST      = $post;
		add_action( 'woocommerce_before_save_order_items', array( $integration, 'calculate_backend_totals' ), 20 );
		add_action( 'woocommerce_order_item_after_calculate_taxes', array( $integration, 'override_order_item_taxes' ), 10, 2 );
		add_filter( 'wp_doing_ajax', '__return_true' );

		try {
			wc_get_container()->get( \Automattic\WooCommerce\Internal\Orders\TaxesController::class )->calc_line_taxes( $post );
		} finally {
			$_POST = $saved_post;
			remove_action( 'woocommerce_before_save_order_items', array( $integration, 'calculate_backend_totals' ), 20 );
			remove_action( 'woocommerce_order_item_after_calculate_taxes', array( $integration, 'override_order_item_taxes' ), 10 );
			remove_filter( 'wp_doing_ajax', '__return_true' );
		}

		return wc_get_order( $order->get_id() );
	}

	/**
	 * The state tax row of a tax class, as a percent string, or null when it has none.
	 *
	 * @param string $tax_class Tax class slug, '' for Standard.
	 * @return string|null
	 */
	private function state_rate( $tax_class ) {
		global $wpdb;

		$rates = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare( "SELECT tax_rate FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class = %s AND tax_rate_name = %s", $tax_class, 'MARQUETTE GWINN : State Sales Tax' ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		$this->assertLessThanOrEqual( 1, count( $rates ), 'More than one state row in the class.' );

		return $rates ? (string) (float) $rates[0] : null;
	}

	/**
	 * Tax charged on an order item.
	 *
	 * @param WC_Order_Item_Product $item Order item.
	 * @return float
	 */
	private static function item_tax( $item ) {
		return (float) array_sum( $item->get_taxes()['total'] );
	}

	/**
	 * Call a private method of the integration.
	 *
	 * @param WC_Connect_TaxJar_Integration $integration Integration.
	 * @param string                        $method      Method name.
	 * @param array                         $args        Arguments.
	 * @return mixed
	 */
	private static function call( $integration, $method, array $args ) {
		$reflection = new ReflectionMethod( 'WC_Connect_TaxJar_Integration', $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( $integration, $args );
	}

	/**
	 * Set a private property of the integration.
	 *
	 * @param WC_Connect_TaxJar_Integration $integration Integration.
	 * @param string                        $property    Property name.
	 * @param mixed                         $value       Value.
	 */
	private static function set( $integration, $property, $value ) {
		$reflection = new ReflectionProperty( 'WC_Connect_TaxJar_Integration', $property );
		$reflection->setAccessible( true );
		$reflection->setValue( $integration, $value );
	}

	/**
	 * A Standard-class rate row with a distinct percent, for telling rows apart.
	 *
	 * @param float $rate Percent.
	 * @return int Rate id.
	 */
	private static function rate_row( $rate ) {
		return (int) WC_Tax::_insert_tax_rate(
			array(
				'tax_rate_country'  => 'US',
				'tax_rate_state'    => 'MI',
				'tax_rate_name'     => 'Tax ' . $rate,
				'tax_rate_priority' => 1,
				'tax_rate_compound' => 0,
				'tax_rate_shipping' => 1,
				'tax_rate'          => (string) $rate,
				'tax_rate_class'    => '',
			)
		);
	}

	/**
	 * Recalculate writes a variation's answer to the variation's own tax class, and
	 * leaves the parent's class to the products that are in it.
	 */
	public function test_recalculate_writes_a_variation_to_its_own_tax_class() {
		$standard  = $this->simple_product();
		$variation = $this->variations( array( $this->clothing_slug ) )[0];

		// The variation is the order's last line, so its answer is written last.
		$order = $this->admin_recalculate( $this->order( array( $standard, $variation ) ), $this->taxjar() );

		$this->assertSame( '6', $this->state_rate( '' ), 'Standard rows hold the simple product\'s 6%.' );
		$this->assertSame( '5', $this->state_rate( $this->clothing_slug ), 'Clothing rows hold the variation\'s 5%.' );

		$taxed = array();
		foreach ( $order->get_items() as $item ) {
			$taxed[ $item->get_variation_id() ? 'variation' : 'simple' ] = self::item_tax( $item );
		}
		$this->assertEqualsWithDelta( 6.0, $taxed['simple'], 0.001, '$100 at 6%' );
		$this->assertEqualsWithDelta( 5.0, $taxed['variation'], 0.001, '$100 at 5%' );
	}

	/**
	 * Two variations of one product in different tax classes each write their own
	 * class, so the second does not overwrite the first.
	 */
	public function test_recalculate_keeps_sibling_variations_in_their_own_tax_classes() {
		$variations = $this->variations( array( 'parent', $this->clothing_slug ) );

		$this->admin_recalculate( $this->order( $variations ), $this->taxjar() );

		$this->assertSame( '6', $this->state_rate( '' ), 'Standard rows hold the "Same as parent" variation\'s 6%.' );
		$this->assertSame( '5', $this->state_rate( $this->clothing_slug ), 'Clothing rows hold the other variation\'s 5%.' );
	}

	/**
	 * With the shipping tax class left to inherit, an order of one clothing variation
	 * has its shipping taxed from the clothing rows. Recalculate writes those rows, so
	 * shipping keeps its tax: $10 at the clothing class's 5%.
	 */
	public function test_recalculate_taxes_shipping_that_inherits_a_variation_tax_class() {
		$variation = $this->variations( array( $this->clothing_slug ) )[0];

		$order = $this->admin_recalculate( $this->order( array( $variation ), 10 ), $this->taxjar( true ) );

		$this->assertEqualsWithDelta( 5.0, (float) $order->get_cart_tax(), 0.001, 'Item tax' );
		$this->assertEqualsWithDelta( 0.5, (float) $order->get_shipping_tax(), 0.001, 'Shipping tax' );
	}

	/**
	 * The cart and an admin order send a variation to TaxJar under the same line id,
	 * so the same basket shares one cached answer whichever path asks.
	 */
	public function test_cart_and_order_send_a_variation_under_the_same_line_id() {
		$variation = $this->variations( array( $this->clothing_slug ) )[0];

		$this->assertNotFalse( WC()->cart->add_to_cart( $variation->get_parent_id(), 1, $variation->get_id() ) );
		$integration = $this->integration();
		$cart_lines  = self::call( $integration, 'get_line_items', array( WC()->cart ) );
		$order_lines = self::call( $integration, 'get_backend_line_items', array( $this->order( array( $variation ) ) ) );

		$this->assertCount( 1, $cart_lines );
		$this->assertCount( 1, $order_lines );
		$this->assertStringStartsWith( $variation->get_id() . '-', reset( $cart_lines )['id'] );
		$this->assertSame( reset( $cart_lines )['id'], reset( $order_lines )['id'] );
	}

	/**
	 * Answers for this product and for one whose id begins with this one's digits, in
	 * both orders. The other product need not exist: only its line id is read.
	 *
	 * @return array
	 */
	public function provide_answer_orders() {
		return array(
			'other product answered first' => array( true ),
			'this product answered first'  => array( false ),
		);
	}

	/**
	 * A cart item with no recorded line takes its own product's line, not the line of a
	 * product whose id starts with the same digits (product 1 and product 10).
	 *
	 * @dataProvider provide_answer_orders
	 *
	 * @param bool $other_first Whether the other product's line comes first in the answer.
	 */
	public function test_cart_item_without_a_recorded_line_takes_its_own_product_line( $other_first ) {
		$product = $this->simple_product();
		$own     = self::rate_row( 6 );
		$other   = self::rate_row( 9 );

		$lines = array(
			$product->get_id() . '-aaaaaaaaaaaa-0'  => array( $own ),
			$product->get_id() . '0-bbbbbbbbbbbb-0' => array( $other ),
		);
		if ( $other_first ) {
			$lines = array_reverse( $lines, true );
		}

		$integration = $this->integration();
		self::set( $integration, 'response_rate_ids', $lines );
		self::set( $integration, 'response_cart_item_keys', array() );

		$item          = new stdClass();
		$item->product = $product;
		$item->key     = 'a-cart-item-key-with-no-recorded-line';

		$rates = $integration->override_cart_item_tax_rates( array(), $item, WC()->cart );

		$this->assertSame( array( $own ), array_keys( $rates ) );
	}

	/**
	 * At checkout, an order item TaxJar answered as exempt is matched to its own line,
	 * not to the taxed line of a product whose id starts with the same digits. That
	 * line would be the only taxed candidate, so a looser match would charge its rate.
	 */
	public function test_checkout_order_item_takes_its_own_product_line() {
		$product = $this->simple_product();
		$own_key = $product->get_id() . '-aaaaaaaaaaaa-0';
		$other   = $product->get_id() . '0-bbbbbbbbbbbb-0';

		$integration = $this->integration();
		self::set(
			$integration,
			'response_line_items',
			array(
				$other   => (object) array_merge( array( 'combined_tax_rate' => 0.06 ), self::$general ),
				$own_key => (object) array( 'combined_tax_rate' => 0.0 ),
			)
		);
		self::set( $integration, 'response_rate_ids', array( $other => array( self::rate_row( 6 ) ) ) );
		self::set( $integration, 'response_cart_item_keys', array() );

		$item = new WC_Order_Item_Product();
		$item->set_product( $product );

		$this->assertSame( $own_key, self::call( $integration, 'find_order_item_line_key', array( $item ) ) );
	}
}
