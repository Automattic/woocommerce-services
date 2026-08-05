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
 * trimming city semicolons, taking the first segment of a comma-list postcode)
 * across many call sites and made it easy to forget one.
 *
 * This class centralises the policy:
 *
 *   $address = Address::from_taxable_tuple( $tuple );
 *   $body    = $address->to_taxjar_body( 'to_' );
 *   $rates   = WC_Tax::find_rates( $address->to_find_rates_args( $tax_class ) );
 *
 * Construction normalises once; every accessor and `to_*()` method is a pure
 * function of the stored fields.
 *
 * This object is **internal**. Every boundary that faces third-party code — filters,
 * public method signatures — converts back to the array shapes those consumers expect.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\Tax;

use WC_Customer;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Address value object for the TaxJar integration. Immutable.
 */
final class Address {

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
	 * Separator joining the components of `jurisdiction_key()`.
	 *
	 * @var string
	 */
	private const KEY_SEPARATOR = '|';

	/**
	 * Validation schema used by `validate()`. Mirrors the rules previously inlined
	 * in `WC_Connect_TaxJar_Integration::is_nexus_address_valid()`.
	 *
	 * @var array
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
	 *
	 * @param string      $country  Country code, uppercased on construction.
	 * @param string      $state    State / province code, uppercased on construction.
	 * @param string      $postcode Postal code; only the first comma-separated segment is kept.
	 * @param string      $city     City name; semicolons stripped and whitespace collapsed.
	 * @param string      $street   Street address line, trimmed.
	 * @param string|null $id       Optional nexus-address identifier.
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
	 *
	 * @return self
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
	 * @return self
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
	 * @return self
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
	 *
	 * @param array $nexus Nexus address array, optionally carrying an `id`.
	 * @return self
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
	 *
	 * @param array $settings Store settings array.
	 * @return self
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
	 * @return self
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
	 * @return self
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
	 * Unslashes the superglobal once, then applies `wc_clean()` to each field. Caller
	 * must have already verified the nonce / capability — this method does not check
	 * authorization.
	 *
	 * The unslashing is load-bearing, not ceremony. WordPress slashes every superglobal
	 * on load and `wc_clean()` sanitizes without unslashing, so an apostrophe in an admin
	 * order address would otherwise reach TaxJar — and the `wp_woocommerce_tax_rates`
	 * city column — as the literal `O\'Brien`, which can never match the unslashed value
	 * the cart path writes for the same address.
	 *
	 * It happens exactly once, and only on the superglobal. `wp_unslash()` is
	 * `stripslashes_deep()`, which is not idempotent: a second pass over an already
	 * unslashed value eats real backslashes (`A\B` becomes `AB`). The `$post` override
	 * is a test seam that is never slashed, so it must not be unslashed either.
	 *
	 * @param array|null $post Override source for testing. Defaults to `$_POST`.
	 * @return self
	 */
	public static function from_post_request( ?array $post = null ): self {
		// Real requests arrive slashed (WP magic-quotes); wc_clean() does not unslash,
		// so unslash the superglobal here. The $post test override is never slashed.
		$source = is_array( $post ) ? $post : wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce/capability verified by the caller (see docblock); each field is sanitized via wc_clean() below.

		// is_scalar() drops array-valued fields (e.g. a crafted `city[]=x`), which
		// would otherwise cast to the literal string "Array" with a PHP warning.
		$pluck = static function ( $key ) use ( $source ) {
			return isset( $source[ $key ] ) && is_scalar( $source[ $key ] ) ? (string) wc_clean( $source[ $key ] ) : '';
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
	 * @return self
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
	 *
	 * @return string
	 */
	public function country(): string {
		return $this->country;
	}

	/**
	 * State / province code, uppercase. Empty if unknown.
	 *
	 * @return string
	 */
	public function state(): string {
		return $this->state;
	}

	/**
	 * State in the exact form WooCommerce core stores it in `tax_rate_state`.
	 *
	 * This is not a normalisation policy of ours — it is a mirror of core's.
	 * `WC_Tax::prepare_tax_rate()` singles this one column out and runs it through
	 * `sanitize_key()` before `format_tax_rate_state()` uppercases it, so the value
	 * that reaches the column has been lower-cased and stripped of everything
	 * outside `[a-z0-9_-]`. `WC_Tax::get_matched_tax_rates()` then compares against
	 * it with a plain `tax_rate_state IN ( %s, '' )` and no normalisation of its own.
	 *
	 * A lookup that asks for anything else therefore cannot find the row it just
	 * wrote, and `create_or_update_tax_rate()` responds by inserting another one —
	 * once per calculation, forever. Stripping spaces (which is all this method used
	 * to do) covers `'N Y'` and nothing else: `'N.Y.'`, and any state carrying
	 * non-ASCII, still diverge.
	 *
	 * One boundary to that story: a state `sanitize_key()` empties *outright* (`'ЛЕН'`,
	 * `'东京'`) was never the duplicating shape, because the blank it stores satisfies
	 * the `''` arm of that `IN` clause whatever the lookup asked for. The rows that
	 * could not be found again are the ones that collapse *partially*, to a shorter
	 * non-empty code — `'ÎF'` stored as `'F'`.
	 *
	 * Because core applies the same function on the way in, mirroring it here cannot
	 * lose information the stored value still has.
	 *
	 * @return string
	 */
	public function state_compact(): string {
		return strtoupper( sanitize_key( $this->state ) );
	}

	/**
	 * Postal / ZIP code (first segment of any comma-list).
	 *
	 * Preserved as entered apart from that split and a trim — in particular the
	 * `+4` suffix of a US ZIP is **not** stripped. See `jurisdiction_key()`.
	 *
	 * @return string
	 */
	public function postcode(): string {
		return $this->postcode;
	}

	/**
	 * City name with semicolons stripped and whitespace collapsed.
	 *
	 * @return string
	 */
	public function city(): string {
		return $this->city;
	}

	/**
	 * Street address line.
	 *
	 * @return string
	 */
	public function street(): string {
		return $this->street;
	}

	/**
	 * Optional identifier — only set for nexus addresses that carry one.
	 *
	 * @return string|null
	 */
	public function id(): ?string {
		return $this->id;
	}

	/**
	 * True if all five core fields are empty. Mirrors the early-return path
	 * `get_taxable_address()` takes when `WC()->customer` is null.
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return '' === $this->country
			&& '' === $this->state
			&& '' === $this->postcode
			&& '' === $this->city
			&& '' === $this->street;
	}

	/* ──── Jurisdiction identity ──── */

	/**
	 * Stable key for the tax jurisdiction this address resolves to at TaxJar.
	 *
	 * Two addresses sharing a key are answered by TaxJar with the same
	 * `jurisdictions` object, so a jurisdiction resolved for one may be reused for
	 * the other. This is the single place the key is derived: it must be used for
	 * both the write and the read side of any jurisdiction cache, because deriving
	 * it differently at each end is precisely what made an earlier attempt at
	 * jurisdiction-keyed rate rows (PR #2906) miss on every lookup.
	 *
	 * **The field set is measured, not assumed.** It was established against the
	 * live TaxJar API in July 2026, over roughly 35 calls across three ZIP codes;
	 * an earlier `country + state + postcode` hypothesis was falsified by that run:
	 *
	 * - **City is load-bearing.** ZIP 81323 CO with city `Dolores` resolves to
	 *   `US|CO|MONTEZUMA|DOLORES`; with the city absent or misspelled it resolves to
	 *   `US|CO|DOLORES|RICO` — a different, real county. Excluding the city would
	 *   collapse two distinct jurisdictions onto one key.
	 * - **The postcode must be keyed exactly as entered, `+4` included.**
	 *   `81323-2100` resolves to the county-only `US|CO|MONTEZUMA|` even when an
	 *   in-town city and street are supplied. Truncating to five digits would merge
	 *   genuinely different jurisdictions.
	 * - **Street is excluded.** Across every pairing tried it never moved
	 *   `jurisdictions` — not even as a tiebreaker when the city was absent.
	 *   Including it would fragment the key for no accuracy gain.
	 * - **Store nexus is excluded.** Nexus was measured to leave `jurisdictions`
	 *   unchanged, so a jurisdiction resolved under one store address stays valid
	 *   after the store moves.
	 *
	 * Note the scope of that last point: nexus does not change the *jurisdiction*,
	 * but it does change the *rate breakdown* within it. A jurisdiction is therefore
	 * a sufficient identity for a stored rate row **only while the store nexus is
	 * fixed** — a store-address change invalidates stored rates even though every
	 * jurisdiction key stays the same.
	 *
	 * Components are upper-cased so that differences of letter case, which TaxJar
	 * ignores, do not fragment the key. The separator is stripped from each
	 * component first so a value containing it cannot forge a different key.
	 *
	 * The case folding is `wc_strtoupper()`, not `strtoupper()`, and the difference
	 * is load-bearing. `strtoupper()` is byte-wise ASCII: it leaves every non-ASCII
	 * byte alone, so `Zürich` folds to `ZüRICH` while `ZÜRICH` folds to itself —
	 * two keys for one jurisdiction, which is precisely the fragmentation this
	 * method exists to prevent. `normalize_city()` preserves multibyte input, so a
	 * non-ASCII city reaches here intact from any international checkout.
	 *
	 * Note this deliberately diverges from the city sent to `to_find_rates_args()`,
	 * which must stay on `strtoupper()`. That value's contract is byte-parity with
	 * WooCommerce core, which upper-cases with plain `strtoupper()` on both the write
	 * (`WC_Tax::format_tax_rate_city()`) and the read (the `location_code`
	 * comparison inside `WC_Tax::find_rates()`). Folding wider than core there would
	 * store `ZÜRICH` while core searched for `ZüRICH` — manufacturing the unbounded
	 * rate-row growth that normalisation exists to close. Same operation, opposite
	 * correct answers, because this key is ours and that one is core's.
	 *
	 * `wc_strtoupper()` falls back to `strtoupper()` when ext-mbstring is absent, so
	 * this is no worse than the previous behavior on a minimal PHP build.
	 *
	 * @return string Key of the form `COUNTRY|STATE|POSTCODE|CITY`.
	 */
	public function jurisdiction_key(): string {
		$components = array(
			wc_strtoupper( $this->country ),
			wc_strtoupper( $this->state ),
			wc_strtoupper( $this->postcode ),
			wc_strtoupper( $this->city ),
		);

		$components = array_map(
			static function ( string $component ): string {
				return str_replace( self::KEY_SEPARATOR, ' ', $component );
			},
			$components
		);

		return implode( self::KEY_SEPARATOR, $components );
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
	 * @return WP_Error
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
	 * @return array<string, string>
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
	 * Args for `WC_Tax::find_rates()` — the read end of the tax-rate-table round trip.
	 *
	 * Every field is in the form WC core stores it, so this and
	 * `to_rate_table_locations()` cannot disagree: a row written from one address is
	 * found by a lookup built from the same address.
	 *
	 * @param string $tax_class Tax class slug to look up.
	 * @return array{country: string, state: string, postcode: string, city: string, tax_class: string}
	 */
	public function to_find_rates_args( string $tax_class = '' ): array {
		return array(
			'country'   => $this->country,
			'state'     => $this->state_compact(),
			'postcode'  => $this->postcode_as_stored(),
			'city'      => $this->city_as_stored(),
			'tax_class' => $tax_class,
		);
	}

	/**
	 * Location codes for the write end: the `postcode` and `city` rows that
	 * `WC_Tax::_update_tax_rate_postcodes()` / `_update_tax_rate_cities()` persist
	 * alongside a rate.
	 *
	 * Paired with `to_find_rates_args()` on purpose. Deriving the two independently is
	 * what let `create_or_update_tax_rate()` look up a value it had never written and
	 * insert a fresh row for the same address on every calculation.
	 *
	 * @return array{postcode: string, city: string}
	 */
	public function to_rate_table_locations(): array {
		return array(
			'postcode' => $this->postcode_as_stored(),
			'city'     => $this->city_as_stored(),
		);
	}

	/**
	 * Postcode in the form WC core matches rate rows against.
	 *
	 * `WC_Tax::find_rates()` normalizes its argument with
	 * `wc_normalize_postcode( wc_clean( … ) )`, while
	 * `_update_tax_rate_postcodes()` stores what it is handed verbatim. Applying the
	 * same normalisation to both ends is what closes that gap; it is idempotent, so
	 * passing an already-normalised value into `find_rates()` changes nothing.
	 *
	 * @return string
	 */
	private function postcode_as_stored(): string {
		return (string) wc_normalize_postcode( wc_clean( $this->postcode ) );
	}

	/**
	 * City in the form WC core stores in the `city` location row.
	 *
	 * Core upper-cases and trims on both ends (`format_tax_rate_city()` on the write,
	 * `strtoupper()` inside the lookup SQL) but sanitizes on neither. The plugin used
	 * to apply `wc_clean()` on the write only, so a city carrying anything
	 * `sanitize_text_field()` strips was stored in one form and searched for in
	 * another — a fresh rate row per calculation.
	 *
	 * @return string
	 */
	private function city_as_stored(): string {
		return strtoupper( trim( (string) wc_clean( $this->city ) ) );
	}

	/**
	 * Back-compat shape used by `get_address()` and `get_backend_address()` return
	 * values: `to_country`, `to_state`, `to_zip`, `to_city`, `to_street` — but with
	 * empty-string fields converted to `false`, the value the legacy
	 * "isset && !empty" extraction produced for a missing field. Deliberately
	 * narrower than `! empty()`: a literal `'0'` is preserved, where the legacy
	 * check collapsed it to `false`.
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
	 * (it `explode(';', ...)`s the input), but `WC_Tax::find_rates()` queries the
	 * city column with a single `location_code = '<CITY>'` literal — so a checkout
	 * city containing `;` (e.g. typo'd `Casse;Berry`) gets stored as two separate
	 * location rows (`CASSE`, `BERRY`) yet looked up as the joined string
	 * `CASSE;BERRY`. That asymmetry makes `find_rates()` miss on every subsequent
	 * calculation, which makes `create_or_update_tax_rate()` insert a fresh row each
	 * checkout — unbounded growth of `wp_woocommerce_tax_rates`.
	 *
	 * This is the canonical implementation. `WC_Connect_TaxJar_Integration::normalize_city()`
	 * is a deprecated delegate to it.
	 *
	 * @param string $city Raw city value, possibly user-entered.
	 * @return string Normalized city, safe for `_update_tax_rate_cities` and `find_rates`.
	 */
	public static function normalize_city( string $city ): string {
		if ( '' === $city ) {
			return '';
		}

		$city = str_replace( ';', ' ', $city );
		$city = preg_replace( '/\s+/u', ' ', $city );

		// `preg_replace` returns null on malformed UTF-8 with the /u flag; cast so trim() stays safe.
		return trim( (string) $city );
	}

	/**
	 * Uppercase + trim. Returns empty string for empty input.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function upper( string $value ): string {
		$value = trim( $value );
		return '' === $value ? '' : strtoupper( $value );
	}

	/**
	 * Take only the first segment of a comma-list postcode and trim it.
	 *
	 * Some carts (notably WC's `,`-joined zone matching) deliver postcodes as
	 * `"33033,33034"`; TaxJar wants a single value. A `+4` suffix is hyphenated,
	 * not comma-separated, so it survives this split intact — which
	 * `jurisdiction_key()` depends on.
	 *
	 * @param string $postcode Raw postcode value.
	 * @return string
	 */
	private static function first_postcode_segment( string $postcode ): string {
		if ( '' === $postcode ) {
			return '';
		}

		$first = explode( ',', $postcode );
		return trim( (string) array_shift( $first ) );
	}
}
