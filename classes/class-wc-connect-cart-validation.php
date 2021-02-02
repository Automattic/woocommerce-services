<?php
/**
 * Validation logic.
 *
 * @package WooCommerce\Shipping
 */

/**
 * Validates the shipping info in the cart.
 */
class WC_Connect_Cart_Validation {

	/**
	 * Needed to keep track of the current package being proccessed.
	 *
	 * @var array
	 */
	private $current_package;

	/**
	 * Register filters when required instead of using constructor.
	 */
	public function register_filters() {
		add_filter( 'woocommerce_cart_no_shipping_available_html', [ $this, 'error_no_shipping_available_html' ] );
		add_filter( 'woocommerce_shipping_package_name', [ $this, 'set_current_package' ], 10, 3 );
	}

	/**
	 * We use this filter to store the current package array because it's not passed to the woocommerce_cart_no_shipping_available_html filter.
	 *
	 * @param string $package_name Package Name.
	 * @param int    $i Index.
	 * @param array  $package Current package we need to store.
	 *
	 * @return mixed
	 */
	public function set_current_package( $package_name, $i, $package ) {
		$this->current_package = $package;
		return $package_name;
	}

	/**
	 * More friendly error message when WCS has an error.
	 *
	 * @param string $error_html Generic error message from WC.
	 * @return string
	 */
	public function error_no_shipping_available_html( $error_html ) {
		try {
			$this->validate_package_destination( $this->current_package );
			return $error_html;
		} catch ( WC_Connect_Cart_Exception $exception ) {
			return $exception->getMessage();
		}
	}

	/**
	 * Called by above filter to get error message to show and by shipping method to detect errors.
	 *
	 * @param array $package Current Package.
	 * @return bool
	 * @throws WC_Connect_Cart_Exception Thrown when validation error occurs.
	 */
	public function validate_package_destination( $package ) {
		$country   = isset( $package['destination']['country'] ) ? $package['destination']['country'] : '';
		$postcode  = isset( $package['destination']['postcode'] ) ? $package['destination']['postcode'] : '';
		$state     = isset( $package['destination']['state'] ) ? $package['destination']['state'] : '';
		$countries = WC()->countries->get_countries();

		// Ensure that Country is specified.
		if ( empty( $country ) ) {
			throw new WC_Connect_Cart_Exception(
				esc_html__(
					'A country is required',
					'woocommerce-services'
				)
			);
		}

		// Validate Postcode.
		if ( ! WC_Validation::is_postcode( $postcode, $country ) ) {
			$fields = WC()->countries->get_address_fields( $country, '' );
			if ( empty( $postcode ) ) {
				throw new WC_Connect_Cart_Exception(
					sprintf(
						/* Translators: %1$s: Localized label for Zip/postal code, %2$s: Country name */
						esc_html__(
							'A %1$s is required for %2$s.',
							'woocommerce-services'
						),
						'<strong>' . esc_html( $fields['postcode']['label'] ) . '</strong>',
						'<strong>' . esc_html( $countries[ $country ] ) . '</strong>'
					)
				);
			} else {
				throw new WC_Connect_Cart_Exception(
					sprintf(
						/* Translators: %1$s: Localized label for Zip/postal code, %2$s: submitted zip/postal code, %3$s: Country name */
						esc_html__(
							'%1$s %2$s is invalid for %3$s.',
							'woocommerce-services'
						),
						esc_html( $fields['postcode']['label'] ),
						'<strong>' . esc_html( $postcode ) . '</strong>',
						'<strong>' . esc_html( $countries[ $country ] ) . '</strong>'
					)
				);
			}
		}

		// Validate State.
		$valid_states = WC()->countries->get_states( $country );

		if ( $valid_states && ! isset( $valid_states[ $state ] ) ) {
			if ( empty( $state ) ) {
				$fields = WC()->countries->get_address_fields( $country, '' );
				throw new WC_Connect_Cart_Exception(
					sprintf(
						/* Translators: %1$s: Localized label for province/region/state, %2$s: Country name */
						esc_html__(
							'A %1$s is required for %2$s.',
							'woocommerce-services'
						),
						'<strong>' . esc_html( $fields['state']['label'] ) . '</strong>',
						'<strong>' . esc_html( $countries[ $country ] ) . '</strong>'
					)
				);
			} else {
				throw new WC_Connect_Cart_Exception(
					sprintf(
						/* Translators: %1$s: State name, %2$s: Country name */
						esc_html__(
							'State %1$s is invalid for %2$s.',
							'woocommerce-services'
						),
						'<strong>' . esc_html( $state ) . '</strong>',
						'<strong>' . esc_html( $countries[ $country ] ) . '</strong>'
					)
				);
			}
		}

		return true;
	}
}
