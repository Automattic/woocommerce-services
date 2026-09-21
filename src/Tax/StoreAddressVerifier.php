<?php
/**
 * Checks the store address against TaxJar's address validation.
 *
 * TaxJar reads the store's state and ZIP to decide the origin of every US order.
 * A wrong state makes in-state orders untaxed (the integration returns before calling
 * TaxJar when the states differ), and a wrong ZIP prices every in-state order in the
 * wrong place. Nothing else checks that the saved store address is real, so this class
 * asks TaxJar's `v2/addresses/validate` about it and stores the answer for
 * StoreAddressNotice to show.
 *
 * The check runs when the address changes, not on every calculation. The stored
 * result carries a hash of the address it was made for. When the store address no
 * longer matches that hash, a check is due. The General settings screen checks
 * straight after saving so the result shows on the same page. Every other way of
 * changing the address (onboarding, REST, WP-CLI) and stores that already had an address
 * before this shipped are picked up by a background event on the next admin page load.
 *
 * Nothing here blocks a save or a tax calculation. Any failure is logged and leaves
 * the notice unchanged.
 *
 * @internal Not part of the public API; may change without notice.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\Tax;

use WC_Connect_API_Client;
use WC_Connect_TaxJar_Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Store address verification against TaxJar.
 */
final class StoreAddressVerifier {

	/**
	 * Option holding the latest result. Site-scoped, like the rest of the plugin's settings.
	 */
	const OPTION_NAME = 'wc_connect_store_address_verification';

	/**
	 * WP-Cron hook for the background check.
	 */
	const CRON_HOOK = 'wc_connect_verify_store_address';

	/**
	 * Proxy path of TaxJar's address validation endpoint.
	 */
	const VALIDATE_PATH = 'taxjar/v2/addresses/validate';

	/**
	 * Time limit, in seconds, for the check made while the merchant waits on the settings screen.
	 */
	const SAVE_TIMEOUT = 5;

	/**
	 * How long to wait before retrying after a failed request.
	 */
	const RETRY_AFTER = HOUR_IN_SECONDS;

	const STATUS_VERIFIED    = 'verified';
	const STATUS_SUGGESTION  = 'suggestion';
	const STATUS_AMBIGUOUS   = 'ambiguous';
	const STATUS_NOT_FOUND   = 'not_found';
	const STATUS_ZIP_MISSING = 'zip_missing';
	const STATUS_SKIPPED     = 'skipped';
	const STATUS_ERROR       = 'error';

	/**
	 * Client used to reach TaxJar through the Connect server.
	 *
	 * @var WC_Connect_API_Client
	 */
	private $api_client;

	/**
	 * Supplies the store address exactly as the tax calculation sends it, and the log.
	 *
	 * @var WC_Connect_TaxJar_Integration
	 */
	private $taxjar;

	/**
	 * Constructor.
	 *
	 * @param WC_Connect_API_Client         $api_client API client.
	 * @param WC_Connect_TaxJar_Integration $taxjar     TaxJar integration.
	 */
	public function __construct( WC_Connect_API_Client $api_client, WC_Connect_TaxJar_Integration $taxjar ) {
		$this->api_client = $api_client;
		$this->taxjar     = $taxjar;
	}

	/**
	 * Register hooks.
	 */
	public function init() {
		add_action( self::CRON_HOOK, array( $this, 'verify' ) );
		add_action( 'admin_init', array( $this, 'maybe_schedule_check' ) );
		add_action( 'woocommerce_update_options_general', array( $this, 'verify_after_settings_save' ) );
	}

	/**
	 * The store address TaxJar receives, normalised.
	 *
	 * Read through the integration so a `taxjar_store_settings` filter is honoured: that
	 * filtered address is the one tax is calculated from.
	 *
	 * @return Address
	 */
	public function get_store_address() {
		return Address::from_store_settings( $this->taxjar->get_store_settings() );
	}

	/**
	 * Fingerprint of an address. A new fingerprint means a new check is due.
	 *
	 * @param Address $address Address.
	 * @return string
	 */
	public static function hash_address( Address $address ) {
		return md5(
			wp_json_encode(
				array(
					$address->country(),
					$address->state(),
					$address->postcode(),
					$address->city(),
					$address->street(),
				)
			)
		);
	}

	/**
	 * The stored result, or null when there is none or it is malformed.
	 *
	 * @return array|null
	 */
	public function get_result() {
		$result = get_option( self::OPTION_NAME );

		if ( ! is_array( $result ) || empty( $result['hash'] ) || empty( $result['status'] ) ) {
			return null;
		}

		return $result;
	}

	/**
	 * The stored result, only if it was made for the current store address.
	 *
	 * @return array|null
	 */
	public function get_current_result() {
		$result = $this->get_result();

		if ( null === $result || self::hash_address( $this->get_store_address() ) !== $result['hash'] ) {
			return null;
		}

		return $result;
	}

