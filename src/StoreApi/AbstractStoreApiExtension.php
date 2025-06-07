<?php
/**
 * Abstract Store API Extension class.
 *
 * Provides a base class for extending the Store API.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\StoreApi;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\ProductSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Interface StoreApiExtensionInterface
 */
abstract class AbstractStoreApiExtension {

	/**
	 * Stores the Store API Extend Schema instance.
	 *
	 * @var ExtendSchema
	 */
	protected static ExtendSchema $extend_schema;

	/**
	 * Stores the possible endpoints to extend.
	 *
	 * @var array
	 */
	protected static array $endpoints = array(
		'cart-item' => CartItemSchema::IDENTIFIER,
		'cart'      => CartSchema::IDENTIFIER,
		'checkout'  => CheckoutSchema::IDENTIFIER,
		'product'   => ProductSchema::IDENTIFIER,

	);

	/**
	 * Stores the possible schema types.
	 *
	 * @var array
	 */
	protected static array $schema_types = array(
		'array_a' => 'ARRAY_A',
		'array_n' => 'ARRAY_N',
	);

	/**
	 * AbstractStoreApiExtension constructor.
	 *
	 * @param ExtendSchema $extend_schema The ExtendSchema instance.
	 */
	public function __construct( ExtendSchema $extend_schema ) {
		self::$extend_schema = $extend_schema;
	}

	/**
	 * Get the namespace for the extension.
	 */
	public function get_namespace(): string {
		return StoreApiExtendSchema::IDENTIFIER;
	}

	/**
	 * Get the endpoint to extend.
	 *
	 * Should return one of the keys from the $endpoints array.
	 *
	 * @return string
	 */
	abstract public function get_endpoint(): string;

	/**
	 * The data callback method.
	 *
	 * This is where you can define the data this endpoint should return.
	 *
	 * @return array
	 */
	abstract public function data_callback(): array;

	/**
	 * The schema callback method.
	 *
	 * This is where you can define the schema for the endpoint.
	 *
	 * @return array
	 */
	abstract public function schema_callback(): array;

	/**
	 * The update callback method.
	 *
	 * This is where you can listen for updates to the endpoint and handle accordingly.
	 *
	 * @param array $data Data to update.
	 *
	 * @return void
	 */
	abstract public function update_callback( array $data ): void;

	/**
	 * Get the schema type to extend the endpoint with.
	 *
	 * Should return one of the keys from the $schema_types array.
	 *
	 * @return string
	 */
	abstract public function get_schema_type(): string;
}
