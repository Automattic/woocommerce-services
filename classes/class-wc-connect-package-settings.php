<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Connect_Package_Settings {
	/**
	 * @var WC_Connect_Service_Settings_Store
	 */
	protected $settings_store;

	/**
	 * @var WC_Connect_Service_Schemas_Store
	 */
	protected $service_schemas_store;

	public function __construct(
		WC_Connect_Service_Settings_Store $settings_store,
		WC_Connect_Service_Schemas_Store $service_schemas_store
	) {
		$this->settings_store = $settings_store;
		$this->service_schemas_store = $service_schemas_store;
	}
	public function get() {
		return array(
			'storeOptions' => $this->settings_store->get_store_options(),
			'formSchema'   => array(
				'custom' => $this->service_schemas_store->get_packages_schema(),
				'predefined' => $this->service_schemas_store->get_predefined_packages_schema()
			),
			'formData'     => array(
				'custom' => $this->settings_store->get_packages(),
				'predefined' => $this->settings_store->get_predefined_packages()
			)
		);
	}
}
