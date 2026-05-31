<?php
/**
 * Tests for WC_Connect_TaxJar_Address.
 *
 * @package WooCommerce\Tests
 */

/**
 * Class WP_Test_WC_Connect_TaxJar_Address
 */
class WP_Test_WC_Connect_TaxJar_Address extends WC_Unit_Test_Case {

	/**
	 * Load the address class once.
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();
		require_once __DIR__ . '/../../classes/class-wc-connect-taxjar-address.php';
	}

	/* ──── Factories — each input shape produces the same conceptual address ──── */

	/**
	 * Empty factory: every field is empty and is_empty() is true.
	 */
	public function test_empty_factory_returns_empty_address() {
		$address = WC_Connect_TaxJar_Address::empty();

		$this->assertSame( '', $address->country() );
		$this->assertSame( '', $address->state() );
		$this->assertSame( '', $address->postcode() );
		$this->assertSame( '', $address->city() );
		$this->assertSame( '', $address->street() );
		$this->assertNull( $address->id() );
		$this->assertTrue( $address->is_empty() );
	}

	/**
	 * Positional 5-tuple → address. Uppercases country + state, normalises city.
	 */
	public function test_from_taxable_tuple_extracts_positions_in_order() {
		$tuple   = array( 'us', 'fl', '33033', 'Casse;Berry', '337 W 26th Ave' );
		$address = WC_Connect_TaxJar_Address::from_taxable_tuple( $tuple );

		$this->assertSame( 'US', $address->country() );
		$this->assertSame( 'FL', $address->state() );
		$this->assertSame( '33033', $address->postcode() );
		$this->assertSame( 'Casse Berry', $address->city() );
		$this->assertSame( '337 W 26th Ave', $address->street() );
	}

	/**
	 * Short tuples don't crash — missing positions become empty strings.
	 */
	public function test_from_taxable_tuple_missing_positions_become_empty() {
		$address = WC_Connect_TaxJar_Address::from_taxable_tuple( array( 'CA', 'ON' ) );

		$this->assertSame( 'CA', $address->country() );
		$this->assertSame( 'ON', $address->state() );
		$this->assertSame( '', $address->postcode() );
		$this->assertSame( '', $address->city() );
		$this->assertSame( '', $address->street() );
	}

	/**
	 * `from_options` accepts both 'to_' and 'from_' prefixes.
	 */
	public function test_from_options_supports_to_and_from_prefix() {
		$opts = array(
			'to_country'   => 'us',
			'to_state'     => 'fl',
			'to_zip'       => '33033',
			'to_city'      => 'Homestead',
			'to_street'    => '337 W 26th Ave',
			'from_country' => 'us',
			'from_state'   => 'NY',
			'from_zip'     => '10001',
		);

		$to   = WC_Connect_TaxJar_Address::from_options( $opts, 'to_' );
		$from = WC_Connect_TaxJar_Address::from_options( $opts, 'from_' );

		$this->assertSame( 'FL', $to->state() );
		$this->assertSame( '33033', $to->postcode() );
		$this->assertSame( 'NY', $from->state() );
		$this->assertSame( '10001', $from->postcode() );
		$this->assertSame( '', $from->city() );
	}

	/**
	 * `from_nexus` round-trips the `id` field if present.
	 */
	public function test_from_nexus_carries_id_field() {
		$nexus = array(
			'id'      => 'nexus-1',
			'country' => 'US',
			'zip'     => '10001',
			'state'   => 'NY',
			'city'    => 'New York',
			'street'  => '5th Ave',
		);

		$address = WC_Connect_TaxJar_Address::from_nexus( $nexus );

		$this->assertSame( 'nexus-1', $address->id() );
		$this->assertSame( 'US', $address->country() );
		$this->assertSame( '10001', $address->postcode() );
	}

