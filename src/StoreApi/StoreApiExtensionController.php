<?php
/**
 * Store API Extension Controller.
 *
 * A class that manages all the Store API extensions for WooCommerce Shipping.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\StoreApi;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Store API Extension Controller.
 */
class StoreApiExtensionController {

	/**
	 * Stores the Store API Extend Schema instance.
	 *
	 * @var ExtendSchema
	 */
	private static ExtendSchema $extend_schema;

	/**
	 * Stores the registered extensions.
	 *
	 * @var array
	 */
	private array $registered_extensions = array();

	/**
	 * StoreApiExntensionController constructor.
	 *
	 * @param ExtendSchema $extend_schema The StoreApiExtendSchema instance.
	 */
	public function __construct( ExtendSchema $extend_schema ) {
		self::$extend_schema = $extend_schema;
	}

	/**
	 * Registers an extension.
	 *
	 * @param AbstractStoreApiExtension $extension The extension to register.
	 */
	public function register_extension( AbstractStoreApiExtension $extension ) {
		$this->registered_extensions[] = $extension;
	}

	/**
	 * Returns the registered extensions.
	 *
	 * @return array
	 */
	public function get_registered_extensions(): array {
		return $this->registered_extensions;
	}

	/**
	 * Registers the data into each endpoint.
	 */
	public function extend_store() {
		$registered_extensions = $this->get_registered_extensions();

		if ( empty( $registered_extensions ) ) {
			return;
		}

		foreach ( $registered_extensions as $extension ) {
			$this->register_endpoint_data( $extension );
			$this->register_update_callback( $extension );
		}
	}

	/**
	 * Registers the endpoint data for an extension.
	 *
	 * @param AbstractStoreApiExtension $extension The extension to register.
	 */
	public function register_endpoint_data( AbstractStoreApiExtension $extension ) {
		try {
			self::$extend_schema->register_endpoint_data(
				array(
					'endpoint'        => $extension->get_endpoint(),
					'namespace'       => $extension->get_namespace(),
					'data_callback'   => array( $extension, 'data_callback' ),
					'schema_callback' => array( $extension, 'schema_callback' ),
					'schema_type'     => $extension->get_schema_type(),
				)
			);
		} catch ( Exception $e ) {
			wc_get_logger()->debug( 'Failed to register endpoint data for extension', array( 'error', $e->getMessage() ) );
		}
	}

	/**
	 * Registers the update callback for an extension.
	 *
	 * @param AbstractStoreApiExtension $extension The extension to register.
	 */
	public function register_update_callback( AbstractStoreApiExtension $extension ) {
		try {
			self::$extend_schema->register_update_callback(
				array(
					'namespace' => $extension->get_namespace(),
					'callback'  => array( $extension, 'update_callback' ),
				)
			);
		} catch ( Exception $e ) {
			wc_get_logger()->debug( 'Failed to register update callback for extension', array( 'error', $e->getMessage() ) );
		}
	}
}
