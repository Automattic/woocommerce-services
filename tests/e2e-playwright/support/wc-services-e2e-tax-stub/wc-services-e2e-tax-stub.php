<?php
/**
 * Plugin Name: WooCommerce Tax E2E API Stub
 * Description: Serves deterministic TaxJar responses for the WooCommerce Tax E2E suite, through the same Connect server proxy the plugin calls. Inert until armed over its REST route, and refuses to arm on a store with a real WordPress.com connection.
 * Version: 1.0.0
 * Author: WooCommerce
 * Requires PHP: 7.4
 *
 * This support plugin exists so the E2E suite is deterministic and needs no
 * network egress and no WordPress.com connection. It is installed only in test
 * environments (.wp-env.json locally, qit.json for QIT runs) and never ships:
 * the release zip is built from the whitelist in tasks/release.js, which does
 * not include tests/.
 *
 * Automated taxes need three things a fresh test site does not have, and the
 * stub supplies each of them only while armed:
 *
 * 1. A Jetpack state other than "not connected". WC_Connect_Loader::pre_wc_init()
 *    returns before attaching any hook when get_jetpack_install_status() reports
 *    JETPACK_NOT_CONNECTED. Offline mode is the state the plugin already supports
 *    for development, so the stub turns it on through Jetpack's own
 *    `jetpack_offline_mode` filter.
 * 2. Accepted terms of service, without which pre_wc_init() also stops early.
 * 3. A blog token. WC_Connect_API_Client::authorization_header() returns a
 *    WP_Error before any HTTP request is made when there is none. The stub
 *    supplies a sentinel token through the plugin's public
 *    `wc_connect_jetpack_access_token` filter; the request it signs is answered
 *    by the stub and never leaves the site.
 *
 * @package woocommerce-services
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Option holding the armed flag. Owned by this stub, so arming never writes to
 * a setting the plugin under test reads.
 */
define( 'WC_SERVICES_E2E_TAX_STUB_ARMED_OPTION', 'wc_services_e2e_tax_stub_armed' );

/**
 * Option recording that /arm accepted the terms of service, so /disarm only
 * undoes an acceptance the stub made itself.
 */
define( 'WC_SERVICES_E2E_TAX_STUB_TOS_OPTION', 'wc_services_e2e_tax_stub_set_tos' );

/**
 * Transient holding the TaxJar requests the stub has answered since the last
 * reset. Read back over REST so a spec can assert on what the plugin SENT, not
 * merely on what the stub sent back.
 */
define( 'WC_SERVICES_E2E_TAX_STUB_REQUESTS_KEY', 'wc_services_e2e_tax_stub_requests' );

/**
 * Cap on retained requests, so a long local session cannot grow the transient
 * without bound. Only the most recent are kept.
 */
define( 'WC_SERVICES_E2E_TAX_STUB_REQUESTS_MAX', 20 );

/**
 * Option holding the IDs of the tax rate rows inserted while armed. The plugin
 * writes each TaxJar rate into the WooCommerce tax tables, and core keeps
 * applying a row through its own lookup whenever the TaxJar path yields nothing,
 * so a row left behind would let a spec pass without the stub being asked and
 * would keep taxing a developer's store after /disarm.
 */
define( 'WC_SERVICES_E2E_TAX_STUB_RATE_IDS_OPTION', 'wc_services_e2e_tax_stub_rate_ids' );

/**
 * Rates the stub quotes for every line item, keyed by the TaxJar breakdown field
 * name. The plugin turns each `*_tax_rate` field into its own WooCommerce tax
 * rate row (get_itemized_tax_rates()), so two components exercise the itemized
 * path while keeping the expected totals easy to derive: on a $100 item they
 * come to $6.25 and $2.00.
 *
 * County and special-district rates are left out on purpose. A zero-rate
 * component would still create a rate row, and an extra row is noise in a
 * baseline suite.
 *
 * @return array<string, float>
 */
function wc_services_e2e_tax_stub_rates() {
	return array(
		'state_sales_tax_rate' => 0.0625,
		'city_tax_rate'        => 0.02,
	);
}

