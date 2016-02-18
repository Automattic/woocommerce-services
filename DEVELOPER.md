# GET /services

## Example Request

### Query Params:

    None

### Headers:

    Authorization: Bearer API_TOKEN
    Accept: application/vnd.woocommerce-connect.v1
    Accept-Language: en

### Body (JSON):

```javascript
{
  settings: {
    base_location: 'US',
    currency: 'USD',
    weight_unit: 'kg',
    dimension_unit: 'cm'
  }
}
```

## Example Response (JSON):

```javascript
{
  "services":{
    "shipping":[
      {
        "id":"usps",
        "method_title":"USPS (WooCommerce Connect)",
        "method_description":"Obtains rates dynamically from the USPS API during cart/checkout.",
        "service_settings":{
          "type":"object",
          "required":[
          ],
          "definitions":{
            "countries":{
              "type":"string",
              "enum":[
                "AU",
                "GB",
                "US"
              ],
              "enumNames":[
                "Australia",
                "United Kingdom (UK)",
                "United States (US)"
              ]
            }
          },
          "properties":{
            "enabled":{
              "type":"boolean",
              "title":" Enable/Disable",
              "description":"Enable this shipping method",
              "default":false
            },
            "title":{
              "type":"string",
              "title":"Method Title",
              "description":"This controls the title which the user sees during checkout.",
              "default":"USPS"
            },
            "origin":{
              "type":"string",
              "title":"Origin Postcode",
              "description":"Enter the postcode for the sender.",
              "default":""
            },
            "method_availability":{
              "type":"object",
              "title":"Method Availability",
              "properties":{
                "filter":{
                  "type":"string",
                  "enum":[
                    "all",
                    "specific",
                    "excluding"
                  ],
                  "enumNames":[
                    "All Countries",
                    "Specific Countries",
                    "Exclude Specific Countries"
                  ]
                }
              },
              "oneOf":[
                {
                  "properties":{
                    "filter":{
                      "enum":[
                        "all"
                      ]
                    }
                  }
                },
                {
                  "properties":{
                    "filter":{
                      "enum":[
                        "specific"
                      ]
                    },
                    "countries":{
                      "title":"Specific Countries",
                      "$ref":"#/definitions/countries"
                    }
                  }
                },
                {
                  "properties":{
                    "filter":{
                      "enum":[
                        "excluding"
                      ]
                    },
                    "countries":{
                      "title":"Specific Countries",
                      "$ref":"#/definitions/countries"
                    }
                  }
                }
              ],
              "x-hints":{
                "form":{
                  "selector":"filter"
                }
              }
            },
            "boxes":{
              "type":"array",
              "title":"Box Sizes",
              "description":"Items will be packed into these boxes based on item dimensions and volume. Outer dimensions will be passed to USPS, whereas inner dimensions will be used for packing. Items not fitting into boxes will be packed individually.",
              "items":{
                "type":"object",
                "title":"Box",
                "properties":{
                  "name":{
                    "type":"string",
                    "title":"Name"
                  },
                  "outer_length":{
                    "type":"number",
                    "title":"L (in)"
                  },
                  "outer_width":{
                    "type":"number",
                    "title":"W (in)"
                  },
                  "outer_height":{
                    "type":"number",
                    "title":"H (in)"
                  },
                  "inner_length":{
                    "type":"number",
                    "title":"Inner L (in)"
                  },
                  "inner_width":{
                    "type":"number",
                    "title":"Inner W (in)"
                  },
                  "inner_height":{
                    "type":"number",
                    "title":"Inner H (in)"
                  },
                  "box_weight":{
                    "type":"number",
                    "title":"Weight of Box (lbs)"
                  },
                  "max_weight":{
                    "type":"number",
                    "title":"Max Weight (lbs)"
                  },
                  "is_letter":{
                    "type":"boolean",
                    "title":"Letter"
                  }
                }
              }
            }
          }
        }
      },
      {
        "id":"canada_post",
        "method_title":"Canada Post (WooCommerce Connect)",
        "method_description":"Obtains rates dynamically from the Canada Post API during cart/checkout.",
        "service_settings":{
          "type":"object",
          "required":[
          ],
          "definitions":{
          },
          "properties":{
            "enabled":{
              "type":"boolean",
              "title":" Enable/Disable",
              "description":"Enable this shipping method",
              "default":false
            },
            "title":{
              "type":"string",
              "title":"Method Title",
              "description":"This controls the title which the user sees during checkout.",
              "default":"Canada Post"
            }
          }
        }
      }
    }
  }
}
```

# GET /shipping/rates

## Example Request

### Query Params:

    None

### Headers:

    Authorization: Bearer API_TOKEN
    Accept: application/vnd.woocommerce-connect.v1
    Accept-Language: en

### Body (JSON):

```javascript
{
  settings: {
    base_location: 'US',
    currency: 'USD',
    weight_unit: 'kg',
    dimension_unit: 'cm'
  },
  services: [
    {
      id: 'usps',
      instance: 0,
      service_settings: {
        'enabled': true,
        'title': 'USPS - West Coast Warehouse',
        'method-availability': 'specific',
        'countries': [
          'US'
        ],
        origin: 98290
      }
    },
    {
      id: 'usps',
      instance: 1,
      service_settings: {
        'enabled': true,
        'title': 'USPS - East Coast Warehouse',
        'method-availability': 'specific',
        'countries': [
          'US'
        ],
        origin: 22306
      }
    },
    {
      id: 'canada_post',
      instance: 0,
      service_settings: {
        'enabled': false
      }
    }
  ],
  destination: {
    country: 'US',
    state: 'WA',
    postcode: 98290,
    city: 'Snohomish',
    address: '1003 1st St',
    address_2: ''
  },
  contents: [
    {
      product_id: 403,
      quantity: 1,
      weight: 5,
      length: 10,
      width: 10,
      height: 10
    },
    {
      product_id: 407,
      quantity: 1,
      weight: 10,
      length: 15,
      width: 15,
      height: 15
    },
    {
      product_id: 513,
      quantity: 2,
      weight: 2,
      length: 2,
      width: 3,
      height: 4
    }
  ]
}
```

## Example Response (JSON):

```javascript
{
  rates:[
    [
      {
        id: 'usps',
        instance: 0,
        title: 'USPS - West Coast Warehouse',
        rate: 15.00
      },
      {
        id: 'usps',
        instance: 1,
        title: 'USPS - East Coast Warehouse',
        rate: 8.00
      }
    ]
  ]
}
```