	/**
	 * `from_store_settings` accepts the `get_store_settings()` shape.
	 */
	public function test_from_store_settings_accepts_postcode_key() {
		$settings = array(
			'country'  => 'US',
			'state'    => 'FL',
			'postcode' => '33033',
			'city'     => 'Homestead',
			'street'   => '337 W 26th Ave',
		);

		$address = WC_Connect_TaxJar_Address::from_store_settings( $settings );

		$this->assertSame( 'US', $address->country() );
		$this->assertSame( '33033', $address->postcode() );
	}

	/**
	 * `from_post_request` runs `wc_clean()` on values and tolerates missing keys.
	 */
	public function test_from_post_request_with_override_applies_wc_clean() {
		$override = array(
			'country' => '  US  ',
			'state'   => 'fl',
			'city'    => 'Casse;Berry',
		);

		$address = WC_Connect_TaxJar_Address::from_post_request( $override );

		$this->assertSame( 'US', $address->country() );
		$this->assertSame( 'FL', $address->state() );
		$this->assertSame( 'Casse Berry', $address->city() );
		$this->assertSame( '', $address->postcode() );
	}

	/**
	 * `from_jurisdictions` accepts an `stdClass` (matches TaxJar response shape).
	 */
	public function test_from_jurisdictions_extracts_partial_fields() {
		$jur          = new stdClass();
		$jur->country = 'US';
		$jur->state   = 'FL';
		$jur->city    = 'Homestead';

		$address = WC_Connect_TaxJar_Address::from_jurisdictions( $jur );

		$this->assertSame( 'US', $address->country() );
		$this->assertSame( 'FL', $address->state() );
		$this->assertSame( 'Homestead', $address->city() );
		$this->assertSame( '', $address->postcode() );
		$this->assertSame( '', $address->street() );
	}

	/**
	 * `from_jurisdictions( null )` returns an empty address (defensive null guard).
	 */
	public function test_from_jurisdictions_null_returns_empty() {
		$address = WC_Connect_TaxJar_Address::from_jurisdictions( null );
		$this->assertTrue( $address->is_empty() );
	}

