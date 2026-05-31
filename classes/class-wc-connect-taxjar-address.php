<?php
/**
 * Immutable value object representing a single address used by the TaxJar integration.
 *
 * The TaxJar code paths in `class-wc-connect-taxjar-integration.php` consume the same
 * five fields (country, state, postcode, city, street) in many distinct shapes:
 *
 * - WC core's positional 5-tuple `[country, state, postcode, city, street]`
 * - WooCommerce customer billing / shipping getters
 * - Store base address from `WC()->countries->get_base_*`
 * - Admin order `$_POST` form keys (no prefix)
 * - `calculate_tax( $options )` arguments (`to_*` prefix)
 * - TaxJar API request body (both `from_*` and `to_*` prefix)
 * - TaxJar `nexus_addresses[]` sub-shape (no prefix, `zip` instead of `postcode`)
 * - `WC_Tax::find_rates()` lookup args (no prefix, `postcode` not `zip`, state space-stripped, city upper-normalised)
 *
 * Each input shape used to be hand-extracted at the call site, and each output
 * shape was hand-built. That spread normalisation rules (uppercasing country/state,
 * trimming city semicolons per WOOTAX-19, taking the first segment of a comma-list
 * postcode) across many call sites and made it easy to forget one.
 *
 * This class centralises the policy:
 *
 *   $address = WC_Connect_TaxJar_Address::from_taxable_tuple( $tuple );
 *   $body    = $address->to_taxjar_body( 'to_' );
 *   $rates   = WC_Tax::find_rates( $address->to_find_rates_args( $tax_class ) );
 *
 * Construction normalises once; every accessor and `to_*()` method is a pure
 * function of the stored fields.
 *
 * @package WooCommerce_Services
 */

// phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Address value object for the TaxJar integration. Immutable.
 */
final class WC_Connect_TaxJar_Address {

	/**
	 * Two-letter ISO country code, uppercase. Empty string if unknown.
	 *
	 * @var string
	 */
	private $country = '';

	/**
	 * State / province code, uppercase. Empty string if unknown.
	 *
	 * @var string
	 */
	private $state = '';

	/**
	 * Postal / ZIP code. May be empty for VAT countries.
	 *
	 * @var string
	 */
	private $postcode = '';

	/**
	 * City name with semicolons stripped and whitespace collapsed.
	 *
	 * @var string
	 */
	private $city = '';

	/**
	 * Street address.
	 *
	 * @var string
	 */
	private $street = '';

	/**
	 * Optional identifier (only used by nexus addresses).
	 *
	 * @var string|null
	 */
	private $id = null;

	/**
	 * Validation schema used by `validate()`. Mirrors the rules previously inlined
	 * in `is_nexus_address_valid()`.
	 */
	private const VALIDATION_SCHEMA = array(
		'id'       => array(
			'type'       => 'string',
			'max_length' => 255,
		),
		'country'  => array(
			'type'       => 'string',
			'pattern'    => '/^[A-Z]{2}$/',
			'max_length' => 2,
		),
		'postcode' => array(
			'type'       => 'string',
			'max_length' => 20,
		),
		'state'    => array(
			'type'       => 'string',
			'pattern'    => '/^[A-Z0-9\-]{1,100}$/',
			'max_length' => 100,
		),
		'city'     => array(
			'type'       => 'string',
			'max_length' => 100,
		),
		'street'   => array(
			'type'       => 'string',
			'max_length' => 255,
		),
	);

	/**
	 * Private constructor — use the named static factories below.
	 */
	private function __construct( string $country = '', string $state = '', string $postcode = '', string $city = '', string $street = '', ?string $id = null ) {
		$this->country  = self::upper( $country );
		$this->state    = self::upper( $state );
		$this->postcode = self::first_postcode_segment( $postcode );
		$this->city     = self::normalize_city( $city );
		$this->street   = trim( $street );
		$this->id       = ( null === $id || '' === $id ) ? null : (string) $id;
	}

	/* ──── Named constructors ──── */

	/**
	 * Empty address. Useful for early-return paths (e.g. when `WC()->customer` is null).
	 */
	public static function empty(): self {
		return new self();
	}

