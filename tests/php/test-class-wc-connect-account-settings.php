<?php

/**
 * Unit test for WC_Connect_Account_Settings and the account settings REST route.
 */
class WP_Test_WC_Connect_Account_Settings extends WC_REST_Unit_Test_Case {

	const OWNER_EMAIL       = 'owner@example.com';
	const OWNER_WPCOM_LOGIN = 'storeownerwpcom';
	const ADD_CARD_URL      = 'https://wordpress.com/me/purchases/add-credit-card';

	/** @var WP_User */
	protected $owner;

	/** @var WC_Connect_Account_Settings */
	protected $account_settings;

	/** @var array */
	protected $card = array(
		'payment_method_id' => 7,
		'card_type'         => 'visa',
		'name'              => 'Store Owner',
		'card_digits'       => '4242',
		'expiry'            => '2030-01-31',
	);

	/**
	 * {@inheritDoc}
	 */
	public static function set_up_before_class() {
		$classes = __DIR__ . '/../../classes/';
		require_once $classes . 'class-wc-connect-logger.php';
		require_once $classes . 'class-wc-connect-api-client.php';
		require_once $classes . 'class-wc-connect-api-client-live.php';
		require_once $classes . 'class-wc-connect-service-schemas-store.php';
		require_once $classes . 'class-wc-connect-service-settings-store.php';
		require_once $classes . 'class-wc-connect-payment-methods-store.php';
		require_once $classes . 'class-wc-connect-account-settings.php';
		require_once $classes . 'class-wc-rest-connect-base-controller.php';
		require_once $classes . 'class-wc-rest-connect-account-settings-controller.php';
	}

