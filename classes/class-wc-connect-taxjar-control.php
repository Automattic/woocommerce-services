<?php

class WC_Connect_TaxJar_Control {

	/**
	 * @var WC_Connect_API_Client
	 */
	public $api_client;

	/**
	 * @var WC_Connect_Logger
	 */
	public $logger;

	const PROXY_PATH               = 'taxjar/v2';
	const SETUP_WIZARD_OPTION_NAME = 'woocommerce_setup_automated_taxes';

	public function __construct(
		WC_Connect_API_Client $api_client,
		WC_Connect_Logger $logger
	) {
		$this->api_client = $api_client;
		$this->logger = $logger;
	}

	public function init() {
		if ( ! class_exists( 'WC_Taxjar' ) ) {
			return;
		}

		add_filter( 'taxjar_method_description', array( $this, 'taxjar_method_description' ), 10, 1 );
		add_filter( 'taxjar_should_check_status', array( $this, 'should_check_status' ), 10, 1 );
		add_filter( 'taxjar_api_token_valid', array( $this, 'validate_api_token' ), 10, 1 );
		add_filter( 'taxjar_can_connect', array( $this, 'set_taxjar_can_connect' ), 10, 1 );
		add_filter( 'taxjar_form_fields', array( $this, 'modify_form_fields' ), 10, 1 );
		add_filter( 'taxjar_smartcalcs_request', array( $this, 'smartcalcs_request' ), 10, 2 );
		add_filter( 'taxjar_download_orders', array( $this, 'should_download_orders' ), 10, 1 );
		add_filter( 'woocommerce_settings_api_sanitized_fields_taxjar-integration', array( $this, 'options_save' ), 10, 1 );
		add_action( 'taxjar_log', array( $this, 'log' ), 10, 1 );
	}

	public function options_save( $options ) {
		if ( ! $this->is_enabled() ) {
			return $options;
		}

		$options['enabled'] = 'yes';
		return $options;
	}

	public function taxjar_method_description( $description ) {
		if ( ! $this->is_enabled() ) {
			return $description;
		}

		return __( 'TaxJar is currently powered by WooCommerce Services. Use the controls below to disable the WooCommerce Services integration and enter your own API token (<a href="https://app.taxjar.com/api_sign_up/" target="_blank">click here to get a token</a>).', 'woocommerce-services' );
	}

	public function should_check_status( $value ) {
		return ! $this->is_enabled();
	}

	public function validate_api_token( $is_valid ) {
		return $is_valid || $this->is_enabled();
	}

	public function set_taxjar_can_connect( $can_connect ) {
		return $can_connect || $this->is_enabled();
	}

	public function should_download_orders( $value ) {
		if ( ! $this->is_enabled() ) {
			return $value;
		}
		return false;
	}

	public function smartcalcs_request( $json, $default ) {
		if ( ! $this->is_enabled() ) {
			return $default;
		}

		$this->log( 'Requesting: ' . $path . ' - ' . $json );

		$path = trailingslashit( self::PROXY_PATH ) . 'taxes';
		$response = $this->api_client->proxy_request( $path, array(
			'method'  => 'POST',
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'body' => $json,
		) );

		if ( 200 == $response['response']['code'] ) {
			return $response;
		}

		if ( is_wp_error( $response ) ) {
			$this->error( 'Error retrieving the tax rates. Received (' . $response->get_error_code() . '): ' . $response->get_error_message() );
		} else {
			$this->error( 'Error retrieving the tax rates. Received (' . $response['response']['code'] . '): ' . $response['body'] );
		}

		return $response;
	}

	public function modify_form_fields( $form_fields ) {
		$modified_fields = array_merge( array(
			'wcs_taxjar_title_step_1' => array(
				'title'               => __( '<h3>Step 1 (optional):</h3>', 'woocommerce-services' ),
				'type'                => 'hidden',
				'description'         => __( '<h3>WooCommerce Services</h3>', 'woocommerce-services' ),
			),
			'wcs_taxjar_control'      => array(
				'label'               => __( 'Let WooCommerce Services configure your TaxJar plugin', 'woocommerce-services' ),
				'type'                => 'checkbox',
				'description'         => __( 'WooCommerce Services will allow you to use TaxJar without the API key. You will only be able to receive automated tax rates, and will not be able to add nexus addresses or download orders.' ),
			),
		), $form_fields );

		if ( $this->is_enabled() ) {
			$hidden_fields = array( 'taxjar_title_step_1', 'api_token', 'taxjar_status', 'enabled', 'nexus', 'taxjar_download' );
			foreach( $hidden_fields as $hidden_field ) {
				unset( $modified_fields[ $hidden_field ] );
			}
		} else {
			if ( isset ( $modified_fields['taxjar_title_step_1'] ) ) {
				$modified_fields['taxjar_title_step_1']['title'] = __( '<h3>Step 2:</h3>', 'woocommerce-services' );
			}
			if ( isset ( $modified_fields['taxjar_title_step_2'] ) ) {
				$modified_fields['taxjar_title_step_2']['title'] = __( '<h3>Step 3:</h3>', 'woocommerce-services' );
			}
		}

		return $modified_fields;
	}

	/**
	 * Is automated taxes control enabled?
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$taxjar_settings = get_option( 'woocommerce_taxjar-integration_settings', array() );

		// Migrate automated taxes selection from the setup wizard
		if ( get_option( self::SETUP_WIZARD_OPTION_NAME ) ) {
			$taxjar_settings['wcs_taxjar_control'] = 'yes';
			delete_option( self::SETUP_WIZARD_OPTION_NAME );

			return true;
		}

		//check if the setting is being currently toggled to render the settings page correctly
		if ( isset( $_POST['woocommerce_taxjar-integration_wcs_taxjar_title_step_1'] ) ) {
			return isset( $_POST[ 'woocommerce_taxjar-integration_wcs_taxjar_control' ] ) && '1' === $_POST[ 'woocommerce_taxjar-integration_wcs_taxjar_control' ];
		}

		return ( wc_tax_enabled() && isset( $taxjar_settings['wcs_taxjar_control'] ) && 'yes' === $taxjar_settings['wcs_taxjar_control'] );
	}

	public function log( $message ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$formatted_message = is_scalar( $message ) ? $message : json_encode( $message );
		$this->logger->log( $formatted_message, 'WCS Tax' );
	}

	public function error( $message ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$formatted_message = is_scalar( $message ) ? $message : json_encode( $message );

		//ignore error messages caused by customer input
		$state_zip_mismatch = false !== strpos( $formatted_message, 'to_zip' ) && false !== strpos( $formatted_message, 'is not used within to_state' );
		$invalid_postcode = false !== strpos( $formatted_message, 'isn\'t a valid postal code for' );
		if ( $state_zip_mismatch || $invalid_postcode ) {
 			if ( $state_zip_mismatch ) {
				$message = __( 'Postcode/ZIP does not match the selected state.', '%s - Postcode/Zip checkout field label', 'woocommerce-services' );
			} else {
				$message = __( 'Invalid Postcode/ZIP entered.', 'woocommerce-services' );
			}
			wc_add_notice( $message, 'error' );
 			return;
		}

		$this->logger->log( $formatted_message, 'WCS Tax' );
	}
}