	/**
	 * `from_customer_billing` reads each WC_Customer billing getter.
	 */
	public function test_from_customer_billing_reads_billing_getters() {
		$customer = $this->getMockBuilder( 'WC_Customer' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_billing_country', 'get_billing_state', 'get_billing_postcode', 'get_billing_city', 'get_billing_address' ) )
			->getMock();

		$customer->method( 'get_billing_country' )->willReturn( 'US' );
		$customer->method( 'get_billing_state' )->willReturn( 'FL' );
		$customer->method( 'get_billing_postcode' )->willReturn( '33033' );
		$customer->method( 'get_billing_city' )->willReturn( 'Homestead' );
		$customer->method( 'get_billing_address' )->willReturn( '337 W 26th Ave' );

		$address = WC_Connect_TaxJar_Address::from_customer_billing( $customer );

		$this->assertSame( 'US', $address->country() );
		$this->assertSame( 'FL', $address->state() );
		$this->assertSame( '33033', $address->postcode() );
		$this->assertSame( 'Homestead', $address->city() );
		$this->assertSame( '337 W 26th Ave', $address->street() );
	}

	/**
	 * `from_customer_shipping` reads each WC_Customer shipping getter.
	 */
	public function test_from_customer_shipping_reads_shipping_getters() {
		$customer = $this->getMockBuilder( 'WC_Customer' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_shipping_country', 'get_shipping_state', 'get_shipping_postcode', 'get_shipping_city', 'get_shipping_address' ) )
			->getMock();

		$customer->method( 'get_shipping_country' )->willReturn( 'CA' );
		$customer->method( 'get_shipping_state' )->willReturn( 'ON' );
		$customer->method( 'get_shipping_postcode' )->willReturn( 'M5A 1A1' );
		$customer->method( 'get_shipping_city' )->willReturn( 'Toronto' );
		$customer->method( 'get_shipping_address' )->willReturn( '100 Queen St W' );

		$address = WC_Connect_TaxJar_Address::from_customer_shipping( $customer );

		$this->assertSame( 'CA', $address->country() );
		$this->assertSame( 'ON', $address->state() );
		$this->assertSame( 'M5A 1A1', $address->postcode() );
	}

	/* ──── Normalisation invariants — applied at construction ──── */

	/**
	 * Comma-list postcodes resolve to the first segment.
	 */
	public function test_first_postcode_segment_is_used() {
		$address = WC_Connect_TaxJar_Address::from_options( array( 'to_zip' => '33033, 33034, 33035' ) );

		$this->assertSame( '33033', $address->postcode() );
	}

	/**
	 * `state_compact()` strips internal spaces (e.g. accidental form input).
	 */
	public function test_state_compact_strips_spaces() {
		$address = WC_Connect_TaxJar_Address::from_options( array( 'to_state' => 'N Y' ) );

		$this->assertSame( 'N Y', $address->state() );
		$this->assertSame( 'NY', $address->state_compact() );
	}

	/**
	 * Address-level `normalize_city` matches the WOOTAX-19 contract.
	 *
	 * @dataProvider normalize_city_provider
	 *
	 * @param string $input    Raw city.
	 * @param string $expected Expected normalised value.
	 */
	public function test_normalize_city_strips_semicolons( $input, $expected ) {
		$this->assertSame( $expected, WC_Connect_TaxJar_Address::normalize_city( $input ) );
	}

	/**
	 * Data provider mirroring the WOOTAX-19 test cases on the integration class.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public function normalize_city_provider() {
		return array(
			'no semicolon — unchanged'            => array( 'New York', 'New York' ),
			'simple semicolon between words'      => array( 'Casse;Berry', 'Casse Berry' ),
			'semicolon with following space'      => array( 'Casse; Berry', 'Casse Berry' ),
			'leading semicolon'                   => array( ';Casselberry', 'Casselberry' ),
			'trailing semicolon'                  => array( 'Casselberry;', 'Casselberry' ),
			'consecutive semicolons'              => array( 'Casse;;Berry', 'Casse Berry' ),
			'wrapped in whitespace'               => array( '  Casselberry  ', 'Casselberry' ),
			'empty string'                        => array( '', '' ),
			'multi-segment with mixed separators' => array( ' Casse; ;Berry ', 'Casse Berry' ),
		);
	}

	/* ──── Output methods — one per consumer shape ──── */

	/**
	 * `to_taxable_tuple` returns positional `[country, state, postcode, city, street]`.
	 */
	public function test_to_taxable_tuple_returns_positional_5_tuple() {
		$address = WC_Connect_TaxJar_Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'FL',
				'to_zip'     => '33033',
				'to_city'    => 'Homestead',
				'to_street'  => '337 W 26th Ave',
			)
		);

		$this->assertSame(
			array( 'US', 'FL', '33033', 'Homestead', '337 W 26th Ave' ),
			$address->to_taxable_tuple()
		);
	}