/**
 * Whether the stub is armed.
 *
 * Recomputed from the option on every call, so arming and disarming take
 * effect on the next request.
 *
 * @return bool
 */
function wc_services_e2e_tax_stub_is_armed() {
	return 'yes' === get_option( WC_SERVICES_E2E_TAX_STUB_ARMED_OPTION );
}

/**
 * Whether the store holds a real WordPress.com connection.
 *
 * Checked through the connection manager directly, not offline mode, so the
 * offline-mode filter below cannot hide a real connection from it. A blog token
 * alone counts: WC_Connect_Jetpack::is_connected() also requires a connected
 * owner, and a site-only connection is still a real one that arming would put
 * into offline mode.
 *
 * @return bool
 */
function wc_services_e2e_tax_stub_has_real_connection() {
	return class_exists( 'WC_Connect_Jetpack' ) && WC_Connect_Jetpack::get_connection_manager()->is_connected();
}

/**
 * Whether the stub should act: armed, and not on a store with a real
 * connection.
 *
 * /arm refuses a connected store, but a store can be connected after arming,
 * for example while the stub is deactivated. The behavioral filters check this
 * so they stand down on such a store. It is kept out of is_armed() on purpose:
 * /disarm and /reset are armed-gated, and would otherwise refuse to clean up
 * the very store this protects.
 *
 * @return bool
 */
function wc_services_e2e_tax_stub_is_active() {
	return wc_services_e2e_tax_stub_is_armed() && ! wc_services_e2e_tax_stub_has_real_connection();
}

/**
 * Turn on Jetpack offline mode while active.
 *
 * @param bool $offline_mode Whether offline mode is active.
 *
 * @return bool
 */
function wc_services_e2e_tax_stub_offline_mode( $offline_mode ) {
	return wc_services_e2e_tax_stub_is_active() ? true : $offline_mode;
}
add_filter( 'jetpack_offline_mode', 'wc_services_e2e_tax_stub_offline_mode' );

/**
 * Supply a sentinel blog token while active and no real token exists.
 *
 * The secret only has to have the `key.secret` shape authorization_header()
 * splits on. Nothing verifies the signature: the stub answers the request it
 * signs.
 *
 * @param mixed $token Token from the connection manager.
 *
 * @return mixed
 */
function wc_services_e2e_tax_stub_access_token( $token ) {
	// A real token is an object with a secret. Anything else (false by default,
	// a WP_Error when errors are not suppressed) is not one, so it is replaced.
	if ( ( is_object( $token ) && ! empty( $token->secret ) ) || ! wc_services_e2e_tax_stub_is_active() ) {
		return $token;
	}

	return (object) array(
		'secret'           => 'wcservicese2e.stubsecret',
		'external_user_id' => 1,
	);
}
add_filter( 'wc_connect_jetpack_access_token', 'wc_services_e2e_tax_stub_access_token' );

/**
 * Record a TaxJar request the stub is about to answer.
 *
 * @param string $path Path below the Connect server URL.
 * @param mixed  $body Decoded request body.
 *
 * @return void
 */
function wc_services_e2e_tax_stub_record_request( $path, $body ) {
	$recorded = get_transient( WC_SERVICES_E2E_TAX_STUB_REQUESTS_KEY );

	if ( ! is_array( $recorded ) ) {
		$recorded = array();
	}

	$recorded[] = array(
		'path' => $path,
		'body' => $body,
	);

	if ( count( $recorded ) > WC_SERVICES_E2E_TAX_STUB_REQUESTS_MAX ) {
		$recorded = array_slice( $recorded, -WC_SERVICES_E2E_TAX_STUB_REQUESTS_MAX );
	}

	set_transient( WC_SERVICES_E2E_TAX_STUB_REQUESTS_KEY, $recorded, HOUR_IN_SECONDS );
}

/**
 * Build a SmartCalcs `taxes` response for a request body.
 *
 * The plugin keys its rate rows on the line item IDs it sent
 * (get_itemized_tax_rates() reads `breakdown.line_items[].id`), so the
 * breakdown has to echo those IDs back; a fixed body would match no cart.
 *
 * @param array $body Decoded request body.
 *
 * @return array
 */