	/**
	 * Build from WC's positional 5-tuple `[country, state, postcode, city, street]`.
	 *
	 * Matches the contract of the `woocommerce_customer_taxable_address` filter.
	 *
	 * @param array $tuple Indexed 5-element array. Missing positions become empty strings.
	 */
	public static function from_taxable_tuple( array $tuple ): self {
		return new self(
			(string) ( $tuple[0] ?? '' ),
			(string) ( $tuple[1] ?? '' ),
			(string) ( $tuple[2] ?? '' ),
			(string) ( $tuple[3] ?? '' ),
			(string) ( $tuple[4] ?? '' )
		);
	}

	/**
	 * Build from an associative `to_*` (or `from_*`) shape — e.g. `calculate_tax()` options.
	 *
	 * @param array  $options Source array. Missing keys default to empty.
	 * @param string $prefix  Either 'to_' (default) or 'from_'.
	 */
	public static function from_options( array $options, string $prefix = 'to_' ): self {
		return new self(
			(string) ( $options[ $prefix . 'country' ] ?? '' ),
			(string) ( $options[ $prefix . 'state' ] ?? '' ),
			(string) ( $options[ $prefix . 'zip' ] ?? '' ),
			(string) ( $options[ $prefix . 'city' ] ?? '' ),
			(string) ( $options[ $prefix . 'street' ] ?? '' )
		);
	}

	/**
	 * Build from the unprefixed nexus shape (`country, zip, state, city, street`).
	 *
	 * Note: `zip` (not `postcode`) — matches both the TaxJar nexus_addresses[] payload
	 * and the `woocommerce_taxjar_nexus_address` filter contract.
	 */
	public static function from_nexus( array $nexus ): self {
		return new self(
			(string) ( $nexus['country'] ?? '' ),
			(string) ( $nexus['state'] ?? '' ),
			(string) ( $nexus['zip'] ?? '' ),
			(string) ( $nexus['city'] ?? '' ),
			(string) ( $nexus['street'] ?? '' ),
			isset( $nexus['id'] ) ? (string) $nexus['id'] : null
		);
	}

	/**
	 * Build from the store-settings shape `{street, city, state, country, postcode}`.
	 *
	 * Matches the array returned by `WC_Connect_TaxJar_Integration::get_store_settings()`.
	 */
	public static function from_store_settings( array $settings ): self {
		return new self(
			(string) ( $settings['country'] ?? '' ),
			(string) ( $settings['state'] ?? '' ),
			(string) ( $settings['postcode'] ?? '' ),
			(string) ( $settings['city'] ?? '' ),
			(string) ( $settings['street'] ?? '' )
		);
	}

	/**
	 * Build from a WooCommerce customer's billing address.
	 *
	 * @param WC_Customer $customer Live customer object — typically `WC()->customer`.
	 */
	public static function from_customer_billing( WC_Customer $customer ): self {
		return new self(
			(string) $customer->get_billing_country(),
			(string) $customer->get_billing_state(),
			(string) $customer->get_billing_postcode(),
			(string) $customer->get_billing_city(),
			(string) $customer->get_billing_address()
		);
	}

	/**
	 * Build from a WooCommerce customer's shipping address.
	 *
	 * @param WC_Customer $customer Live customer object — typically `WC()->customer`.
	 */
	public static function from_customer_shipping( WC_Customer $customer ): self {
		return new self(
			(string) $customer->get_shipping_country(),
			(string) $customer->get_shipping_state(),
			(string) $customer->get_shipping_postcode(),
			(string) $customer->get_shipping_city(),
			(string) $customer->get_shipping_address()
		);
	}

	/**
	 * Build from an admin-order `$_POST` payload.
	 *
	 * Applies `wc_clean()` to each field. Caller must have already verified the
	 * nonce / capability — this method does not check authorization.
	 *
	 * @param array|null $post Override source for testing. Defaults to `$_POST`.
	 */
	public static function from_post_request( ?array $post = null ): self {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$source = is_array( $post ) ? $post : $_POST;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$pluck = static function ( $key ) use ( $source ) {
			return isset( $source[ $key ] ) ? (string) wc_clean( $source[ $key ] ) : '';
		};

		return new self(
			$pluck( 'country' ),
			$pluck( 'state' ),
			$pluck( 'postcode' ),
			$pluck( 'city' ),
			$pluck( 'street' )
		);
	}

