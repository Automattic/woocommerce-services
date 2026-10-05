<?php
/**
 * Test double for a Store API extension whose registration data throws.
 *
 * @package Automattic/WCServices
 */

// No direct access please.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCServices_Throwing_Store_Api_Extension' ) ) {

	/**
	 * WooCommerce's ExtendSchema is final, so it cannot be stubbed to throw during
	 * registration. The controller reads these getters inside the same try blocks, so an
	 * extension that throws from them exercises the same catches (WOOTAX-304).
	 */
	class WCServices_Throwing_Store_Api_Extension extends \Automattic\WCServices\StoreApi\AbstractStoreApiExtension {

		/**
		 * What every getter throws.
		 *
		 * @var Throwable
		 */
		private $throwable;

		/**
		 * Constructor.
		 *
		 * @param \Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema $extend_schema The ExtendSchema instance.
		 * @param Throwable                                             $throwable     What every getter throws.
		 */
		public function __construct( \Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema $extend_schema, Throwable $throwable ) {
			parent::__construct( $extend_schema );
			$this->throwable = $throwable;
		}

		// phpcs:disable Squiz.Commenting.FunctionComment.InvalidNoReturn -- Test double intentionally always throws.
		/**
		 * Throws.
		 *
		 * @throws Throwable Always.
		 * @return string
		 */
		public function get_namespace(): string {
			throw $this->throwable;
		}

		/**
		 * Throws.
		 *
		 * @throws Throwable Always.
		 * @return string
		 */
		public function get_endpoint(): string {
			throw $this->throwable;
		}

		/**
		 * Throws.
		 *
		 * @throws Throwable Always.
		 * @return string
		 */
		public function get_schema_type(): string {
			throw $this->throwable;
		}
		// phpcs:enable Squiz.Commenting.FunctionComment.InvalidNoReturn

		/**
		 * Never reached: registration fails first.
		 *
		 * @return array
		 */
		public function data_callback(): array {
			return array();
		}

		/**
		 * Never reached: registration fails first.
		 *
		 * @return array
		 */
		public function schema_callback(): array {
			return array();
		}

		/**
		 * Never reached: registration fails first.
		 *
		 * @param array $data Update data.
		 */
		public function update_callback( array $data ): void {}
	}
}