function wc_services_e2e_tax_stub_taxes_response( array $body ) {
	$rates    = wc_services_e2e_tax_stub_rates();
	$combined = array_sum( $rates );
	$items    = isset( $body['line_items'] ) && is_array( $body['line_items'] ) ? $body['line_items'] : array();

	$line_items = array();
	$taxable    = 0.0;

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) || ! isset( $item['id'] ) ) {
			continue;
		}

		$quantity = isset( $item['quantity'] ) ? (float) $item['quantity'] : 1.0;
		$price    = isset( $item['unit_price'] ) ? (float) $item['unit_price'] : 0.0;
		$discount = isset( $item['discount'] ) ? (float) $item['discount'] : 0.0;
		$amount   = $quantity * $price - $discount;
		$taxable += $amount;

		$line_items[] = array_merge(
			array(
				'id'                => (string) $item['id'],
				'taxable_amount'    => $amount,
				'tax_collectable'   => round( $amount * $combined, 2 ),
				'combined_tax_rate' => $combined,
			),
			$rates
		);
	}

	return array(
		'tax' => array(
			'order_total_amount' => $taxable,
			'shipping'           => 0,
			'taxable_amount'     => $taxable,
			'amount_to_collect'  => round( $taxable * $combined, 2 ),
			'rate'               => $combined,
			'has_nexus'          => true,
			'freight_taxable'    => false,
			'tax_source'         => 'destination',
			'jurisdictions'      => array(
				'country' => isset( $body['to_country'] ) ? $body['to_country'] : '',
				'state'   => isset( $body['to_state'] ) ? $body['to_state'] : '',
				'county'  => 'E2E COUNTY',
				'city'    => isset( $body['to_city'] ) ? strtoupper( (string) $body['to_city'] ) : '',
			),
			'breakdown'          => array(
				'line_items' => $line_items,
			),
		),
	);
}

/**
 * Build an `addresses/validate` response confirming the address as sent.
 *
 * StoreAddressVerifier classifies a single candidate in the same state and ZIP
 * as "verified", so echoing the address keeps the store-address notice quiet
 * instead of reporting an error on every admin screen.
 *
 * @param array $body Decoded request body.
 *
 * @return array
 */
function wc_services_e2e_tax_stub_validate_response( array $body ) {
	return array(
		'addresses' => array(
			array(
				'country' => isset( $body['country'] ) ? $body['country'] : '',
				'state'   => isset( $body['state'] ) ? $body['state'] : '',
				'zip'     => isset( $body['zip'] ) ? $body['zip'] : '',
				'city'    => isset( $body['city'] ) ? $body['city'] : '',
				'street'  => isset( $body['street'] ) ? $body['street'] : '',
			),
		),
	);
}

/**
 * Answer requests to the Connect server while armed.
 *
 * Only URLs under WOOCOMMERCE_CONNECT_SERVER_URL are considered. Of those, the
 * two TaxJar endpoints the tax path calls get canned answers; every other one
 * fails with a WP_Error rather than falling through to the live server, which
 * would be a slow timeout inside a `network: false` QIT container and a real
 * request with a fake signature everywhere else.
 *
 * @param false|array|WP_Error $preempt     Whether to preempt the request.
 * @param array                $parsed_args Request arguments.
 * @param string               $url         Request URL.
 *
 * @return false|array|WP_Error
 */
