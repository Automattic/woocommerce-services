<?php
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
		foreach ( WC()->shipping()->load_shipping_methods( $this->current_package ) as $shipping_method ) {
			if ( $shipping_method instanceof WC_Connect_Shipping_Method ) {
				// We have to always force validation to run because WC_Shipping could cache package rates.
				$shipping_method->is_valid_package_destination( $this->current_package );
				$errors = $shipping_method->get_package_validation_errors();
				if ( $errors->has_errors() ) {
					return $errors->get_error_message();
				}
			}
		}
		return $error_html;
	}
}
