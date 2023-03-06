<?php

use PHPUnit\Framework\MockObject\MockObject;

class WP_Test_WC_Connect_Notice_Manager extends WC_Unit_Test_Case {

	protected $order_id = 123;

	public static function set_up_before_class() {
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-service-schemas-store.php';
		require_once dirname( __FILE__ ) . '/../../classes/class-wc-connect-notice-manager.php';
	}

	/**
	 * @var WC_Connect_Service_Schemas_Store|MockObject
	 */
	private $mock_schemas_store;

	/**
	 * @var WC_Connect_Notice_Manager
	 */
	private $notice_manager;

	public function set_up() {
		parent::set_up();

		$this->mock_schemas_store = $this->getMockBuilder( WC_Connect_Service_Schemas_Store::class )
			->disableOriginalConstructor()
			->getMock();
		$this->notice_manager = new WC_Connect_Notice_Manager( $this->mock_schemas_store );
	}

	public function tear_down() {
		delete_transient( 'wcc_notice_dismissed_foo' );
		delete_option( 'wcc_notice_dismissed_foo' );
	}

	public function test_it_returns_all_notices_sent_by_server_indexed_by_notice_id() {
		$fake_notice_one          = new stdClass();
		$fake_notice_one->id      = 'foo';
		$fake_notice_one->message = 'This is not really a notice, you know.';

		$fake_notice_two          = new stdClass();
		$fake_notice_two->id      = 'bar';
		$fake_notice_two->message = 'This is not one either.';

		$this->set_up_schemas_store_notices( array( $fake_notice_one, $fake_notice_two ) );

		$expected_result = array(
			'foo' => $fake_notice_one,
			'bar' => $fake_notice_two,
		);

		$this->assertEquals( $expected_result, $this->notice_manager->get_available_notices() );
	}

	public function test_it_returns_notice_was_dismissed_if_dismissed_in_transient() {
		set_transient( 'wcc_notice_dismissed_foo', true, MONTH_IN_SECONDS );

		$this->assertTrue( $this->notice_manager->is_dismissed( 'foo' ) );
	}

	public function test_it_returns_notice_was_dismissed_if_dismissed_in_option() {
		update_option( 'wcc_notice_dismissed_foo', 123 );

		$this->assertTrue( $this->notice_manager->is_dismissed( 'foo' ) );
	}

	public function test_it_returns_notice_was_not_dismissed_if_not_dismissed_in_transient_or_option() {
		$this->assertFalse( $this->notice_manager->is_dismissed( 'foo' ) );
	}

	public function test_it_detects_dismissible_notices() {
		$dismissible_notice = new stdClass();
		$dismissible_notice->id = 'foo';
		$dismissible_notice->dismissible = true;

		$undismissible_notice = new stdClass();
		$undismissible_notice->id = 'bar';
		$undismissible_notice->dismissible = false;

		$this->set_up_schemas_store_notices( array( $dismissible_notice, $undismissible_notice ) );

		$this->assertTrue( $this->notice_manager->is_dismissible( 'foo' ) );
		$this->assertFalse( $this->notice_manager->is_dismissible( 'bar' ) );
	}

	public function test_it_returns_undismissed_notices_indexed_by_notice_id() {
		set_transient( 'wcc_notice_dismissed_foo', 'a notice dismissed in transient' );
		update_option( 'wcc_notice_dismissed_bar', 'a notice dismissed in option' );
		// "baz" is not dismissed anywhere.

		$foo = new stdClass();
		$foo->id = 'foo';

		$bar = new stdClass();
		$bar->id = 'bar';

		$baz = new stdClass();
		$baz->id = 'baz';

		$this->set_up_schemas_store_notices( array( $foo, $bar, $baz ) );

		$this->assertEquals(
			array( 'baz' => $baz ),
			$this->notice_manager->get_undismissed_notices()
		);
	}

	public function test_it_marks_notice_as_dismissed() {
		$foo = new stdClass();
		$foo->id = 'foo';
		$foo->dismissible = true;

		$this->set_up_schemas_store_notices( array( $foo ) );

		$this->notice_manager->dismiss( 'foo' );

		$this->assertIsInt( get_option( 'wcc_notice_dismissed_foo' ) );
	}

	public function test_it_throws_exception_if_notice_id_is_invalid() {
		$foo = new stdClass();
		$foo->id = 'foo';
		$foo->dismissible = true;

		$this->set_up_schemas_store_notices( array( $foo ) );

		$this->setExpectedException( InvalidArgumentException::class );
		$this->notice_manager->dismiss( 'bar' );
	}

	public function test_it_throws_exception_if_notice_id_is_not_dismissible() {
		$foo = new stdClass();
		$foo->id = 'foo';
		$foo->dismissible = false;

		$this->set_up_schemas_store_notices( array( $foo ) );

		$this->setExpectedException( InvalidArgumentException::class );
		$this->notice_manager->dismiss( 'foo' );
	}

	public function test_it_returns_dismissible_notice_ids() {
		$dismissible_notice = new stdClass();
		$dismissible_notice->id = 'foo';
		$dismissible_notice->dismissible = true;

		$undismissible_notice = new stdClass();
		$undismissible_notice->id = 'bar';
		$undismissible_notice->dismissible = false;

		$this->set_up_schemas_store_notices( array( $dismissible_notice, $undismissible_notice ) );

		$this->assertEquals( array( 'foo' ), $this->notice_manager->get_dismissible_notice_ids() );
	}

	private function set_up_schemas_store_notices( array $notices ) {
		$schemas = new stdClass();
		$schemas->notices = $notices;

		$this->mock_schemas_store->method( 'get_service_schemas' )
		                         ->willReturn( $schemas );
	}
}
