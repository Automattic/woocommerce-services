<?php

namespace WCShip\Notifications;

use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Front-end notification manager.
 *
 * This is different from the WP admin notices which are handled separately.
 */
class Notification_Manager {
	const USER_META_DISMISSED_PREFIX = 'wcc_notification_dismissed_';

	/**
	 * Notifications whose display is handled by the front-end (as opposed to WP notices which are rendered by PHP).
	 *
	 * We only store their IDs and whether they are dismissible here. Notification contents are handled by
	 * the front-end.
	 */
	private $notifications;

	/**
	 * @param Notification[] $notifications
	 */
	public function __construct( array $notifications ) {
		$this->notifications = $notifications;
		$this->notifications = array_combine(
			array_map( function ( Notification $notification ) { return $notification->get_id(); }, $this->notifications ),
			$this->notifications
		);
	}

	/**
	 * Returns if a notification ID was already dismissed in the past.
	 *
	 * @param string $id Notification ID.
	 *
	 * @return bool Is the notification ID marked as dismissed in the past.
	 */
	public function is_dismissed( $id ) {
		return false !== get_user_meta( get_current_user_id(), self::USER_META_DISMISSED_PREFIX . $id, true );
	}

	/**
	 * @return string[]
	 */
	public function get_undismissed_notification_ids() {
		return array_keys(
			array_filter(
				$this->notifications,
				function ( Notification $notification ) {
					return $notification->is_dismissible() && ! $this->is_dismissed( $notification->get_id() );
				}
			)
		);
	}

	/**
	 * @param string $id Notification ID.
	 *
	 * @return void
	 */
	public function dismiss( $id ) {
		if ( ! in_array( $id, $this->get_undismissed_notification_ids(), true ) ) {
			throw new InvalidArgumentException();
		}

		update_user_meta( get_current_user_id(), self::USER_META_DISMISSED_PREFIX . $id, time() );
	}
}
