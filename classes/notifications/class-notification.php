<?php

namespace WCShip\Notifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Notification {
	/** @var string */
	private $id;

	/** @var bool */
	private $is_dismissible;

	/**
	 * @param string $id
	 * @param bool $is_dismissible
	 */
	public function __construct( $id, $is_dismissible ) {
		$this->id = $id;
		$this->is_dismissible = $is_dismissible;
	}

	/**
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @return bool
	 */
	public function is_dismissible() {
		return $this->is_dismissible;
	}
}
