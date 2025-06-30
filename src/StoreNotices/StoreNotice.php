<?php
/**
 * StoreNotice class.
 *
 * @package Automattic/WCServices
 */

namespace Automattic\WCServices\StoreNotices;

defined( 'ABSPATH' ) || exit;

/**
 * Class StoreNotice
 */
class StoreNotice {
	/**
	 * The notice message.
	 *
	 * @var string
	 */
	private string $message;

	/**
	 * The notice type.
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * The notice data.
	 *
	 * @var array|null
	 */
	private ?array $data;

	/**
	 * StoreNotice constructor.
	 *
	 * @param string     $message The notice message.
	 * @param string     $type    The notice type.
	 * @param array|null $data    The notice data.
	 */
	public function __construct( string $message, string $type, array $data = null ) {
		$this->message = $message;
		$this->type    = $type;
		$this->data    = $data;
	}

	/**
	 * Get the notice message.
	 *
	 * @return string
	 */
	public function get_message(): string {
		return $this->message;
	}

	/**
	 * Set the notice message.
	 *
	 * @param string $message The notice message.
	 */
	public function set_message( string $message ) {
		$this->message = $message;
	}

	/**
	 * Get the notice type.
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Set the notice type.
	 *
	 * @param string $type The notice type.
	 */
	public function set_type( string $type ) {
		$this->type = $type;
	}

	/**
	 * Get the notice data.
	 *
	 * @return array|null
	 */
	public function get_data(): ?array {
		return $this->data;
	}

	/**
	 * Set the notice data.
	 *
	 * @param array|null $data The notice data.
	 */
	public function set_data( ?array $data ) {
		$this->data = $data;
	}

	/**
	 * To array.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'message' => $this->message,
			'type'    => $this->type,
			'data'    => $this->data,
		);
	}
}