	/**
	 * Is a check due for the current store address?
	 *
	 * @return bool
	 */
	public function is_check_due() {
		$result = $this->get_result();

		if ( null === $result || self::hash_address( $this->get_store_address() ) !== $result['hash'] ) {
			return true;
		}

		if ( self::STATUS_ERROR === $result['status'] ) {
			$checked_at = isset( $result['checked_at'] ) ? (int) $result['checked_at'] : 0;

			return time() - $checked_at >= self::RETRY_AFTER;
		}

		return false;
	}

	/**
	 * Schedule a background check when one is due.
	 *
	 * Runs on admin_init for store managers only, so front-end traffic never pays for it.
	 */
	public function maybe_schedule_check() {
		if ( ! current_user_can( 'manage_woocommerce' ) || wp_doing_ajax() ) {
			return;
		}

		if ( ! $this->is_check_due() ) {
			return;
		}

		if ( ! $this->needs_request( $this->get_store_address() ) ) {
			// Nothing to send, so answer now and the notice shows on this page load.
			$this->verify();

			return;
		}

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time(), self::CRON_HOOK );
		}
	}

	/**
	 * Store the current address as verified without asking TaxJar.
	 *
	 * Used after the merchant applies TaxJar's own suggestion, which needs no second check.
	 */
	public function mark_verified() {
		$this->save_result(
			array(
				'hash'       => self::hash_address( $this->get_store_address() ),
				'status'     => self::STATUS_VERIFIED,
				'suggestion' => null,
				'checked_at' => time(),
				'dismissed'  => false,
			)
		);
	}

	/**
	 * Does checking this address need a call to TaxJar?
	 *
	 * @param Address $address Address.
	 * @return bool False for a non-US store or a US store without a ZIP: both are answered locally.
	 */
	private function needs_request( Address $address ) {
		return 'US' === $address->country() && '' !== $address->postcode();
	}

	/**
	 * Check straight after the General settings are saved, with a short time limit.
	 *
	 * The merchant is waiting on the page, so a slow answer is left to the background
	 * check instead: a failed request is stored as an error, which is retried later.
	 */
	public function verify_after_settings_save() {
		if ( ! $this->is_check_due() ) {
			return;
		}

		$this->verify( self::SAVE_TIMEOUT );
	}

	/**
	 * Check the current store address and store the result.
	 *
	 * @param int $timeout Optional time limit in seconds for the request. 0 keeps the client's default.
	 * @return array The stored result.
	 */
	public function verify( $timeout = 0 ) {
		$address = $this->get_store_address();
		$result  = array(
			'hash'       => self::hash_address( $address ),
			'status'     => self::STATUS_SKIPPED,
			'suggestion' => null,
			'checked_at' => time(),
			'dismissed'  => false,
		);

		if ( ! $this->needs_request( $address ) ) {
			// TaxJar's address validation is US-only, and a US store without a ZIP can't be placed at all.
			if ( 'US' === $address->country() ) {
				$result['status'] = self::STATUS_ZIP_MISSING;
			}

			return $this->save_result( $result );
		}

		$response = $this->request_validation( $address, (int) $timeout );

		$result = array_merge( $result, self::classify_response( $address, $response ) );

		if ( self::STATUS_ERROR === $result['status'] ) {
			$this->taxjar->_log( 'Store address check failed: ' . self::describe_response( $response ) );
		}

		return $this->save_result( $result );
	}

	/**
	 * Mark the current result as dismissed, so its notice stays hidden until the address changes.
	 */
	public function dismiss() {
		$result = $this->get_current_result();

		if ( null === $result ) {
			return;
		}

		$result['dismissed'] = true;
		$this->save_result( $result );
	}

	/**
	 * Turn a TaxJar response into a result status and, when useful, a suggested address.
	 *
	 * Only the state and the 5-digit ZIP decide the outcome: they are what TaxJar uses to
	 * place the store. A different street, city spelling or ZIP+4 does not change the tax,
	 * so it does not earn a notice.
	 *
	 * @param Address         $address  Address that was checked.
	 * @param array|\WP_Error $response Response from WC_Connect_API_Client::proxy_request().
	 * @return array{status: string, suggestion: array|null}
	 */
	public static function classify_response( Address $address, $response ) {
		$error = array(
			'status'     => self::STATUS_ERROR,
			'suggestion' => null,
		);

		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return $error;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 404 === $code ) {
			// TaxJar answers 404 when it can't match the address to a real one.
			return array(
				'status'     => self::STATUS_NOT_FOUND,
				'suggestion' => null,
			);
		}

		if ( 200 !== $code ) {
			return $error;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $body ) || ! isset( $body['addresses'] ) || ! is_array( $body['addresses'] ) ) {
			return $error;
		}

		$candidates = array_values( array_filter( $body['addresses'], 'is_array' ) );

		if ( empty( $candidates ) ) {
			return array(
				'status'     => self::STATUS_NOT_FOUND,
				'suggestion' => null,
			);
		}

		$all_agree = true;
		foreach ( $candidates as $candidate ) {
			if ( ! self::places_same( $address, $candidate ) ) {
				$all_agree = false;
				break;
			}
		}

		if ( $all_agree ) {
			return array(
				'status'     => self::STATUS_VERIFIED,
				'suggestion' => null,
			);
		}

		if ( count( $candidates ) > 1 ) {
			// Several different places match: don't guess which one is right.
			return array(
				'status'     => self::STATUS_AMBIGUOUS,
				'suggestion' => null,
			);
		}

		// Suggest only the state and 5-digit ZIP. They are all that changes the tax, and the rest of
		// the store address (which also appears on invoices and emails) stays as the merchant wrote it.
		$candidate = $candidates[0];

		return array(
			'status'     => self::STATUS_SUGGESTION,
			'suggestion' => array(
				'state'    => self::candidate_state( $candidate ),
				'postcode' => self::zip5( self::candidate_zip( $candidate ) ),
			),
		);
	}

	/**
	 * Does a TaxJar candidate place the store in the same state and 5-digit ZIP?
	 *
	 * @param Address $address   Address that was checked.
	 * @param array   $candidate One entry of the response's `addresses`.
	 * @return bool
	 */
	private static function places_same( Address $address, array $candidate ) {
		return self::candidate_state( $candidate ) === $address->state()
			&& self::zip5( self::candidate_zip( $candidate ) ) === self::zip5( $address->postcode() );
	}

	/**
	 * A candidate's ZIP, as returned.
	 *
	 * @param array $candidate One entry of the response's `addresses`.
	 * @return string
	 */
	private static function candidate_zip( array $candidate ) {
		return isset( $candidate['zip'] ) && is_scalar( $candidate['zip'] ) ? (string) $candidate['zip'] : '';
	}

	/**
	 * A candidate's state code, uppercased.
	 *
	 * @param array $candidate One entry of the response's `addresses`.
	 * @return string
	 */
	private static function candidate_state( array $candidate ) {
		return isset( $candidate['state'] ) && is_scalar( $candidate['state'] ) ? strtoupper( trim( (string) $candidate['state'] ) ) : '';
	}

	/**
	 * The first five digits of a US ZIP code.
	 *
	 * @param string $zip ZIP or ZIP+4.
	 * @return string
	 */
	private static function zip5( $zip ) {
		return substr( preg_replace( '/[^0-9]/', '', $zip ), 0, 5 );
	}

	/**
	 * Send the address to TaxJar.
	 *
	 * @param Address $address Address to check.
	 * @param int     $timeout Time limit in seconds; 0 keeps the client's default.
	 * @return array|\WP_Error
	 */
	private function request_validation( Address $address, $timeout ) {
		$limit_timeout = static function ( $args, $url ) use ( $timeout ) {
			if ( is_array( $args ) && is_string( $url ) && false !== strpos( $url, self::VALIDATE_PATH ) ) {
				$args['timeout'] = $timeout;
			}

			return $args;
		};

		if ( $timeout > 0 ) {
			// proxy_request() always sets its own long time limit; shorten it for this one call only.
			add_filter( 'http_request_args', $limit_timeout, PHP_INT_MAX, 2 );
		}

		try {
			return $this->api_client->proxy_request(
				self::VALIDATE_PATH,
				array(
					'method'  => 'POST',
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => wp_json_encode(
						array(
							'country' => $address->country(),
							'state'   => $address->state(),
							'zip'     => $address->postcode(),
							'city'    => $address->city(),
							'street'  => $address->street(),
						)
					),
				)
			);
		} finally {
			if ( $timeout > 0 ) {
				remove_filter( 'http_request_args', $limit_timeout, PHP_INT_MAX );
			}
		}
	}

	/**
	 * Short description of a failed response for the log.
	 *
	 * @param array|\WP_Error $response Response.
	 * @return string
	 */
	private static function describe_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response->get_error_code() . ': ' . $response->get_error_message();
		}

		if ( ! is_array( $response ) ) {
			return 'unexpected response type';
		}

		return 'HTTP ' . wp_remote_retrieve_response_code( $response ) . ': ' . substr( (string) wp_remote_retrieve_body( $response ), 0, 300 );
	}

	/**
	 * Store a result.
	 *
	 * @param array $result Result.
	 * @return array The same result.
	 */
	private function save_result( array $result ) {
		update_option( self::OPTION_NAME, $result, false );

		return $result;
	}
}
