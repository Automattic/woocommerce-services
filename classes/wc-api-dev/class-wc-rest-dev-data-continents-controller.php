<?php
/**
 * REST API Data controller.
 *
 * Handles requests to the /data/continents endpoint.
 *
 * Directly copied from the wc-api-dev plugin. Delete this when the "v3" REST API is included in all the WC versions we support.
 *
 * @author   Automattic
 * @category API
 * @package  WooCommerce/API
 * @since    3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Data controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_REST_Dev_Data_Continents_Controller extends WC_REST_Dev_Data_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * @var WC_Connect_Continents
	 */
	protected $continents;

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'data/continents';

	public function __construct() {
		parent::__construct();
		$this->continents = new WC_Connect_Continents();
	}

	/**
	 * Register routes.
	 *
	 * @since 3.1.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<location>[\w-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => array(
						'continent' => array(
							'description' => __( '2 character continent code.', 'woocommerce' ),
							'type'        => 'string',
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Return the list of states for all continents.
	 *
	 * @since  3.1.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$continents = WC()->countries->get_continents();
		$data       = array();

		foreach ( array_keys( $continents ) as $continent_code ) {
			$continent = $this->continents->get_continent( $continent_code, $request );
			$response  = $this->prepare_item_for_response( $continent, $request );
			$data[]    = $this->prepare_response_for_collection( $response );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Return the list of locations for a given continent.
	 *
	 * @since  3.1.0
	 * @param  WP_REST_Request $request
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$data = $this->continents->get_continent( strtoupper( $request['location'] ), $request );
		if ( empty( $data ) ) {
			return new WP_Error( 'woocommerce_rest_data_invalid_location', __( 'There are no locations matching these parameters.', 'woocommerce' ), array( 'status' => 404 ) );
		}
		return $this->prepare_item_for_response( $data, $request );
	}

	/**
	 * Prepare the data object for response.
	 *
	 * @since  3.1.0
	 * @param object          $item Data object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data     = $this->add_additional_fields_to_object( $item, $request );
		$data     = $this->filter_response_by_context( $data, 'view' );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filter the location list returned from the API.
		 *
		 * Allows modification of the loction data right before it is returned.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param array            $item     The original list of continent(s), countries, and states.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_data_continent', $response, $item, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param object $item Data object.
	 * @return array Links for the given continent.
	 */
	protected function prepare_links( $item ) {
		$continent_code = strtolower( $item['code'] );
		$links          = array(
			'self'       => array(
				'href' => rest_url( sprintf( '/%s/%s/%s', $this->namespace, $this->rest_base, $continent_code ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);
		return $links;
	}

	/**
	 * Get the location schema, conforming to JSON Schema.
	 *
	 * @since  3.1.0
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'data_continents',
			'type'       => 'object',
			'properties' => array(
				'code'      => array(
					'type'        => 'string',
					'description' => __( '2 character continent code.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'name'      => array(
					'type'        => 'string',
					'description' => __( 'Full name of continent.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'countries' => array(
					'type'        => 'array',
					'description' => __( 'List of countries on this continent.', 'woocommerce' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
					'items'       => array(
						'type'       => 'object',
						'context'    => array( 'view' ),
						'readonly'   => true,
						'properties' => array(
							'code'           => array(
								'type'        => 'string',
								'description' => __( 'ISO3166 alpha-2 country code.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'currency_code'  => array(
								'type'        => 'string',
								'description' => __( 'Default ISO4127 alpha-3 currency code for the country.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'currency_pos'   => array(
								'type'        => 'string',
								'description' => __( 'Currency symbol position for this country.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'decimal_sep'    => array(
								'type'        => 'string',
								'description' => __( 'Decimal separator for displayed prices for this country.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'dimension_unit' => array(
								'type'        => 'string',
								'description' => __( 'The unit lengths are defined in for this country.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'name'           => array(
								'type'        => 'string',
								'description' => __( 'Full name of country.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'num_decimals'   => array(
								'type'        => 'integer',
								'description' => __( 'Number of decimal points shown in displayed prices for this country.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'states'         => array(
								'type'        => 'array',
								'description' => __( 'List of states in this country.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
								'items'       => array(
									'type'       => 'object',
									'context'    => array( 'view' ),
									'readonly'   => true,
									'properties' => array(
										'code' => array(
											'type'        => 'string',
											'description' => __( 'State code.', 'woocommerce' ),
											'context'     => array( 'view' ),
											'readonly'    => true,
										),
										'name' => array(
											'type'        => 'string',
											'description' => __( 'Full name of state.', 'woocommerce' ),
											'context'     => array( 'view' ),
											'readonly'    => true,
										),
									),
								),
							),
							'thousand_sep'   => array(
								'type'        => 'string',
								'description' => __( 'Thousands separator for displayed prices in this country.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'weight_unit'    => array(
								'type'        => 'string',
								'description' => __( 'The unit weights are defined in for this country.', 'woocommerce' ),
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}
}
