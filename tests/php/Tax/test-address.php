<?php
/**
 * Tests for Automattic\WCServices\Tax\Address.
 *
 * @package WooCommerce\Tests
 */

use Automattic\WCServices\Tax\Address;

/**
 * Class WP_Test_WCServices_Tax_Address
 */
class WP_Test_WCServices_Tax_Address extends WC_Unit_Test_Case {

	/* ──── Factories — each input shape produces the same conceptual address ──── */

	/**
	 * Empty factory: every field is empty and is_empty() is true.
	 */
	public function test_empty_factory_returns_empty_address() {
		$address = Address::empty();

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
		$address = Address::from_taxable_tuple( $tuple );

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
		$address = Address::from_taxable_tuple( array( 'CA', 'ON' ) );

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

		$to   = Address::from_options( $opts, 'to_' );
		$from = Address::from_options( $opts, 'from_' );

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

		$address = Address::from_nexus( $nexus );

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

		$address = Address::from_store_settings( $settings );

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

		$address = Address::from_post_request( $override );

		$this->assertSame( 'US', $address->country() );
		$this->assertSame( 'FL', $address->state() );
		$this->assertSame( 'Casse Berry', $address->city() );
		$this->assertSame( '', $address->postcode() );
	}

	/**
	 * `from_post_request` unslashes the real `$_POST` superglobal.
	 *
	 * WP magic-quotes slashes `$_POST` and `wc_clean()` does not unslash, so without
	 * `wp_unslash()` a value like `O'Brien` would be stored as `O\'Brien`.
	 */
	public function test_from_post_request_unslashes_superglobal() {
		$original_post = $_POST;

		// Simulate the slashing WordPress applies to $_POST on real requests.
		$_POST = array(
			'country' => 'US',
			'city'    => "O\\'Brien",
			'street'  => "123 O\\'Malley St",
		);

		$address = Address::from_post_request();

		$this->assertSame( "O'Brien", $address->city() );
		$this->assertSame( "123 O'Malley St", $address->street() );

		$_POST = $original_post;
	}

	/**
	 * `from_jurisdictions` accepts an `stdClass` (matches TaxJar response shape).
	 */
	public function test_from_jurisdictions_extracts_partial_fields() {
		$jur          = new stdClass();
		$jur->country = 'US';
		$jur->state   = 'FL';
		$jur->city    = 'Homestead';

		$address = Address::from_jurisdictions( $jur );

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
		$address = Address::from_jurisdictions( null );
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

		$address = Address::from_customer_billing( $customer );

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

		$address = Address::from_customer_shipping( $customer );

		$this->assertSame( 'CA', $address->country() );
		$this->assertSame( 'ON', $address->state() );
		$this->assertSame( 'M5A 1A1', $address->postcode() );
	}

	/* ──── Normalisation invariants — applied at construction ──── */

	/**
	 * Comma-list postcodes resolve to the first segment.
	 */
	public function test_first_postcode_segment_is_used() {
		$address = Address::from_options( array( 'to_zip' => '33033, 33034, 33035' ) );

		$this->assertSame( '33033', $address->postcode() );
	}

	/**
	 * `state_compact()` strips internal spaces (e.g. accidental form input).
	 */
	public function test_state_compact_strips_spaces() {
		$address = Address::from_options( array( 'to_state' => 'N Y' ) );

		$this->assertSame( 'N Y', $address->state() );
		$this->assertSame( 'NY', $address->state_compact() );
	}

	/**
	 * Address-level `normalize_city` strips semicolons and collapses whitespace.
	 *
	 * @dataProvider normalize_city_provider
	 *
	 * @param string $input    Raw city.
	 * @param string $expected Expected normalised value.
	 */
	public function test_normalize_city_strips_semicolons( $input, $expected ) {
		$this->assertSame( $expected, Address::normalize_city( $input ) );
	}