function wc_services_e2e_tax_stub_pre_http_request( $preempt, $parsed_args, $url ) {
	if ( false !== $preempt || ! defined( 'WOOCOMMERCE_CONNECT_SERVER_URL' ) ) {
		return $preempt;
	}

	$server = trailingslashit( WOOCOMMERCE_CONNECT_SERVER_URL );

	// Cheapest test first: this filter runs on every outbound request on the site.
	if ( 0 !== strpos( (string) $url, $server ) || ! wc_services_e2e_tax_stub_is_active() ) {
		return $preempt;
	}

	$path = (string) wp_parse_url( substr( $url, strlen( $server ) ), PHP_URL_PATH );
	$body = isset( $parsed_args['body'] ) && is_string( $parsed_args['body'] ) ? json_decode( $parsed_args['body'], true ) : null;
	$body = is_array( $body ) ? $body : array();

	if ( 'taxjar/v2/taxes' === $path ) {
		wc_services_e2e_tax_stub_record_request( $path, $body );
		$response = wc_services_e2e_tax_stub_taxes_response( $body );
	} elseif ( 'taxjar/v2/addresses/validate' === $path ) {
		wc_services_e2e_tax_stub_record_request( $path, $body );
		$response = wc_services_e2e_tax_stub_validate_response( $body );
	} else {
		return new WP_Error(
			'wc_services_e2e_tax_stub_unmatched_url',
			sprintf(
				'WooCommerce Tax E2E stub is armed and does not answer %s. Add the endpoint to the stub rather than letting the suite reach the Connect server.',
				esc_url_raw( $url )
			)
		);
	}

	return array(
		'headers'  => array( 'content-type' => 'application/json' ),
		'body'     => wp_json_encode( $response ),
		'response' => array(
			'code'    => 200,
			'message' => 'OK',
		),
		'cookies'  => array(),
		'filename' => null,
	);
}
add_filter( 'pre_http_request', 'wc_services_e2e_tax_stub_pre_http_request', 10, 3 );

/**
 * Warn on every wp-admin screen while armed.
 *
 * `npm run env:start` activates this plugin by default, so without a visible
 * signal every tax amount on the site would be silently fabricated.
 *
 * @return void
 */
function wc_services_e2e_tax_stub_admin_notice() {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce core capability.
	if ( ! wc_services_e2e_tax_stub_is_armed() || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}

	// Not translatable: a developer-facing notice from a test-only plugin that is
	// never packaged, so the string could never reach a translation file.
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		wp_kses_post( '<strong>WooCommerce Tax E2E API Stub is armed.</strong> Every automated tax amount on this site is fabricated by the stub, not calculated by TaxJar. Deactivate the plugin to stop it.' )
	);
}
add_action( 'admin_notices', 'wc_services_e2e_tax_stub_admin_notice' );

/**
 * Delete the plugin's cached TaxJar responses.
 *
 * smartcalcs_cache_request() caches every response in a `tj_tax_*` transient,
 * so without this a spec could pass against a cached response the stub never
 * served in this run, and no request would be recorded for it.
 *
 * The wp_cache_flush() is load-bearing: under a persistent object cache the
 * transients live in the cache rather than the options table, so the DELETE
 * alone would remove nothing get_transient() reads.
 *
 * @return void
 */
function wc_services_e2e_tax_stub_clear_tax_cache() {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Test-only support plugin; the cache keys are hashes that cannot be enumerated otherwise.
	$wpdb->query(
		"DELETE FROM `$wpdb->options`
		 WHERE `option_name` LIKE '\_transient\_tj\_tax\_%'
		    OR `option_name` LIKE '\_transient\_timeout\_tj\_tax\_%'"
	);

	wp_cache_flush();
}

/**
 * Remember a tax rate row inserted while armed, so /reset and /disarm can
 * remove it.
 *
 * @param int $tax_rate_id Inserted tax rate ID.
 *
 * @return void
 */
function wc_services_e2e_tax_stub_track_rate( $tax_rate_id ) {
	if ( ! wc_services_e2e_tax_stub_is_armed() ) {
		return;
	}

	$ids = get_option( WC_SERVICES_E2E_TAX_STUB_RATE_IDS_OPTION, array() );
	$ids = is_array( $ids ) ? $ids : array();

	$ids[] = absint( $tax_rate_id );

	update_option( WC_SERVICES_E2E_TAX_STUB_RATE_IDS_OPTION, array_values( array_unique( $ids ) ), false );
}
add_action( 'woocommerce_tax_rate_added', 'wc_services_e2e_tax_stub_track_rate' );

/**
 * Delete the tax rate rows inserted while armed.
 *
 * Only tracked rows are removed, so a store's own rates survive. Rows the
 * plugin has since updated in place keep their ID and are removed too.
 *
 * @return void
 */
