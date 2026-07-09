<?php
/**
 * StoreApiExtendSchema class.
 *
 * Wrapper class for the ExtendSchema instance.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\StoreApi;

use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * StoreApiExtendSchema class.
 */
class StoreApiExtendSchema {
	/**
	 * Stores Store API ExtendSchema instance.
	 *
	 * Null when the instance has not been resolved, or when resolution failed.
	 *
	 * @var ExtendSchema|null
	 */
	private static ?ExtendSchema $instance = null;

	/**
	 * Whether resolving the ExtendSchema instance has been attempted.
	 *
	 * Guards against re-running container resolution (and re-logging) on every
	 * request when resolution fails on an install with a broken Store API.
	 *
	 * @var bool
	 */
	private static bool $attempted = false;

	/**
	 * Plugin Identifier
	 *
	 * @var string
	 */
	const IDENTIFIER = 'woocommerce-services';

	/**
	 * ExtendSchemaService constructor.
	 */
	private function __construct() {
		self::$attempted = true;

		try {
			self::$instance = StoreApi::container()->get( ExtendSchema::class );
		} catch ( Throwable $e ) {
			wc_get_logger()->debug( 'Failed to get ExtendSchema instance.', array( 'exception' => $e ) );
		}
	}

	/**
	 * Returns the ExtendSchema instance, or null when it cannot be resolved.
	 *
	 * Callers MUST check for null before use: on a partial or broken WooCommerce
	 * install the container can fail to resolve ExtendSchema even when the
	 * top-level StoreApi class exists.
	 */
	public static function instance(): ?ExtendSchema {
		if ( ! self::$attempted ) {
			new self();
		}

		return self::$instance;
	}
}
