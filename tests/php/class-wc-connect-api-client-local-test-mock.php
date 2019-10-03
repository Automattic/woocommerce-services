<?php

// No direct access please
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Connect_API_Client_Local_Test_Mock' ) ) {
	require_once( dirname( __FILE__ ) . '/../../classes/class-wc-connect-api-client.php' );

	class WC_Connect_API_Client_Local_Test_Mock extends WC_Connect_API_Client {

		/**
		 * Sends a request to the WooCommerce Services Server
		 *
		 * @param $method
		 * @param $path
		 * @param $body
		 * @return mixed|WP_Error
		 */
		protected function request( $method, $path, $body = array() ) {
			switch( $path ) {
				case '/shipping/label/rates':
					// Return two mock rates.
					return json_decode( '{"success":true,"rates":{"default_box":{"shipment_id":"1234","rates":[{"rate_id":"rate_1","service_id":"Mock1","carrier_id":"usps","title":"USPS - Mock 1","rate":12.3,"retail_rate":14.35,"is_selected":false},{"rate_id":"rate_2","service_id":"Mock2","carrier_id":"usps","title":"USPS - Mock 2","rate":4.56,"retail_rate":14.35,"is_selected":false}],"errors":[]}}}' );
				case '/payment/methods':
					// Has credit Card. Set payment_method_id to 0 to simulate no credit card.
					return json_decode( '{"payment_methods":[{"payment_method_id":123, "name":"mockcard", "card_type":"visa", "card_digits":"1234", "expiry":"2050-01-31"}]}' );
				case '/services':
					$services_json = <<<JSON
{
  "shipping": [
    {
      "id":"usps",
      "method_description":"USPS Shipping Rates, Powered by WooCommerce Services",
      "method_title": "USPS (WooCommerce Services)",
      "form_layout": [
        {
          "type": "fieldset",
          "title": "Setup",
          "items": [
            {
              "key": "title",
              "validation_hint": "Title is required."
            },
            {
              "key": "origin",
              "validation_hint": "The zip code of where you are shipping from is required and should be a valid 5 digit or ZIP+4 United States ZIP code."
            },
            {
              "key": "shipping_classes",
              "type": "shipping_classes",
              "requiredWcsVersion": ">= 1.17.0"
            }
          ]
        },
        {
          "type": "fieldset",
          "title": "Rates",
          "items": [
            {
              "key": "services",
              "type": "shipping_services",
              "validation_hint": "Select at least one service."
            },
            {
              "key": "rate_filter",
              "type": "radios",
              "titleMap": {
                "all": "Show all available rates and let customers choose.",
                "cheapest": "Only show the cheapest rate."
              }
            },
            {
              "key": "fallback_rate",
              "validation_hint": "Fallback rate should be a positive number.",
              "placeholder": "Rate amount"
            },
            {
              "key": "rate_class",
              "type": "radios",
              "titleMap": {
                "retail": "Retail — Standard post office rates.",
                "cbp": "Commercial — Discounted post office rates."
              }
            },
            {
              "key": "shipping_learn_more",
              "type": "text",
              "description": "Learn more</a> about available shipping rates and prices."
            }
          ]
        },
        {
          "type": "fieldset",
          "title": "Packaging",
          "items": [
            {
              "key": "packing_method",
              "type": "radios",
              "titleMap": {
                "by_price": "Pack items together, in as few packages as possible.",
                "individual": "Ship items individually, in their original packaging."
              }
            },
            {
              "key": "boxes",
              "type": "packages"
            }
          ]
        },
        {
          "type": "actions",
          "items": [
            {
              "type": "submit",
              "title": "Save changes"
            }
          ]
        }
      ],
      "service_settings": {
        "type": "object",
        "title": "USPS",
        "description": "The USPS extension obtains rates dynamically from the USPS API during cart/checkout.",
        "required": [
          "title",
          "origin",
          "services"
        ],
        "definitions": {
          "shipping_service": {
            "type": "object",
            "properties": {
              "id": {
                "type": "string",
                "minLength": 1
              },
              "enabled": {
                "type": "boolean",
                "default": false
              },
              "adjustment": {
                "type": "number",
                "default": 0
              },
              "adjustment_type": {
                "type": "string",
                "enum": [
                  "flat",
                  "percentage"
                ],
                "default": "flat"
              }
            }
          },
          "services": [
            {
              "id": "pri",
              "name": "Priority Mail",
              "group": "priority",
              "group_name": "Priority Mail",
              "group_estimate": "1-3 days"
            },
            {
              "id": "pri_flat_env",
              "name": "Priority Mail - Flat Rate Envelope",
              "group": "priority",
              "group_name": "Priority Mail",
              "group_estimate": "1-3 days",
              "predefined_package": "flat_envelope"
            }
          ]
        },
        "properties": {
          "title": {
            "type": "string",
            "title": "Shipping method title",
            "default": "USPS",
            "minLength": 1
          },
          "account_id": {
            "type": "string",
            "title": "USPS Account",
            "description": "Use the account provided or sign up for your own</a>",
            "default": "",
            "minLength": 0
          },
          "origin": {
            "type": "string",
            "title": "Origin ZIP Code",
            "description": "Where are you shipping from?",
            "default": "97055",
            "pattern": ""
          },
          "shipping_classes": {
            "type": "array",
            "title": "Shipping Classes",
            "description": "Do you need to limit the shipping method to certain classes?",
            "default": []
          },
          "fallback_rate": {
            "type": "number",
            "title": "Fallback Rate (in $)",
            "description": "If USPS returns no rates, offer the customer a fallback rate so they are still able to checkout. Leave this field as 0 to indicate no fallback rate.",
            "default": 0,
            "minimum": 0
          },
          "rate_filter": {
            "type": "string",
            "title": "Which rates would you like to show?",
            "enum": [
              "all",
              "cheapest"
            ],
            "default": "all"
          },
          "rate_class": {
            "type": "string",
            "title": "What price would you like to charge?",
            "enum": [
              "retail",
              "cbp"
            ],
            "default": "retail"
          },
          "packing_method": {
            "type": "string",
            "title": "Packing Method",
            "enum": [
              "by_price",
              "individual"
            ],
            "default": "by_price"
          }
        }
      },
      "packages": {
        "pri_envelopes": {
          "title": "USPS Priority Mail Flat Rate Envelopes",
          "definitions": [
            {
              "inner_dimensions": "12.5 x 9.5 x 0.5",
              "outer_dimensions": "12.5 x 9.5 x 0.5",
              "box_weight": 0,
              "is_flat_rate": true,
              "id": "flat_envelope",
              "name": "Flat Rate Envelope",
              "dimensions": [
                "12.5 x 9.5 x 0.5",
                "12.5 x 7.5 x 1.5",
                "12.5 x 5.5 x 2.5",
                "12.5 x 3.5 x 3.5"
              ],
              "max_weight": 1120,
              "is_letter": true,
              "group_id": "pri_envelopes",
              "can_ship_international": true
            },
            {
              "inner_dimensions": "15 x 9.5 x 0.5",
              "outer_dimensions": "15 x 9.5 x 0.5",
              "box_weight": 0,
              "is_flat_rate": true,
              "id": "legal_flat_envelope",
              "name": "Legal Flat Rate Envelope",
              "dimensions": [
                "15 x 9.5 x 0.5",
                "12.5 x 7.5 x 1.5",
                "12.5 x 5.5 x 2.5",
                "12.5 x 3.5 x 3.5"
              ],
              "max_weight": 1120,
              "is_letter": true,
              "group_id": "pri_envelopes",
              "can_ship_international": true
            }
          ]
        }
      }
    }
  ],
  "boxes": {
    "type": "array",
    "title": "Box Sizes",
    "description": "Items will be packed into these boxes based on item dimensions and volume. Outer dimensions will be passed to the delivery service, whereas inner dimensions will be used for packing. Items not fitting into boxes will be packed individually.",
    "default": [],
    "items": {
      "type": "object",
      "title": "Box",
      "required": [
        "name",
        "inner_dimensions",
        "box_weight",
        "max_weight"
      ],
      "properties": {
        "name": {
          "type": "string",
          "title": "Name"
        },
        "is_user_defined": {
          "type": "boolean"
        },
        "inner_dimensions": {
          "type": "string",
          "title": "Inner Dimensions (L x W x H)",
          "pattern": ""
        },
        "outer_dimensions": {
          "type": "string",
          "title": "Outer Dimensions (L x W x H)",
          "pattern": ""
        },
        "box_weight": {
          "type": "number",
          "title": "Weight of Box (lbs)"
        },
        "max_weight": {
          "type": "number",
          "title": "Max Weight (lbs)"
        },
        "is_letter": {
          "type": "boolean",
          "title": "Letter"
        }
      }
    }
  }
}
JSON;
					return json_decode( $services_json );
				default:
					// Just return empty data for other requests.
					return new stdClass;

			}
		}
	}
}