function wc_services_e2e_tax_stub_delete_rates() {
	// Without WooCommerce the rows cannot be deleted, so keep the IDs for a
	// later /disarm rather than forgetting them.
	if ( ! class_exists( 'WC_Tax' ) ) {
		return;
	}

	$ids = get_option( WC_SERVICES_E2E_TAX_STUB_RATE_IDS_OPTION, array() );

	if ( is_array( $ids ) ) {
		foreach ( $ids as $id ) {
			WC_Tax::_delete_tax_rate( absint( $id ) );
		}
	}

	delete_option( WC_SERVICES_E2E_TAX_STUB_RATE_IDS_OPTION );
}

/**
 * Whether an earlier disarm left cleanup undone: a terms acceptance or tax rate
 * rows the stub made, recorded because WooCommerce Tax or WooCommerce was
 * inactive at the time.
 *
 * @return bool
 */
function wc_services_e2e_tax_stub_has_pending_cleanup() {
	return 'yes' === get_option( WC_SERVICES_E2E_TAX_STUB_TOS_OPTION )
		|| false !== get_option( WC_SERVICES_E2E_TAX_STUB_RATE_IDS_OPTION );
}

/**
 * Disarm and undo what arming changed.
 *
 * Each piece of cleanup forgets its record only once it has run, so a disarm
 * while WooCommerce Tax or WooCommerce is inactive leaves the rest for the next
 * one.
 *
 * @return void
 */
function wc_services_e2e_tax_stub_undo_arming() {
	if ( 'yes' === get_option( WC_SERVICES_E2E_TAX_STUB_TOS_OPTION ) && class_exists( 'WC_Connect_Options' ) ) {
		WC_Connect_Options::delete_option( 'tos_accepted' );
		delete_option( WC_SERVICES_E2E_TAX_STUB_TOS_OPTION );
	}

	wc_services_e2e_tax_stub_delete_rates();
	delete_option( WC_SERVICES_E2E_TAX_STUB_ARMED_OPTION );
	delete_transient( WC_SERVICES_E2E_TAX_STUB_REQUESTS_KEY );
	wc_services_e2e_tax_stub_clear_tax_cache();
}

/**
 * Disarm when the stub is deactivated.
 *
 * Otherwise the armed flag survives deactivation, and reactivating the stub
 * later would bring it back armed without anyone calling /arm.
 *
 * @return void
 */
function wc_services_e2e_tax_stub_deactivate() {
	if ( wc_services_e2e_tax_stub_is_armed() || wc_services_e2e_tax_stub_has_pending_cleanup() ) {
		wc_services_e2e_tax_stub_undo_arming();
	}
}
register_deactivation_hook( __FILE__, 'wc_services_e2e_tax_stub_deactivate' );

/**
 * Register the E2E-only REST routes.
 *
 * `GET /status` reports the armed state and the recorded requests. It is not
 * armed-gated: provisioning calls it to find out whether the stub is armed.
 *
 * `POST /arm` cannot be armed-gated, so it refuses instead on a store with a
 * real WordPress.com connection. `POST /reset` is armed-gated, and `POST
 * /disarm` acts only when armed or when an earlier disarm left cleanup undone,
 * so a disarmed stub never touches a store's caches or cart.
 *
 * All routes require `manage_woocommerce`, which WooCommerce also grants to
 * shop managers. That is accepted: the routes only exist on wp-env checkouts and
 * disposable QIT sites, and the protections that matter are the real-connection
 * refusal and the plugin never being packaged.
 *
 * @return void
 */
function wc_services_e2e_tax_stub_register_routes() {
	$can_manage = function () {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- WooCommerce core capability.
		return current_user_can( 'manage_woocommerce' );
	};

	$routes = array(
		'/status' => array( 'GET', 'wc_services_e2e_tax_stub_status' ),
		'/arm'    => array( 'POST', 'wc_services_e2e_tax_stub_arm' ),
		'/disarm' => array( 'POST', 'wc_services_e2e_tax_stub_disarm' ),
		'/reset'  => array( 'POST', 'wc_services_e2e_tax_stub_reset' ),
	);

	foreach ( $routes as $route => $handler ) {
		register_rest_route(
			'wc-services-e2e-tax-stub/v1',
			$route,
			array(
				'methods'             => $handler[0],
				'callback'            => $handler[1],
				'permission_callback' => $can_manage,
			)
		);
	}
}
add_action( 'rest_api_init', 'wc_services_e2e_tax_stub_register_routes' );

