<?php

if ( ! class_exists( 'WC_Connect_Services_Store' ) ) {

    class WC_Connect_Services_Store
    {
        /**
         * @var array An in memory copy of all services and their properties
         */
        protected $services = array();

        /**
         * @var object A reference to a logger
         */
        protected $log = null;

        /**
         * @var Singleton The reference the *Singleton* instance of this class
         */
        private static $instance;

        /**
         * Returns the *Singleton* instance of this class.
         *
         * @return Singleton The *Singleton* instance.
         */
        public static function getInstance() {
            if ( null === self::$instance ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Private clone method to prevent cloning of the instance of the
         * *Singleton* instance.
         *
         * @return void
         */
        private function __clone() {
        }

        /**
         * Private unserialize method to prevent unserializing of the *Singleton*
         * instance.
         *
         * @return void
         */
        private function __wakeup() {
        }

        /**
         * Protected constructor to prevent creating a new instance of the
         * *Singleton* via the `new` operator from outside of this class.
         */
        protected function __construct() {
            $this->services = get_option( 'wc_connect_services', null );

            // If null, load the services from dummy data
            if ( ! $this->services ) {
                $this->load_services_from_dummy_data();
            }

            // Hook fetching the available services from the connect server
            if ( defined( 'WOOCOMMERCE_CONNECT_FREQUENT_FETCH' ) ) {
                add_action( 'admin_init', array( $this, 'fetch_services_from_connect_server' ) );
            } else if ( ! wp_next_scheduled( 'wc_connect_fetch_services' ) ) {
                wp_schedule_event( time(), 'daily', 'wc_connect_fetch_services' );
            }

            add_action( 'wc_connect_fetch_services', array( $this, 'fetch_services_from_connect_server' ) );

        }

        public function fetch_services_from_connect_server() {

            require_once( plugin_basename( 'classes/class-wc-connect-api-client.php' ) );

            $response = WC_Connect_API_Client::get_services();
            if ( is_wp_error( $response ) ) {
                $this->log( $response->get_error_code() . ' ' . $response->get_error_message() . ' (fetch_services)' );
                return;
            }

            if ( ! array_key_exists( 'body', $response ) ) {
                $this->log( 'Server response did not include body.  Services not updated. (fetch_services)' );
                $this->log( 'Server response = ' . print_r( $response, true ) );
                return;
            }

            $body = json_decode( $response['body'] );

            if ( ! is_object( $body ) ) {
                $this->log( 'Server response body is not an object.  Services not updated. (fetch_services)' );
                $this->log( 'Server response body = ' . print_r( $body, true ) );
                return;
            }

            if ( property_exists( $body, 'error' ) ) {
                $this->log(
                    sprintf( 'Server responded with an error : %s : %s. (fetch_services)',
                        $body->error, $body->message
                    )
                );
                return;
            }

            $this->update_services( $body );
        }

        protected function update_services( $services ) {

            // Validate
            // Make sure each of services properties is an array
            // e.g. $kind = "shipping" and $kind_services is an array of service objects (e.g. usps, canada post, etc)
            foreach ( $services as $kind => $kind_services ) {
                if ( ! is_array( $kind_services ) ) {
                    $this->log(
                        sprintf(
                            "services['%s'] does not reference an array. Services not updated. (update_services)",
                            $kind
                        )
                    );
                    return;
                }

                $this->log(
                    sprintf(
                        "Found %d %s services to process",
                        count( $kind_services ), $kind
                    )
                );

                // Check each service of this kind for required properties
                $required_properties = array( 'id', 'method_description', 'method_title', 'service_settings' );
                $kind_service_offset = 0;
                // e.g. each kind_service should be an object
                foreach ( $kind_services as $kind_service ) {
                    if ( ! is_object( $kind_service ) ) {
                        $this->log(
                            sprintf(
                                "services['%s'][%d] is not an object. Services not updated. (update_services)",
                                $kind, $kind_service_offset
                            )
                        );
                        return;
                    }

                    foreach ( $required_properties as $required_property ) {
                        if ( ! property_exists( $kind_service, $required_property ) ) {
                            $this->log(
                                sprintf(
                                    "services['%s'][%d] is missing %s, which is required. Services not updated. (update_services)",
                                    $kind, $kind_service_offset, $required_property
                                )
                            );
                            $this->log(
                                sprintf(
                                    "services['%s'][%d] = %s",
                                    $kind, $kind_service_offset, print_r( $kind_service, true )
                                )
                            );
                            return;
                        }
                    }

                    $kind_service_offset++;
                }
            }

            // If we made it this far, it is safe to store the object
            update_option( 'wc_connect_services', $services );

            // And set the instance variable to match
            $this->services = $services;
        }

        /**
         * Loads services from dummy data
         * TODO : This method should be removed before beta
         *
         * @return void
         */
        protected function load_services_from_dummy_data() {

            $this->services = array(
                'shipping' => array(
                    (object) array(
                        'id'                 => 'wc_connect_usps',
                        'method_description' => __( 'Shipping via USPS, Powered by WooCommerce Connect', 'woocommerce' ),
                        'method_title'       => __( 'USPS (WooCommerce Connect)', 'woocommerce' ),
                        'service_settings'   => array(
                            'type'        => 'object',
                            'title'       => 'USPS',
                            'description' => 'The USPS service obtains rates dynamically from the USPS API during cart/checkout.',
                            'required'    => array(),
                            'properties'  => array(
                                'enabled' => array(
                                    'type'        => 'boolean',
                                    'title'       => 'Enable/Disable',
                                    'description' => 'Enable this shipping method.',
                                    'default'     => true,
                                ),
                                'title'   => array(
                                    'type'        => 'string',
                                    'title'       => 'Method Title',
                                    'description' => 'This controls the title which the user sees during checkout.',
                                    'default'     => 'USPS',
                                ),
                            ),
                        ),
                    ),
                    (object) array(
                        'id'                 => 'wc_connect_canada_post',
                        'method_description' => __( 'Shipping via Canada Post, Powered by WooCommerce Connect', 'woocommerce' ),
                        'method_title'       => __( 'Canada Post (WooCommerce Connect)', 'woocommerce' ),
                        'service_settings'   => array(
                            'type'        => 'object',
                            'title'       => 'USPS',
                            'description' => 'The Canada Post service obtains rates dynamically from the Canada Post API during cart/checkout.',
                            'required'    => array(),
                            'properties'  => array(
                                'enabled' => array(
                                    'type'        => 'boolean',
                                    'title'       => 'Enable/Disable',
                                    'description' => 'Enable this shipping method.',
                                    'default'     => true,
                                ),
                                'title'   => array(
                                    'type'        => 'string',
                                    'title'       => 'Method Title',
                                    'description' => 'This controls the title which the user sees during checkout.',
                                    'default'     => 'Canada Post',
                                ),
                            ),
                        ),
                    )
                ),
                'payment' => array(
                    (object) array(
                        'id' => 'wc_connect_paypal',
                        'method_description' => __( 'Checkout via PayPal, Powered by WooCommerce Connect', 'woocommerce' ),
                        'method_title' => __( 'PayPal (WooCommerce Connect)', 'woocommerce' ),
                    )
                )
            );
        }

        /**
         * Returns all service ids of a specific type (e.g. shipping)
         *
         * @param string $type The type of services to return
         * @return array An array of that type's service ids, or an empty array if no such type is known
         */
        public function get_all_service_ids_of_type( $type ) {
            if ( empty( $type ) ) {
                return array();
            }

            if ( ! array_key_exists( $type, $this->services ) ) {
                return array();
            }

            $service_ids = array();

            foreach ( $this->services[ $type ] as $service ) {
                $service_ids[] = $service->id;
            }

            return $service_ids;
        }

        /**
         * Returns a particular service's properties given its id
         *
         * @param string service_id The service id for which to return properties
         * @return object|null The service properties or null if no such id was found
         */
        public function get_service_by_id( $service_id ) {

            $service_types = array_keys( $this->services );
            foreach ( $service_types as $service_type ) {
                foreach ( $this->services[ $service_type ] as $service ) {
                    if ( $service->id === $service_id ) {
                        return $service;
                    }
                }
            }

            return null;

        }

        /**
         * Returns a service's properties given its shipping zone instance
         *
         * @param string $instance_id The shipping zone instance id for which to return properties
         * @return object|null The service properties or null if no such instance was found
         */
        public function get_service_by_instance_id( $instance_id ) {
            global $wpdb;
            $service_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT method_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d;",
                    $instance_id
                )
            );
            return $this->get_service_by_id( $service_id );
        }

    }

}
