<?php
/**
 * Store_API_Extension class.
 *
 * A class to extend the store public API with Table Rate Shipping Abort Message functionality.
 *
 * @package WooCommerce_Table_Rate_Shipping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

/**
 * Store API Extension.
 */
class WC_Connect_Store_API_Extension {
	/**
	 * Stores Rest Extending instance.
	 *
	 * @var ExtendSchema
	 */
	private static $extend;

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'woocommerce_services';

	/**
	 * Bootstraps the class and hooks required data.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::$extend = StoreApi::container()->get( ExtendSchema::class );
		self::extend_store();
	}

	/**
	 * Registers the data into each endpoint.
	 */
	public static function extend_store() {

		self::$extend->register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( static::class, 'data' ),
				'schema_callback' => array( static::class, 'schema' ),
				'schema_type'     => ARRAY_A,
			)
		);
	}

	/**
	 * Store API extension data callback.
	 *
	 * @return array
	 */
	public static function data() {
		$notices = WC()->session->get( WC_Connect_TaxJar_Integration::NOTICE_KEY );
		$notices = is_array( $notices ) ? $notices : array();

		return array(
			'error_notices' => $notices,
		);
	}

	/**
	 * Store API extension schema callback.
	 *
	 * @return array Registered schema.
	 */
	public static function schema() {
		return array(
			'error_notices' => array(
				'description' => __( 'Error notices from TaxJar operation.', 'woocommerce-services' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
		);
	}
}
