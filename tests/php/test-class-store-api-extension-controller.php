<?php
/**
 * Tests for StoreApiExtensionController.
 *
 * @package Automattic/WCServices
 */

use Automattic\WCServices\StoreApi\StoreApiExtensionController;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;

require_once __DIR__ . '/class-wcservices-throwing-store-api-extension.php';

/**
 * A failure while registering one extension is logged and skipped, whatever it throws.
 */
class WP_Test_Store_Api_Extension_Controller extends WC_Unit_Test_Case {

	/**
	 * The controller's ExtendSchema before the test, put back afterwards.
	 *
	 * @var ExtendSchema|null
	 */
	private $saved_extend_schema;

	/**
	 * The real ExtendSchema from the Store API container.
	 *
	 * @var ExtendSchema
	 */
	private $extend_schema;

	/**
	 * Logger that records what reaches it.
	 *
	 * @var WC_Logger
	 */
	private $logger;

	/**
	 * Save the shared static and route wc_get_logger() to a recording logger.
	 */
	public function set_up() {
		parent::set_up();

		$property                  = $this->extend_schema_property();
		$this->saved_extend_schema = $property->isInitialized() ? $property->getValue() : null;
		$this->extend_schema       = StoreApi::container()->get( ExtendSchema::class );

		$this->logger = new class() extends WC_Logger {
			/**
			 * Entries logged, as level, message and context.
			 *
			 * @var array[]
			 */
			public $entries = array();

			/**
			 * Record instead of writing.
			 *
			 * @param string $level   Level.
			 * @param string $message Message.
			 * @param array  $context Context.
			 */
			public function log( $level, $message, $context = array() ) {
				$this->entries[] = compact( 'level', 'message', 'context' );
			}
		};
		add_filter( 'woocommerce_logging_class', array( $this, 'get_logger' ) );
	}

	/**
	 * Put the static and the logger back.
	 */
	public function tear_down() {
		remove_filter( 'woocommerce_logging_class', array( $this, 'get_logger' ) );

		if ( null !== $this->saved_extend_schema ) {
			$this->extend_schema_property()->setValue( null, $this->saved_extend_schema );
		}

		parent::tear_down();
	}

	/**
	 * Filter callback for woocommerce_logging_class.
	 *
	 * @return WC_Logger
	 */
	public function get_logger() {
		return $this->logger;
	}

	/**
	 * The controller's private static ExtendSchema.
	 *
	 * @return ReflectionProperty
	 */
	private function extend_schema_property() {
		$property = new ReflectionProperty( StoreApiExtensionController::class, 'extend_schema' );
		$property->setAccessible( true );

		return $property;
	}

	/**
	 * An extension that throws the given value from every getter.
	 *
	 * @param Throwable $throwable What to throw.
	 * @return WCServices_Throwing_Store_Api_Extension
	 */
	private function throwing_extension( Throwable $throwable ) {
		return new WCServices_Throwing_Store_Api_Extension( $this->extend_schema, $throwable );
	}

	/**
	 * Assert one debug entry with the plugin's source and the message under an error key.
	 *
	 * @param string $message Expected log message.
	 * @param string $error   Expected error text.
	 */
	private function assert_logged( $message, $error ) {
		$this->assertCount( 1, $this->logger->entries );
		$this->assertSame( 'debug', $this->logger->entries[0]['level'] );
		$this->assertSame( $message, $this->logger->entries[0]['message'] );
		$this->assertSame(
			array(
				'source' => 'woocommerce-services',
				'error'  => $error,
			),
			$this->logger->entries[0]['context']
		);
	}

	/**
	 * A TypeError while registering endpoint data is logged, not fatal.
	 */
	public function test_type_error_registering_endpoint_data_is_logged() {
		$controller = new StoreApiExtensionController( $this->extend_schema );

		$controller->register_endpoint_data( $this->throwing_extension( new TypeError( 'Simulated type error.' ) ) );

		$this->assert_logged( 'Failed to register endpoint data for extension', 'Simulated type error.' );
	}

	/**
	 * An Error while registering the update callback is logged, not fatal.
	 */
	public function test_error_registering_update_callback_is_logged() {
		$controller = new StoreApiExtensionController( $this->extend_schema );

		$controller->register_update_callback( $this->throwing_extension( new Error( 'Simulated error.' ) ) );

		$this->assert_logged( 'Failed to register update callback for extension', 'Simulated error.' );
	}

	/**
	 * One broken extension does not stop extend_store(): both registrations are attempted and logged.
	 */
	public function test_extend_store_continues_past_a_throwing_extension() {
		$controller = new StoreApiExtensionController( $this->extend_schema );
		$controller->register_extension( $this->throwing_extension( new Error( 'Simulated error.' ) ) );

		$controller->extend_store();

		$this->assertCount( 2, $this->logger->entries );
	}

	/**
	 * An Exception is still caught and logged, as before.
	 */
	public function test_exception_registering_endpoint_data_is_logged() {
		$controller = new StoreApiExtensionController( $this->extend_schema );

		$controller->register_endpoint_data( $this->throwing_extension( new Exception( 'Simulated exception.' ) ) );

		$this->assert_logged( 'Failed to register endpoint data for extension', 'Simulated exception.' );
	}
}
