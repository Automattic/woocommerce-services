<?php
/**
 * StoreNoticesExtension class.
 *
 * Extends the WooCommerce Store API to add address validation to the cart and checkout blocks.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\StoreApi\Extensions;

use Automattic\WCServices\StoreNotices\StoreNoticesNotifier;
use Automattic\WCServices\StoreNotices\StoreNotice;
use Automattic\WCServices\StoreApi\AbstractStoreApiExtension;

defined( 'ABSPATH' ) || exit;

/**
 * Class StoreNoticesExtension
 */
class StoreNoticesExtension extends AbstractStoreApiExtension {

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

		// Get notices from the StoreNoticesNotifier.
		$notices = StoreNoticesNotifier::get_notices();
		StoreNoticesNotifier::clear_notices();

		if ( empty( $notices ) ) {
			return $data;
		}

		// Get the HTML formatter.
		$html_formatter = self::$extend_schema->get_formatter( 'html' );

		// Format the notices.
		foreach ( $notices as $type => $messages ) {
			foreach ( $messages as $notice_data ) {
				// Create a StoreNotice object.
				$notice = new StoreNotice(
					$notice_data['message'],
					$type,
					! empty( $notice_data['data'] ) ? array( 'details' => $notice_data['data'] ) : null
				);

				$notice_message = $notice->get_message();
				$notice_details = $notice->get_data();

				// Format the message with the HTML formatter.
				$notice->set_message( $html_formatter->format( $notice_message ) );

				if ( ! empty( $notice_details ) ) {
					$notice->set_data( $notice_details );
				}

				$data['notices'][] = $notice->to_array();
			}
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
				'description' => __( 'WC Services store notices', 'woocommerce-services' ),
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

	/**
	 * The update callback method.
	 *
	 * This is where you can listen for updates to the endpoint and handle accordingly.
	 *
	 * @param array $data Data to update.
	 *
	 * @return void
	 */
	public function update_callback( array $data ): void {
		// TODO: Implement update_callback() method.
	}
}
