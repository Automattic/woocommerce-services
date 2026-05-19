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
use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * StoreApiExtendSchema class.
 */
class StoreApiExtendSchema {
	/**
	 * Stores Store API ExtendSchema instance.
	 *
	 * @var ExtendSchema
	 */
	private static ExtendSchema $instance;

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
		try {
			self::$instance = StoreApi::container()->get( ExtendSchema::class );
		} catch ( Exception $e ) {
			wc_get_logger()->debug( 'Failed to get ExtendSchema instance.', array( 'exception' => $e ) );
		}
	}

	/**
	 * Returns the ExtendSchema instance.
	 */
	public static function instance(): ExtendSchema {
		if ( ! isset( self::$instance ) ) {
			new self();
		}

		return self::$instance;
	}
}
