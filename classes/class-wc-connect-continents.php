<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Connect_Continents' ) ) {
	return;
}

class WC_Connect_Continents {

	/**
	 * Return the list of countries and states for a given continent.
	 *
	 * @since  3.1.0
	 * @param  string          $continent_code
	 * @return array|mixed Response data, ready for insertion into collection data.
	 */
	public function get_continent( $continent_code = false ) {
		$continents  = WC()->countries->get_continents();
		$countries   = WC()->countries->get_countries();
		$states      = WC()->countries->get_states();
		$locale_info = include WC()->plugin_path() . '/i18n/locale-info.php';
		$data        = array();

		if ( ! array_key_exists( $continent_code, $continents ) ) {
			return false;
		}

		$continent_list = $continents[ $continent_code ];

		$continent = array(
			'code' => $continent_code,
			'name' => $continent_list['name'],
		);

		$local_countries = array();
		foreach ( $continent_list['countries'] as $country_code ) {
			if ( isset( $countries[ $country_code ] ) ) {
				$country = array(
					'code' => $country_code,
					'name' => $countries[ $country_code ],
				);

				// If we have detailed locale information include that in the response
				if ( array_key_exists( $country_code, $locale_info ) ) {
					// Defensive programming against unexpected changes in locale-info.php
					$country_data = wp_parse_args( $locale_info[ $country_code ], array(
						'currency_code'  => 'USD',
						'currency_pos'   => 'left',
						'decimal_sep'    => '.',
						'dimension_unit' => 'in',
						'num_decimals'   => 2,
						'thousand_sep'   => ',',
						'weight_unit'    => 'lbs',
					) );

					$country = array_merge( $country, $country_data );
				}

				$local_states = array();
				if ( isset( $states[ $country_code ] ) ) {
					foreach ( $states[ $country_code ] as $state_code => $state_name ) {
						$local_states[] = array(
							'code' => $state_code,
							'name' => $state_name,
						);
					}
				}
				$country['states'] = $local_states;

				// Allow only desired keys (e.g. filter out tax rates)
				$allowed = array(
					'code',
					'currency_code',
					'currency_pos',
					'decimal_sep',
					'dimension_unit',
					'name',
					'num_decimals',
					'states',
					'thousand_sep',
					'weight_unit',
				);
				$country = array_intersect_key( $country, array_flip( $allowed ) );

				$local_countries[] = $country;
			}
		}

		$continent['countries'] = $local_countries;
		return $continent;
	}


	public function get() {
		$continents = array();
		foreach ( array_keys( WC()->countries->get_continents() ) as $continent_code ) {
			$continents[] = $this->get_continent( $continent_code, null );
		}

		return $continents;
	}
}