	/**
	 * `to_taxjar_body` emits `zip` (not `postcode`) and supports both prefixes.
	 */
	public function test_to_taxjar_body_uses_zip_not_postcode_and_supports_both_prefixes() {
		$address = WC_Connect_TaxJar_Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'FL',
				'to_zip'     => '33033',
				'to_city'    => 'Homestead',
				'to_street'  => '337 W 26th Ave',
			)
		);

		$to_body   = $address->to_taxjar_body( 'to_' );
		$from_body = $address->to_taxjar_body( 'from_' );

		$this->assertArrayHasKey( 'to_zip', $to_body );
		$this->assertArrayNotHasKey( 'to_postcode', $to_body );
		$this->assertSame( '33033', $to_body['to_zip'] );
		$this->assertSame( '33033', $from_body['from_zip'] );
		$this->assertSame( 'Homestead', $from_body['from_city'] );
	}

	/**
	 * `to_nexus_array` only includes the `id` key when the address carries one.
	 */
	public function test_to_nexus_array_omits_id_when_absent_and_includes_when_present() {
		$without_id = WC_Connect_TaxJar_Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'FL',
				'to_zip'     => '33033',
			)
		);

		$with_id = WC_Connect_TaxJar_Address::from_nexus(
			array(
				'id'      => 'nexus-1',
				'country' => 'US',
				'state'   => 'FL',
				'zip'     => '33033',
			)
		);

		$this->assertArrayNotHasKey( 'id', $without_id->to_nexus_array() );
		$this->assertSame( 'nexus-1', $with_id->to_nexus_array()['id'] );
	}

	/**
	 * `to_find_rates_args` compacts state and uppercases city to match WC core's lookup contract.
	 */
	public function test_to_find_rates_args_compacts_state_and_uppercases_city() {
		$address = WC_Connect_TaxJar_Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'N Y',
				'to_zip'     => '10001',
				'to_city'    => 'New York',
			)
		);

		$args = $address->to_find_rates_args( 'standard' );

		$this->assertSame( 'NY', $args['state'] );
		$this->assertSame( 'NEW YORK', $args['city'] );
		$this->assertSame( 'standard', $args['tax_class'] );
		$this->assertArrayNotHasKey( 'street', $args );
	}

	/**
	 * `to_legacy_options` converts empty strings to `false` for backward compatibility.
	 */
	public function test_to_legacy_options_converts_empty_strings_to_false() {
		$address = WC_Connect_TaxJar_Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'FL',
				// to_zip, to_city, to_street omitted.
			)
		);

		$legacy = $address->to_legacy_options( 'to_' );

		$this->assertSame( 'US', $legacy['to_country'] );
		$this->assertSame( 'FL', $legacy['to_state'] );
		$this->assertFalse( $legacy['to_zip'] );
		$this->assertFalse( $legacy['to_city'] );
		$this->assertFalse( $legacy['to_street'] );
	}

	/* ──── Validation ──── */

	/**
	 * `validate()` returns a WP_Error with no errors for a complete US address.
	 */
	public function test_validate_passes_for_well_formed_address() {
		$address = WC_Connect_TaxJar_Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'FL',
				'to_zip'     => '33033',
				'to_city'    => 'Homestead',
			)
		);

		$errors = $address->validate();
		$this->assertFalse( $errors->has_errors() );
	}

	/**
	 * Missing required country surfaces with the `address.country.required` error code.
	 */
	public function test_validate_flags_missing_required_country_with_specific_code() {
		$address = WC_Connect_TaxJar_Address::from_options(
			array(
				'to_state' => 'FL',
			)
		);

		$errors = $address->validate( array( 'country', 'state' ) );

		$this->assertTrue( $errors->has_errors() );
		$this->assertNotEmpty( $errors->get_error_messages( 'address.country.required' ) );
	}

	/**
	 * Three-letter codes don't match the `^[A-Z]{2}$` country pattern.
	 */
	public function test_validate_country_pattern_rejects_lowercase_or_long_code() {
		// `from_taxable_tuple` uppercases on construction, so we can't get a
		// lowercase pattern violation through the public factories. Use a
		// 3-letter country to force a pattern miss instead.
		$address = WC_Connect_TaxJar_Address::from_options(
			array(
				'to_country' => 'USA',
				'to_state'   => 'FL',
			)
		);

		$errors = $address->validate();
		$this->assertNotEmpty( $errors->get_error_messages( 'address.country.pattern' ) );
	}

	/**
	 * City over 100 characters raises `address.city.max_length`.
	 */
	public function test_validate_max_length_violation_for_city() {
		$long_city = str_repeat( 'A', 101 );
		$address   = WC_Connect_TaxJar_Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'FL',
				'to_city'    => $long_city,
			)
		);

		$errors = $address->validate();
		$this->assertNotEmpty( $errors->get_error_messages( 'address.city.max_length' ) );
	}

	/* ──── is_empty ──── */

	/**
	 * `is_empty()` only returns true when every core field is blank.
	 */
	public function test_is_empty_returns_true_only_when_all_core_fields_blank() {
		$blank   = WC_Connect_TaxJar_Address::empty();
		$partial = WC_Connect_TaxJar_Address::from_options( array( 'to_country' => 'US' ) );

		$this->assertTrue( $blank->is_empty() );
		$this->assertFalse( $partial->is_empty() );
	}
}