	/**
	 * Build from a TaxJar response's `jurisdictions` object.
	 *
	 * Partial: only `country`, `state`, `city` are present in the response —
	 * postcode and street default to empty.
	 *
	 * @param object|array|null $jurisdictions A `$response_body->tax->jurisdictions` snippet.
	 */
	public static function from_jurisdictions( $jurisdictions ): self {
		if ( null === $jurisdictions ) {
			return self::empty();
		}

		$jurisdictions = (array) $jurisdictions;

		return new self(
			(string) ( $jurisdictions['country'] ?? '' ),
			(string) ( $jurisdictions['state'] ?? '' ),
			'',
			(string) ( $jurisdictions['city'] ?? '' ),
			''
		);
	}

	/* ──── Field accessors — normalised on construction, cheap reads ──── */

	/**
	 * Two-letter ISO country code, uppercase. Empty if unknown.
	 */
	public function country(): string {
		return $this->country;
	}

	/**
	 * State / province code, uppercase. Empty if unknown.
	 */
	public function state(): string {
		return $this->state;
	}

	/**
	 * State with all spaces removed. Used by `WC_Tax::find_rates()` lookup —
	 * state codes shouldn't contain spaces, and stripping defends against
	 * accidental whitespace from form input.
	 */
	public function state_compact(): string {
		return str_replace( ' ', '', $this->state );
	}

	/**
	 * Postal / ZIP code (first segment of any comma-list).
	 */
	public function postcode(): string {
		return $this->postcode;
	}

	/**
	 * City name with semicolons stripped and whitespace collapsed (WOOTAX-19).
	 */
	public function city(): string {
		return $this->city;
	}

	/**
	 * Street address line.
	 */
	public function street(): string {
		return $this->street;
	}

	/**
	 * Optional identifier — only set for nexus addresses that carry one.
	 */
	public function id(): ?string {
		return $this->id;
	}

	/**
	 * True if all five core fields are empty. Mirrors the early-return path
	 * `get_taxable_address()` takes when `WC()->customer` is null.
	 */
	public function is_empty(): bool {
		return '' === $this->country
			&& '' === $this->state
			&& '' === $this->postcode
			&& '' === $this->city
			&& '' === $this->street;
	}

	/* ──── Validation ──── */

	/**
	 * Validate fields against the address schema.
	 *
	 * Returns a `WP_Error`. Use `->has_errors()` to branch:
	 *
	 *   $errors = $address->validate( array( 'country', 'state' ) );
	 *   if ( $errors->has_errors() ) { ... }
	 *
	 * @param string[] $required Field names that must be non-empty for the address
	 *                           to be considered valid. Defaults to country + state
	 *                           (the historic nexus-validity contract).
	 */
	public function validate( array $required = array( 'country', 'state' ) ): WP_Error {
		$errors = new WP_Error();
		$values = array(
			'id'       => $this->id ?? '',
			'country'  => $this->country,
			'state'    => $this->state,
			'postcode' => $this->postcode,
			'city'     => $this->city,
			'street'   => $this->street,
		);

		foreach ( self::VALIDATION_SCHEMA as $field => $rules ) {
			$value       = $values[ $field ] ?? '';
			$is_required = in_array( $field, $required, true );

			if ( $is_required && '' === $value ) {
				$errors->add( "address.{$field}.required", "[$field] field is required" );
				continue;
			}

			if ( '' === $value ) {
				continue;
			}

			if ( isset( $rules['max_length'] ) && strlen( $value ) > $rules['max_length'] ) {
				$errors->add( "address.{$field}.max_length", "[$field] field exceeds maximum length of {$rules['max_length']}" );
			}

			if ( isset( $rules['pattern'] ) && ! preg_match( $rules['pattern'], $value ) ) {
				$errors->add( "address.{$field}.pattern", "[$field] field format is invalid" );
			}
		}//end foreach

		return $errors;
	}

	/* ──── Output methods — one per consumer shape ──── */

	/**
	 * WC core's positional 5-tuple. Feed this into the
	 * `woocommerce_customer_taxable_address` filter to preserve the existing
	 * external-plugin contract.
	 *
	 * @return array{0: string, 1: string, 2: string, 3: string, 4: string}
	 */
	public function to_taxable_tuple(): array {
		return array( $this->country, $this->state, $this->postcode, $this->city, $this->street );
	}