/**
 * REST callback: report the armed state and the recorded requests.
 *
 * @return WP_REST_Response
 */
function wc_services_e2e_tax_stub_status() {
	$recorded = get_transient( WC_SERVICES_E2E_TAX_STUB_REQUESTS_KEY );

	return rest_ensure_response(
		array(
			'armed'    => wc_services_e2e_tax_stub_is_armed(),
			'requests' => is_array( $recorded ) ? array_values( $recorded ) : array(),
		)
	);
}

/**
 * REST callback: arm the stub.
 *
 * Accepts the terms of service when they are not yet accepted, and remembers
 * doing so for /disarm. The plugin reads both the armed state and the terms on
 * its next load, so automated taxes become available from the next request.
 *
 * @return WP_REST_Response|WP_Error
 */
function wc_services_e2e_tax_stub_arm() {
	if ( wc_services_e2e_tax_stub_has_real_connection() ) {
		return new WP_Error(
			'wc_services_e2e_tax_stub_real_connection',
			'Refusing to arm: this store has a real WordPress.com connection, so it is not a disposable test store.',
			array( 'status' => 409 )
		);
	}

	if ( ! class_exists( 'WC_Connect_Options' ) ) {
		return new WP_Error(
			'wc_services_e2e_tax_stub_plugin_inactive',
			'Refusing to arm: WooCommerce Tax is not active.',
			array( 'status' => 409 )
		);
	}

	if ( ! WC_Connect_Options::get_option( 'tos_accepted' ) ) {
		WC_Connect_Options::update_option( 'tos_accepted', true );
		update_option( WC_SERVICES_E2E_TAX_STUB_TOS_OPTION, 'yes' );
	}

	update_option( WC_SERVICES_E2E_TAX_STUB_ARMED_OPTION, 'yes' );

	// Responses cached before arming were answered by whatever ran before.
	wc_services_e2e_tax_stub_clear_tax_cache();

	return rest_ensure_response( array( 'armed' => true ) );
}

/**
 * REST callback: disarm the stub and undo what /arm changed.
 *
 * Called from the Playwright global teardown so a developer's wp-env store is
 * not left on fabricated tax amounts after a run. Also finishes cleanup an
 * earlier disarm had to leave undone.
 *
 * @return WP_REST_Response
 */
function wc_services_e2e_tax_stub_disarm() {
	if ( wc_services_e2e_tax_stub_is_armed() || wc_services_e2e_tax_stub_has_pending_cleanup() ) {
		wc_services_e2e_tax_stub_undo_arming();
	}

	return rest_ensure_response( array( 'armed' => false ) );
}

/**
 * REST callback: clear the state that would let a spec skip the stub.
 *
 * - The plugin's cached TaxJar responses.
 * - The tax rate rows written from earlier stub answers, which core would
 *   otherwise apply on its own if the TaxJar path broke.
 * - The requests recorded so far, so a spec cannot pass on an earlier capture.
 * - The logged-in admin's persistent cart, so a stray item from an earlier run
 *   does not change the totals a spec asserts.
 *
 * @return WP_REST_Response|WP_Error
 */
function wc_services_e2e_tax_stub_reset() {
	if ( ! wc_services_e2e_tax_stub_is_armed() ) {
		return new WP_Error(
			'wc_services_e2e_tax_stub_not_armed',
			'Refusing to reset: the WooCommerce Tax E2E stub is not armed, so this store is not a disposable test store.',
			array( 'status' => 409 )
		);
	}

	wc_services_e2e_tax_stub_delete_rates();
	wc_services_e2e_tax_stub_clear_tax_cache();
	delete_transient( WC_SERVICES_E2E_TAX_STUB_REQUESTS_KEY );

	// A REST request initialises neither the session nor the cart.
	if ( function_exists( 'wc_load_cart' ) ) {
		wc_load_cart();

		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}
	}

	return rest_ensure_response( array( 'reset' => true ) );
}
