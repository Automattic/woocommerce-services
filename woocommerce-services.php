<?php
/**
 * Plugin Name: WooCommerce Services
 * Plugin URI: http://woocommerce.com/
 * Description: WooCommerce Services: Hosted services for WooCommerce, including free real-time USPS and Canada Post rates and discounted USPS shipping labels.
 * Author: Automattic
 * Author URI: http://woocommerce.com/
 * Version: 1.2.0
 *
 * Copyright (c) 2017 Automattic
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( plugin_basename( 'classes/class-wc-connect-options.php' ) );

if ( ! class_exists( 'WC_Connect_Loader' ) ) {

	define( 'WOOCOMMERCE_CONNECT_MINIMUM_WOOCOMMERCE_VERSION', '2.6' );
	define( 'WOOCOMMERCE_CONNECT_MINIMUM_JETPACK_VERSION', '3.9' );
	define( 'WOOCOMMERCE_CONNECT_SCHEMA_AGE_WARNING_THRESHOLD', DAY_IN_SECONDS );
	define( 'WOOCOMMERCE_CONNECT_SCHEMA_AGE_ERROR_THRESHOLD', 3 * DAY_IN_SECONDS );
	define( 'WOOCOMMERCE_CONNECT_MAX_JSON_DECODE_DEPTH', 32 );

	class WC_Connect_Loader {

		/**
		 * @var WC_Connect_Logger
		 */
		protected $logger;

		/**
		 * @var WC_Connect_API_Client
		 */
		protected $api_client;

		/**
		 * @var WC_Connect_Service_Schemas_Store
		 */
		protected $service_schemas_store;

		/**
		 * @var WC_Connect_Service_Settings_Store
		 */
		protected $service_settings_store;

		/**
		 * @var WC_Connect_Payment_Methods_Store
		 */
		protected $payment_methods_store;

		/**
		 * @var WC_REST_Connect_Account_Settings_Controller
		 */
		protected $rest_account_settings_controller;

		/**
		 * @var WC_REST_Connect_Packages_Controller
		 */
		protected $rest_packages_controller;

		/**
		 * @var WC_REST_Connect_Services_Controller
		 */
		protected $rest_services_controller;

		/**
		 * @var WC_REST_Connect_Self_Help_Controller
		 */
		protected $rest_self_help_controller;

		/**
		 * @var WC_REST_Connect_Shipping_Label_Controller
		 */
		protected $rest_shipping_label_controller;

		/**
		 * @var WC_REST_Connect_Shipping_Label_Status_Controller
		 */
		protected $rest_shipping_label_status_controller;

		/**
		 * @var WC_REST_Connect_Shipping_Label_Refund_Controller
		 */
		protected $rest_shipping_label_refund_controller;

		/**
		 * @var WC_REST_Connect_Shipping_Labels_Preview_Controller
		 */
		protected $rest_shipping_labels_preview_controller;

		/**
		 * @var WC_REST_Connect_Shipping_Labels_Print_Controller
		 */
		protected $rest_shipping_labels_print_controller;

		/**
		 * @var WC_REST_Connect_Shipping_Rates_Controller
		 */
		protected $rest_shipping_rates_controller;

		/**
		 * @var WC_REST_Connect_Address_Normalization_Controller
		 */
		protected $rest_address_normalization_controller;

		/**
		 * @var WC_Connect_Service_Schemas_Validator
		 */
		protected $service_schemas_validator;

		/**
		 * @var WC_Connect_Settings_Page
		 */
		protected $settings_page;

		/**
		 * @var WC_Connect_Help_View
		 */
		protected $help_view;

		/**
		 * @var WC_Connect_Shipping_Label
		 */
		protected $shipping_label;

		/**
		 * @var WC_Connect_Nux
		 */
		protected $nux;

		protected $services = array();

		protected $service_object_cache = array();

		protected $wc_connect_base_url;

		static function load_tracks_for_activation_hooks() {
			require_once( plugin_basename( 'classes/class-wc-connect-logger.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-tracks.php' ) );
			$logger = null;
			if ( class_exists( 'WC_Logger' ) ) {
				$logger = new WC_Connect_Logger( new WC_Logger() );
			}
			return new WC_Connect_Tracks( $logger );
		}

		static function plugin_deactivation() {
			$tracks = self::load_tracks_for_activation_hooks();
			$tracks->opted_out();
			wp_clear_scheduled_hook( 'wc_connect_fetch_service_schemas' );
		}

		static function plugin_uninstall() {
			WC_Connect_Options::delete_all_options();
		}

		public function __construct() {
			$this->wc_connect_base_url = trailingslashit( defined( 'WOOCOMMERCE_CONNECT_DEV_SERVER_URL' ) ? WOOCOMMERCE_CONNECT_DEV_SERVER_URL : plugins_url( 'dist/', __FILE__ ) );
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_action( 'woocommerce_init', array( $this, 'init' ) );
		}

		public function get_logger() {
			return $this->logger;
		}

		public function set_logger( WC_Connect_Logger $logger ) {
			$this->logger = $logger;
		}

		public function get_api_client() {
			return $this->api_client;
		}

		public function set_api_client( WC_Connect_API_Client $api_client ) {
			$this->api_client = $api_client;
		}

		public function get_service_schemas_store() {
			return $this->service_schemas_store;
		}

		public function set_service_schemas_store( WC_Connect_Service_Schemas_Store $schemas_store ) {
			$this->service_schemas_store = $schemas_store;
		}

		public function get_service_settings_store() {
			return $this->service_settings_store;
		}

		public function set_service_settings_store( WC_Connect_Service_Settings_Store $settings_store ) {
			$this->service_settings_store = $settings_store;
		}

		public function get_payment_methods_store() {
			return $this->payment_methods_store;
		}

		public function set_payment_methods_store( WC_Connect_Payment_Methods_Store $payment_methods_store ) {
			$this->payment_methods_store = $payment_methods_store;
		}

		public function get_tracks() {
			return $this->tracks;
		}

		public function set_tracks( WC_Connect_Tracks $tracks ) {
			$this->tracks = $tracks;
		}

		public function get_rest_account_settings_controller() {
			return $this->rest_account_settings_controller;
		}

		public function set_rest_packages_controller( WC_REST_Connect_Packages_Controller $rest_packages_controller ) {
			$this->rest_packages_controller = $rest_packages_controller;
		}

		public function set_rest_account_settings_controller( WC_REST_Connect_Account_Settings_Controller $rest_account_settings_controller ) {
			$this->rest_account_settings_controller = $rest_account_settings_controller;
		}

		public function get_rest_services_controller() {
			return $this->rest_services_controller;
		}

		public function set_rest_services_controller( WC_REST_Connect_Services_Controller $rest_services_controller ) {
			$this->rest_services_controller = $rest_services_controller;
		}

		public function get_rest_self_help_controller() {
			return $this->rest_self_help_controller;
		}

		public function set_rest_self_help_controller( WC_REST_Connect_Self_Help_Controller $rest_self_help_controller ) {
			$this->rest_self_help_controller = $rest_self_help_controller;
		}

		public function get_rest_shipping_label_controller() {
			return $this->rest_shipping_label_controller;
		}

		public function set_rest_shipping_label_controller( WC_REST_Connect_Shipping_Label_Controller $rest_shipping_label_controller ) {
			$this->rest_shipping_label_controller = $rest_shipping_label_controller;
		}

		public function get_rest_shipping_label_status_controller() {
			return $this->rest_shipping_label_status_controller;
		}

		public function set_rest_shipping_label_status_controller( WC_REST_Connect_Shipping_Label_Status_Controller $rest_shipping_label_status_controller ) {
			$this->rest_shipping_label_status_controller = $rest_shipping_label_status_controller;
		}

		public function get_rest_shipping_label_refund_controller() {
			return $this->rest_shipping_label_refund_controller;
		}

		public function set_rest_shipping_label_refund_controller( WC_REST_Connect_Shipping_Label_Refund_Controller $rest_shipping_label_refund_controller ) {
			$this->rest_shipping_label_refund_controller = $rest_shipping_label_refund_controller;
		}

		public function get_rest_shipping_labels_preview_controller() {
			return $this->rest_shipping_labels_preview_controller;
		}

		public function set_rest_shipping_labels_preview_controller( WC_REST_Connect_Shipping_Labels_Preview_Controller $rest_shipping_labels_preview_controller ) {
			$this->rest_shipping_labels_preview_controller = $rest_shipping_labels_preview_controller;
		}

		public function get_rest_shipping_labels_print_controller() {
			return $this->rest_shipping_labels_print_controller;
		}

		public function set_rest_shipping_labels_print_controller( WC_REST_Connect_Shipping_Labels_Print_Controller $rest_shipping_labels_print_controller ) {
			$this->rest_shipping_labels_print_controller = $rest_shipping_labels_print_controller;
		}

		public function set_rest_shipping_rates_controller( WC_REST_Connect_Shipping_Rates_Controller $rest_shipping_rates_controller ) {
			$this->rest_shipping_rates_controller = $rest_shipping_rates_controller;
		}

		public function set_rest_address_normalization_controller( WC_REST_Connect_Address_Normalization_Controller $rest_address_normalization_controller ) {
			$this->rest_address_normalization_controller = $rest_address_normalization_controller;
		}

		public function get_service_schemas_validator() {
			return $this->service_schemas_validator;
		}

		public function set_service_schemas_validator( WC_Connect_Service_Schemas_Validator $validator ) {
			$this->service_schemas_validator = $validator;
		}

		public function get_settings_pages() {
			return $this->settings_pages;
		}

		public function set_settings_pages( WC_Connect_Settings_Pages $settings_pages ) {
			$this->settings_pages = $settings_pages;
		}

		public function get_help_view() {
			return $this->help_view;
		}

		public function set_help_view( WC_Connect_Help_View $help_view ) {
			$this->help_view = $help_view;
		}

		public function set_shipping_label( WC_Connect_Shipping_Label $shipping_label ) {
			$this->shipping_label = $shipping_label;
		}

		public function set_nux( WC_Connect_Nux $nux ) {
			$this->nux = $nux;
		}

		/**
		 * Load our textdomain
		 *
		 * @codeCoverageIgnore
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'woocommerce-services', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		}

		/**
		 * Bootstrap our plugin and hook into WP/WC core.
		 *
		 * @codeCoverageIgnore
		 */
		public function init() {
			add_action( 'admin_init', array( $this, 'admin_enqueue_scripts' ) );

			if ( ! WC_Connect_Options::get_option( 'tos_accepted', false ) ) {
				add_action( 'admin_init', array( $this, 'admin_tos_notice' ) );
				return;
			}

			$this->load_dependencies();
			$this->schedule_service_schemas_fetch();
			$this->service_settings_store->migrate_legacy_services();
			$this->attach_hooks();
		}

		/**
		 * Load all plugin dependencies.
		 */
		public function load_dependencies() {
			require_once( plugin_basename( 'classes/class-wc-connect-error-notice.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-logger.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-api-client.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-service-schemas-validator.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-shipping-method.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-service-schemas-store.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-service-settings-store.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-payment-methods-store.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-tracks.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-help-view.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-shipping-label.php' ) );
			require_once( plugin_basename( 'classes/class-wc-connect-nux.php' ) );

			$logger                = new WC_Connect_Logger( new WC_Logger() );
			$validator             = new WC_Connect_Service_Schemas_Validator();
			$api_client            = new WC_Connect_API_Client( $validator, $this );
			$schemas_store         = new WC_Connect_Service_Schemas_Store( $api_client, $logger );
			$settings_store        = new WC_Connect_Service_Settings_Store( $schemas_store, $api_client, $logger );
			$payment_methods_store = new WC_Connect_Payment_Methods_Store( $settings_store, $api_client, $logger );
			$tracks                = new WC_Connect_Tracks( $logger );
			$help_view             = new WC_Connect_Help_View( $schemas_store, $settings_store, $logger );
			$nux                   = new WC_Connect_Nux();
			$shipping_label        = new WC_Connect_Shipping_Label( $api_client, $settings_store, $schemas_store, $payment_methods_store );

			$this->set_logger( $logger );
			$this->set_api_client( $api_client );
			$this->set_service_schemas_validator( $validator );
			$this->set_service_schemas_store( $schemas_store );
			$this->set_service_settings_store( $settings_store );
			$this->set_payment_methods_store( $payment_methods_store );
			$this->set_tracks( $tracks );
			$this->set_help_view( $help_view );
			$this->set_shipping_label( $shipping_label );
			$this->set_nux( $nux );

			add_action( 'admin_init', array( $this, 'load_admin_dependencies' ) );
		}

		/**
		 * Load admin-only plugin dependencies.
		 */
		public function load_admin_dependencies() {
			require_once( plugin_basename( 'classes/class-wc-connect-debug-tools.php' ) );
			new WC_Connect_Debug_Tools( $this->api_client );

			require_once( plugin_basename( 'classes/class-wc-connect-settings-pages.php' ) );
			$settings_pages = new WC_Connect_Settings_Pages( $this->payment_methods_store, $this->service_settings_store, $this->service_schemas_store, $this->logger );
			$this->set_settings_pages( $settings_pages );

			add_action( 'admin_notices', array( WC_Connect_Error_Notice::instance(), 'render_notice' ) );
		}

		/**
		 * Hook plugin classes into WP/WC core.
		 */
		public function attach_hooks() {
			$schemas_store = $this->get_service_schemas_store();
			$schemas = $schemas_store->get_service_schemas();

			if ( $schemas ) {
				add_filter( 'woocommerce_shipping_methods', array( $this, 'woocommerce_shipping_methods' ) );
				add_action( 'woocommerce_load_shipping_methods', array( $this, 'woocommerce_load_shipping_methods' ) );
				add_filter( 'woocommerce_payment_gateways', array( $this, 'woocommerce_payment_gateways' ) );
				add_action( 'wc_connect_service_init', array( $this, 'init_service' ), 10, 2 );
				add_action( 'wc_connect_service_admin_options', array( $this, 'localize_and_enqueue_service_script' ), 10, 2 );
				add_action( 'woocommerce_shipping_zone_method_added', array( $this, 'shipping_zone_method_added' ), 10, 3 );
				add_action( 'woocommerce_shipping_zone_method_deleted', array( $this, 'shipping_zone_method_deleted' ), 10, 3 );
				add_action( 'woocommerce_shipping_zone_method_status_toggled', array( $this, 'shipping_zone_method_status_toggled' ), 10, 4 );
			}

			add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
			add_action( 'woocommerce_settings_saved', array( $schemas_store, 'fetch_service_schemas_from_connect_server' ) );
			add_action( 'wc_connect_fetch_service_schemas', array( $schemas_store, 'fetch_service_schemas_from_connect_server' ) );
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_wc_connect_package_meta_data' ) );
			add_filter( 'is_protected_meta', array( $this, 'hide_wc_connect_order_meta_data' ), 10, 3 );
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 5 );
			add_filter( 'woocommerce_shipping_fields' , array( $this, 'add_shipping_phone_to_checkout' ) );
			add_action( 'woocommerce_admin_shipping_fields', array( $this, 'add_shipping_phone_to_order_fields' ) );
			add_filter( 'woocommerce_get_order_address', array( $this, 'get_shipping_phone_from_order' ), 10, 3 );
			add_action( 'admin_enqueue_scripts', array( $this->nux, 'show_pointers' ) );
		}

		/**
		 * Hook the REST API
		 * Note that we cannot load our controller until this time, because prior to
		 * rest_api_init firing, WP_REST_Controller is not yet defined
		 */
		public function rest_api_init() {
			$schemas_store = $this->get_service_schemas_store();
			$settings_store = $this->get_service_settings_store();
			$logger = $this->get_logger();

			//////////////////////////////////////////////////////////////////////////////
			// TODO - Remove this when woocommerce/pull/10435 lands
			if ( ! class_exists( 'WP_REST_Controller' ) ) {
				include_once( plugin_basename( 'vendor/class-wp-rest-controller.php' ) );
			}
			//////////////////////////////////////////////////////////////////////////////

			if ( ! class_exists( 'WP_REST_Controller' ) ) {
				$this->logger->debug( 'Error. WP_REST_Controller could not be found', __FUNCTION__ );
				return;
			}

			require_once( plugin_basename( 'classes/class-wc-rest-connect-packages-controller.php' ) );
			$rest_packages_controller = new WC_REST_Connect_Packages_Controller( $settings_store, $logger );
			$this->set_rest_packages_controller( $rest_packages_controller );
			$rest_packages_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-account-settings-controller.php' ) );
			$rest_account_settings_controller = new WC_REST_Connect_Account_Settings_Controller( $this->api_client, $settings_store, $logger );
			$this->set_rest_account_settings_controller( $rest_account_settings_controller );
			$rest_account_settings_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-services-controller.php' ) );
			$rest_services_controller = new WC_REST_Connect_Services_Controller( $schemas_store, $settings_store, $this->nux, $logger );
			$this->set_rest_services_controller( $rest_services_controller );
			$rest_services_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-self-help-controller.php' ) );
			$rest_self_help_controller = new WC_REST_Connect_Self_Help_Controller( $logger );
			$this->set_rest_self_help_controller( $rest_self_help_controller );
			$rest_self_help_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-shipping-label-controller.php' ) );
			$rest_shipping_label_controller = new WC_REST_Connect_Shipping_Label_Controller( $this->api_client, $settings_store, $logger );
			$this->set_rest_shipping_label_controller( $rest_shipping_label_controller );
			$rest_shipping_label_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-shipping-label-status-controller.php' ) );
			$rest_shipping_label_status_controller = new WC_REST_Connect_Shipping_Label_Status_Controller( $this->api_client, $settings_store, $logger );
			$this->set_rest_shipping_label_status_controller( $rest_shipping_label_status_controller );
			$rest_shipping_label_status_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-shipping-label-refund-controller.php' ) );
			$rest_shipping_label_refund_controller = new WC_REST_Connect_Shipping_Label_Refund_Controller( $this->api_client, $settings_store, $logger );
			$this->set_rest_shipping_label_refund_controller( $rest_shipping_label_refund_controller );
			$rest_shipping_label_refund_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-shipping-labels-preview-controller.php' ) );
			$rest_shipping_labels_preview_controller = new WC_REST_Connect_Shipping_Labels_Preview_Controller( $this->api_client, $settings_store, $logger );
			$this->set_rest_shipping_labels_preview_controller( $rest_shipping_labels_preview_controller );
			$rest_shipping_labels_preview_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-shipping-labels-print-controller.php' ) );
			$rest_shipping_labels_print_controller = new WC_REST_Connect_Shipping_Labels_Print_Controller( $this->api_client, $settings_store, $logger );
			$this->set_rest_shipping_labels_print_controller( $rest_shipping_labels_print_controller );
			$rest_shipping_labels_print_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-shipping-rates-controller.php' ) );
			$rest_shipping_rates_controller = new WC_REST_Connect_Shipping_Rates_Controller( $this->api_client, $settings_store, $logger );
			$this->set_rest_shipping_rates_controller( $rest_shipping_rates_controller );
			$rest_shipping_rates_controller->register_routes();

			require_once( plugin_basename( 'classes/class-wc-rest-connect-address-normalization-controller.php' ) );
			$rest_address_normalization_controller = new WC_REST_Connect_Address_Normalization_Controller( $this->api_client, $settings_store, $logger );
			$this->set_rest_address_normalization_controller( $rest_address_normalization_controller );
			$rest_address_normalization_controller->register_routes();

			add_filter( 'rest_request_before_callbacks', array( $this, 'log_rest_api_errors' ), 10, 3 );
		}

		/**
		 * Log any WP_Errors encountered before our REST API callbacks
		 *
		 * Note: intended to be hooked into 'rest_request_before_callbacks'
		 *
		 * @param WP_HTTP_Response $response Result to send to the client. Usually a WP_REST_Response.
		 * @param WP_REST_Server   $handler  ResponseHandler instance (usually WP_REST_Server).
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 *
		 * @return mixed - pass through value of $response.
		 */
		public function log_rest_api_errors( $response, $handler, $request ) {
			if ( ! is_wp_error( $response ) ) {
				return $response;
			}

			if ( 0 === strpos( $request->get_route(), '/wc/v1/connect/' ) ) {
				$route_info = $request->get_method() . ' ' . $request->get_route();

				$this->get_logger()->error( $response, $route_info );
				$this->get_logger()->error( $route_info, $request->get_body() );
			}

			return $response;
		}

		/**
		 * This function is added to the wc_connect_service_admin_options action by this class
		 * (see attach_hooks) and then that action is fired by WC_Connect_Shipping_Method::admin_options
		 * to get the service instance form layout and settings bundled inside wcConnectData
		 * as the form container is emitted into the body's HTML
		 */
		public function localize_and_enqueue_service_script( $id, $instance = false ) {
			if ( ! function_exists( 'get_rest_url' ) ) {
				return;
			}

			$settings_store = $this->get_service_settings_store();
			$schemas_store = $this->get_service_schemas_store();
			$service_schema = $schemas_store->get_service_schema_by_id_or_instance_id( $instance ? $instance : $id );

			if ( ! $service_schema ) {
				return;
			}

			$path = $instance ? "/wc/v1/connect/services/{$id}/{$instance}" : "/wc/v1/connect/services/{$id}";

			$admin_array = array(
				'storeOptions'       => $settings_store->get_store_options(),
				'formSchema'         => $service_schema->service_settings,
				'formLayout'         => $service_schema->form_layout,
				'formData'           => $settings_store->get_service_settings( $id, $instance ),
				'callbackURL'        => get_rest_url( null, $path ),
				'nonce'              => wp_create_nonce( 'wp_rest' ),
				'rootView'           => 'wc-connect-service-settings',
				'noticeDismissed'    => $this->nux->is_notice_dismissed( 'service_settings' ),
				'dismissURL'         => get_rest_url( null, '/wc/v1/connect/services/dismiss_notice' )
			);

			wp_localize_script( 'wc_connect_admin', 'wcConnectData', $admin_array );
			wp_enqueue_script( 'wc_connect_admin' );
			wp_enqueue_style( 'wc_connect_admin' );
		}

		/**
		 * Hook fetching the available services from the connect server
		 */
		public function schedule_service_schemas_fetch() {

			$schemas_store = $this->get_service_schemas_store();
			$schemas = $schemas_store->get_service_schemas();

			if ( ! $schemas ) {
				$schemas_store->fetch_service_schemas_from_connect_server();
			} else if ( defined( 'WOOCOMMERCE_CONNECT_FREQUENT_FETCH' ) && WOOCOMMERCE_CONNECT_FREQUENT_FETCH ) {
				$schemas_store->fetch_service_schemas_from_connect_server();
			} else if ( ! wp_next_scheduled( 'wc_connect_fetch_service_schemas' ) ) {
				wp_schedule_event( time(), 'daily', 'wc_connect_fetch_service_schemas' );
			}

		}

		/**
		 * Inject API Client and Logger into WC Connect shipping method instances.
		 *
		 * @param WC_Connect_Shipping_Method $method
		 * @param int|string                 $id_or_instance_id
		 */
		public function init_service( WC_Connect_Shipping_Method $method, $id_or_instance_id ) {

			// TODO - make more generic - allow things other than WC_Connect_Shipping_Method to work here

			$method->set_api_client( $this->get_api_client() );
			$method->set_logger( $this->get_logger() );
			$method->set_service_settings_store( $this->get_service_settings_store() );

			$service_schema = $this->get_service_schemas_store()->get_service_schema_by_id_or_instance_id( $id_or_instance_id );

			if ( $service_schema ) {
				$method->set_service_schema( $service_schema );
			}

		}

		/**
		 * Returns a reference to a service (e.g. WC_Connect_Shipping_Method) of
		 * a particular id so we can avoid instantiating them multiple times
		 *
		 * @param string $class_name Class name of service to create (e.g. WC_Connect_Shipping_Method)
		 * @param string $service_id Service id of service to create (e.g. usps)
		 * @return mixed
		 */
		protected function get_service_object_by_id( $class_name, $service_id ) {
			if ( ! array_key_exists( $service_id, $this->service_object_cache ) ) {
				$this->service_object_cache[ $service_id ] = new $class_name( $service_id );
			}

			return $this->service_object_cache[ $service_id ];
		}

		/**
		 * Filters in shipping methods for things like WC_Shipping::get_shipping_method_class_names
		 *
		 * @param $shipping_methods
		 * @return mixed
		 */
		public function woocommerce_shipping_methods( $shipping_methods ) {
			$shipping_service_ids = $this->get_service_schemas_store()->get_all_shipping_method_ids();

			foreach ( $shipping_service_ids as $shipping_service_id ) {
				$shipping_methods[ $shipping_service_id ] = $this->get_service_object_by_id( 'WC_Connect_Shipping_Method', $shipping_service_id );
			}

			return $shipping_methods;
		}

		/**
		 * Registers shipping methods for use in things like the Add Shipping Method dialog
		 * on the Shipping Zones view
		 *
		 */
		public function woocommerce_load_shipping_methods() {
			$shipping_service_ids = $this->get_service_schemas_store()->get_all_shipping_method_ids();

			foreach ( $shipping_service_ids as $shipping_service_id ) {
				$shipping_method = $this->get_service_object_by_id( 'WC_Connect_Shipping_Method', $shipping_service_id );
				WC_Shipping::instance()->register_shipping_method( $shipping_method );
			}
		}


		public function woocommerce_payment_gateways( $payment_gateways ) {
			return $payment_gateways;
		}

		/**
		 * Registers the React UI bundle
		 */
		public function admin_enqueue_scripts() {
			// Note: This will break outside of wp-admin, if/when we put user-facing JS/CSS we'll have to figure out another way to version them
			$plugin_data = get_plugin_data( __FILE__, false, false );
			$plugin_version = $plugin_data[ 'Version' ];

			wp_register_style( 'noticons', plugins_url( 'assets/stylesheets/noticons.css', __FILE__ ), array(), $plugin_version );

			wp_register_style( 'wc_connect_admin', $this->wc_connect_base_url . 'woocommerce-services.css', array( 'noticons' ), $plugin_version );
			wp_register_script( 'wc_connect_admin', $this->wc_connect_base_url . 'woocommerce-services.js', array(), $plugin_version );
			wp_register_script( 'wc_services_admin_pointers', $this->wc_connect_base_url . 'woocommerce-services-admin-pointers.js', array( 'wp-pointer', 'jquery' ), $plugin_version );
			wp_register_style( 'wc_connect_banner', $this->wc_connect_base_url . 'woocommerce-services-banner.css', array(), $plugin_version );

			require_once( plugin_basename( 'i18n/strings.php' ) );
			wp_localize_script( 'wc_connect_admin', 'i18nLocaleStrings', $i18nStrings );
		}

		public function get_active_shipping_services() {
			global $wpdb;
			$active_shipping_services = array();
			$shipping_service_ids = $this->get_service_schemas_store()->get_all_shipping_method_ids();

			foreach ( $shipping_service_ids as $shipping_service_id ) {
				$is_active = $wpdb->get_var( $wpdb->prepare(
					"SELECT instance_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE is_enabled = 1 AND method_id = %s LIMIT 1;",
					$shipping_service_id
				) );

				if ( $is_active ) {
					$active_shipping_services[] = $shipping_service_id;
				}
			}

			return $active_shipping_services;
		}

		/**
		 * Checks for TOS accept/decline and renders the notice as needed
		 */
		public function admin_tos_notice() {
			// Do nothing if the TOS has been accepted or declined
			if ( $this->dismiss_tos_notice() ) {
				return;
			}

			if ( $this->can_accept_tos() ) {
				wp_enqueue_style( 'wc_connect_banner' );
				add_action( 'admin_notices', array( $this, 'show_tos_notice' ) );
			}
		}

		private function dismiss_tos_notice() {
			if ( ! isset( $_GET['wc-connect-notice'] ) ) {
				return false;
			}

			if ( 'accept' === $_GET['wc-connect-notice'] ) {
				$tracks = self::load_tracks_for_activation_hooks();
				$tracks->opted_in();
				WC_Connect_Options::update_option( 'tos_accepted', true );
				wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) );

				exit;
			}

			deactivate_plugins( plugin_basename( __FILE__ ) );
			add_action( 'admin_notices', array( $this, 'deactivate_notice' ) );
			return true;
		}

		/**
		 * Check that the current user is the owner of the Jetpack connection
		 * Only that person can accept the TOS
		 * @return bool
		 */
		private function can_accept_tos() {
			if ( defined( 'JETPACK_DEV_DEBUG' ) ) {
				return true;
			}

			if ( ! class_exists( 'Jetpack_Data' ) ) {
				return false;
			}

			$user_token = Jetpack_Data::get_access_token( JETPACK_MASTER_USER );
			if ( $user_token && is_object( $user_token ) && isset( $user_token->external_user_id ) ) {
				return get_current_user_id() === $user_token->external_user_id;
			}

			return false;
		}

		public function show_tos_notice() {
			$accept_url = admin_url( 'admin.php?page=wc-settings&tab=shipping&wc-connect-notice=accept' );
			$decline_url = admin_url( 'plugins.php?wc-connect-notice=decline' );

			?>
			<div class="notice wcc-admin-notice">
				<h1><?php _e( 'Welcome to WooCommerce Services' ) ?></h1>
				<a href="<?php echo esc_url( $decline_url ); ?>" style="text-decoration: none;" class="notice-dismiss" title="<?php esc_attr_e( 'Dismiss and deactivate the plugin', 'woocommerce-services' ); ?>"></a>
				<p>
					<b>Connect to get live shipping rates and print discounted labels. You will also get access to new features as we add them.</b>
				</p>
				<p>
					<?php
					printf(
						__( 'By clicking "Connect" below, you agree to our <a target="_blank" href="%s">Terms of Service</a>, and understand that Connect passes some data to external servers in order to enable its features. You can find more information about how WooCommerce Services handles your store\'s data <a target="_blank" href="%s">here</a>.', 'woocommerce-services' ),
						esc_url( 'https://woocommerce.com/terms-conditions/' ),
						esc_url( 'https://woocommerce.com/terms-conditions/connect-privacy' )
					);
					?>
				</p>
				<p>
					<a href="<?php echo( esc_url( $accept_url ) ) ?>" class="button-primary"><?php _e( 'Connect' ) ?></a>
				</p>
			</div>
			<?php
		}

		public function deactivate_notice() {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e( 'WooCommerce Services plugin has been disabled.', 'woocommerce-services' ); ?></p>
			</div>
			<?php
		}

		public function get_active_services() {
			return $this->get_active_shipping_services();
		}

		public function is_wc_connect_shipping_service( $service_id ) {
			$shipping_service_ids = $this->get_service_schemas_store()->get_all_shipping_method_ids();
			return in_array( $service_id, $shipping_service_ids );
		}

		public function shipping_zone_method_added( $instance_id, $service_id, $zone_id ) {
			if ( $this->is_wc_connect_shipping_service( $service_id ) ) {
				do_action( 'wc_connect_shipping_zone_method_added', $instance_id, $service_id, $zone_id );
			}
		}

		public function shipping_zone_method_deleted( $instance_id, $service_id, $zone_id ) {
			if ( $this->is_wc_connect_shipping_service( $service_id ) ) {
				WC_Connect_Options::delete_shipping_method_options(  $service_id, $instance_id );
				do_action( 'wc_connect_shipping_zone_method_deleted', $instance_id, $service_id, $zone_id );
			}
		}

		public function shipping_zone_method_status_toggled( $instance_id, $service_id, $zone_id, $enabled ) {
			if ( $this->is_wc_connect_shipping_service( $service_id ) ) {
				do_action( 'wc_connect_shipping_zone_method_status_toggled', $instance_id, $service_id, $zone_id, $enabled );
			}
		}

		public function add_meta_boxes() {
			if ( $this->shipping_label->should_show_meta_box() ) {
				add_meta_box( 'woocommerce-order-label', __( 'Shipping Label', 'woocommerce-services' ), array( $this->shipping_label, 'meta_box' ), null, 'side', 'default' );
			}
		}

		public function hide_wc_connect_package_meta_data( $hidden_keys ) {
			$hidden_keys[] = 'wc_connect_packages';
			return $hidden_keys;
		}

		function hide_wc_connect_order_meta_data( $protected, $meta_key, $meta_type ) {
			if ( 'wc_connect_labels' === $meta_key ) {
				$protected = true;
			}

			return $protected;
		}

		function add_shipping_phone_to_checkout( $fields ) {
			$fields[ 'shipping_phone' ] = array(
				'label'        => __( 'Phone', 'woocommerce' ),
				'type'         => 'tel',
				'required'     => false,
				'class'        => array( 'form-row-wide' ),
				'clear'        => true,
				'validate'     => array( 'phone' ),
				'autocomplete' => 'tel',
			);
			return $fields;
		}

		function add_shipping_phone_to_order_fields( $fields ) {
			$fields[ 'phone' ] = array(
				'label' => __( 'Phone', 'woocommerce' ),
			);
			return $fields;
		}

		function get_shipping_phone_from_order( $fields, $address_type, $order ) {
			if ( 'shipping' === $address_type ) {
				$shipping_phone = get_post_meta( $order->id, '_shipping_phone', true );
				if ( ! $shipping_phone ) {
					$billing_address = $order->get_address( 'billing' );
					$shipping_phone = $billing_address[ 'phone' ];
				}
				$fields[ 'phone' ] =  $shipping_phone;
			}
			return $fields;
		}
	}

	if ( ! defined( 'WC_UNIT_TESTING' ) ) {
		new WC_Connect_Loader();
	}
}

register_deactivation_hook( __FILE__, array( 'WC_Connect_Loader', 'plugin_deactivation' ) );
register_uninstall_hook( __FILE__, array( 'WC_Connect_Loader', 'plugin_uninstall' ) );
