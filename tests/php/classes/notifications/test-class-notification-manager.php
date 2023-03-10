<?php

namespace WCShip\Notifications;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use WCShip\Notifications\Notification;
use WCShip\Notifications\Notification_Manager;

class WP_Test_Notification_Manager extends \WC_Unit_Test_Case {

	protected $order_id = 123;

	public static function set_up_before_class() {
		global $wcship_root;
		require_once $wcship_root . '/classes/notifications/class-notification.php';
		require_once $wcship_root . '/classes/notifications/class-notification-manager.php';
	}

	public function set_up() {
		parent::set_up();
	}

	public function tear_down() {
		delete_user_meta( get_current_user_id(), 'wcc_notification_dismissed_foo' );
		delete_user_meta( get_current_user_id(), 'wcc_notification_dismissed_bar' );
		delete_user_meta( get_current_user_id(), 'wcc_notification_dismissed_baz' );
	}

	public function test_it_returns_only_dismissible_notifications() {
		$notification_manager = new Notification_Manager( array(
			new Notification( 'foo', true ),
			new Notification( 'bar', false ),
		) );

		$this->assertEquals( array( 'foo' ), $notification_manager->get_undismissed_notification_ids() );
	}

	public function test_it_returns_only_undismissed_notifications() {
		$notification_manager = new Notification_Manager( array(
			new Notification( 'foo', true ),
			new Notification( 'bar', true ),
			new Notification( 'baz', true ),
		) );

		update_user_meta( get_current_user_id(), 'wcc_notification_dismissed_bar', 123 );

		$this->assertEquals( array( 'foo', 'baz' ), $notification_manager->get_undismissed_notification_ids() );
	}

	public function test_it_returns_true_if_notification_was_dismissed() {
		$notification_manager = new Notification_Manager( array(
			'foo' => new Notification( 'foo', true ),
		) );

		update_user_meta( get_current_user_id(), 'wcc_notification_dismissed_foo', 123 );

		$this->assertTrue( $notification_manager->is_dismissed( 'foo' ) );
	}

	public function test_it_returns_false_if_notification_was_not_dismissed() {
		$notification_manager = new Notification_Manager( array(
			'foo' => new Notification( 'foo', true ),
		) );

		$this->assertFalse( $notification_manager->is_dismissed( 'foo' ) );
	}

	public function test_it_marks_notice_as_dismissed() {
		$notification_manager = new Notification_Manager(
			array(
				new Notification( 'foo', true ),
			)
		);

		$notification_manager->dismiss( 'foo' );

		$this->assertIsInt( get_user_meta( get_current_user_id(), 'wcc_notification_dismissed_foo', true ) );
	}

	public function test_it_throws_exception_if_notification_id_is_invalid() {
		$notification_manager = new Notification_Manager(
			array(
				new Notification( 'foo', true ),
			)
		);

		$this->setExpectedException( InvalidArgumentException::class );
		$notification_manager->dismiss( 'bar' );
	}

	public function test_it_throws_exception_if_notification_is_not_dismissible() {
		$notification_manager = new Notification_Manager(
			array(
				new Notification( 'foo', false ),
			)
		);

		$this->setExpectedException( InvalidArgumentException::class );
		$notification_manager->dismiss( 'foo' );
	}
}
