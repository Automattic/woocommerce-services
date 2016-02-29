<?php

if ( ! class_exists( 'WC_Connect_Services_Validator' ) ) {

    class WC_Connect_Services_Validator {

        public function __construct() {
        }

        public function validate_services( $services ) {

            if ( ! is_object( $services ) ) {
                WC_Connect_Logger::getInstance()->log(
                    "Malformed services. Outermost container is not an object."
                );
                return false;
            }

            foreach ( $services as $service_kind => $service_kind_services ) {
                if ( ! is_array( $service_kind_services ) ) {
                    WC_Connect_Logger::getInstance()->log(
                        sprintf(
                            "Malformed services. Service kind '%s' does not reference an array.",
                            $service_kind
                        )
                    );
                    return false;
                }

                $service_counter = 0;
                foreach ( $service_kind_services as $service ) {
                    if ( ! is_object( $service ) ) {
                        WC_Connect_Logger::getInstance()->log(
                            sprintf(
                                "Malformed services. Service kind '%s' [%d] does not reference an object.",
                                $service_kind,
                                $service_counter
                            )
                        );
                        return false;
                    }

                    if ( ! $this->validate_service_schema( $service_kind, $service_counter, $service ) ) {
                        return false;
                    }

                    $service_counter++;
                }

            }

            return true;

        }

        protected function validate_service_schema( $service_kind, $service_counter, $service ) {

            $required_properties = array(
                'id' => 'string',
                'method_description' => 'string',
                'method_title' => 'string',
                'service_settings' => 'object'
            );

            foreach ( $required_properties as $required_property => $required_property_type ) {
                if ( ! property_exists( $service, $required_property ) ) {
                    WC_Connect_Logger::getInstance()->log(
                        sprintf(
                            "Malformed services. Service kind '%s' [%d] does not include a required '%s' property.",
                            $service_kind,
                            $service_counter,
                            $required_property
                        )
                    );
                    return false;
                }

                $property_type = gettype( $service->$required_property );
                if ( $required_property_type != $property_type ) {
                    WC_Connect_Logger::getInstance()->log(
                        sprintf(
                            "Malformed services. Service kind '%s' [%d] property '%s' is a %s. Was expecting a %s.",
                            $service_kind,
                            $service_counter,
                            $property_type,
                            $required_property_type
                        )
                    );
                    return false;
                }


            }

            if ( ! $this->validate_service_settings_schema( $service->id, $service->service_settings ) ) {
                return false;
            }

            return true;

        }

        protected function validate_service_settings_schema( $service_id, $service_settings ) {

            $required_properties = array(
                'type' => 'string',
                'required' => 'array',
                'properties' => 'object'
            );

            foreach ( $required_properties as $required_property => $required_property_type ) {
                if ( ! property_exists( $service_settings, $required_property ) ) {
                    WC_Connect_Logger::getInstance()->log(
                        sprintf(
                            "Malformed services. Service '%s' service_settings do not include a required '%s' property.",
                            $service_id,
                            $required_property
                        )
                    );
                    return false;
                }

                $property_type = gettype( $service_settings->$required_property );
                if ( $required_property_type != $property_type ) {
                    WC_Connect_Logger::getInstance()->log(
                        sprintf(
                            "Malformed services. Service '%s' service_setting property '%s' is a %s. Was expecting a %s.",
                            $service_id,
                            $required_property,
                            $property_type,
                            $required_property_type
                        )
                    );
                    return false;
                }

            }

            if ( ! $this->validate_service_settings_required_properties( $service_id, $service_settings->properties ) ) {
                return false;
            }

            WC_Connect_Logger::getInstance()->log(
                sprintf(
                    "Service '%s' schema validated successfully.",
                    $service_id
                )
            );

            return true;

        }

        protected function validate_service_settings_required_properties( $service_id, $service_settings_properties ) {

            $required_properties = array(
                'enabled',
                'title'
            );

            foreach ( $required_properties as $required_property ) {
                if ( ! property_exists( $service_settings_properties, $required_property ) ) {
                    WC_Connect_Logger::getInstance()->log(
                        sprintf(
                            "Malformed services. Service '%s' service_settings properties do not include a required '%s' property.",
                            $service_id,
                            $required_property
                        )
                    );
                    return false;
                }

            }

            return true;

        }

    }

}