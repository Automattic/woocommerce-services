<?php

if ( ! class_exists( 'WC_Connect_Services_Validator' ) ) {

	class WC_Connect_Services_Validator {

		private function __construct() {
		}

		/**
		 * Validates the passed services object, especially the parts of each service
		 * that WC relies on like id, method_title, method_description, etc
		 *
		 * @param $services
		 *
		 * @return bool
		 */
		public static function validate_services( $services ) {
			if ( ! is_object( $services ) ) {
				WC_Connect_Logger::log(
					'Malformed services. Outermost container is not an object.',
					__FUNCTION__
				);

				return false;
			}

			foreach ( $services as $service_type => $service_type_services ) {
				if ( ! is_array( $service_type_services ) ) {
					WC_Connect_Logger::log(
						sprintf(
							'Malformed services. Service type \'%s\' does not reference an array.',
							$service_type
						),
						__FUNCTION__
					);

					return false;
				}

				$service_counter = 0;
				foreach ( $service_type_services as $service ) {
					if ( ! is_object( $service ) ) {
						WC_Connect_Logger::log(
							sprintf(
								'Malformed services. Service type \'%s\' [%d] does not reference an object.',
								$service_type,
								$service_counter
							),
							__FUNCTION__
						);

						return false;
					}

					if ( ! self::validate_service_schema( $service_type, $service_counter, $service ) ) {
						return false;
					}

					$service_counter ++;
				}

			}

			return true;
		}

		protected static function validate_service_schema( $service_type, $service_counter, $service ) {
			$required_properties = array(
				'id'                 => 'string',
				'method_description' => 'string',
				'method_title'       => 'string',
				'service_settings'   => 'object'
			);

			foreach ( $required_properties as $required_property => $required_property_type ) {
				if ( ! property_exists( $service, $required_property ) ) {
					WC_Connect_Logger::log(
						sprintf(
							'Malformed service. Service type \'%s\' [%d] does not include a required \'%s\' property.',
							$service_type,
							$service_counter,
							$required_property
						),
						__FUNCTION__
					);

					return false;
				}

				$property_type = gettype( $service->$required_property );
				if ( $required_property_type !== $property_type ) {
					WC_Connect_Logger::log(
						sprintf(
							'Malformed services. Service type \'%s\' [%d] property \'%s\' is a %s. Was expecting a %s.',
							$service_type,
							$service_counter,
							$property_type,
							$required_property_type
						),
						__FUNCTION__
					);

					return false;
				}

			}

			return self::validate_service_settings_schema( $service->id, $service->service_settings );
		}

		protected static function validate_service_settings_schema( $service_id, $service_settings ) {
			$required_properties = array(
				'type'       => 'string',
				'required'   => 'array',
				'properties' => 'object'
			);

			foreach ( $required_properties as $required_property => $required_property_type ) {
				if ( ! property_exists( $service_settings, $required_property ) ) {
					WC_Connect_Logger::log(
						sprintf(
							'Malformed service settings. Service \'%s\' service_settings do not include a required \'%s\' property.',
							$service_id,
							$required_property
						),
						__FUNCTION__
					);

					return false;
				}

				$property_type = gettype( $service_settings->$required_property );
				if ( $required_property_type !== $property_type ) {
					WC_Connect_Logger::log(
						sprintf(
							"Malformed service settings. Service '%s' service_setting property '%s' is a %s. Was expecting a %s.",
							$service_id,
							$required_property,
							$property_type,
							$required_property_type
						),
						__FUNCTION__
					);

					return false;
				}
			}

			if ( ! self::validate_service_settings_required_properties( $service_id, $service_settings->properties ) ) {
				return false;
			}

			WC_Connect_Logger::log(
				sprintf(
					"Service '%s' schema validated successfully.",
					$service_id
				),
				__FUNCTION__
			);

			return true;
		}

		protected static function validate_service_settings_required_properties( $service_id, $service_settings_properties ) {
			$required_properties = array(
				'enabled',
				'title'
			);

			foreach ( $required_properties as $required_property ) {
				if ( ! property_exists( $service_settings_properties, $required_property ) ) {
					WC_Connect_Logger::log(
						sprintf(
							"Malformed service. Service '%s' service_settings properties do not include a required '%s' property.",
							$service_id,
							$required_property
						),
						__FUNCTION__
					);

					return false;
				}
			}

			return true;
		}

	}

}