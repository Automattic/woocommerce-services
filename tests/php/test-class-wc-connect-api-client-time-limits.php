<?php

/**
 * The two Connect server requests an admin waits on when a label screen loads have a short
 * time limit, and a failed heartbeat isn't repeated in the same page request.
 */
class WP_Test_WC_Connect_API_Client_Time_Limits extends WC_Unit_Test_Case {

	const SHORT_LIMIT   = 10;
	const DEFAULT_LIMIT = 60;

	/** @var array The time limit of each request sent. */
	private $sent_limits = array();

	/** @var bool Whether the server is answering. */
	private $server_is_up = true;

	/**
	 * {@inheritDoc}
	 */
	public static function set_up_before_class() {
		parent::set_up_before_class();

		require_once __DIR__ . '/../../classes/class-wc-connect-api-client.php';
		require_once __DIR__ . '/../../classes/class-wc-connect-api-client-live.php';
	}

	/**
	 * Answer every request here instead of sending it.
	 */
	public function set_up() {
		parent::set_up();

		delete_transient( 'connect_server_is_alive_transient' );
		add_filter( 'pre_http_request', array( $this, 'answer_request' ), 10, 2 );
	}

	/**
	 * Remove the fake server.
	 */
	public function tear_down() {
		remove_filter( 'pre_http_request', array( $this, 'answer_request' ), 10 );
		delete_transient( 'connect_server_is_alive_transient' );

		parent::tear_down();
	}