	/**
	 * Prefixed shape used inside the TaxJar API request body — `to_country`,
	 * `to_state`, `to_zip`, `to_city`, `to_street` (or `from_*`).
	 *
	 * Note: the wire-level field is `zip`, not `postcode`.
	 *
	 * @param string $prefix Either 'to_' (default) or 'from_'.
	 */
	public function to_taxjar_body( string $prefix = 'to_' ): array {
		return array(
			$prefix . 'country' => $this->country,
			$prefix . 'state'   => $this->state,
			$prefix . 'zip'     => $this->postcode,
			$prefix . 'city'    => $this->city,
			$prefix . 'street'  => $this->street,
		);
	}

	/**
	 * Unprefixed nexus-address shape used by TaxJar's `nexus_addresses[]` payload
	 * and the `woocommerce_taxjar_nexus_address` filter.
	 *
	 * @return array{country: string, zip: string, state: string, city: string, street: string}
	 */
	public function to_nexus_array(): array {
		$out = array(
			'country' => $this->country,
			'zip'     => $this->postcode,
			'state'   => $this->state,
			'city'    => $this->city,
			'street'  => $this->street,
		);

		if ( null !== $this->id ) {
			$out['id'] = $this->id;
		}

		return $out;
	}

	/**
	 * Args for `WC_Tax::find_rates()`. State is space-compacted, city is uppercase
	 * (consistent with WC core's `format_tax_rate_city()`), `tax_class` passed through.
	 *
	 * @return array{country: string, state: string, postcode: string, city: string, tax_class: string}
	 */
	public function to_find_rates_args( string $tax_class = '' ): array {
		return array(
			'country'   => $this->country,
			'state'     => $this->state_compact(),
			'postcode'  => $this->postcode,
			'city'      => strtoupper( $this->city ),
			'tax_class' => $tax_class,
		);
	}

	/**
	 * Back-compat shape used by `get_address()` and `get_backend_address()` return
	 * values: `to_country`, `to_state`, `to_zip`, `to_city`, `to_street` — but with
	 * empty-string fields converted to `false` to match the legacy "isset && !empty"
	 * extraction logic.
	 *
	 * @param string $prefix Either 'to_' (default) or 'from_'.
	 * @return array<string, string|false>
	 */
	public function to_legacy_options( string $prefix = 'to_' ): array {
		$value_or_false = static function ( string $v ) {
			return '' === $v ? false : $v;
		};

		return array(
			$prefix . 'country' => $value_or_false( $this->country ),
			$prefix . 'state'   => $value_or_false( $this->state ),
			$prefix . 'zip'     => $value_or_false( $this->postcode ),
			$prefix . 'city'    => $value_or_false( $this->city ),
			$prefix . 'street'  => $value_or_false( $this->street ),
		);
	}

	/* ──── Normalisation helpers ──── */

	/**
	 * Strip semicolons and collapse whitespace runs in a city value.
	 *
	 * `WC_Tax::_update_tax_rate_cities()` treats `;` as a multi-city separator
	 * (it `explode(';', ...)`s the input), but `WC_Tax::find_rates()` treats it
	 * as a literal character. Without this normalisation, a checkout city like
	 * `Casse;Berry` would be stored as two location rows (`CASSE`, `BERRY`) but
	 * looked up as the joined string `CASSE;BERRY` — `find_rates()` always
	 * misses, and a fresh tax-rate row is inserted on every calculation.
	 * See WOOTAX-19.
	 */
	public static function normalize_city( string $city ): string {
		if ( '' === $city ) {
			return '';
		}

		$city = str_replace( ';', ' ', $city );
		$city = preg_replace( '/\s+/', ' ', $city );

		return trim( $city );
	}

	/**
	 * Uppercase + trim. Returns empty string for empty input.
	 */
	private static function upper( string $value ): string {
		$value = trim( $value );
		return '' === $value ? '' : strtoupper( $value );
	}

	/**
	 * Take only the first segment of a comma-list postcode and trim it.
	 *
	 * Some carts (notably WC's `,`-joined zone matching) deliver postcodes as
	 * `"33033,33034"`; TaxJar wants a single value.
	 */
	private static function first_postcode_segment( string $postcode ): string {
		if ( '' === $postcode ) {
			return '';
		}

		$first = explode( ',', $postcode );
		return trim( (string) array_shift( $first ) );
	}
}
