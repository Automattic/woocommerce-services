<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the settings payload for the shipping packages screen.
 */
class WC_Connect_Package_Settings {
	/**
	 * The settings store instance.
	 *
	 * @var WC_Connect_Service_Settings_Store
	 */
	protected $settings_store;

	/**
	 * The service schemas store instance.
	 *
	 * @var WC_Connect_Service_Schemas_Store
	 */
	protected $service_schemas_store;

	/**
	 * Constructor.
	 *
	 * @param WC_Connect_Service_Settings_Store $settings_store        The settings store instance.
	 * @param WC_Connect_Service_Schemas_Store  $service_schemas_store The service schemas store instance.
	 */
	public function __construct(
		WC_Connect_Service_Settings_Store $settings_store,
		WC_Connect_Service_Schemas_Store $service_schemas_store
	) {
		$this->settings_store        = $settings_store;
		$this->service_schemas_store = $service_schemas_store;
	}

	/**
	 * Get the package settings payload.
	 *
	 * @return array The store options, form schema, and form data for packages.
	 */
	public function get() {
		return array(
			'storeOptions' => $this->settings_store->get_store_options(),
			'formSchema'   => array(
				'custom'     => $this->service_schemas_store->get_packages_schema(),
				'predefined' => $this->service_schemas_store->get_predefined_packages_schema(),
			),
			'formData'     => array(
				'custom'     => $this->settings_store->get_packages(),
				'predefined' => $this->settings_store->get_predefined_packages(),
			),
		);
	}
}
