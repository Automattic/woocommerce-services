<?php
/**
 * BlockCheckoutNoticesExtension class.
 *
 * Extends the WooCommerce Store API to add address validation to the checkout block.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\StoreApi\Extensions;

use Automattic\WCServices\StoreApi\AbstractStoreApiExtension;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use WC_Customer;

defined( 'ABSPATH' ) || exit;

/**
 * Class BlockCheckoutNoticesExtension
 */
class BlockCheckoutNoticesExtension extends AbstractStoreApiExtension {

	/**
	 * Get the endpoint to extend.
	 *
	 * Should return one of the keys from the $endpoints array.
	 *
	 * @return string
	 */
	public function get_endpoint(): string {
		return self::$endpoints['cart'];
	}

	/**
	 * The data callback method.
	 *
	 * This is where you can define the data this endpoint should return.
	 *
	 * @return array
	 */
	public function data_callback(): array {
		$data = array(
			'notices' => array(),
		);

		// Get the HTML formatter.
		$html_formatter = self::$extend_schema->get_formatter( 'html' );

		// Format the notices.
		foreach ( $response['notices'] as $notice ) {
			$notice_message = $notice->get_message();
			$notice_data    = $notice->get_data();

			$notice->set_message( $html_formatter->format( $notice_message ) );

			if ( ! empty( $notice_data ) ) {
				$notice->set_data( $html_formatter->format( $notice_data ) );
			}

			$data['notices'][] = $notice->to_array();
		}

		return $data;
	}

	/**
	 * The schema callback method.
	 *
	 * This is where you can define the schema for the endpoint.
	 *
	 * @return array
	 */
	public function schema_callback(): array {
		return array(
			'notices' => array(
				'description' => __( 'WC Services checkout notices', 'woocommerce-services' ),
				'type'        => array( 'array' ),
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Get the schema type to extend the endpoint with.
	 *
	 * Should return one of the keys from the $schema_types array.
	 *
	 * @return string
	 */
	public function get_schema_type(): string {
		return self::$schema_types['array_a'];
	}
}