	/**
	 * Data provider mirroring the test cases on the integration class.
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

	/**
	 * Whitespace collapsing leaves multibyte characters intact.
	 *
	 * The `/u` flag makes `preg_replace` walk characters rather than bytes, so a
	 * multibyte sequence adjacent to a match cannot be split.
	 */
	public function test_normalize_city_preserves_multibyte_characters() {
		$this->assertSame( 'Zürich', Address::normalize_city( '  Zürich  ' ) );
		$this->assertSame( 'Ōgaki Shi', Address::normalize_city( "Ōgaki  \tShi" ) );
	}

	/**
	 * Malformed UTF-8 degrades to an empty string rather than a `TypeError`.
	 *
	 * With `/u`, `preg_replace` returns `null` on invalid UTF-8 input. Without the
	 * cast that `normalize_city()` applies, that `null` reaches `trim()` and, on a
	 * `string`-typed return, raises. A city value arrives from checkout input, so
	 * invalid UTF-8 is reachable from outside.
	 */
	public function test_normalize_city_survives_malformed_utf8() {
		$this->assertSame( '', Address::normalize_city( "\xC3\x28" ) );
	}

	/* ──── jurisdiction_key() — field set measured against live TaxJar ──── */

	/**
	 * The key carries country, state, postcode and city, in that order.
	 */
	public function test_jurisdiction_key_has_country_state_postcode_city_shape() {
		$address = Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'CO',
				'to_zip'     => '81323',
				'to_city'    => 'Dolores',
				'to_street'  => '100 S 4th St',
			)
		);

		$this->assertSame( 'US|CO|81323|DOLORES', $address->jurisdiction_key() );
	}

	/**
	 * Street is excluded from the key.
	 *
	 * Measured July 2026 against live TaxJar: across three ZIP codes and every field
	 * pairing, the street never moved the `jurisdictions` TaxJar returned — not even
	 * as a tiebreaker when the city was absent. Including it would fragment the key
	 * for no accuracy gain.
	 */
	public function test_jurisdiction_key_ignores_street() {
		$base = array(
			'to_country' => 'US',
			'to_state'   => 'CO',
			'to_zip'     => '81323',
			'to_city'    => 'Dolores',
		);

		$with_street    = Address::from_options( array_merge( $base, array( 'to_street' => '100 S 4th St' ) ) );
		$other_street   = Address::from_options( array_merge( $base, array( 'to_street' => '25 County Road 38' ) ) );
		$without_street = Address::from_options( $base );

		$this->assertSame( $with_street->jurisdiction_key(), $other_street->jurisdiction_key() );
		$this->assertSame( $with_street->jurisdiction_key(), $without_street->jurisdiction_key() );
	}

	/**
	 * A `+4` suffix survives into the key.
	 *
	 * Measured: `81323-2100` resolves to the county-only `US|CO|MONTEZUMA|` at 3.3%
	 * while bare `81323` with an in-town city resolves to `US|CO|MONTEZUMA|DOLORES`
	 * at 7.3%. Truncating the postcode to five digits would merge two genuinely
	 * different jurisdictions onto one key.
	 */
	public function test_jurisdiction_key_preserves_zip_plus_four() {
		$base = array(
			'to_country' => 'US',
			'to_state'   => 'CO',
			'to_city'    => 'Dolores',
			'to_street'  => '100 S 4th St',
		);

		$five_digit = Address::from_options( array_merge( $base, array( 'to_zip' => '81323' ) ) );
		$plus_four  = Address::from_options( array_merge( $base, array( 'to_zip' => '81323-2100' ) ) );

		$this->assertSame( 'US|CO|81323-2100|DOLORES', $plus_four->jurisdiction_key() );
		$this->assertNotSame( $five_digit->jurisdiction_key(), $plus_four->jurisdiction_key() );
	}

	/**
	 * City is load-bearing and cannot be dropped from the key.
	 *
	 * Measured: ZIP 81323 CO with city `Dolores` resolves to `US|CO|MONTEZUMA|DOLORES`;
	 * with the city absent or misspelled it resolves to `US|CO|DOLORES|RICO` — a
	 * different, real county. Two distinct jurisdictions, so two distinct keys.
	 */
	public function test_jurisdiction_key_distinguishes_present_absent_and_misspelled_city() {
		$base = array(
			'to_country' => 'US',
			'to_state'   => 'CO',
			'to_zip'     => '81323',
		);

		$in_town    = Address::from_options( array_merge( $base, array( 'to_city' => 'Dolores' ) ) );
		$no_city    = Address::from_options( $base );
		$misspelled = Address::from_options( array_merge( $base, array( 'to_city' => 'Doloress' ) ) );

		$this->assertNotSame( $in_town->jurisdiction_key(), $no_city->jurisdiction_key() );
		$this->assertNotSame( $in_town->jurisdiction_key(), $misspelled->jurisdiction_key() );
		$this->assertSame( 'US|CO|81323|', $no_city->jurisdiction_key() );
	}

	/**
	 * Letter case and surrounding whitespace do not fragment the key — TaxJar
	 * answers all three spellings identically.
	 */
	public function test_jurisdiction_key_is_case_and_whitespace_insensitive() {
		$expected = 'US|CO|81323|DOLORES';

		foreach ( array( 'Dolores', 'dolores', '  DOLORES  ' ) as $city ) {
			$address = Address::from_options(
				array(
					'to_country' => 'us',
					'to_state'   => 'co',
					'to_zip'     => ' 81323 ',
					'to_city'    => $city,
				)
			);

			$this->assertSame( $expected, $address->jurisdiction_key(), "City spelling: {$city}" );
		}
	}

	/**
	 * The city normalisation applied at construction flows into the key, so a
	 * semicolon typo keys the same as the clean spelling.
	 */
	public function test_jurisdiction_key_applies_city_normalisation() {
		$typo  = Address::from_options( array( 'to_city' => 'Casse;Berry' ) );
		$clean = Address::from_options( array( 'to_city' => 'Casse Berry' ) );

		$this->assertSame( $clean->jurisdiction_key(), $typo->jurisdiction_key() );
	}

	/**
	 * A comma-list postcode keys on its first segment, matching what is actually
	 * sent to TaxJar.
	 */
	public function test_jurisdiction_key_uses_first_postcode_segment() {
		$address = Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'FL',
				'to_zip'     => '33033,33034',
				'to_city'    => 'Homestead',
			)
		);

		$this->assertSame( 'US|FL|33033|HOMESTEAD', $address->jurisdiction_key() );
	}

	/**
	 * A separator character inside a field cannot forge another address's key.
	 */
	public function test_jurisdiction_key_separator_in_city_cannot_forge_another_key() {
		$injected = Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'CO',
				'to_zip'     => '81323',
				'to_city'    => 'Dolores|81323|Rico',
			)
		);

		$this->assertSame( 'US|CO|81323|DOLORES 81323 RICO', $injected->jurisdiction_key() );
	}

	/* ──── Output methods — one per consumer shape ──── */

	/**
	 * `to_taxable_tuple` returns positional `[country, state, postcode, city, street]`.
	 */
	public function test_to_taxable_tuple_returns_positional_5_tuple() {
		$address = Address::from_options(
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
		$address = Address::from_options(
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
		$without_id = Address::from_options(
			array(
				'to_country' => 'US',
				'to_state'   => 'FL',
				'to_zip'     => '33033',
			)
		);

		$with_id = Address::from_nexus(
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
		$address = Address::from_options(
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
		$address = Address::from_options(
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
		$address = Address::from_options(
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
		$address = Address::from_options(
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
		$address = Address::from_options(
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
		$address   = Address::from_options(
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
		$blank   = Address::empty();
		$partial = Address::from_options( array( 'to_country' => 'US' ) );

		$this->assertTrue( $blank->is_empty() );
		$this->assertFalse( $partial->is_empty() );
	}
}