	/**
	 * Record the request and answer as the fake server.
	 *
	 * @param mixed $pre  Unused.
	 * @param array $args Request arguments.
	 * @return array|WP_Error
	 */
	public function answer_request( $pre, $args ) {
		$this->sent_limits[] = $args['timeout'];

		if ( ! $this->server_is_up ) {
			return new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
		}

		return array(
			'headers'  => array( 'content-type' => 'application/json' ),
			'body'     => '{"payment_methods":[],"add_payment_method_url":""}',
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	/**
	 * A live client that needs no connection to build its requests.
	 *
	 * @return WC_Connect_API_Client_Live
	 */
	private function get_client() {
		return new class() extends WC_Connect_API_Client_Live {
			/** No dependencies needed. */
			public function __construct() {}

			/**
			 * @param array $initial_body Unused.
			 * @return array
			 */
			protected function request_body( $initial_body = array() ) {
				return array();
			}

			/** @return array */
			protected function request_headers() {
				return array();
			}
		};
	}

	/**
	 * @testdox Asking for payment methods has the short time limit.
	 */
	public function test_payment_methods_request_has_short_limit() {
		$this->get_client()->get_payment_methods();

		$this->assertSame( array( self::SHORT_LIMIT ), $this->sent_limits );
	}

	/**
	 * @testdox The heartbeat a label screen asks for has the short time limit.
	 */
	public function test_cached_heartbeat_has_short_limit() {
		$this->get_client()->is_alive_cached();

		$this->assertSame( array( self::SHORT_LIMIT ), $this->sent_limits );
	}

	/**
	 * @testdox The short limit ends with its request, when it succeeds and when it fails.
	 */
	public function test_short_limit_applies_to_that_request_only() {
		$client = $this->get_client();

		$client->get_payment_methods();
		$client->is_alive();

		$this->server_is_up = false;
		$client->get_payment_methods();
		$client->is_alive();

		$this->assertSame(
			array( self::SHORT_LIMIT, self::DEFAULT_LIMIT, self::SHORT_LIMIT, self::DEFAULT_LIMIT ),
			$this->sent_limits
		);
	}

	/**
	 * @testdox The short limit also ends if something throws during its request.
	 */
	public function test_short_limit_ends_when_a_callback_throws() {
		$client = $this->get_client();
		$throw  = static function () {
			throw new RuntimeException( 'A callback failed.' );
		};
		add_filter( 'pre_http_request', $throw, 5 );

		try {
			$client->get_payment_methods();
			$this->fail( 'The exception should reach the caller.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'A callback failed.', $e->getMessage() );
		} finally {
			remove_filter( 'pre_http_request', $throw, 5 );
		}

		$client->is_alive();

		$this->assertSame( array( self::DEFAULT_LIMIT ), $this->sent_limits );
	}

	/**
	 * @testdox A store's callback on wc_connect_request_args can still set the limit.
	 */
	public function test_request_args_filter_still_sets_the_limit() {
		$set_limit = static function ( $args ) {
			$args['timeout'] = 45;

			return $args;
		};
		add_filter( 'wc_connect_request_args', $set_limit );

		$client = $this->get_client();
		$client->get_payment_methods();
		$client->is_alive_cached();

		remove_filter( 'wc_connect_request_args', $set_limit );

		$this->assertSame( array( 45, 45 ), $this->sent_limits );
	}

	/**
	 * @testdox The short limit leaves alone request args that another callback made invalid.
	 */
	public function test_short_limit_ignores_args_that_are_not_an_array() {
		// Registered before the request, so the short limit's callback runs after this one.
		$break_args = static function () {
			return 'not an array';
		};
		add_filter( 'wc_connect_request_args', $break_args, PHP_INT_MIN );

		// Hand the request a usable value again, so it can be sent and the limit read.
		$restore_args = static function ( $args ) {
			return is_array( $args ) ? $args : array( 'timeout' => 1 );
		};
		add_filter( 'wc_connect_request_args', $restore_args, PHP_INT_MAX );

		$this->get_client()->get_payment_methods();

		remove_filter( 'wc_connect_request_args', $break_args, PHP_INT_MIN );
		remove_filter( 'wc_connect_request_args', $restore_args, PHP_INT_MAX );

		$this->assertSame( array( 1 ), $this->sent_limits );
	}

	/**
	 * @testdox When the server is down, the heartbeat is asked once per page request.
	 */
	public function test_failed_heartbeat_is_asked_once_per_request() {
		$this->server_is_up = false;
		$client             = $this->get_client();

		$this->assertFalse( $client->is_alive_cached() );
		$this->assertFalse( $client->is_alive_cached() );

		$this->assertCount( 1, $this->sent_limits );
	}

	/**
	 * @testdox The next page request asks again, so a server that came back is noticed at once.
	 */
	public function test_next_request_asks_the_heartbeat_again() {
		$this->server_is_up = false;
		$this->assertFalse( $this->get_client()->is_alive_cached() );

		$this->server_is_up = true;
		$this->assertTrue( $this->get_client()->is_alive_cached() );

		$this->assertCount( 2, $this->sent_limits );
	}

	/**
	 * @testdox A successful heartbeat is still remembered for the next page request.
	 */
	public function test_successful_heartbeat_is_remembered_across_requests() {
		$this->assertTrue( $this->get_client()->is_alive_cached() );
		$this->assertTrue( $this->get_client()->is_alive_cached() );

		$this->assertCount( 1, $this->sent_limits );
	}

	/**
	 * @testdox The uncached heartbeat always asks.
	 */
	public function test_uncached_heartbeat_always_asks() {
		$this->server_is_up = false;
		$client             = $this->get_client();

		$client->is_alive();
		$client->is_alive();

		$this->assertCount( 2, $this->sent_limits );
	}

	/**
	 * @testdox A subclass's is_alive() still decides the cached heartbeat.
	 */
	public function test_cached_heartbeat_calls_an_overridden_is_alive() {
		$client = new class() extends WC_Connect_API_Client_Live {
			/** @var int */
			public $calls = 0;

			/** No dependencies needed. */
			public function __construct() {}

			/** @return WP_Error */
			public function is_alive() {
				++$this->calls;

				return new WP_Error( 'down', 'Down' );
			}
		};

		$this->assertFalse( $client->is_alive_cached() );
		$this->assertSame( 1, $client->calls );
		$this->assertSame( array(), $this->sent_limits );
	}
}