	/**
	 * Connect the site with a Jetpack owner whose WordPress.com data is already cached,
	 * so the owner fields are filled without a remote call.
	 */
	public function setUp(): void {
		parent::setUp();

		// The base controller sends a no-cache header on dispatch; the spy server records it instead.
		$GLOBALS['wp_rest_server'] = new Spy_REST_Server();
		$this->server              = $GLOBALS['wp_rest_server'];

		$this->owner = get_user_by( 'id', $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		Jetpack_Options::update_option( 'id', 1234 );
		Jetpack_Options::update_option( 'blog_token', 'blogkey.blogsecret' );
		Jetpack_Options::update_option( 'user_tokens', array( $this->owner->ID => 'userkey.usersecret.' . $this->owner->ID ) );
		Jetpack_Options::update_option( 'master_user', $this->owner->ID );
		set_transient(
			'jetpack_connected_user_data_' . $this->owner->ID,
			array(
				'login' => self::OWNER_WPCOM_LOGIN,
				'email' => self::OWNER_EMAIL,
			)
		);
		WC_Connect_Jetpack::get_connection_manager()->reset_connection_status();

		// Subclasses rather than mocks: other tests can leave an empty generated class under
		// either name, and these define every method get() calls either way.
		$settings_store = new class() extends WC_Connect_Service_Settings_Store {
			/** No dependencies needed. */
			public function __construct() {}

			/** @return array */
			public function get_store_options() {
				return array();
			}

			/** @return array */
			public function get_account_settings() {
				return array( 'selected_payment_method_id' => 7 );
			}

			/** @return bool */
			public function can_user_manage_payment_methods() {
				return false;
			}

			/** @return bool */
			public function is_eligible_for_migration() {
				return false;
			}
		};

		$card                  = $this->card;
		$payment_methods_store = new class( $card, self::ADD_CARD_URL ) extends WC_Connect_Payment_Methods_Store {
			/** @var array */
			private $card;

			/** @var string */
			private $add_card_url;

			/**
			 * @param array  $card         The card to return.
			 * @param string $add_card_url The add-card URL to return.
			 */
			public function __construct( $card, $add_card_url ) {
				$this->card         = $card;
				$this->add_card_url = $add_card_url;
			}

			/** @return bool */
			public function fetch_payment_methods_from_connect_server() {
				return true;
			}

			/** @return array */
			public function get_payment_methods() {
				return array( $this->card );
			}

			/** @return string */
			public function get_add_payment_method_url() {
				return $this->add_card_url;
			}
		};

		$this->account_settings = new WC_Connect_Account_Settings( $settings_store, $payment_methods_store );

		$controller = new WC_REST_Connect_Account_Settings_Controller(
			$this->createMock( WC_Connect_API_Client_Live::class ),
			$settings_store,
			$this->createMock( WC_Connect_Logger::class ),
			$payment_methods_store
		);
		$controller->register_routes();
	}

	/**
	 * Disconnect the site again and clear the cached connection status.
	 */
	public function tearDown(): void {
		foreach ( array( 'id', 'blog_token', 'user_tokens', 'master_user' ) as $name ) {
			Jetpack_Options::delete_option( $name );
		}
		delete_transient( 'jetpack_connected_user_data_' . $this->owner->ID );
		WC_Connect_Jetpack::get_connection_manager()->reset_connection_status();

		parent::tearDown();
	}

	/**
	 * Make the current user someone who holds only wcship_manage_labels.
	 */
	private function set_label_only_user() {
		$user = get_user_by( 'id', $this->factory->user->create( array( 'role' => 'subscriber' ) ) );
		$user->add_cap( 'wcship_manage_labels' );
		wp_set_current_user( $user->ID );
	}

	/**
	 * @testdox A store manager still sees the connection owner and the store's cards.
	 */
	public function test_manager_sees_owner_and_cards() {
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'shop_manager' ) ) );

		$meta = $this->account_settings->get()['formMeta'];

		$this->assertNotEmpty( $this->owner->display_name );
		$this->assertSame( $this->owner->display_name, $meta['master_user_name'] );
		$this->assertSame( $this->owner->user_login, $meta['master_user_login'] );
		$this->assertSame( self::OWNER_WPCOM_LOGIN, $meta['master_user_wpcom_login'] );
		$this->assertSame( self::OWNER_EMAIL, $meta['master_user_email'] );
		$this->assertSame( array( $this->card ), $meta['payment_methods'] );
		$this->assertSame( self::ADD_CARD_URL, $meta['add_payment_method_url'] );
	}

	/**
	 * @testdox A label-only user gets the same keys with the owner identity and cards emptied.
	 */
	public function test_label_only_user_gets_owner_and_cards_redacted() {
		wp_set_current_user( $this->owner->ID );
		$full = $this->account_settings->get();
		$this->set_label_only_user();

		$settings = $this->account_settings->get();
		$meta     = $settings['formMeta'];

		$this->assertSame( array_keys( $full['formMeta'] ), array_keys( $meta ) );
		$this->assertSame( '', $meta['master_user_login'] );
		$this->assertSame( '', $meta['master_user_wpcom_login'] );
		$this->assertSame( '', $meta['master_user_email'] );
		$this->assertSame( array(), $meta['payment_methods'] );
		$this->assertSame( '', $meta['add_payment_method_url'] );
		$this->assertSame( '', $meta['master_user_name'] );
		$this->assertSame( 7, $settings['formData']['selected_payment_method_id'], 'Label purchase needs the selected card id.' );
	}

	/**
	 * @testdox A label-only user still reads account settings over REST, without the owner's name or email.
	 */
	public function test_label_only_user_reads_redacted_settings_over_rest() {
		$this->set_label_only_user();

		$response = $this->server->dispatch( new WP_REST_Request( 'GET', '/wc/v1/connect/account/settings' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( '', $response->get_data()['formMeta']['master_user_email'] );
		$this->assertSame( '', $response->get_data()['formMeta']['master_user_name'] );
		$this->assertStringNotContainsString( self::OWNER_EMAIL, wp_json_encode( $response->get_data() ) );
	}

	/**
	 * @testdox A label-only user's HEAD request on account settings is allowed, like GET.
	 */
	public function test_label_only_user_head_request_is_allowed() {
		$this->set_label_only_user();

		$response = $this->server->dispatch( new WP_REST_Request( 'HEAD', '/wc/v1/connect/account/settings' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertStringNotContainsString( self::OWNER_EMAIL, wp_json_encode( $response->get_data() ) );
	}
}
