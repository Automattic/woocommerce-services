<?php

if ( ! class_exists( 'WC_Connect_Services_Validator' ) ) {

	class WC_Connect_Services_Validator {

		/**
		 * Validates the overall passed services object (all service types and all services therein)
		 *
		 * @param object $services
		 *
		 * @return WP_Error|true
		 */
		public function validate_services( $services ) {

			if ( ! is_object( $services ) ) {
				return new WP_Error(
					'outermost_container_not_object',
					'Malformed services. Outermost container is not an object.'
				);
			}

			foreach ( $services as $service_type => $service_type_services ) {
				if ( ! is_array( $service_type_services ) ) {
					return new WP_Error(
						'service_type_not_ref_array',
						sprintf(
							'Malformed services. Service type \'%s\' does not reference an array.',
							$service_type
						)
					);
				}

				$service_counter = 0;
				foreach ( $service_type_services as $service ) {
					if ( ! is_object( $service ) ) {
						return new WP_Error(
							'service_not_ref_object',
							sprintf(
								'Malformed services. Service type \'%s\' [%d] does not reference an object.',
								$service_type,
								$service_counter
							)
						);
					}

					$result = $this->validate_service_schema( $service_type, $service_counter, $service );
					if ( is_wp_error( $result ) ) {
						return $result;
					}

					$service_counter ++;
				}

			}

			return true;
		}

		/**
		 * Validates a particular service, especially the parts of the service that WC relies
		 * on like id, method_title, method_description, etc
		 *
		 * @param string $service_type
		 * @param integer $service_counter
		 * @param object $service
		 *
		 * @return WP_Error|true
		 */
		protected function validate_service_schema( $service_type, $service_counter, $service ) {
			$required_properties = array(
				'id'                 => 'string',
				'method_description' => 'string',
				'method_title'       => 'string',
				'service_settings'   => 'object',
				'form_layout'        => 'array'
			);

			foreach ( $required_properties as $required_property => $required_property_type ) {
				if ( ! property_exists( $service, $required_property ) ) {
					return new WP_Error(
						'required_service_property_missing',
						sprintf(
							'Malformed service. Service type \'%s\' [%d] does not include a required \'%s\' property.',
							$service_type,
							$service_counter,
							$required_property
						)
					);
				}

				$property_type = gettype( $service->$required_property );
				if ( $required_property_type !== $property_type ) {
					return new WP_Error(
						'required_service_property_wrong_type',
						sprintf(
							'Malformed services. Service type \'%s\' [%d] property \'%s\' is a %s. Was expecting a %s.',
							$service_type,
							$service_counter,
							$service->$required_property,
							$property_type,
							$required_property_type
						)
					);
				}

			}

			return $this->validate_service_settings_schema( $service->id, $service->service_settings );
		}

		/**
		 * Validates a particular service's service settings schema, especially the parts of the
		 * service settings that WC relies on like type, required and properties
		 *
		 * @param string $service_id
		 * @param object $service_settings
		 *
		 * @return WP_Error|true
		 */
		protected function validate_service_settings_schema( $service_id, $service_settings ) {
			$required_properties = array(
				'type'       => 'string',
				'required'   => 'array',
				'properties' => 'object'
			);

			foreach ( $required_properties as $required_property => $required_property_type ) {
				if ( ! property_exists( $service_settings, $required_property ) ) {
					return new WP_Error(
						'service_settings_missing_required_property',
						sprintf(
							'Malformed service settings. Service \'%s\' service_settings do not include a required \'%s\' property.',
							$service_id,
							$required_property
						)
					);
				}

				$property_type = gettype( $service_settings->$required_property );
				if ( $required_property_type !== $property_type ) {
					return new WP_Error(
						'service_settings_property_wrong_type',
						sprintf(
							"Malformed service settings. Service '%s' service_setting property '%s' is a %s. Was expecting a %s.",
							$service_id,
							$required_property,
							$property_type,
							$required_property_type
						)
					);
				}
			}

			$result = $this->validate_service_settings_required_properties( $service_id, $service_settings->properties );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return true;
		}


		/**
		 * Validates a particular service's required properties, especially the parts of the
		 * properties that WC relies on like enabled and title
		 *
		 * @param string $service_id
		 * @param object $service_settings_properties
		 *
		 * @return WP_Error|true
		 */
		protected function validate_service_settings_required_properties( $service_id, $service_settings_properties ) {
			$required_properties = array(
				'enabled',
				'title'
			);

			foreach ( $required_properties as $required_property ) {
				if ( ! property_exists( $service_settings_properties, $required_property ) ) {
					return new WP_Error(
						'service_properties_missing_required_property',
						sprintf(
							"Malformed service. Service '%s' service_settings properties do not include a required '%s' property.",
							$service_id,
							$required_property
						)
					);
				}
			}

			return true;
		}

	}

}