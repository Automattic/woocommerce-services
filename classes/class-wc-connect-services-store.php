<?php

if ( ! class_exists( 'WC_Connect_Services_Store' ) ) {

    class WC_Connect_Services_Store
    {
        private function __construct() {
        }

        public static function fetch_services_from_connect_server() {
            require_once( plugin_basename( 'class-wc-connect-api-client.php' ) );

            $response = WC_Connect_API_Client::get_services();
            if ( is_wp_error( $response ) ) {
                WC_Connect_Logger::log(
                    $response,
                    __FUNCTION__
                );
                return;
            }

            if ( ! array_key_exists( 'body', $response ) ) {
                WC_Connect_Logger::log(
                    'Server response did not include body. Service schemas not updated.',
                    __FUNCTION__
                );
                return;
            }

            $body = json_decode( $response['body'] );

            if ( ! is_object( $body ) ) {
                WC_Connect_Logger::log(
                    'Server response body is not an object. Service schemas not updated.',
                    __FUNCTION__
                );
                return;
            }

            if ( property_exists( $body, 'error' ) ) {
                WC_Connect_Logger::log(
                    sprintf( 'Server responded with an error : %s : %s.',
                        $body->error, $body->message
                    ),
                    __FUNCTION__
                );
                return;
            }

            require_once( plugin_basename( 'class-wc-connect-services-validator.php' ) );

            if ( ! WC_Connect_Services_Validator::validate_services( $body ) ) {
                WC_Connect_Logger::log(
                    'One or more service schemas failed to validate. Will not store services in options.',
                    __FUNCTION__
                );
                return;
            }

            WC_Connect_Logger::log(
                'Successfully loaded service schemas from server response.',
                __FUNCTION__
            );

            // If we made it this far, it is safe to store the object
            self::update_services( $body );
        }

        protected static function get_services() {
            return get_option( 'wc_connect_services', null );
        }

        protected static function update_services( $services ) {
            update_option( 'wc_connect_services', $services );
        }

        /**
         * Returns all service ids of a specific type (e.g. shipping)
         *
         * @param string $type The type of services to return
         * @return array An array of that type's service ids, or an empty array if no such type is known
         */
        public static function get_all_service_ids_of_type( $type ) {
            if ( empty( $type ) ) {
                return array();
            }

            $services = self::get_services();
            if ( ! is_object( $services ) ) {
                return array();
            }

            if ( ! property_exists( $services, $type ) ) {
                return array();
            }

            if ( ! is_array( $services->$type ) ) {
                return array();
            }

            $service_ids = array();

            foreach ( $services->$type as $service ) {
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
        public static function get_service_by_id( $service_id ) {
            $services = self::get_services();
            if ( ! is_object( $services ) ) {
                return null;
            }

            foreach ( $services as $service_type => $service_type_services ) {
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
        public static function get_service_by_instance_id( $instance_id ) {
            global $wpdb;
            $service_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT method_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d;",
                    $instance_id
                )
            );
            return self::get_service_by_id( $service_id );
        }

    }

}
