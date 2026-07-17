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
	 * Guards against re-running container resolution (and re-logging) more than
	 * once within a single request when resolution fails. Note this cannot span
	 * requests: PHP statics reset on every page load, so a broken install still
	 * logs once per request.
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
	 *
	 * Protected rather than private so tests can subclass and override
	 * resolve_extend_schema() to exercise the resolution-failure path.
	 */
	protected function __construct() {
		self::$attempted = true;

		try {
			self::$instance = static::resolve_extend_schema();
		} catch ( Throwable $e ) {
			wc_get_logger()->debug(
				'Failed to get ExtendSchema instance.',
				array(
					'source'    => 'woocommerce-services',
					'exception' => $e,
				)
			);
		}
	}

	/**
	 * Resolve the ExtendSchema instance from the Store API container.
	 *
	 * Extracted as a seam so a broken container can be simulated in tests (subclass
	 * and override to throw) without needing a genuinely partial WooCommerce install.
	 *
	 * @return ExtendSchema
	 */
	protected static function resolve_extend_schema(): ExtendSchema {
		return StoreApi::container()->get( ExtendSchema::class );
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
			new static();
		}

		return self::$instance;
	}
}
