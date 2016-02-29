<?php

if ( ! class_exists( 'WC_Connect_Services_Store' ) ) {

    class WC_Connect_Services_Store
    {
        /**
         * @var array An in memory copy of all services and their properties
         */
        protected $services = null;

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

        }

        public function fetch_services_from_connect_server() {

            require_once( plugin_basename( 'class-wc-connect-api-client.php' ) );

            $response = WC_Connect_API_Client::get_services();
            if ( is_wp_error( $response ) ) {
                WC_Connect_Logger::getInstance()->log( $response->get_error_code() . ' ' . $response->get_error_message() . ' (fetch_services)' );
                return;
            }

            if ( ! array_key_exists( 'body', $response ) ) {
                WC_Connect_Logger::getInstance()->log( 'Server response did not include body.  Service schemas not updated. (fetch_services)' );
                return;
            }

            $body = json_decode( $response['body'] );

            if ( ! is_object( $body ) ) {
                WC_Connect_Logger::getInstance()->log( 'Server response body is not an object.  Service schemas not updated. (fetch_services)' );
                return;
            }

            if ( property_exists( $body, 'error' ) ) {
                WC_Connect_Logger::getInstance()->log(
                    sprintf( 'Server responded with an error : %s : %s. (fetch_services)',
                        $body->error, $body->message
                    )
                );
                return;
            }

            require_once( plugin_basename( 'class-wc-connect-services-validator.php' ) );
            $services_validator = new WC_Connect_Services_Validator();

            if ( ! $services_validator->validate_services( $body ) ) {
                WC_Connect_Logger::getInstance()->log( 'One or more service schemas failed to validate. Will not store services in options.' );
                return;
            }

            WC_Connect_Logger::getInstance()->log( "Successfully loaded service schemas from server response." );

            // If we made it this far, it is safe to store the object
            $this->update_services( $body );

        }

        protected function update_services( $services ) {

            update_option( 'wc_connect_services', $services );

            // And set the instance variable to match
            $this->services = $services;

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

            if ( ! is_object( $this->services ) ) {
                return array();
            }

            if ( ! property_exists( $this->services, $type ) ) {
                return array();
            }

            if ( ! is_array( $this->services->$type ) ) {
                return array();
            }

            $service_ids = array();

            foreach ( $this->services->$type as $service ) {
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

            if ( ! is_object( $this->services ) ) {
                return null;
            }

            foreach ( $this->services as $service_type => $service_type_services ) {
                foreach ( $service_type_services as $service ) {
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
